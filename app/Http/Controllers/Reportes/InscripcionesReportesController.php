<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InscripcionesReportesController extends Controller
{
    /**
     * Valida los filtros, obtiene los registros del tipo de reporte solicitado y
     * entrega la vista, el PDF o el archivo Excel correspondiente.
     */
    public function index(Request $request, string $reporte)
    {
        $reportes = $this->reportes();
        abort_unless(isset($reportes[$reporte]), 404);

        $configuracion = $reportes[$reporte];
        $formato = $request->input('formato');
        // Los filtros pasan a ser obligatorios tanto al mostrar resultados como al exportarlos.
        $generado = $request->boolean('generar') || in_array($formato, ['excel', 'pdf'], true);
        $filtros = $this->validarFiltros($request, $configuracion, $generado, $reporte);
        $opciones = $this->opciones($request, $reporte);
        $filas = collect();

        if ($generado) {
            // Los postulantes se ordenan con las columnas de su tabla; los demás reportes,
            // con las columnas de persona compartidas por las inscripciones.
            $aliasPersona = $reporte === 'lista-postulantes' ? 'po' : 'p';
            $filas = $this->consulta($reporte, $filtros)
                ->orderBy($aliasPersona.'.primerapellido')
                ->orderBy($aliasPersona.'.segundoapellido')
                ->orderBy($aliasPersona.'.nombre')
                ->get()
                ->values()
                // Asigna numeración consecutiva y presenta campos internos con etiquetas legibles.
                ->map(function (object $fila, int $indice) use ($reporte): object {
                    $fila->nro = $indice + 1;

                    if (isset($fila->fecha) && $fila->fecha) {
                        $fila->fecha = Carbon::parse($fila->fecha)->format('d/m/Y');
                    }

                    if (isset($fila->turno)) {
                        $fila->turno = [1 => 'Mañana', 2 => 'Tarde', 3 => 'Noche'][$fila->turno] ?? $fila->turno;
                    }

                    if (isset($fila->becado)) {
                        $fila->becado = $fila->becado ? 'Sí' : 'No';
                    }

                    if ($reporte === 'lista-inscritos-paralelo' && isset($fila->paralelo)) {
                        $fila->paralelo = $this->nombreParalelo($fila->paralelo);
                    }

                    return $fila;
                });
        }

        // El PDF representa las mismas columnas y filas que la vista de resultados.
        if ($formato === 'pdf') {
            return Pdf::loadView('reportes.inscripciones.pdf', compact(
                'configuracion',
                'filas',
                'filtros'
            ))->setPaper('a4', 'landscape')
                ->download('Reporte_'.$reporte.'_'.now()->format('Y-m-d_H-i').'.pdf');
        }

        // Excel se construye a partir del orden definido en "columnas", respetando
        // los valores normalizados previamente para fechas, turnos y becas.
        if ($formato === 'excel') {
            $columnas = $configuracion['columnas'];
            $encabezados = array_values($columnas);
            $datos = $filas->map(fn (object $fila): array => array_map(
                fn (string $campo): mixed => $fila->{$campo} ?? '',
                array_keys($columnas)
            ))->all();

            return $this->exportarExcel(
                'Reporte_'.$reporte.'_'.now()->format('Y-m-d_H-i').'.xlsx',
                $encabezados,
                $datos
            );
        }

        $vista = 'reportes.inscripciones.'.$reporte.'.index';

        return view($vista, compact('configuracion', 'opciones', 'filas', 'filtros', 'generado', 'reporte'));
    }

    /**
     * Define los siete reportes disponibles, sus filtros y las columnas compartidas
     * por la tabla web y por ambas exportaciones.
     *
     * Las listas de inscritos se distinguen por su alcance: carrera (una carrera),
     * paralelo (nivel, turno y letra), grupo (grupo concreto), multicarrera
     * (personas inscritas en más de una carrera) y libros (gestión/periodo o carrera).
     * La lista de postulantes, en cambio, muestra postulaciones y su estado de inscripción.
     */
    private function reportes(): array
    {
        // Datos comunes a las distintas presentaciones de inscripciones.
        $datosInscripcion = [
            'nro' => 'N°',
            'codigo' => 'Código',
            'fecha' => 'Fecha',
            'ci' => 'C.I.',
            'primerapellido' => 'Primer apellido',
            'segundoapellido' => 'Segundo apellido',
            'nombre' => 'Nombres',
            'carrera' => 'Carrera',
            'gestion' => 'Gestión',
            'periodo' => 'Periodo',
        ];

        // Filtros compartidos por los informes limitados a una gestión y una carrera.
        $filtrosGestionCarrera = [
            ['name' => 'id_gestionacademica', 'label' => 'Gestión académica', 'options' => 'gestiones'],
            ['name' => 'id_carrera', 'label' => 'Carrera', 'options' => 'carreras'],
        ];

        return [
            'lista-postulantes' => [
                'titulo' => 'Reporte Lista Postulantes',
                'filtros' => $filtrosGestionCarrera,
                'columnas' => [
                    'nro' => 'N°', 'codigo' => 'Código', 'fecha' => 'Fecha', 'ci' => 'C.I.',
                    'primerapellido' => 'Primer apellido', 'segundoapellido' => 'Segundo apellido',
                    'nombre' => 'Nombres', 'carrera' => 'Carrera', 'gestion' => 'Gestión',
                    'periodo' => 'Periodo', 'estado' => 'Estado', 'celular' => 'Celular', 'email' => 'Correo electrónico',
                    'deposito' => 'Comprobante',
                ],
            ],
            'lista-inscritos-carrera' => [
                'titulo' => 'Reporte Lista Inscritos Carrera',
                'filtros' => $filtrosGestionCarrera,
                'columnas' => [...$datosInscripcion, 'becado' => 'Becado'],
            ],
            'lista-inscritos-paralelo' => [
                'titulo' => 'Reporte Lista Inscritos Paralelo',
                'filtros' => [
                    $filtrosGestionCarrera[0],
                    $filtrosGestionCarrera[1],
                    ['name' => 'nivel', 'label' => 'Nivel', 'options' => 'niveles'],
                    ['name' => 'turno', 'label' => 'Turno', 'options' => 'turnos'],
                    ['name' => 'paralelo', 'label' => 'Paralelo', 'options' => 'paralelos'],
                ],
                'columnas' => [...$datosInscripcion, 'nivel' => 'Nivel', 'turno' => 'Turno', 'paralelo' => 'Paralelo', 'materia' => 'Materia'],
            ],
            'lista-inscritos-grupo' => [
                'titulo' => 'Reporte Lista Inscritos Grupo',
                'filtros' => [
                    $filtrosGestionCarrera[0],
                    $filtrosGestionCarrera[1],
                    ['name' => 'nivel', 'label' => 'Nivel', 'options' => 'niveles'],
                    ['name' => 'id_grupo', 'label' => 'Grupo', 'options' => 'grupos'],
                ],
                'columnas' => [...$datosInscripcion, 'nivel' => 'Nivel', 'turno' => 'Turno', 'paralelo' => 'Grupo', 'materia' => 'Materia'],
            ],
            'libro-inscripciones' => [
                'titulo' => 'Reporte Libro de Inscripciones',
                'filtros' => [
                    ['name' => 'gestion', 'label' => 'Gestión', 'options' => 'gestionesNumericas'],
                    ['name' => 'periodo', 'label' => 'Periodo', 'options' => 'periodos'],
                ],
                'columnas' => [...$datosInscripcion, 'becado' => 'Becado'],
            ],
            'libro-inscripciones-carrera' => [
                'titulo' => 'Reporte Libro de Inscripciones Carrera',
                'filtros' => $filtrosGestionCarrera,
                'columnas' => [...$datosInscripcion, 'becado' => 'Becado'],
            ],
            'lista-inscritos-multicarrera' => [
                'titulo' => 'Reporte Lista Inscritos Multicarrera',
                'filtros' => [
                    ['name' => 'id_gestionacademica', 'label' => 'Gestión académica', 'options' => 'gestiones'],
                ],
                'columnas' => [...$datosInscripcion, 'becado' => 'Becado'],
            ],
        ];
    }

    /**
     * Valida únicamente los filtros del informe seleccionado.
     * En la pantalla inicial son opcionales; al generar una tabla o exportación,
     * los campos declarados para ese tipo de reporte son obligatorios.
     */
    private function validarFiltros(Request $request, array $configuracion, bool $generado, string $reporte): array
    {
        $rules = [
            'generar' => ['nullable', 'boolean'],
            'formato' => ['nullable', Rule::in(['excel', 'pdf'])],
        ];

        // Además de validar el formato, las reglas relacionales evitan combinar
        // valores que no corresponden a los grupos, materias o periodo elegidos.
        foreach ($configuracion['filtros'] as $filtro) {
            $required = $generado ? 'required' : 'nullable';
            $rules[$filtro['name']] = match ($filtro['name']) {
                'id_gestionacademica' => [
                    $required,
                    'integer',
                    'exists:gestionacademica,id_gestionacademica',
                    function (string $attribute, mixed $value, \Closure $fail) use ($generado, $reporte): void {
                        if ($generado && ! $this->registrosDisponibles($reporte)
                            ->where('id_gestionacademica', $value)
                            ->exists()) {
                            $fail('No hay datos disponibles para la gestión seleccionada en este reporte.');
                        }
                    },
                ],
                'id_carrera' => [
                    $required,
                    'integer',
                    'exists:carreras,id_carrera',
                    function (string $attribute, mixed $value, \Closure $fail) use ($request, $generado, $reporte): void {
                        if (! $generado) {
                            return;
                        }

                        $existe = $this->registrosDisponibles($reporte)
                            ->where('id_gestionacademica', $request->input('id_gestionacademica'))
                            ->where('id_carrera', $value)
                            ->exists();

                        if (! $existe) {
                            $fail('La carrera no tiene datos en la gestión seleccionada para este reporte.');
                        }
                    },
                ],
                'nivel' => [
                    $required,
                    'integer',
                    'min:1',
                    function (string $attribute, mixed $value, \Closure $fail) use ($request, $generado): void {
                        if (! $generado) {
                            return;
                        }

                        $carrera = Carrera::find($request->input('id_carrera'));
                        $nivelPermitido = $carrera
                            && in_array((int) $value, Carrera::nivelesPorRegimen($carrera->regimen), true);
                        $nivelDisponible = $this->gruposFiltroQuery($request)
                            ->where('m.nivel', $value)
                            ->exists();

                        if (! $nivelPermitido || ! $nivelDisponible) {
                            $fail('El nivel no tiene grupos disponibles para la gestión y carrera seleccionadas.');
                        }
                    },
                ],
                'turno' => [
                    $required,
                    'integer',
                    Rule::in([1, 2, 3]),
                    function (string $attribute, mixed $value, \Closure $fail) use ($request, $generado): void {
                        if ($generado && ! $this->gruposFiltroQuery($request)
                            ->where('m.nivel', $request->input('nivel'))
                            ->where('gr.turno', $value)
                            ->exists()) {
                            $fail('No hay grupos para el turno seleccionado con los demás filtros indicados.');
                        }
                    },
                ],
                'paralelo' => [
                    $required,
                    'string',
                    'regex:/^[A-Z]{1,2}$/i',
                    function (string $attribute, mixed $value, \Closure $fail) use ($request, $generado): void {
                        if (! $generado) {
                            return;
                        }

                        $paralelo = strtoupper((string) $value);
                        $query = $this->gruposFiltroQuery($request)
                            ->where('m.nivel', $request->input('nivel'))
                            ->where('gr.turno', $request->input('turno'))
                            ->where(function (Builder $query) use ($paralelo): void {
                                $query->whereRaw('UPPER(gr.codigo) = ?', [$paralelo])
                                    ->orWhereRaw('UPPER(gr.codigo) LIKE ?', ['%-'.$paralelo]);
                            });

                        if (! $query->exists()) {
                            $fail('No hay grupos para el paralelo seleccionado con los filtros indicados.');
                        }
                    },
                ],
                'id_grupo' => [
                    $required,
                    'integer',
                    'exists:grupo,id_grupo',
                    function (string $attribute, mixed $value, \Closure $fail) use ($request, $generado): void {
                        if ($generado && ! $this->gruposFiltroQuery($request)
                            ->where('m.nivel', $request->input('nivel'))
                            ->where('gr.id_grupo', $value)
                            ->exists()) {
                            $fail('El grupo no corresponde a la gestión, carrera y nivel seleccionados.');
                        }
                    },
                ],
                'gestion' => [
                    $required,
                    'integer',
                    Rule::exists('gestionacademica', 'gestion')
                        ->where('periodo', $request->input('periodo')),
                    function (string $attribute, mixed $value, \Closure $fail) use ($request, $generado): void {
                        if (! $generado) {
                            return;
                        }

                        $existe = DB::table('inscrito as i')
                            ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
                            ->where('i.activo', true)
                            ->whereNull('i.id_inscrito_reemplazo')
                            ->where('ga.gestion', $value)
                            ->where('ga.periodo', $request->input('periodo'))
                            ->exists();

                        if (! $existe) {
                            $fail('No hay inscripciones disponibles para la gestión y periodo seleccionados.');
                        }
                    },
                ],
                'periodo' => [
                    $required,
                    'integer',
                    Rule::exists('gestionacademica', 'periodo')
                        ->where('gestion', $request->input('gestion')),
                    function (string $attribute, mixed $value, \Closure $fail) use ($request, $generado): void {
                        if (! $generado) {
                            return;
                        }

                        $existe = DB::table('inscrito as i')
                            ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
                            ->where('i.activo', true)
                            ->whereNull('i.id_inscrito_reemplazo')
                            ->where('ga.gestion', $request->input('gestion'))
                            ->where('ga.periodo', $value)
                            ->exists();

                        if (! $existe) {
                            $fail('No hay inscripciones disponibles para la gestión y periodo seleccionados.');
                        }
                    },
                ],
                default => [$required],
            };
        }

        return $request->validate($rules);
    }

    /**
     * Prepara los valores de los selectores y conserva las dependencias entre ellos:
     * grupos y paralelos se acotan por gestión, carrera, nivel y turno cuando aplica;
     * los periodos se acotan por la gestión numérica seleccionada.
     */
    private function opciones(Request $request, string $reporte): array
    {
        $registros = $this->registrosDisponibles($reporte);
        $gestionIds = (clone $registros)->distinct()->pluck('id_gestionacademica');
        $gestiones = DB::table('gestionacademica')
            ->whereIn('id_gestionacademica', $gestionIds)
            ->orderByDesc('gestion')
            ->orderByDesc('periodo')
            ->get(['id_gestionacademica', 'gestion', 'periodo'])
            ->map(fn (object $gestion): object => (object) [
                'valor' => $gestion->id_gestionacademica,
                'etiqueta' => 'Gestión '.$gestion->gestion.' - Periodo '.$gestion->periodo,
            ]);

        $carreraIds = (clone $registros)
            ->when(
                $request->filled('id_gestionacademica'),
                fn (Builder $query) => $query->where('id_gestionacademica', $request->input('id_gestionacademica'))
            )
            ->distinct()
            ->pluck('id_carrera');
        $carreras = Carrera::query()
            ->whereIn('id_carrera', $carreraIds)
            ->orderBy('nombre')
            ->get()
            ->map(function (Carrera $carrera): object {
                $resoluciones = $carrera->resolucionesMinisteriales();

                return (object) [
                    'valor' => $carrera->id_carrera,
                    'etiqueta' => $carrera->nombre.($resoluciones
                        ? ' · R.M. '.implode(' / ', $resoluciones)
                        : ''),
                ];
            });

        $gruposBase = collect();
        $niveles = collect();
        $turnos = collect();
        $paralelos = collect();
        $grupos = collect();

        if (in_array($reporte, ['lista-inscritos-paralelo', 'lista-inscritos-grupo'], true)) {
            $gruposBase = $this->gruposFiltroQuery($request)
                ->orderBy('gr.codigo')
                ->orderBy('m.nombre')
                ->get([
                    'gr.id_grupo',
                    'gr.codigo',
                    'gr.turno',
                    'gr.id_gestionacademica',
                    'm.nivel',
                    'm.id_carrera',
                    'm.nombre as materia',
                    'c.regimen',
                ])
                ->filter(fn (object $grupo): bool => $grupo->nivel !== null
                    && in_array(
                        (int) $grupo->nivel,
                        Carrera::nivelesPorRegimen($grupo->regimen),
                        true
                    ))
                ->values();

            $niveles = $gruposBase
                ->groupBy('nivel')
                ->map(function ($gruposNivel, $nivel): object {
                    $regimenes = $gruposNivel
                        ->map(fn (object $grupo): int => Carrera::regimenEfectivo($grupo->regimen) ?? 0)
                        ->unique()
                        ->values();
                    $etiqueta = $regimenes->count() === 1
                        ? Carrera::etiquetaNivel($regimenes->first(), $nivel)
                        : 'Nivel '.$nivel;

                    return (object) ['valor' => $nivel, 'etiqueta' => $etiqueta];
                })
                ->sortBy('valor')
                ->values();

            $gruposNivel = $request->filled('nivel')
                ? $gruposBase->where('nivel', $request->input('nivel'))->values()
                : $gruposBase;
            $turnos = $gruposNivel
                ->whereNotNull('turno')
                ->sortBy('turno')
                ->unique('turno')
                ->map(fn (object $grupo): object => (object) [
                    'valor' => $grupo->turno,
                    'etiqueta' => [1 => 'Mañana', 2 => 'Tarde', 3 => 'Noche'][$grupo->turno] ?? $grupo->turno,
                ])
                ->values();
            $gruposTurno = $request->filled('turno')
                ? $gruposNivel->where('turno', $request->input('turno'))->values()
                : $gruposNivel;
            $paralelos = $gruposTurno
                ->map(fn (object $grupo): ?string => $this->nombreParalelo((string) $grupo->codigo))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->map(fn (string $paralelo): object => (object) [
                    'valor' => $paralelo,
                    'etiqueta' => $paralelo,
                ]);
            $grupos = $gruposNivel
                ->map(function (object $grupo): object {
                    $turno = [1 => 'Mañana', 2 => 'Tarde', 3 => 'Noche'][$grupo->turno] ?? 'Sin turno';
                    $grupo->valor = $grupo->id_grupo;
                    $grupo->etiqueta = $grupo->codigo.' - '.$grupo->materia.' ('
                        .Carrera::etiquetaNivel($grupo->regimen, $grupo->nivel).', '.$turno.')';

                    return $grupo;
                })
                ->values();
        }

        $inscripcionesPeriodo = DB::table('inscrito as i')
            ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
            ->where('i.activo', true)
            ->whereNull('i.id_inscrito_reemplazo');
        $gestionesNumericas = (clone $inscripcionesPeriodo)
            ->select('ga.gestion')
            ->distinct()
            ->orderByDesc('ga.gestion')
            ->pluck('ga.gestion')
            ->map(fn ($gestion): object => (object) ['valor' => $gestion, 'etiqueta' => $gestion]);
        $periodos = (clone $inscripcionesPeriodo)
            ->when($request->filled('gestion'), fn (Builder $query) => $query->where('ga.gestion', $request->input('gestion')))
            ->select('ga.periodo')
            ->distinct()
            ->orderBy('ga.periodo')
            ->pluck('ga.periodo')
            ->map(fn ($periodo): object => (object) ['valor' => $periodo, 'etiqueta' => $periodo]);

        return compact('gestiones', 'carreras', 'niveles', 'turnos', 'paralelos', 'grupos', 'gestionesNumericas', 'periodos');
    }

    /**
     * Limita gestión y carrera a registros que el reporte puede mostrar.
     */
    private function registrosDisponibles(string $reporte): Builder
    {
        if ($reporte === 'lista-postulantes') {
            return DB::table('postulante as po')
                ->select('po.id_gestionacademica', 'po.id_carrera');
        }

        if (in_array($reporte, ['lista-inscritos-paralelo', 'lista-inscritos-grupo'], true)) {
            return DB::table('grupo as gr')
                ->join('materia as m', 'm.id_materia', '=', 'gr.id_materia')
                ->select('gr.id_gestionacademica', 'm.id_carrera');
        }

        $query = DB::table('inscrito as i')
            ->where('i.activo', true)
            ->whereNull('i.id_inscrito_reemplazo')
            ->select('i.id_gestionacademica', 'i.id_carrera');

        if ($reporte === 'lista-inscritos-multicarrera') {
            $query->whereExists(function (Builder $subquery): void {
                $subquery->selectRaw('1')
                    ->from('inscrito as otra')
                    ->whereColumn('otra.id_persona', 'i.id_persona')
                    ->whereColumn('otra.id_gestionacademica', 'i.id_gestionacademica')
                    ->where('otra.activo', true)
                    ->whereNull('otra.id_inscrito_reemplazo')
                    ->groupBy('otra.id_persona', 'otra.id_gestionacademica')
                    ->havingRaw('COUNT(DISTINCT otra.id_carrera) > 1');
            });
        }

        return $query;
    }

    /**
     * Consulta grupos vinculados a la gestión y carrera seleccionadas.
     */
    private function gruposFiltroQuery(Request $request): Builder
    {
        return DB::table('grupo as gr')
            ->join('materia as m', 'm.id_materia', '=', 'gr.id_materia')
            ->join('carreras as c', 'c.id_carrera', '=', 'm.id_carrera')
            ->when(
                $request->filled('id_gestionacademica'),
                fn (Builder $query) => $query->where('gr.id_gestionacademica', $request->input('id_gestionacademica'))
            )
            ->when(
                $request->filled('id_carrera'),
                fn (Builder $query) => $query->where('m.id_carrera', $request->input('id_carrera'))
            );
    }

    /**
     * Construye la consulta específica del reporte.
     *
     * La lista de postulantes incluye postulaciones de una carrera y gestión e indica
     * si cada registro ya originó una inscripción. Los informes de paralelo y grupo
     * relacionan las inscripciones con sus materias asignadas; los demás parten de
     * inscripciones activas y aplican el alcance definido por sus filtros.
     */
    private function consulta(string $reporte, array $filtros): Builder
    {
        if ($reporte === 'lista-postulantes') {
            // El left join permite conservar también postulantes aún no inscritos.
            return DB::table('postulante as po')
                ->join('carreras as c', 'c.id_carrera', '=', 'po.id_carrera')
                ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'po.id_gestionacademica')
                ->leftJoin('inscrito as i', 'i.id_postulante_origen', '=', 'po.id_postulante')
                ->where('po.id_gestionacademica', $filtros['id_gestionacademica'])
                ->where('po.id_carrera', $filtros['id_carrera'])
                ->select(
                    'po.codigo',
                    'po.fecha',
                    'po.ci',
                    'po.primerapellido',
                    'po.segundoapellido',
                    'po.nombre',
                    'c.nombre as carrera',
                    'ga.gestion',
                    'ga.periodo',
                    DB::raw("CASE WHEN i.id_inscrito IS NULL THEN 'Pendiente de inscripción' ELSE 'Inscrito como estudiante' END as estado"),
                    'po.celular',
                    'po.email',
                    'po.deposito'
                );
        }

        if (in_array($reporte, ['lista-inscritos-paralelo', 'lista-inscritos-grupo'], true)) {
            // Estos reportes muestran una fila por asignación de materia a un grupo;
            // por ello una persona puede aparecer más de una vez si tiene varias materias.
            $query = $this->consultaInscritos()
                ->join('inscrito_materia as im', 'im.id_inscrito', '=', 'i.id_inscrito')
                ->join('grupo as gr', 'gr.id_grupo', '=', 'im.id_grupo')
                ->join('materia as m', 'm.id_materia', '=', 'gr.id_materia')
                ->whereColumn('im.id_materia', 'gr.id_materia')
                ->where('i.id_gestionacademica', $filtros['id_gestionacademica'])
                ->where('i.id_carrera', $filtros['id_carrera'])
                ->where('gr.id_gestionacademica', $filtros['id_gestionacademica'])
                ->where('m.id_carrera', $filtros['id_carrera'])
                ->where('m.nivel', $filtros['nivel'])
                ->addSelect('m.nivel', 'gr.turno', 'gr.codigo as paralelo', 'm.nombre as materia');

            if ($reporte === 'lista-inscritos-paralelo') {
                // El código de grupo puede ser solo la letra o incluirla al final
                // tras un guion; ambas formas representan el mismo paralelo.
                $paralelo = strtoupper($filtros['paralelo']);

                return $query->where('gr.turno', $filtros['turno'])
                    ->where(function (Builder $query) use ($paralelo): void {
                        $query->whereRaw('UPPER(gr.codigo) = ?', [$paralelo])
                            ->orWhereRaw('UPPER(gr.codigo) LIKE ?', ['%-'.$paralelo]);
                    });
            }

            return $query->where('gr.id_grupo', $filtros['id_grupo']);
        }

        $query = $this->consultaInscritos();

        if ($reporte === 'libro-inscripciones') {
            // El libro general usa la gestión y el periodo numéricos, no el identificador interno.
            return $query->where('ga.gestion', $filtros['gestion'])
                ->where('ga.periodo', $filtros['periodo']);
        }

        $gestionId = $filtros['id_gestionacademica'] ?? null;
        if ($gestionId !== null) {
            $query->where('i.id_gestionacademica', $gestionId);
        }

        if (isset($filtros['id_carrera'])) {
            $query->where('i.id_carrera', $filtros['id_carrera']);
        }

        if ($reporte === 'lista-inscritos-multicarrera') {
            // Se seleccionan inscripciones de personas con más de una carrera activa
            // en la misma gestión; el subquery evita multiplicar las filas del informe.
            $query->whereExists(function (Builder $subquery): void {
                $subquery->selectRaw('1')
                    ->from('inscrito as otra')
                    ->whereColumn('otra.id_persona', 'i.id_persona')
                    ->whereColumn('otra.id_gestionacademica', 'i.id_gestionacademica')
                    ->where('otra.activo', true)
                    ->whereNull('otra.id_inscrito_reemplazo')
                    ->groupBy('otra.id_persona', 'otra.id_gestionacademica')
                    ->havingRaw('COUNT(DISTINCT otra.id_carrera) > 1');
            });
        }

        return $query;
    }

    /**
     * Consulta base de estudiantes inscritos vigentes. Se excluyen registros
     * inactivos y reemplazados para que los reportes reflejen solo inscripciones válidas.
     */
    private function consultaInscritos(): Builder
    {
        return DB::table('inscrito as i')
            ->join('personas as p', 'p.id_persona', '=', 'i.id_persona')
            ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
            ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
            ->where('i.activo', true)
            ->whereNull('i.id_inscrito_reemplazo')
            ->select(
                'i.codigo',
                'i.fecha',
                'p.ci',
                'p.primerapellido',
                'p.segundoapellido',
                'p.nombre',
                'c.nombre as carrera',
                'ga.gestion',
                'ga.periodo',
                'i.becado'
            );
    }

    /**
     * Obtiene la letra final de paralelo en códigos como "A" o "MAT-101-A".
     * Devuelve null si el código no termina en una letra reconocida.
     */
    private function nombreParalelo(string $codigo): ?string
    {
        return preg_match('/(?:^|-)([A-Z]{1,2})$/i', trim($codigo), $coincidencias)
            ? strtoupper($coincidencias[1])
            : null;
    }
}
