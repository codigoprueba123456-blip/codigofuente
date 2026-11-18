<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Materia;
use App\Support\PlanEstudiosImporter;
use App\Support\CareerCreditageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use RuntimeException;

class MateriaController extends Controller
{
    /**
     * Lista materias con prerrequisitos y carrera, con filtros opcionales por nombre, código y carrera.
     */
    public function index()
    {
        $query = Materia::with(['carrera', 'prerequisitos'])
            ->when(request()->filled('q'), fn ($query) => $query->where(function ($query): void {
                $term = '%'.request('q').'%';
                $query->where('nombre', 'like', $term)
                    ->orWhere('codigo', 'like', $term);
            }))
            ->orderBy('nombre');
        if (request()->filled('id_carrera')) {
            $query->where('id_carrera', request('id_carrera'));
        }
        $materias = $query->get();
        $carreras = Carrera::orderBy('nombre')->get();

        return view('planes.materias.index', compact('materias', 'carreras'));
    }

    /**
     * Prepara el alta con carreras y materias activas disponibles para relacionar.
     */
    public function create()
    {
        $carreras = Carrera::activas()->orderBy('nombre')->get();
        $materiasDisponibles = Materia::activas()->orderBy('nombre')->get();

        return view('planes.materias.create', compact('carreras', 'materiasDisponibles'));
    }

    /**
     * Muestra la pantalla de importación para la carrera indicada.
     */
    public function importForm($id)
    {
        $carrera = Carrera::findOrFail($id);

        return view('planes.materias.import', compact('carrera'));
    }

