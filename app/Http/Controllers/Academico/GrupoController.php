<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrupoController extends Controller
{
    /**
     * Valida filtros y carga las opciones de gestión y carrera para la página de grupos.
     * Solo consulta grupos cuando ambos filtros están presentes; luego los agrupa por nivel
     * de materia para presentar una tabla ordenada por bloques.
     */
    public function index(Request $request)
    {
        $request->validate([
            'id_gestionacademica' => ['nullable', 'integer', 'exists:gestionacademica,id_gestionacademica'],
            'id_carrera' => ['nullable', 'integer', 'exists:carreras,id_carrera'],
        ]);

        $gestiones = GestionAcademica::query()->orderByDesc('gestion')->orderBy('periodo')->get();
        $carreras = Carrera::orderBy('nombre')->get();
        $hasFilters = $request->filled('id_gestionacademica') && $request->filled('id_carrera');
        $grupos = $hasFilters
            ? $this->query($request)->withCount('inscritos')->get()
            : collect();
        $gruposPorNivel = $grupos
            ->groupBy(fn (Grupo $grupo) => $grupo->materia->nivel ?? 'Sin nivel')
            ->sortKeys();

        return view('gestiones.grupos.index', compact(
            'gestiones',
            'carreras',
            'grupos',
            'gruposPorNivel',
            'hasFilters'
        ));
    }

    /**
     * Busca la gestión habilitada más reciente y ofrece carreras activas para programación.
     * Si no hay gestión vigente y activa, findOrFail detiene el flujo en lugar de crear grupos sin periodo.
     */
    public function programar(Request $request)
    {
        $gestion = GestionAcademica::habilitadas()
            ->orderByDesc('gestion')
            ->orderBy('periodo')
            ->firstOrFail();

        $carreras = Carrera::activas()->orderBy('nombre')->get();
        return view('gestiones.grupos.create', compact('gestion', 'carreras'));
    }

    /**
     * Obtiene la gestión habilitada más reciente y valida carrera, nivel y datos compartidos del grupo.
     * Crea un grupo por cada materia activa del nivel, genera códigos disponibles y devuelve al listado
     * con el número de grupos creados; si no existen materias, informa el error sin escribir registros.
     */
    public function store(Request $request)
    {
        $gestion = GestionAcademica::habilitadas()
            ->orderByDesc('gestion')
            ->orderBy('periodo')
            ->firstOrFail();

        $validated = $request->validate($this->rules($request));
        $validated['id_gestionacademica'] = $gestion->id_gestionacademica;

        $materias = Materia::query()
            ->where('activo', true)
            ->where('id_carrera', $validated['id_carrera'])
            ->where('nivel', $validated['nivel'])
            ->orderBy('nombre')
            ->get();

        if ($materias->isEmpty()) {
            throw ValidationException::withMessages([
                'nivel' => 'No hay materias registradas para la carrera y nivel seleccionados.',
            ]);
        }

        // La programación es masiva: crea un grupo por cada materia activa del nivel elegido,
        // aplicando a todos la capacidad, turno y estado recibidos en el formulario.
        $gruposCreados = 0;

        foreach ($materias as $index => $materia) {
            $codigo = $this->buildGrupoCodigo($index, $materia);

            Grupo::create([
                'codigo' => $codigo,
                'capacidad' => $validated['capacidad'] ?? null,
                'turno' => $validated['turno'] ?? null,
                'abierto' => $validated['abierto'] ?? true,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'id_materia' => $materia->id_materia,
            ]);

            $gruposCreados++;
        }

        return redirect()->route('admin.grupos.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $validated['id_carrera'],
        ])->with('success', 'Se programaron ' . $gruposCreados . ' grupos correctamente.');
    }

    /**
     * Busca grupo y materia, y verifica que la gestión de origen siga habilitada.
     * Carga gestiones editables y materias activas de carreras activas, conservando la materia asignada
     * aunque haya sido deshabilitada para no obligar a reemplazarla al editar otros campos.
     */
    public function edit($id)
    {
        $grupo = Grupo::with('materia')->findOrFail($id);
        $this->ensureEnabled($grupo->id_gestionacademica);
        $gestiones = GestionAcademica::habilitadas()->orderByDesc('gestion')->orderBy('periodo')->get();
        $materias = Materia::query()
            ->where(function ($query) use ($grupo): void {
                $query->where(function ($query): void {
                    $query->where('activo', true)
                        ->whereHas('carrera', fn ($carreras) => $carreras->where('activo', true));
                })->orWhere('id_materia', $grupo->id_materia);
            })
            ->orderBy('nombre')
            ->get();

        return view('gestiones.grupos.edit', compact('grupo', 'gestiones', 'materias'));
    }

    /**
     * Comprueba que origen y destino sean gestiones habilitadas antes de actualizar el grupo.
     * Si cambia la materia, verifica además que materia y carrera de destino estén activas;
     * finalmente conserva los filtros de gestión y carrera en la redirección.
     */
    public function update(Request $request, $id)
    {
        $grupo = Grupo::findOrFail($id);
        $this->ensureEnabled($grupo->id_gestionacademica);
        $validated = $request->validate($this->updateRules($grupo));
        $this->ensureEnabled($validated['id_gestionacademica']);
        if (isset($validated['id_materia']) && (int) $validated['id_materia'] !== (int) $grupo->id_materia) {
            $materia = Materia::with('carrera')->findOrFail($validated['id_materia']);
            if (! $materia->activo || ! $materia->carrera?->activo) {
                throw ValidationException::withMessages([
                    'id_materia' => 'Solo se pueden asignar materias y carreras habilitadas.',
                ]);
            }
        }
        $grupo->update($validated);

        return redirect()->route('admin.grupos.index', [
            'id_gestionacademica' => $validated['id_gestionacademica'],
            'id_carrera' => $grupo->materia?->id_carrera,
        ])->with('success', 'Grupo actualizado correctamente.');
    }

    /**
     * Valida y cambia el estado activo del grupo sin alterar materia, docente ni gestión.
     * La redirección conserva los filtros asociados al grupo para volver al mismo contexto.
     */
    public function cambiarEstado(Request $request, $id)
    {
        $grupo = Grupo::findOrFail($id);
        $gestion = $grupo->id_gestionacademica;
        $validated = $request->validate([
            'activo' => ['required', 'boolean'],
        ]);
        $grupo->update(['activo' => $validated['activo']]);

        return redirect()->route('admin.grupos.index', [
            'id_gestionacademica' => $gestion,
            'id_carrera' => $grupo->materia?->id_carrera,
        ])->with('success', 'Estado del grupo actualizado correctamente.');
    }

    /**
     * Arma la consulta reutilizable con materia y docente precargados.
     * Filtra opcionalmente por gestión, carrera y texto en código de grupo o datos de materia,
     * y ordena por código antes de que la acción solicitante ejecute la consulta.
     */
    private function query(Request $request)
    {
        return Grupo::with(['materia', 'docente'])
            ->when($request->filled('id_gestionacademica'), fn ($query) =>
                $query->where('id_gestionacademica', $request->id_gestionacademica))
            ->when($request->filled('id_carrera'), fn ($query) =>
                $query->whereHas('materia', fn ($materia) =>
                    $materia->where('id_carrera', $request->id_carrera)))
            ->when($request->filled('buscar'), fn ($query) =>
                $query->where(function ($search) use ($request): void {
                    $term = '%' . $request->buscar . '%';

                    $search->where('codigo', 'like', $term)
                        ->orWhereHas('materia', fn ($materia) => $materia
                            ->where('nombre', 'like', $term)
                            ->orWhere('codigo', 'like', $term));
                }))
            ->orderBy('codigo');
    }

    /**
     * Define las reglas de programación masiva para carrera activa y nivel propio de su régimen.
     * También limita capacidad, turno y estado; el ID de gestión no se acepta del formulario,
     * porque la acción asigna la gestión habilitada del servidor.
     */
    private function rules(Request $request): array
    {
        return [
            'id_carrera' => [
                'required',
                Rule::exists('carreras', 'id_carrera')->where('activo', true),
            ],
            'nivel' => [
                'required',
                'integer',
                Rule::in(Carrera::nivelesPorRegimen(
                    Carrera::whereKey($request->input('id_carrera'))->value('regimen')
                )),
            ],
            'capacidad' => ['nullable', 'integer', 'min:1'],
            'turno' => ['nullable', 'integer', 'in:1,2,3'],
            'abierto' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Define campos editables y mantiene la materia actual como excepción si está inactiva.
     * La comprobación de que una nueva materia y su carrera estén activas se completa en update().
     */
    private function updateRules(Grupo $grupo): array
    {
        return [
            'codigo' => ['required', 'string', 'max:255'],
            'capacidad' => ['nullable', 'integer', 'min:1'],
            'turno' => ['nullable', 'integer', 'in:1,2,3'],
            'abierto' => ['sometimes', 'boolean'],
            'id_gestionacademica' => ['required', 'exists:gestionacademica,id_gestionacademica'],
            'id_materia' => [
                'nullable',
                Rule::exists('materia', 'id_materia')
                    ->where(fn ($query) => $query
                        ->where('activo', true)
                        ->orWhere('id_materia', $grupo->id_materia)),
            ],
            'id_persona' => ['nullable', 'exists:personas,id_persona'],
        ];
    }

    /**
     * Construye el código del grupo usando el código de materia y una letra derivada del índice.
     * Si el código ya existe en cualquier gestión, avanza por los sufijos hasta encontrar uno libre.
     */
    private function buildGrupoCodigo(int $index, Materia $materia): string
    {
        $letter = strtolower(chr(97 + ($index % 26)));
        $base = $materia->codigo ?: 'G';

        $codigo = $base . '-' . $letter;
        $counter = 1;

        // Los códigos deben ser únicos entre gestiones, no solo dentro de la gestión actual;
        // si el sufijo calculado ya se usó, se prueba el siguiente.
        while (Grupo::where('codigo', $codigo)->exists()) {
            $counter++;
            $codigo = $base . '-' . strtolower(chr(97 + (($index + $counter - 1) % 26)));
        }

        return $codigo;
    }

    /**
     * Exige que la gestión asociada esté activa y vigente antes de editar el grupo.
     * Lanza un error de validación sobre id_gestionacademica para regresar al formulario con contexto.
     */
    private function ensureEnabled(int $idGestion): void
    {
        if (!GestionAcademica::habilitadas()->whereKey($idGestion)->exists()) {
            throw ValidationException::withMessages([
                'id_gestionacademica' => 'La gestión académica seleccionada está deshabilitada.',
            ]);
        }
    }
}
