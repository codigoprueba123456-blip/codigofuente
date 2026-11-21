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
     * Consulta materias junto con carrera y prerrequisitos para construir la tabla del plan.
     * Aplica q sobre nombre/código y el filtro id_carrera por separado, y carga carreras para el selector.
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
     * Carga carreras activas y materias activas que pueden usarse como prerrequisitos.
     * Devuelve ambas colecciones al formulario de creación.
     */
    public function create()
    {
        $carreras = Carrera::activas()->orderBy('nombre')->get();
        $materiasDisponibles = Materia::activas()->orderBy('nombre')->get();

        return view('planes.materias.create', compact('carreras', 'materiasDisponibles'));
    }

    /**
     * Resuelve la carrera por su identificador y abre el formulario de carga de plan.
     * Si no existe, Eloquent interrumpe la acción con una respuesta 404.
     */
    public function importForm($id)
    {
        $carrera = Carrera::findOrFail($id);

        return view('planes.materias.import', compact('carrera'));
    }

    /**
     * Valida el archivo, convierte su contenido a filas y persiste las materias de la carrera.
     * Una primera pasada crea/actualiza todas las materias y una segunda enlaza prerrequisitos por código,
     * permitiendo referencias a filas posteriores; cualquier fila o referencia inválida revierte todo.
     * Al confirmar, recalcula el creditaje y redirige al plan con el total de registros importados.
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
     * Valida la materia y que carrera, nivel y prerrequisitos correspondan al régimen seleccionado.
     * Guarda la materia, sincroniza sus relaciones y recalcula el creditaje curricular en una transacción.
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
     * Busca la materia con sus prerrequisitos actuales y arma las opciones de carrera y materias.
     * Conserva disponible la carrera y los prerrequisitos seleccionados aunque estén inactivos,
     * y excluye la propia materia para impedir una autorreferencia desde el formulario.
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
     * Valida campos y relaciones dentro de la carrera de destino, incluyendo el régimen del nivel.
     * Actualiza la materia y reemplaza sus prerrequisitos en una transacción; luego recalcula el
     * creditaje de la carrera anterior y, si hubo traslado, también el de la nueva.
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
     * Valida el nuevo estado de la materia y lo guarda junto con el recálculo de la carrera.
     * El servicio solo distribuye creditaje entre materias curriculares que permanecen activas.
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