    /**
     * Importa materias en dos pasadas para poder resolver prerrequisitos por código.
     * La transacción garantiza que filas y relaciones se guarden todas o ninguna.
     */
    public function import(Request $request, $id, PlanEstudiosImporter $importer, CareerCreditageService $creditage)
    {
        $carrera = Carrera::findOrFail($id);
        $validated = $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,pdf', 'max:10240'],
        ]);

        try {
            $rows = $importer->rows($validated['archivo']);
            $imported = DB::transaction(function () use ($rows, $carrera, $creditage) {
                $count = 0;
                $materiasImportadas = [];

                // Primera pasada: crea o actualiza todas las materias y guarda sus códigos de prerrequisitos.
                // Esto permite que una materia dependa de otra que aparezca más adelante en el archivo.
                foreach ($rows as $index => $row) {
                    $validator = Validator::make($row, [
                        'codigo' => ['nullable', 'string', 'max:255'],
                        'nombre' => ['required', 'string', 'max:255'],
                        'horas' => ['nullable', 'integer', 'min:0'],
                        'nivel' => ['nullable', 'integer', 'min:0'],
                        'numero' => ['nullable', 'integer', 'min:0'],
                        'curricular' => ['required', 'boolean'],
                        'prerequisitos' => ['present', 'array'],
                        'prerequisitos.*' => ['string', 'max:255'],
                    ]);

                    if ($validator->fails()) {
                        throw ValidationException::withMessages([
                            'archivo' => 'Error en la fila ' . ($index + 2) . ': ' . $validator->errors()->first(),
                        ]);
                    }

                    $data = $validator->validated();
                    $prerequisitos = $data['prerequisitos'];
                    unset($data['prerequisitos']);
                    $identity = !empty($data['codigo'])
                        ? ['id_carrera' => $carrera->id_carrera, 'codigo' => $data['codigo']]
                        : ['id_carrera' => $carrera->id_carrera, 'nombre' => $data['nombre']];
                    $materia = Materia::updateOrCreate($identity, $data);
                    $materiasImportadas[] = [
                        'materia' => $materia,
                        'prerequisitos' => $prerequisitos,
                        'fila' => $index + 2,
                    ];
                    $count++;
                }

                $materiasPorCodigo = Materia::query()
                    ->where('id_carrera', $carrera->id_carrera)
                    ->whereNotNull('codigo')
                    ->get()
                    ->keyBy(fn (Materia $materia): string => mb_strtoupper(trim($materia->codigo)));

                // Segunda pasada: resuelve los códigos contra las materias ya persistidas y enlaza sus IDs.
                foreach ($materiasImportadas as $importada) {
                    $idsPrerequisitos = [];
                    foreach ($importada['prerequisitos'] as $codigo) {
                        $prerequisito = $materiasPorCodigo->get(mb_strtoupper(trim($codigo)));
                        if (! $prerequisito) {
                            throw ValidationException::withMessages([
                                'archivo' => 'Error en la fila ' . $importada['fila']
                                    . ': no existe la materia prerequisito "' . $codigo . '" en esta carrera.',
                            ]);
                        }
                        if ($prerequisito->is($importada['materia'])) {
                            throw ValidationException::withMessages([
                                'archivo' => 'Error en la fila ' . $importada['fila']
                                    . ': una materia no puede ser prerequisito de sí misma.',
                            ]);
                        }
                        $idsPrerequisitos[] = $prerequisito->id_materia;
                    }
                    $importada['materia']->prerequisitos()->sync($idsPrerequisitos);
                }

                // La lectura, validación, alta de materias y relaciones están dentro de una transacción:
                // cualquier fila inválida o prerrequisito inexistente revierte la importación completa.
                $creditage->syncCareer($carrera);

                return $count;
            });
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['archivo' => $exception->getMessage()]);
        }

        return redirect()->route('planes.materias.index', ['id_carrera' => $carrera->id_carrera])
            ->with('success', $imported . ' materias cargadas correctamente para ' . $carrera->nombre . '.');
    }

    /**
     * Valida y crea una materia junto con sus prerrequisitos y actualiza el creditaje de la carrera.
     */
    public function store(Request $request, CareerCreditageService $creditage)
    {
        $nivelesPermitidos = Carrera::nivelesPorRegimen(
            Carrera::whereKey($request->input('id_carrera'))->value('regimen')
        );

        $validated = $request->validate([
            'codigo' => ['nullable', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'curricular' => ['nullable', 'boolean'],
            'horas' => ['nullable', 'integer', 'min:0'],
            'nivel' => ['nullable', 'integer', Rule::in($nivelesPermitidos)],
            'numero' => ['nullable', 'integer', 'min:0'],
            'id_carrera' => [
                'required',
                Rule::exists('carreras', 'id_carrera')->where('activo', true),
            ],
            'prerequisitos' => ['nullable', 'array'],
            'prerequisitos.*' => [
                'integer',
                Rule::exists('materia', 'id_materia')
                    ->where(fn ($query) => $query
                        ->where('id_carrera', $request->input('id_carrera'))
                        ->where('activo', true)),
            ],
        ]);

        DB::transaction(function () use ($validated, $request, $creditage): void {
            $materia = Materia::create([
                'codigo' => $validated['codigo'] ?? null,
                'nombre' => $validated['nombre'],
                'curricular' => $request->has('curricular') ? true : false,
                'horas' => $validated['horas'] ?? null,
                'nivel' => $validated['nivel'] ?? null,
                'numero' => $validated['numero'] ?? null,
                'id_carrera' => $validated['id_carrera'],
            ]);

            if (!empty($validated['prerequisitos'])) {
                $materia->prerequisitos()->sync($validated['prerequisitos']);
            }
            $creditage->syncCareer($materia->carrera);
        });

        return redirect()->route('planes.materias.index')->with('success', 'Materia creada correctamente.');
    }

    /**
     * Carga la materia, sus prerrequisitos y las opciones válidas para modificarla.
     */
    public function edit($id)
    {
        $materia = Materia::with('prerequisitos')->findOrFail($id);
        $idsPrerequisitos = $materia->prerequisitos->pluck('id_materia');
        $carreras = Carrera::query()
            ->where('activo', true)
            ->orWhere('id_carrera', $materia->id_carrera)
            ->orderBy('nombre')
            ->get();
        $materiasDisponibles = Materia::query()
            ->where('id_materia', '<>', $id)
            ->where(function ($query) use ($idsPrerequisitos): void {
                $query->where('activo', true)->orWhereIn('id_materia', $idsPrerequisitos);
            })
            ->orderBy('nombre')
            ->get();

        return view('planes.materias.edit', compact('materia', 'carreras', 'materiasDisponibles'));
    }

    /**
     * Actualiza datos y prerrequisitos; si cambia de carrera, recalcula el creditaje de ambas.
     */
    public function update(Request $request, $id, CareerCreditageService $creditage)
    {
        $materia = Materia::with('prerequisitos')->findOrFail($id);
        $nivelesPermitidos = Carrera::nivelesPorRegimen(
            Carrera::whereKey($request->input('id_carrera'))->value('regimen')
        );

        $validated = $request->validate([
            'codigo' => ['nullable', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'curricular' => ['nullable', 'boolean'],
            'horas' => ['nullable', 'integer', 'min:0'],
            'nivel' => ['nullable', 'integer', Rule::in($nivelesPermitidos)],
            'numero' => ['nullable', 'integer', 'min:0'],
            'id_carrera' => [
                'required',
                Rule::exists('carreras', 'id_carrera')
                    ->where(fn ($query) => $query
                        ->where('activo', true)
                        ->orWhere('id_carrera', $materia->id_carrera)),
            ],
            'prerequisitos' => ['nullable', 'array'],
            'prerequisitos.*' => [
                'integer',
                Rule::exists('materia', 'id_materia')
                    ->where(fn ($query) => $query
                        ->where('id_carrera', $request->input('id_carrera'))
                        ->where(function ($query) use ($materia): void {
                            $query->where('activo', true)
                                ->orWhereIn('id_materia', $materia->prerequisitos->pluck('id_materia'));
                        })),
                Rule::notIn([$materia->id_materia]),
            ],
        ]);

        DB::transaction(function () use ($materia, $validated, $request, $creditage): void {
            $carreraAnterior = $materia->id_carrera;
            $materia->update([
                'codigo' => $validated['codigo'] ?? null,
                'nombre' => $validated['nombre'],
                'curricular' => $request->has('curricular') ? true : false,
                'horas' => $validated['horas'] ?? null,
                'nivel' => $validated['nivel'] ?? null,
                'numero' => $validated['numero'] ?? null,
                'id_carrera' => $validated['id_carrera'],
            ]);

            $materia->prerequisitos()->sync($validated['prerequisitos'] ?? []);
            // Al mover una materia cambia el reparto de ambos planes; si no se movió, solo se recalcula
            // la carrera de origen. El servicio considera únicamente materias curriculares activas.
            $creditage->syncCareer(Carrera::findOrFail($carreraAnterior));
            if ((int) $materia->id_carrera !== (int) $carreraAnterior) {
                $creditage->syncCareer($materia->carrera);
            }
        });

        return redirect()->route('planes.materias.index')->with('success', 'Materia actualizada correctamente.');
    }

    /**
     * Cambia el estado de la materia y actualiza el reparto de creditaje de su carrera.
     */
    public function cambiarEstado(Request $request, $id, CareerCreditageService $creditage)
    {
        $materia = Materia::findOrFail($id);
        $validated = $request->validate([
            'activo' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($materia, $validated, $creditage): void {
            $materia->update(['activo' => $validated['activo']]);
            $creditage->syncCareer($materia->carrera);
        });

        return redirect()->route('planes.materias.index')
            ->with('success', 'Estado de la materia actualizado correctamente.');
    }
}
