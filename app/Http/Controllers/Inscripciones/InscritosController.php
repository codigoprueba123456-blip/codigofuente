<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Inscrito;
use App\Services\Inscripciones\DisponibilidadTurnos;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InscritosController extends Controller
{
    // Normaliza el código del grupo para ordenar primero por letra de paralelo.
    private const SQL_ORDEN_PARALELO = "CASE WHEN SUBSTR(UPPER(gr.codigo), -2, 1) = '-' THEN SUBSTR(UPPER(gr.codigo), -1) ELSE UPPER(gr.codigo) END";

    /**
     * Recibe el servicio que valida la disponibilidad de turnos, paralelos y cupos.
     */
    public function __construct(private DisponibilidadTurnos $disponibilidadTurnos)
    {
    }

    /**
     * Filtra y muestra las inscripciones, sus indicadores y las acciones administrativas disponibles.
     * También genera la exportación solicitada usando los mismos filtros aplicados al listado.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'id_gestionacademica' => ['nullable', 'integer', 'exists:gestionacademica,id_gestionacademica'],
            'id_carrera' => ['nullable', 'integer', 'exists:carreras,id_carrera'],
            'nivel' => [
                'nullable',
                'integer',
                'between:1,6',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if (empty($request->input('id_carrera'))) {
                        return;
                    }

                    $carrera = Carrera::find($request->input('id_carrera'));
                    if (! $carrera || ! in_array((int) $value, Carrera::nivelesPorRegimen($carrera->regimen), true)) {
                        $fail('El nivel no corresponde al régimen de la carrera seleccionada.');
                    }
                },
            ],
            'bth' => ['nullable', 'in:0,1'],
            'traspaso_convalidacion' => ['nullable', 'in:0,1'],
            'turno' => ['nullable', 'string', 'max:30'],
            'paralelo' => ['nullable', 'string', 'max:20'],
            'buscar_rapido' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:activos,deshabilitados,todos'],
        ]);

        // Todos los filtros se aplican a una consulta común para mantener iguales la tabla y los reportes.
        $query = $this->inscritosQuery();
        $estado = $filters['estado'] ?? 'activos';
        if ($estado === 'activos') {
            $query->where('i.activo', true)->whereNull('i.id_inscrito_reemplazo');
        } elseif ($estado === 'deshabilitados') {
            $query->where(function (Builder $status): void {
                $status->where('i.activo', false)->orWhereNotNull('i.id_inscrito_reemplazo');
            });
        }

        if (! empty($filters['buscar_rapido'])) {
            $terminos = preg_split('/\s+/', trim($filters['buscar_rapido']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($terminos as $termino) {
                $query->where(function (Builder $search) use ($termino): void {
                    $search->where('p.ci', 'like', '%'.$termino.'%')
                        ->orWhere('p.primerapellido', 'like', '%'.$termino.'%')
                        ->orWhere('p.segundoapellido', 'like', '%'.$termino.'%')
                        ->orWhere('p.nombre', 'like', '%'.$termino.'%');
                });
            }
        }

        if (! empty($filters['id_gestionacademica'])) {
            $query->where('i.id_gestionacademica', $filters['id_gestionacademica']);
        }

        if (! empty($filters['id_carrera'])) {
            $query->where('i.id_carrera', $filters['id_carrera']);
        }

        if (isset($filters['bth'])) {
            $query->where(function (Builder $bth) use ($filters): void {
                if ($filters['bth'] === '1') {
                    $bth->whereNotNull('e.path_bth')->where('e.path_bth', '!=', '');
                } else {
                    $bth->whereNull('e.path_bth')->orWhere('e.path_bth', '');
                }
            });
        }

        if (isset($filters['traspaso_convalidacion'])) {
            $query->where('e.traspaso_convalidacion', $filters['traspaso_convalidacion']);
        }

        if (! empty($filters['nivel'])) {
            $query->whereExists(function (Builder $level) use ($filters): void {
                $level->selectRaw('1')
                    ->from('cursa as cu')
                    ->whereColumn('cu.id_persona', 'i.id_persona')
                    ->whereColumn('cu.id_carrera', 'i.id_carrera')
                    ->whereColumn('cu.id_gestionacademica', 'i.id_gestionacademica')
                    ->where('cu.nivelinicio', $filters['nivel']);
            });
        }

        if (! empty($filters['turno']) || ! empty($filters['paralelo'])) {
            $query->whereExists(function (Builder $assignment) use ($filters): void {
                $assignment->selectRaw('1')
                    ->from('inscrito_materia as im')
                    ->join('grupo as gr', 'gr.id_grupo', '=', 'im.id_grupo')
                    ->join('materia as m', function ($join): void {
                        $join->on('m.id_materia', '=', 'im.id_materia')
                            ->on('m.id_materia', '=', 'gr.id_materia');
                    })
                    ->whereColumn('im.id_inscrito', 'i.id_inscrito');

                $assignment->whereColumn('m.id_carrera', 'i.id_carrera')
                    ->whereColumn('gr.id_gestionacademica', 'i.id_gestionacademica');

                if (! empty($filters['nivel'])) {
                    $assignment->where('m.nivel', $filters['nivel']);
                }

                if (! empty($filters['turno'])) {
                    $assignment->where('gr.turno', $filters['turno']);
                }

                if (! empty($filters['paralelo'])) {
                    $paralelo = $this->nombreParalelo($filters['paralelo'])
                        ?? mb_strtoupper(trim($filters['paralelo']));
                    $assignment->where(function (Builder $parallel) use ($paralelo): void {
                        $parallel->whereRaw('UPPER(gr.codigo) = ?', [$paralelo])
                            ->orWhereRaw('UPPER(gr.codigo) LIKE ?', ['%-'.$paralelo]);
                    });
                }
            });
        }

        // Cuando se solicita exportar, carga todas las coincidencias en lugar de limitarse a una página.
        if ($request->has('formato')) {
            $exportRequest = $request->validate([
                'formato' => ['required', 'in:excel,pdf'],
            ]);
            $inscritosData = (clone $query)->orderByDesc('i.fecha')->orderByDesc('i.id_inscrito')->get();
            $inscritosData->each(function (object $inscrito): void {
                if ($inscrito->carrera !== null) {
                    $inscrito->carrera = Carrera::formatearNombreConResolucion(
                        $inscrito->carrera,
                        $inscrito->resolucionministerial1,
                        $inscrito->resolucionministerial2,
                        $inscrito->resolucionministerial3
                    );
                }
            });

            $carreraSeleccionada = ! empty($filters['id_carrera'])
                ? Carrera::where('id_carrera', $filters['id_carrera'])->first()
                : null;
            $gestionSeleccionada = ! empty($filters['id_gestionacademica'])
                ? GestionAcademica::where('id_gestionacademica', $filters['id_gestionacademica'])->first()
                : null;

            if ($exportRequest['formato'] === 'pdf') {
                return view('Inscripciones.inscriptos.pdf', compact(
                    'inscritosData',
                    'carreraSeleccionada',
                    'gestionSeleccionada'
                ));
            }

            $rows = $inscritosData->map(fn ($inscrito): array => [
                        $inscrito->codigo,
                        $inscrito->ci,
                        trim(implode(' ', array_filter([
                            $inscrito->nombre,
                            $inscrito->primerapellido,
                            $inscrito->segundoapellido,
                        ]))),
                        $inscrito->carrera,
                        $inscrito->gestion,
                        $inscrito->periodo,
                        $inscrito->fecha,
                        $inscrito->becado ? 'Sí' : 'No',
                        $inscrito->activo ? 'Activo' : 'Deshabilitado',
                    ])->all();

            return $this->exportarExcel(
                'Inscriptos_'.now()->format('Y-m-d_H-i').'.xlsx',
                ['Código', 'CI', 'Nombre', 'Carrera', 'Gestión', 'Período', 'Fecha', 'Becado', 'Estado'],
                $rows
            );
        }

        // Los indicadores se calculan sobre el mismo conjunto filtrado que se presenta en pantalla.
        $statsQuery = clone $query;
        $totalInscripciones = (clone $statsQuery)->count('i.id_inscrito');
        $totalEstudiantes = (clone $statsQuery)->distinct()->count('i.id_persona');
        $totalBecados = (clone $statsQuery)->where('i.becado', true)->count('i.id_inscrito');
        $multiplesCarrerasCount = (clone $statsQuery)
            ->whereExists(function (Builder $multiple): void {
                $multiple->selectRaw('1')
                    ->from('inscrito as otra')
                    ->whereColumn('otra.id_persona', 'i.id_persona')
                    ->whereColumn('otra.id_gestionacademica', 'i.id_gestionacademica')
                    ->where('otra.activo', true)
                    ->whereNull('otra.id_inscrito_reemplazo')
                    ->groupBy('otra.id_persona', 'otra.id_gestionacademica')
                    ->havingRaw('COUNT(DISTINCT otra.id_carrera) > 1');
            })
            ->distinct()
            ->count('i.id_persona');

        // Ordena y pagina el resultado; los grupos de la gestión se cargan luego para completar cada fila.
        $inscritos = $this->ordenarPorTurnoYParalelo($query)
            ->paginate(10)
            ->withQueryString();
        $gruposPorInscrito = $this->gruposPorInscrito($inscritos->getCollection()->pluck('id_inscrito'));
        $inscritos->getCollection()->each(function (object $inscrito) use ($gruposPorInscrito): void {
            if ($inscrito->carrera !== null) {
                $inscrito->carrera = Carrera::formatearNombreConResolucion(
                    $inscrito->carrera,
                    $inscrito->resolucionministerial1,
                    $inscrito->resolucionministerial2,
                    $inscrito->resolucionministerial3
                );
            }
            $inscrito->curso = $inscrito->nivelinicio !== null
                ? Carrera::etiquetaNivel($inscrito->regimen, $inscrito->nivelinicio)
                : 'Sin curso registrado';
            $gruposActuales = $gruposPorInscrito[$inscrito->id_inscrito] ?? collect();
            $paraleloInscrito = trim((string) $inscrito->paralelo);
            if ($inscrito->turno !== null || $paraleloInscrito !== '') {
                $grupoActual = $gruposActuales->first();
                $inscrito->grupos_asignados = collect([(object) [
                    'paralelo' => $paraleloInscrito ?: $grupoActual?->paralelo,
                    'turno_etiqueta' => $inscrito->turno !== null
                        ? $this->etiquetaTurno($inscrito->turno)
                        : $grupoActual?->turno_etiqueta,
                ]]);
            } else {
                $inscrito->grupos_asignados = $gruposActuales;
            }
        });

        $carrerasPorPersonaGestion = $this->carrerasPorPersonaGestion($inscritos->getCollection());
        foreach ($inscritos as $inscrito) {
            $clave = $this->clavePersonaGestion($inscrito->id_persona, $inscrito->id_gestionacademica);
            $inscrito->carreras_inscritas = $carrerasPorPersonaGestion[$clave] ?? collect();
        }

        $gestiones = GestionAcademica::query()
            ->orderByDesc('gestion')
            ->orderByDesc('periodo')
            ->get();
        $carreras = Carrera::query()
            ->when(
                ! empty($filters['id_gestionacademica']),
                function ($query) use ($filters): void {
                    $filtrosGestion = ['id_gestionacademica' => $filters['id_gestionacademica']];
                    $carrerasConGrupos = $this->gruposConfiguradosQuery($filtrosGestion)
                        ->select('m.id_carrera')
                        ->distinct()
                        ->pluck('m.id_carrera');
                    $carrerasInscritas = DB::table('inscrito')
                        ->where('id_gestionacademica', $filters['id_gestionacademica'])
                        ->select('id_carrera')
                        ->distinct()
                        ->pluck('id_carrera');

                    $query->whereIn(
                        'id_carrera',
                        $carrerasConGrupos->merge($carrerasInscritas)->unique()->values()
                    );
                }
            )
            ->orderBy('nombre')
            ->get();

        $niveles = collect();
        if (! empty($filters['id_gestionacademica']) && ! empty($filters['id_carrera'])) {
            $carrera = $carreras->firstWhere('id_carrera', (int) $filters['id_carrera']);
            if ($carrera) {
                $nivelesPermitidos = Carrera::nivelesPorRegimen($carrera->regimen);
                $filtrosCarreraGestion = array_intersect_key(
                    $filters,
                    array_flip(['id_gestionacademica', 'id_carrera'])
                );
                $niveles = $this->gruposConfiguradosQuery($filtrosCarreraGestion)
                    ->whereNotNull('m.nivel')
                    ->select('m.nivel')
                    ->distinct()
                    ->orderBy('m.nivel')
                    ->pluck('m.nivel')
                    ->filter(fn ($nivel): bool => in_array((int) $nivel, $nivelesPermitidos, true))
                    ->values()
                    ->map(fn ($nivel): object => (object) [
                        'valor' => $nivel,
                        'etiqueta' => Carrera::etiquetaNivel($carrera->regimen, $nivel),
                    ]);
            }
        }

        $filtrosGrupos = array_intersect_key(
            $filters,
            array_flip(['id_gestionacademica', 'id_carrera', 'nivel'])
        );
        $gruposPorNivel = $this->gruposConfiguradosQuery($filtrosGrupos);
        $turnos = ! empty($filters['nivel'])
            ? (clone $gruposPorNivel)->whereNotNull('gr.turno')
                ->whereIn('gr.turno', [1, 2, 3])
                ->select('gr.turno')
                ->distinct()
                ->orderBy('gr.turno')
                ->pluck('gr.turno')
                ->map(fn ($turno): object => (object) [
                    'valor' => $turno,
                    'etiqueta' => $this->etiquetaTurno($turno),
                ])
            : collect();
        $filtrosParalelos = array_intersect_key(
            $filters,
            array_flip(['id_gestionacademica', 'id_carrera', 'nivel', 'turno'])
        );
        $paralelos = ! empty($filters['nivel']) && ! empty($filters['turno'])
            ? $this->gruposConfiguradosQuery($filtrosParalelos)
                ->whereNotNull('gr.codigo')
                ->select('gr.codigo')
                ->distinct()
                ->orderBy('gr.codigo')
                ->pluck('gr.codigo')
                ->map(fn (string $codigo): ?string => $this->nombreParalelo($codigo))
                ->filter()
                ->unique()
                ->sort()
                ->values()
            : collect();

        return view('Inscripciones.inscriptos.index', compact(
            'inscritos',
            'gestiones',
            'carreras',
            'niveles',
            'turnos',
            'paralelos',
            'totalInscripciones',
            'totalEstudiantes',
            'totalBecados',
            'multiplesCarrerasCount'
        ))->with('estado', $estado);
    }

    /**
     * Carga una inscripción activa y prepara sus datos, gestiones y grupos para editarla.
     * Mantiene disponible la combinación de turno y paralelo actual aunque ya no se ofrezca a nuevos estudiantes.
     */
    public function edit(Inscrito $inscrito)
    {
        abort_unless($inscrito->activo && ! $inscrito->id_inscrito_reemplazo, 404);

        $inscripcion = DB::table('inscrito as i')
            ->join('personas as p', 'p.id_persona', '=', 'i.id_persona')
            ->join('estudiante as e', 'e.id_persona', '=', 'i.id_persona')
            ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
            ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
            ->leftJoin('cursa as cu', function ($join): void {
                $join->on('cu.id_persona', '=', 'i.id_persona')
                    ->on('cu.id_carrera', '=', 'i.id_carrera')
                    ->on('cu.id_gestionacademica', '=', 'i.id_gestionacademica');
            })
            ->where('i.id_inscrito', $inscrito->id_inscrito)
            ->where('i.activo', true)
            ->whereNull('i.id_inscrito_reemplazo')
            ->first([
                'i.id_inscrito',
                'i.id_persona',
                'i.id_carrera',
                'i.id_gestionacademica',
                'i.fecha',
                'i.becado',
                'i.deposito',
                'i.turno',
                'i.paralelo',
                'p.nombre',
                'p.primerapellido',
                'p.segundoapellido',
                'p.ci',
                'p.lugarexpedicion',
                'p.fechanacimiento',
                'p.lugarnacimiento',
                'p.nacionalidad',
                'p.sexo',
                'p.direccion',
                'p.telefono',
                'p.celular',
                'p.email',
                'e.diplomabachiller',
                'e.nombrecontacto',
                'e.celularcontacto',
                'e.parentescocontacto',
                'e.nombrecolegio',
                'e.caractercolegio',
                'e.egresocolegio',
                'e.traspaso_convalidacion',
                'c.nombre as carrera',
                'c.regimen',
                'ga.gestion',
                'ga.periodo',
                'cu.nivelinicio',
            ]);
        abort_unless($inscripcion, 404);

        $carreras = Carrera::query()
            ->where('activo', true)
            ->orWhere('id_carrera', $inscripcion->id_carrera)
            ->orderBy('nombre')
            ->get([
                'id_carrera',
                'nombre',
                'regimen',
                'resolucionministerial1',
                'resolucionministerial2',
                'resolucionministerial3',
            ]);
        $gestiones = GestionAcademica::query()
            ->where(function ($query) use ($inscripcion): void {
                $query->where(function ($habilitadas): void {
                    $habilitadas->where('vigente', true)->where('activo', true);
                })->orWhere('id_gestionacademica', $inscripcion->id_gestionacademica);
            })
            ->orderByDesc('gestion')
            ->orderByDesc('periodo')
            ->get();

        $grupoActual = $this->gruposPorInscrito(collect([(int) $inscripcion->id_inscrito]))
            ->get($inscripcion->id_inscrito, collect())
            ->first();
        $turnoInicial = $inscripcion->turno ?? $grupoActual?->turno;
        $paraleloInicial = $inscripcion->paralelo ?: $grupoActual?->paralelo;
        $opcionesTurnosCarrera = [];

        foreach ($gestiones as $gestion) {
            foreach ($carreras as $carrera) {
                foreach (Carrera::nivelesPorRegimen((int) $carrera->regimen) as $nivel) {
                    $opciones = $this->disponibilidadTurnos->opciones(
                        (int) $carrera->id_carrera,
                        (int) $gestion->id_gestionacademica,
                        nivel: (int) $nivel,
                        inscritoExcluir: (int) $inscripcion->id_inscrito
                    );

                    if ($opciones !== []) {
                        $opcionesTurnosCarrera[$gestion->id_gestionacademica][$carrera->id_carrera][$nivel] = $opciones;
                    }
                }
            }
        }

        if ($turnoInicial !== null && $paraleloInicial !== null && $inscripcion->nivelinicio !== null) {
            $opcionesActuales = $opcionesTurnosCarrera[$inscripcion->id_gestionacademica][$inscripcion->id_carrera][$inscripcion->nivelinicio] ?? [];
            $opcionActualExiste = collect($opcionesActuales)->contains(fn (array $opcion): bool =>
                (int) $opcion['turno'] === (int) $turnoInicial
                && mb_strtoupper($opcion['paralelo']) === mb_strtoupper($paraleloInicial)
            );

            if (! $opcionActualExiste) {
                $opcionesTurnosCarrera[$inscripcion->id_gestionacademica][$inscripcion->id_carrera][$inscripcion->nivelinicio][] = [
                    'turno' => (int) $turnoInicial,
                    'paralelo' => mb_strtoupper($paraleloInicial),
                    'capacidad' => null,
                    'disponibles' => null,
                    'actual' => true,
                ];
            }
        }

        return view('Inscripciones.inscriptos.edit', compact(
            'inscripcion',
            'carreras',
            'gestiones',
            'opcionesTurnosCarrera',
            'turnoInicial',
            'paraleloInicial'
        ));
    }

    /**
     * Actualiza datos personales, académicos y administrativos de una inscripción activa.
     * Valida duplicados y disponibilidad; los cambios se guardan juntos y no se permiten cambios de carrera
     * o gestión si ya hay materias asignadas.
     */
    public function update(Request $request, Inscrito $inscrito)
    {
        $inscrito->refresh();
        abort_unless($inscrito->activo && ! $inscrito->id_inscrito_reemplazo, 404);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'primerapellido' => ['required', 'string', 'max:255'],
            'segundoapellido' => ['nullable', 'string', 'max:255'],
            'ci' => ['required', 'string', 'max:255', Rule::unique('personas', 'ci')->ignore($inscrito->id_persona, 'id_persona')],
            'lugarexpedicion' => ['nullable', 'string', 'max:255'],
            'fechanacimiento' => ['nullable', 'date'],
            'lugarnacimiento' => ['nullable', 'string', 'max:255'],
            'nacionalidad' => ['nullable', 'string', 'max:255'],
            'sexo' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'celular' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'email' => ['nullable', 'email', 'max:255'],
            'diplomabachiller' => ['required', 'boolean'],
            'nombrecontacto' => ['nullable', 'string', 'max:255'],
            'celularcontacto' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'parentescocontacto' => ['nullable', 'string', 'max:255'],
            'nombrecolegio' => ['nullable', 'string', 'max:255'],
            'caractercolegio' => ['nullable', Rule::in(['Fiscal', 'Particular', 'Convenio'])],
            'egresocolegio' => ['nullable', 'integer', 'between:1900,2100'],
            'traspaso_convalidacion' => ['required', 'boolean'],
            'fecha' => ['required', 'date'],
            'becado' => ['required', 'boolean'],
            'deposito' => ['required', 'string', 'max:255'],
            'id_carrera' => ['required', 'integer', 'exists:carreras,id_carrera'],
            'id_gestionacademica' => ['required', 'integer', 'exists:gestionacademica,id_gestionacademica'],
            'turno' => ['required', 'integer', 'in:1,2,3'],
            'paralelo' => ['required', 'string', 'max:20'],
            'nivelinicio' => [
                'required',
                'integer',
                Rule::in(Carrera::nivelesPorRegimen(
                    Carrera::whereKey($request->input('id_carrera'))->value('regimen')
                )),
            ],
        ]);

        // Bloquea la inscripción y vuelve a comprobar su estado antes de aplicar los cambios.
        DB::transaction(function () use ($validated, $inscrito): void {
            $actual = DB::table('inscrito')
                ->where('id_inscrito', $inscrito->id_inscrito)
                ->where('activo', true)
                ->whereNull('id_inscrito_reemplazo')
                ->lockForUpdate()
                ->first();
            if (! $actual) {
                throw ValidationException::withMessages([
                    'inscripcion' => 'Esta inscripción ya no está disponible para editar.',
                ]);
            }

            if ((int) $actual->id_carrera !== (int) $validated['id_carrera']
                && ! DB::table('carreras')->where('id_carrera', $validated['id_carrera'])->where('activo', true)->exists()) {
                throw ValidationException::withMessages([
                    'id_carrera' => 'La carrera seleccionada no está habilitada para nuevas inscripciones.',
                ]);
            }

            // Un cambio de carrera o gestión alteraría las materias asociadas, por eso se detiene si ya existen.
            $cambioAcademico = (int) $actual->id_carrera !== (int) $validated['id_carrera']
                || (int) $actual->id_gestionacademica !== (int) $validated['id_gestionacademica'];
            if ($cambioAcademico && DB::table('inscrito_materia')
                ->where('id_inscrito', $inscrito->id_inscrito)
                ->exists()) {
                throw ValidationException::withMessages([
                    'id_carrera' => 'No se puede cambiar carrera o gestión porque esta inscripción ya tiene materias asignadas.',
                ]);
            }

            $nivelActual = DB::table('cursa')
                ->where('id_persona', $actual->id_persona)
                ->where('id_carrera', $actual->id_carrera)
                ->where('id_gestionacademica', $actual->id_gestionacademica)
                ->value('nivelinicio');
                $grupoActual = $this->gruposPorInscrito(collect([(int) $inscrito->id_inscrito]))
                    ->get($inscrito->id_inscrito, collect())
                    ->first();
                $turnoActual = $actual->turno ?? $grupoActual?->turno;
                $paraleloActual = $actual->paralelo ?: $grupoActual?->paralelo;
            $cambioDisponibilidad = $cambioAcademico
                || (int) $nivelActual !== (int) $validated['nivelinicio']
                    || (int) $turnoActual !== (int) $validated['turno']
                    || mb_strtoupper(trim((string) $paraleloActual)) !== mb_strtoupper(trim($validated['paralelo']));

            if ($cambioDisponibilidad) {
                $this->validarTurnoNoRepetido(
                    (int) $actual->id_persona,
                    $validated['ci'],
                    (int) $validated['id_gestionacademica'],
                    (int) $validated['turno'],
                    (int) $inscrito->id_inscrito
                );
                $this->disponibilidadTurnos->validarDisponible(
                    (int) $validated['id_carrera'],
                    (int) $validated['id_gestionacademica'],
                    (int) $validated['turno'],
                    $validated['paralelo'],
                    nivel: (int) $validated['nivelinicio'],
                    inscritoExcluir: (int) $inscrito->id_inscrito
                );
            }

            $duplicada = DB::table('inscrito')
                ->where('id_persona', $actual->id_persona)
                ->where('id_carrera', $validated['id_carrera'])
                ->where('id_gestionacademica', $validated['id_gestionacademica'])
                ->where('activo', true)
                ->whereNull('id_inscrito_reemplazo')
                ->where('id_inscrito', '<>', $inscrito->id_inscrito)
                ->exists();
            if ($duplicada) {
                throw ValidationException::withMessages([
                    'id_carrera' => 'El estudiante ya tiene una inscripción activa en esa carrera y gestión.',
                ]);
            }

            // El comprobante puede conservarse en esta inscripción, pero no duplicarse en otra activa.
            $voucher = trim($validated['deposito']);
            $voucherNormalizado = mb_strtolower($voucher);
            $comprobantesPropios = DB::table('comprobante')
                ->where('id_inscrito', $inscrito->id_inscrito)
                ->pluck('id_comprobante');
            $voucherOcupado = DB::table('inscrito')
                ->where('id_gestionacademica', $validated['id_gestionacademica'])
                ->where('activo', true)
                ->where('id_inscrito', '<>', $inscrito->id_inscrito)
                ->whereRaw('LOWER(deposito) = ?', [$voucherNormalizado])
                ->exists()
                || DB::table('comprobante')
                    ->where('id_gestionacademica', $validated['id_gestionacademica'])
                    ->where('activo', true)
                    ->whereNotIn('id_comprobante', $comprobantesPropios)
                    ->whereRaw('LOWER(deposito) = ?', [$voucherNormalizado])
                    ->exists();
            if ($voucherOcupado) {
                throw ValidationException::withMessages([
                    'deposito' => 'Este comprobante ya está registrado en otra inscripción activa de la gestión.',
                ]);
            }

            $ahora = now();
            // Actualiza por separado los datos compartidos de la persona, su ficha de estudiante y la inscripción.
            DB::table('personas')->where('id_persona', $actual->id_persona)->update([
                'nombre' => $validated['nombre'],
                'primerapellido' => $validated['primerapellido'],
                'segundoapellido' => $validated['segundoapellido'] ?? null,
                'ci' => $validated['ci'],
                'lugarexpedicion' => $validated['lugarexpedicion'] ?? null,
                'fechanacimiento' => $validated['fechanacimiento'] ?? null,
                'lugarnacimiento' => $validated['lugarnacimiento'] ?? null,
                'nacionalidad' => $validated['nacionalidad'] ?? null,
                'sexo' => $validated['sexo'] ?? null,
                'direccion' => $validated['direccion'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'celular' => $validated['celular'] ?? null,
                'email' => $validated['email'] ?? null,
                'updated_at' => $ahora,
            ]);
            DB::table('estudiante')->where('id_persona', $actual->id_persona)->update([
                'diplomabachiller' => (int) $validated['diplomabachiller'],
                'nombrecontacto' => $validated['nombrecontacto'] ?? null,
                'celularcontacto' => $validated['celularcontacto'] ?? null,
                'parentescocontacto' => $validated['parentescocontacto'] ?? null,
                'nombrecolegio' => $validated['nombrecolegio'] ?? null,
                'caractercolegio' => $validated['caractercolegio'] ?? null,
                'egresocolegio' => $validated['egresocolegio'] ?? null,
                'updated_at' => $ahora,
            ]);
            DB::table('inscrito')->where('id_inscrito', $inscrito->id_inscrito)->update([
                'fecha' => $validated['fecha'],
                'becado' => (int) $validated['becado'],
                'deposito' => $voucher,
                'id_carrera' => $validated['id_carrera'],
                'id_gestionacademica' => $validated['id_gestionacademica'],
                'turno' => (int) $validated['turno'],
                'paralelo' => mb_strtoupper(trim($validated['paralelo'])),
                'updated_at' => $ahora,
            ]);
            DB::table('comprobante')
                ->where('id_inscrito', $inscrito->id_inscrito)
                ->where('activo', true)
                ->update([
                    'deposito' => $voucher,
                    'fecha' => $validated['fecha'],
                    'id_carrera' => $validated['id_carrera'],
                    'id_gestionacademica' => $validated['id_gestionacademica'],
                    'updated_at' => $ahora,
                ]);

            $cursaKey = [
                'id_persona' => $actual->id_persona,
                'id_carrera' => $validated['id_carrera'],
                'id_gestionacademica' => $validated['id_gestionacademica'],
            ];
            if (DB::table('cursa')->where($cursaKey)->exists()) {
                DB::table('cursa')->where($cursaKey)->update([
                    'nivelinicio' => $validated['nivelinicio'],
                    'updated_at' => $ahora,
                ]);
            } else {
                DB::table('cursa')->insert($cursaKey + [
                    'nivelinicio' => $validated['nivelinicio'],
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }

            DB::table('estudiante')
                ->where('id_persona', $actual->id_persona)
                ->where('id_carrera', $actual->id_carrera)
                ->where('id_gestionacademica', $actual->id_gestionacademica)
                ->update([
                    'id_carrera' => $validated['id_carrera'],
                    'id_gestionacademica' => $validated['id_gestionacademica'],
                    'becado' => (int) $validated['becado'],
                    'deposito' => $voucher,
                    'traspaso_convalidacion' => (int) $validated['traspaso_convalidacion'],
                    'updated_at' => $ahora,
                ]);
        });

        return redirect()->route('inscriptos.index')->with('success', 'La ficha y la inscripción se actualizaron correctamente.');
    }

    /**
     * Deshabilita la inscripción y los registros administrativos asociados sin borrarlos del historial.
     */
    public function deshabilitar(Inscrito $inscrito)
    {
        DB::transaction(function () use ($inscrito): void {
            $actual = DB::table('inscrito')
                ->where('id_inscrito', $inscrito->id_inscrito)
                ->where('activo', true)
                ->whereNull('id_inscrito_reemplazo')
                ->lockForUpdate()
                ->first(['id_inscrito']);
            if (! $actual) {
                throw ValidationException::withMessages([
                    'inscripcion' => 'La inscripción ya está deshabilitada o reemplazada.',
                ]);
            }

            $ahora = now();
            DB::table('inscrito')->where('id_inscrito', $inscrito->id_inscrito)->update([
                'activo' => false,
                'updated_at' => $ahora,
            ]);
            DB::table('comprobante')->where('id_inscrito', $inscrito->id_inscrito)->update([
                'activo' => false,
                'updated_at' => $ahora,
            ]);
            DB::table('pago')->where('id_inscrito', $inscrito->id_inscrito)->update([
                'estado' => false,
                'updated_at' => $ahora,
            ]);
        });

        return redirect()->route('inscriptos.index')->with('success', 'La inscripción, su comprobante y sus pagos se deshabilitaron. Los registros se conservaron en el historial administrativo.');
    }

    /**
     * Construye la consulta base con datos personales, académicos, documentos y estado de cada inscripción.
     * Incluye datos auxiliares para filtrar, ordenar, mostrar carreras y habilitar acciones administrativas.
     */
    private function inscritosQuery(): Builder
    {
        return DB::table('inscrito as i')
            ->join('personas as p', 'p.id_persona', '=', 'i.id_persona')
            ->leftJoin('estudiante as e', 'e.id_persona', '=', 'i.id_persona')
            ->leftJoin('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
            ->leftJoin('gestionacademica as g', 'g.id_gestionacademica', '=', 'i.id_gestionacademica')
            ->select(
                'i.id_inscrito',
                'i.id_persona',
                'i.id_gestionacademica',
                'i.codigo',
                'i.fecha',
                'i.tipo',
                'i.becado',
                'i.activo',
                'i.turno',
                'i.paralelo',
                'i.id_inscrito_reemplazo',
                'p.primerapellido',
                'p.segundoapellido',
                'p.nombre',
                'p.ci',
                'p.celular',
                'p.foto',
                'c.id_carrera',
                'c.nombre as carrera',
                'c.resolucionministerial1',
                'c.resolucionministerial2',
                'c.resolucionministerial3',
                'c.regimen',
                'g.gestion',
                'g.periodo',
                'e.path_ci',
                'e.path_ci_reverso',
                'e.path_nacimiento',
                'e.path_diploma',
                'e.path_bth',
            )
            ->selectSub(
                DB::table('users as u')
                    ->select('u.id_persona')
                    ->whereColumn('u.id_persona', 'i.id_persona')
                    ->limit(1),
                'usuario_id'
            )
            ->selectSub(
                DB::table('cursa as cu')
                    ->select('cu.nivelinicio')
                    ->whereColumn('cu.id_persona', 'i.id_persona')
                    ->whereColumn('cu.id_carrera', 'i.id_carrera')
                    ->whereColumn('cu.id_gestionacademica', 'i.id_gestionacademica')
                    ->limit(1),
                'nivelinicio'
            )
            ->selectSub(
                DB::table('inscrito_materia as im')
                    ->join('materia as m', 'm.id_materia', '=', 'im.id_materia')
                    ->join('grupo as gr', 'gr.id_grupo', '=', 'im.id_grupo')
                    ->whereColumn('im.id_inscrito', 'i.id_inscrito')
                    ->whereColumn('m.id_carrera', 'i.id_carrera')
                    ->whereColumn('gr.id_gestionacademica', 'i.id_gestionacademica')
                    ->orderBy('gr.turno')
                    ->orderByRaw(self::SQL_ORDEN_PARALELO)
                    ->select('gr.turno')
                    ->limit(1),
                'turno_orden'
            )
            ->selectSub(
                DB::table('inscrito_materia as im')
                    ->join('materia as m', 'm.id_materia', '=', 'im.id_materia')
                    ->join('grupo as gr', 'gr.id_grupo', '=', 'im.id_grupo')
                    ->whereColumn('im.id_inscrito', 'i.id_inscrito')
                    ->whereColumn('m.id_carrera', 'i.id_carrera')
                    ->whereColumn('gr.id_gestionacademica', 'i.id_gestionacademica')
                    ->orderBy('gr.turno')
                    ->orderByRaw(self::SQL_ORDEN_PARALELO)
                    ->selectRaw(self::SQL_ORDEN_PARALELO)
                    ->limit(1),
                'paralelo_orden'
            )
            ->selectSub(
                DB::table('inscrito as otra')
                    ->selectRaw('COUNT(DISTINCT otra.id_carrera)')
                    ->whereColumn('otra.id_persona', 'i.id_persona')
                    ->whereColumn('otra.id_gestionacademica', 'i.id_gestionacademica')
                    ->where('otra.activo', true)
                    ->whereNull('otra.id_inscrito_reemplazo'),
                'cantidad_carreras_gestion'
            );
    }

    /**
     * Devuelve los grupos asignados a inscripciones para las opciones dependientes del listado.
     */
    private function gruposConfiguradosQuery(array $filters): Builder
    {
        return DB::table('grupo as gr')
            ->join('materia as m', 'm.id_materia', '=', 'gr.id_materia')
            ->join('carreras as c', 'c.id_carrera', '=', 'm.id_carrera')
            ->when(
                ! empty($filters['id_gestionacademica']),
                fn (Builder $query) => $query->where('gr.id_gestionacademica', $filters['id_gestionacademica'])
            )
            ->when(
                ! empty($filters['id_carrera']),
                fn (Builder $query) => $query->where('m.id_carrera', $filters['id_carrera'])
            )
            ->when(
                ! empty($filters['nivel']),
                fn (Builder $query) => $query->where('m.nivel', $filters['nivel'])
            )
            ->when(
                ! empty($filters['turno']),
                fn (Builder $query) => $query->where('gr.turno', $filters['turno'])
            );
    }

    /**
     * Ordena las inscripciones por turno, paralelo, nivel y fecha de registro.
     */
    private function ordenarPorTurnoYParalelo(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN turno_orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('turno_orden')
            ->orderBy('paralelo_orden')
            ->orderBy('nivelinicio')
            ->orderByDesc('i.fecha')
            ->orderByDesc('i.id_inscrito');
    }

    /**
     * Obtiene y agrupa los turnos y paralelos de las materias asignadas a las inscripciones indicadas.
     */
    private function gruposPorInscrito(Collection $idsInscritos): Collection
    {
        if ($idsInscritos->isEmpty()) {
            return collect();
        }

        return DB::table('inscrito_materia as im')
            ->join('inscrito as i', 'i.id_inscrito', '=', 'im.id_inscrito')
            ->join('materia as m', 'm.id_materia', '=', 'im.id_materia')
            ->join('grupo as gr', 'gr.id_grupo', '=', 'im.id_grupo')
            ->whereIn('im.id_inscrito', $idsInscritos)
            ->whereColumn('m.id_carrera', 'i.id_carrera')
            ->whereColumn('gr.id_gestionacademica', 'i.id_gestionacademica')
            ->select('im.id_inscrito', 'gr.codigo', 'gr.turno')
            ->distinct()
            ->get()
            ->groupBy('id_inscrito')
            ->map(fn ($grupos) => $grupos
                ->map(function (object $grupo): object {
                    $grupo->paralelo = $this->nombreParalelo((string) $grupo->codigo) ?? $grupo->codigo;
                    $grupo->turno_etiqueta = $this->etiquetaTurno($grupo->turno);

                    return $grupo;
                })
                ->unique(fn (object $grupo): string => $grupo->turno.'|'.$grupo->paralelo)
                ->sortBy(fn (object $grupo): array => [
                    (int) $grupo->turno,
                    mb_strtolower($grupo->paralelo),
                ])
                ->values());
    }

    /**
     * Obtiene el paralelo final de códigos como "A", "MAT-101-A" o "MAT-101-AA".
     */
    private function nombreParalelo(string $codigo): ?string
    {
        return preg_match('/(?:^|-)([A-Z]{1,2})$/i', trim($codigo), $coincidencias)
            ? mb_strtoupper($coincidencias[1])
            : null;
    }

    /**
     * Traduce el número de turno a la etiqueta que se muestra en el listado.
     */
    private function etiquetaTurno(int|string|null $turno): string
    {
        return [
            1 => 'Mañana',
            2 => 'Tarde',
            3 => 'Noche',
        ][(int) $turno] ?? 'Turno no definido';
    }

    /**
     * Evita que una persona tenga otra inscripción o postulación en el mismo turno y gestión.
     */
    private function validarTurnoNoRepetido(
        int $idPersona,
        string $ci,
        int $gestionId,
        int $turno,
        int $inscritoExcluir
    ): void {
        $inscritoEnTurno = DB::table('inscrito')
            ->where('id_persona', $idPersona)
            ->where('id_gestionacademica', $gestionId)
            ->where('turno', $turno)
            ->where('activo', true)
            ->whereNull('id_inscrito_reemplazo')
            ->where('id_inscrito', '<>', $inscritoExcluir)
            ->exists();
        $postulanteEnTurno = DB::table('postulante as p')
            ->where('p.ci', $ci)
            ->where('p.id_gestionacademica', $gestionId)
            ->where('p.turno', $turno)
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('inscrito as origen')
                    ->whereColumn('origen.id_postulante_origen', 'p.id_postulante')
                    ->where('origen.activo', true)
                    ->whereNull('origen.id_inscrito_reemplazo');
            })
            ->exists();

        if ($inscritoEnTurno || $postulanteEnTurno) {
            throw ValidationException::withMessages([
                'turno' => 'La persona ya tiene una inscripción o postulación en ese turno para la gestión seleccionada.',
            ]);
        }
    }

    /**
     * Reúne las carreras activas por persona y gestión para identificar estudiantes multicarrera.
     */
    private function carrerasPorPersonaGestion($inscritos): array
    {
        $pares = $inscritos
            ->map(fn ($inscrito): array => [
                (int) $inscrito->id_persona,
                (int) $inscrito->id_gestionacademica,
            ])
            ->unique()
            ->values();

        if ($pares->isEmpty()) {
            return [];
        }

        $carreras = DB::table('inscrito as i')
            ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
            ->where(function (Builder $query) use ($pares): void {
                foreach ($pares as [$idPersona, $idGestion]) {
                    $query->orWhere(function (Builder $pair) use ($idPersona, $idGestion): void {
                        $pair->where('i.id_persona', $idPersona)
                            ->where('i.id_gestionacademica', $idGestion);
                    });
                }
            })
            ->where('i.activo', true)
            ->whereNull('i.id_inscrito_reemplazo')
            ->orderBy('c.nombre')
            ->get([
                'i.id_persona',
                'i.id_gestionacademica',
                'c.nombre',
                'c.resolucionministerial1',
                'c.resolucionministerial2',
                'c.resolucionministerial3',
            ]);

        return $carreras
            ->groupBy(fn ($carrera): string => $this->clavePersonaGestion(
                $carrera->id_persona,
                $carrera->id_gestionacademica
            ))
            ->map(fn ($registros) => $registros
                ->map(fn (object $carrera): string => Carrera::formatearNombreConResolucion(
                    $carrera->nombre,
                    $carrera->resolucionministerial1,
                    $carrera->resolucionministerial2,
                    $carrera->resolucionministerial3
                ))
                ->unique()
                ->values())
            ->all();
    }

    /**
     * Crea una clave estable para agrupar registros por persona y gestión académica.
     */
    private function clavePersonaGestion(int|string $idPersona, int|string $idGestion): string
    {
        return $idPersona.':'.$idGestion;
    }
}
