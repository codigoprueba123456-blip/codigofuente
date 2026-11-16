<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Inscrito;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InscripcionManualController extends Controller
{
    public function index(Request $request)
    {
        $gestionActual = $this->gestionActual();
        $termino = trim((string) $request->query('q', ''));
        $inscripciones = collect();
        $inscripcionSeleccionada = null;
        $inscripcionesPersona = collect();
        $materiasActuales = collect();

        if ($gestionActual && mb_strlen($termino) >= 2) {
            $inscripciones = DB::table('inscrito as i')
                ->join('estudiante as e', 'e.id_persona', '=', 'i.id_persona')
                ->join('personas as p', 'p.id_persona', '=', 'i.id_persona')
                ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
                ->where('i.id_gestionacademica', $gestionActual->id_gestionacademica)
                ->where('i.activo', true)
                ->whereNull('i.id_inscrito_reemplazo')
                ->where(function ($query) use ($termino): void {
                    $query->where('p.ci', 'like', '%'.$termino.'%')
                        ->orWhere('p.nombre', 'like', '%'.$termino.'%')
                        ->orWhere('p.primerapellido', 'like', '%'.$termino.'%')
                        ->orWhere('p.segundoapellido', 'like', '%'.$termino.'%');
                })
                ->orderBy('p.primerapellido')
                ->orderBy('p.nombre')
                ->orderBy('c.nombre')
                ->limit(30)
                ->get([
                    'i.id_inscrito',
                    'i.id_persona',
                    'i.id_carrera',
                    'p.nombre',
                    'p.primerapellido',
                    'p.segundoapellido',
                    'p.ci',
                    'c.nombre as carrera',
                    'c.resolucionministerial1',
                    'c.resolucionministerial2',
                    'c.resolucionministerial3',
                ])
                ->map(function (object $inscripcion): object {
                    $inscripcion->carrera = Carrera::formatearNombreConResolucion(
                        $inscripcion->carrera,
                        $inscripcion->resolucionministerial1,
                        $inscripcion->resolucionministerial2,
                        $inscripcion->resolucionministerial3
                    );

                    return $inscripcion;
                });
        }

        if ($request->filled('id_inscrito')) {
            $contexto = $this->contexto(Inscrito::findOrFail($request->integer('id_inscrito')));
            $inscripcionSeleccionada = $contexto['inscripcion'];
            if ($termino === '') {
                $termino = trim(implode(' ', array_filter([
                    $inscripcionSeleccionada->nombre,
                    $inscripcionSeleccionada->primerapellido,
                    $inscripcionSeleccionada->segundoapellido,
                ])));
            }
            $inscripcionesPersona = DB::table('inscrito as i')
                ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
                ->where('i.id_persona', $inscripcionSeleccionada->id_persona)
                ->where('i.id_gestionacademica', $gestionActual->id_gestionacademica)
                ->where('i.activo', true)
                ->whereNull('i.id_inscrito_reemplazo')
                ->orderBy('c.nombre')
                ->get([
                    'i.id_inscrito',
                    'i.id_carrera',
                    'c.nombre as carrera',
                    'c.resolucionministerial1',
                    'c.resolucionministerial2',
                    'c.resolucionministerial3',
                ])
                ->map(function (object $inscripcion): object {
                    $inscripcion->carrera = Carrera::formatearNombreConResolucion(
                        $inscripcion->carrera,
                        $inscripcion->resolucionministerial1,
                        $inscripcion->resolucionministerial2,
                        $inscripcion->resolucionministerial3
                    );

                    return $inscripcion;
                });
            $materiasActuales = DB::table('inscrito_materia as im')
                ->join('materia as m', 'm.id_materia', '=', 'im.id_materia')
                ->leftJoin('grupo as g', 'g.id_grupo', '=', 'im.id_grupo')
                ->leftJoin('empleados as e', 'e.id_persona', '=', 'g.id_persona')
                ->leftJoin('personas as docente', 'docente.id_persona', '=', 'e.id_persona')
                ->where('im.id_inscrito', $inscripcionSeleccionada->id_inscrito)
                ->orderBy('m.nivel')
                ->orderBy('m.numero')
                ->orderBy('m.nombre')
                ->get([
                    'm.id_materia',
                    'm.nivel',
                    'm.numero',
                    'm.codigo',
                    'm.nombre',
                    'im.id_grupo',
                    'g.codigo as paralelo',
                    'g.turno',
                    'docente.nombre as docente_nombre',
                    'docente.primerapellido as docente_apellido',
                    'docente.segundoapellido as docente_segundo_apellido',
                ])
                ->map(function ($materia) {
                    $materia->paralelo = $this->nombreParalelo($materia->paralelo);

                    return $materia;
                });
        }

        return view('Inscripciones.inscripcion_manual.index', compact(
            'gestionActual',
            'termino',
            'inscripciones',
            'inscripcionSeleccionada',
            'inscripcionesPersona',
            'materiasActuales'
        ));
    }

    public function buscar(Request $request)
    {
        $gestionActual = $this->gestionActual();
        $termino = trim((string) $request->query('q', ''));
        if (! $gestionActual || mb_strlen($termino) < 2) {
            return response()->json([]);
        }

        $palabras = preg_split('/\s+/', $termino, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $filas = DB::table('inscrito as i')
            ->join('estudiante as e', 'e.id_persona', '=', 'i.id_persona')
            ->join('personas as p', 'p.id_persona', '=', 'i.id_persona')
            ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
            ->leftJoin('cursa as cu', function ($join) use ($gestionActual): void {
                $join->on('cu.id_persona', '=', 'i.id_persona')
                    ->on('cu.id_carrera', '=', 'i.id_carrera')
                    ->where('cu.id_gestionacademica', '=', $gestionActual->id_gestionacademica);
            })
            ->where('i.id_gestionacademica', $gestionActual->id_gestionacademica)
            ->where('i.activo', true)
            ->whereNull('i.id_inscrito_reemplazo')
            ->where(function ($query) use ($palabras): void {
                foreach ($palabras as $palabra) {
                    $query->where(function ($subquery) use ($palabra): void {
                        foreach (['p.ci', 'p.nombre', 'p.primerapellido', 'p.segundoapellido'] as $columna) {
                            $subquery->orWhere($columna, 'like', '%'.$palabra.'%');
                        }
                    });
                }
            })
            ->orderBy('p.primerapellido')
            ->orderBy('p.nombre')
            ->orderBy('c.nombre')
            ->limit(60)
            ->get([
                'i.id_inscrito',
                'i.id_persona',
                'i.id_carrera',
                'p.nombre',
                'p.primerapellido',
                'p.segundoapellido',
                'p.ci',
                'c.nombre as carrera',
                'c.resolucionministerial1',
                'c.resolucionministerial2',
                'c.resolucionministerial3',
                'c.regimen',
                'cu.nivelinicio',
            ]);

        return response()->json($filas->groupBy('id_persona')->map(function ($registros): array {
            $persona = $registros->first();

            return [
                'id' => (int) $persona->id_persona,
                'nombre' => trim(implode(' ', array_filter([
                    $persona->nombre,
                    $persona->primerapellido,
                    $persona->segundoapellido,
                ]))),
                'ci' => $persona->ci,
                'inscripciones' => $registros->map(fn ($registro): array => [
                    'id_inscrito' => (int) $registro->id_inscrito,
                    'id_carrera' => (int) $registro->id_carrera,
                    'carrera' => Carrera::formatearNombreConResolucion(
                        $registro->carrera,
                        $registro->resolucionministerial1,
                        $registro->resolucionministerial2,
                        $registro->resolucionministerial3
                    ),
                    'nivel' => max(1, (int) ($registro->nivelinicio ?? 1)),
                    'regimen' => Carrera::regimenEfectivo($registro->regimen),
                ])->values(),
            ];
        })->values());
    }

    public function retirarMateria(Request $request, Inscrito $inscrito, Materia $materia)
    {
        $contexto = $this->contexto($inscrito);
        abort_unless((int) $materia->id_carrera === (int) $contexto['inscripcion']->id_carrera, 404);

        $eliminadas = DB::table('inscrito_materia')
            ->where('id_inscrito', $inscrito->id_inscrito)
            ->where('id_materia', $materia->id_materia)
            ->delete();
        abort_unless($eliminadas, 404);

        return redirect()
            ->route('inscripcion_manual.index', [
                'q' => $request->input('q'),
                'id_inscrito' => $inscrito->id_inscrito,
            ])
            ->with('success', 'La materia se retiró de esta inscripción.');
    }

    public function oferta(Request $request, Inscrito $inscrito)
    {
        $contexto = $this->contexto($inscrito);
        $estadoCombinacion = $this->estadoCombinacion($inscrito);
        $paraleloSolicitado = strtoupper((string) $request->query('paralelo', 'A'));
        if (! in_array($paraleloSolicitado, ['A', 'B', 'C', 'D'], true)) {
            $paraleloSolicitado = 'A';
        }
        $turnoSolicitado = null;
        if ($request->filled('turno')) {
            $turnoSolicitado = (int) $request->validate([
                'turno' => ['required', 'integer', 'in:1,2,3'],
            ])['turno'];
        }

        $seIntentoCambiarCombinacion = false;
        if ($estadoCombinacion['combinacionInconsistente']) {
            $turno = null;
            $paralelo = $paraleloSolicitado;
        } elseif ($estadoCombinacion['turnoBloqueado'] !== null) {
            $turno = $estadoCombinacion['turnoBloqueado'];
            $paralelo = $estadoCombinacion['paraleloBloqueado'];
            $seIntentoCambiarCombinacion = ($turnoSolicitado !== null && $turnoSolicitado !== $turno)
                || ($request->filled('paralelo') && $paraleloSolicitado !== $paralelo);
        } else {
            $turno = $turnoSolicitado;
            $paralelo = $paraleloSolicitado;
        }

        $turnos = [1 => 'Mañana', 2 => 'Tarde', 3 => 'Noche'];
        $materiasOferta = collect();
        if ($turno !== null && ! $estadoCombinacion['combinacionInconsistente']) {
            $ocupacion = DB::table('inscrito_materia')
                ->whereNotNull('id_grupo')
                ->select('id_grupo')
                ->selectRaw('COUNT(*) as ocupados')
                ->groupBy('id_grupo');
            $gruposOferta = DB::table('grupo as g')
                ->join('materia as m', 'm.id_materia', '=', 'g.id_materia')
                ->join('carreras as c', 'c.id_carrera', '=', 'm.id_carrera')
                ->leftJoin('empleados as e', 'e.id_persona', '=', 'g.id_persona')
                ->leftJoin('personas as docente', 'docente.id_persona', '=', 'e.id_persona')
                ->leftJoinSub($ocupacion, 'ocupacion', 'ocupacion.id_grupo', '=', 'g.id_grupo')
                ->where('m.id_carrera', $contexto['inscripcion']->id_carrera)
                ->where('m.nivel', $contexto['inscripcion']->nivelinicio)
                ->where('g.id_gestionacademica', $contexto['inscripcion']->id_gestionacademica)
                ->where('g.turno', $turno)
                ->where('c.activo', true)
                ->where('m.activo', true)
                ->where('g.activo', true)
                ->where(function ($query) use ($paralelo): void {
                    $query->where('g.codigo', $paralelo)
                        ->orWhere('g.codigo', 'like', '%-'.$paralelo);
                })
                ->orderBy('m.nivel')
                ->orderBy('m.numero')
                ->orderBy('m.nombre')
                ->get([
                    'm.id_materia',
                    'm.nivel',
                    'm.numero',
                    'm.codigo as materia_codigo',
                    'm.nombre as materia',
                    'g.id_grupo',
                    'g.codigo as paralelo',
                    'g.turno',
                    'g.abierto',
                    'g.capacidad',
                    DB::raw('COALESCE(ocupacion.ocupados, 0) as ocupados'),
                    'docente.nombre as docente_nombre',
                    'docente.primerapellido as docente_apellido',
                    'docente.segundoapellido as docente_segundo_apellido',
                ])
                ->map(function ($grupo) {
                    $grupo->paralelo = $this->nombreParalelo($grupo->paralelo);

                    return $grupo;
                });
            $materiasOferta = $gruposOferta->groupBy('id_materia');
        }

        return view('Inscripciones.inscripcion_manual.oferta', [
            ...$contexto,
            'paralelo' => $paralelo,
            'turno' => $turno,
            'turnos' => $turnos,
            ...$estadoCombinacion,
            'seIntentoCambiarCombinacion' => $seIntentoCambiarCombinacion,
            'materiasOferta' => $materiasOferta,
        ]);
    }

    public function materias(Inscrito $inscrito)
    {
        $contexto = $this->contexto($inscrito);
        $materias = DB::table('materia as m')
            ->leftJoin('grupo as g', function ($join) use ($contexto): void {
                $join->on('g.id_materia', '=', 'm.id_materia')
                    ->where('g.id_gestionacademica', '=', $contexto['inscripcion']->id_gestionacademica)
                    ->where('g.activo', true);
            })
            ->join('carreras as c', 'c.id_carrera', '=', 'm.id_carrera')
            ->leftJoin('empleados as e', 'e.id_persona', '=', 'g.id_persona')
            ->leftJoin('personas as docente', 'docente.id_persona', '=', 'e.id_persona')
            ->where('m.id_carrera', $contexto['inscripcion']->id_carrera)
            ->where('c.activo', true)
            ->where('m.activo', true)
            ->orderBy('m.nivel')
            ->orderBy('m.numero')
            ->orderBy('m.nombre')
            ->get([
                'm.id_materia',
                'm.id_carrera',
                'm.nivel',
                'm.numero',
                'm.codigo',
                'm.nombre',
                'g.id_grupo',
                'g.codigo as paralelo',
                'g.turno',
                'docente.nombre as docente_nombre',
                'docente.primerapellido as docente_apellido',
                'docente.segundoapellido as docente_segundo_apellido',
            ])
            ->groupBy('id_materia')
            ->map(function ($registros) {
                $materia = $registros->first();
                $materia->grupos = $registros->whereNotNull('id_grupo')
                    ->map(function ($grupo) {
                        $grupo->paralelo = $this->nombreParalelo($grupo->paralelo);

                        return $grupo;
                    })
                    ->values();

                return $materia;
            });
        $materiasPorNivel = $materias->groupBy('nivel');

        return view('Inscripciones.inscripcion_manual.materias', [
            ...$contexto,
            'materiasPorNivel' => $materiasPorNivel,
        ]);
    }

    public function guardar(Request $request, Inscrito $inscrito)
    {
        $seleccion = collect((array) $request->input('grupos', []))
            ->filter(fn ($id): bool => $id !== null && $id !== '')
            ->all();
        $request->merge(['grupos' => $seleccion]);

        $validated = $request->validate([
            'grupos' => ['required', 'array', 'min:1'],
            'grupos.*' => ['required', 'integer', 'distinct', 'exists:grupo,id_grupo'],
            'turno' => ['required', 'integer', 'in:1,2,3'],
            'paralelo' => ['required', 'string', 'in:A,B,C,D'],
        ]);
        $validated['paralelo'] = strtoupper($validated['paralelo']);
        $contexto = $this->contexto($inscrito);
        $idGrupos = collect($validated['grupos'])->map(fn ($id): int => (int) $id)->values();

        DB::transaction(function () use ($inscrito, $contexto, $idGrupos, $validated): void {
            DB::table('inscrito')
                ->where('id_inscrito', $inscrito->id_inscrito)
                ->lockForUpdate()
                ->first();

            $estadoCombinacion = $this->estadoCombinacion($inscrito);
            if ($estadoCombinacion['combinacionInconsistente']) {
                throw ValidationException::withMessages([
                    'grupos' => 'El estudiante ya tiene materias asignadas en distintos turnos o paralelos. Corrija esas asignaciones antes de agregar otras.',
                ]);
            }
            if ($estadoCombinacion['turnoBloqueado'] !== null
                && ((int) $validated['turno'] !== $estadoCombinacion['turnoBloqueado']
                    || $validated['paralelo'] !== $estadoCombinacion['paraleloBloqueado'])) {
                throw ValidationException::withMessages([
                    'grupos' => 'El estudiante ya tiene materias asignadas en el turno '.$this->nombreTurno($estadoCombinacion['turnoBloqueado'])
                        .' y paralelo '.$estadoCombinacion['paraleloBloqueado'].'. No se pueden mezclar turnos ni paralelos.',
                ]);
            }

            $gruposBloqueados = DB::table('grupo')
                ->whereIn('id_grupo', $idGrupos)
                ->orderBy('id_grupo')
                ->lockForUpdate()
                ->get();
            if ($gruposBloqueados->count() !== $idGrupos->unique()->count()) {
                throw ValidationException::withMessages([
                    'grupos' => 'Uno de los grupos seleccionados ya no está disponible.',
                ]);
            }

            $grupos = DB::table('grupo as g')
                ->join('materia as m', 'm.id_materia', '=', 'g.id_materia')
                ->join('carreras as c', 'c.id_carrera', '=', 'm.id_carrera')
                ->whereIn('g.id_grupo', $idGrupos)
                ->get([
                    'g.id_grupo',
                    'g.codigo',
                    'g.id_materia',
                    'g.id_gestionacademica',
                    'g.turno',
                    'g.activo as grupo_activo',
                    'g.abierto',
                    'g.capacidad',
                    'm.activo as materia_activa',
                    'm.id_carrera',
                    'm.nivel',
                    'c.activo as carrera_activa',
                ])
                ->keyBy('id_grupo');
            $actuales = DB::table('inscrito_materia')
                ->where('id_inscrito', $inscrito->id_inscrito)
                ->whereNotNull('id_grupo')
                ->pluck('id_grupo')
                ->map(fn ($id): int => (int) $id)
                ->all();
            $materiasElegidas = [];

            foreach ($validated['grupos'] as $idMateria => $idGrupo) {
                $grupo = $grupos->get((int) $idGrupo);
                if (! $grupo || (int) $grupo->id_materia !== (int) $idMateria) {
                    throw ValidationException::withMessages([
                        'grupos' => 'Cada materia solo puede tener un grupo seleccionado y debe corresponder a esa materia.',
                    ]);
                }

                if ((int) $grupo->id_gestionacademica !== (int) $contexto['inscripcion']->id_gestionacademica
                    || (int) $grupo->id_carrera !== (int) $contexto['inscripcion']->id_carrera
                    || (int) $grupo->nivel !== (int) $contexto['inscripcion']->nivelinicio) {
                    throw ValidationException::withMessages([
                        'grupos' => 'Solo se pueden seleccionar materias del nivel actual, carrera y gestión de la inscripción.',
                    ]);
                }

                if ((int) $grupo->turno !== (int) $validated['turno']
                    || $this->nombreParalelo($grupo->codigo) !== $validated['paralelo']) {
                    throw ValidationException::withMessages([
                        'grupos' => 'Todas las materias seleccionadas deben pertenecer al turno y paralelo elegidos.',
                    ]);
                }

                if (in_array((int) $grupo->id_materia, $materiasElegidas, true)) {
                    throw ValidationException::withMessages([
                        'grupos' => 'No se puede elegir más de un paralelo para la misma materia.',
                    ]);
                }
                $materiasElegidas[] = (int) $grupo->id_materia;

                $yaAsignado = in_array((int) $grupo->id_grupo, $actuales, true);
                if (! $yaAsignado && (
                    ! (bool) $grupo->grupo_activo
                    || ! (bool) $grupo->materia_activa
                    || ! (bool) $grupo->carrera_activa
                )) {
                    throw ValidationException::withMessages([
                        'grupos' => 'No se pueden asignar grupos, materias o carreras deshabilitados.',
                    ]);
                }
                if (! $yaAsignado && ! (bool) $grupo->abierto) {
                    throw ValidationException::withMessages([
                        'grupos' => 'Uno de los grupos seleccionados está cerrado.',
                    ]);
                }

                $ocupacion = DB::table('inscrito_materia')
                    ->where('id_grupo', $grupo->id_grupo)
                    ->where('id_inscrito', '<>', $inscrito->id_inscrito)
                    ->count();
                if (! $yaAsignado && $ocupacion >= (int) $grupo->capacidad) {
                    throw ValidationException::withMessages([
                        'grupos' => 'Uno de los grupos seleccionados ya alcanzó su capacidad.',
                    ]);
                }
            }

            $ahora = now();
            foreach ($validated['grupos'] as $idMateria => $idGrupo) {
                DB::table('inscrito_materia')->updateOrInsert(
                    [
                        'id_inscrito' => $inscrito->id_inscrito,
                        'id_materia' => (int) $idMateria,
                    ],
                    [
                        'id_grupo' => (int) $idGrupo,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ]
                );
            }
        });

        return redirect()
            ->route('inscripcion_manual.index', [
                'q' => $request->input('q'),
                'id_inscrito' => $inscrito->id_inscrito,
            ])
            ->with('success', 'Las materias y paralelos se asignaron correctamente.');
    }

    private function contexto(Inscrito $inscrito): array
    {
        $gestionActual = $this->gestionActual();
        abort_unless($gestionActual, 404);

        $inscripcion = DB::table('inscrito as i')
            ->join('estudiante as e', 'e.id_persona', '=', 'i.id_persona')
            ->join('personas as p', 'p.id_persona', '=', 'i.id_persona')
            ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
            ->where('i.id_inscrito', $inscrito->id_inscrito)
            ->where('i.id_gestionacademica', $gestionActual->id_gestionacademica)
            ->where('i.activo', true)
            ->whereNull('i.id_inscrito_reemplazo')
            ->first([
                'i.id_inscrito',
                'i.id_persona',
                'i.id_carrera',
                'i.id_gestionacademica',
                'p.nombre',
                'p.primerapellido',
                'p.segundoapellido',
                'p.ci',
                'c.nombre as carrera',
                'c.resolucionministerial1',
                'c.resolucionministerial2',
                'c.resolucionministerial3',
                'c.regimen',
            ]);
        abort_unless($inscripcion, 404);
        $inscripcion->carrera = Carrera::formatearNombreConResolucion(
            $inscripcion->carrera,
            $inscripcion->resolucionministerial1,
            $inscripcion->resolucionministerial2,
            $inscripcion->resolucionministerial3
        );
        $inscripcion->nivelinicio = DB::table('cursa')
            ->where('id_persona', $inscripcion->id_persona)
            ->where('id_carrera', $inscripcion->id_carrera)
            ->where('id_gestionacademica', $inscripcion->id_gestionacademica)
            ->value('nivelinicio') ?? 1;

        $seleccionadosPorMateria = DB::table('inscrito_materia')
            ->where('id_inscrito', $inscrito->id_inscrito)
            ->whereNotNull('id_grupo')
            ->pluck('id_grupo', 'id_materia')
            ->all();

        return compact('gestionActual', 'inscripcion', 'seleccionadosPorMateria');
    }

    private function gestionActual(): ?GestionAcademica
    {
        return GestionAcademica::where('vigente', true)
            ->orderByDesc('id_gestionacademica')
            ->first() ?? GestionAcademica::orderByDesc('id_gestionacademica')->first();
    }

    private function nombreParalelo(?string $codigoGrupo): ?string
    {
        if ($codigoGrupo === null) {
            return null;
        }

        return preg_match('/(?:^|-)([A-D])$/i', $codigoGrupo, $coincidencia)
            ? strtoupper($coincidencia[1])
            : $codigoGrupo;
    }

    private function estadoCombinacion(Inscrito $inscrito): array
    {
        $asignaciones = DB::table('inscrito_materia as im')
            ->leftJoin('grupo as g', 'g.id_grupo', '=', 'im.id_grupo')
            ->where('im.id_inscrito', $inscrito->id_inscrito)
            ->get([
                'im.id_grupo',
                'g.codigo',
                'g.turno',
            ]);
        $tieneMateriasAsignadas = $asignaciones->isNotEmpty();
        $combinaciones = $asignaciones
            ->whereNotNull('id_grupo')
            ->map(fn ($asignacion): array => [
                'turno' => $asignacion->turno === null ? null : (int) $asignacion->turno,
                'paralelo' => $this->nombreParalelo($asignacion->codigo),
            ])
            ->values();
        $combinacionesUnicas = $combinaciones
            ->unique(fn (array $combinacion): string => ($combinacion['turno'] ?? 'sin-turno').'|'.($combinacion['paralelo'] ?? 'sin-paralelo'))
            ->values();
        $combinacionInconsistente = $combinacionesUnicas->count() > 1
            || $combinaciones->contains(fn (array $combinacion): bool => ! in_array($combinacion['turno'], [1, 2, 3], true)
                || ! in_array($combinacion['paralelo'], ['A', 'B', 'C', 'D'], true));
        $combinacionActual = ! $combinacionInconsistente && $combinacionesUnicas->count() === 1
            ? $combinacionesUnicas->first()
            : null;

        return [
            'tieneMateriasAsignadas' => $tieneMateriasAsignadas,
            'combinacionInconsistente' => $combinacionInconsistente,
            'turnoBloqueado' => $combinacionActual['turno'] ?? null,
            'paraleloBloqueado' => $combinacionActual['paralelo'] ?? null,
        ];
    }

    private function nombreTurno(int $turno): string
    {
        return [1 => 'Mañana', 2 => 'Tarde', 3 => 'Noche'][$turno] ?? 'desconocido';
    }
}
