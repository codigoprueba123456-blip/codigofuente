<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InscripcionesDashboardController extends Controller
{
    /**
     * Requiere una sesión autenticada para consultar el resumen estadístico.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Presenta el resumen de inscritos por carrera, nivel, paralelo y turno.
     * Los filtros se limitan a combinaciones de gestión, carrera, nivel, turno
     * y paralelo que existen en grupos activos.
     */
    public function index(Request $request)
    {
        abort_unless(
            $request->user()->isAdministrator()
                || $request->user()->canAccess('reportes/inscripciones/lista-inscritos-carrera'),
            403
        );
        $user = $request->user();
        // El endpoint puede estar habilitado por permisos de reportes, mientras que
        // la vista estadística requiere además permisos de consulta de inscriptos.
        $canViewDashboard = $user->isAdministrator()
            || $user->canAccess('inscripciones/inscriptos');

        if (! $canViewDashboard) {
            return view('home');
        }

        $filters = $request->validate([
            'id_gestionacademica' => ['nullable', 'integer', 'exists:gestionacademica,id_gestionacademica'],
            'id_carrera' => ['nullable', 'integer', 'exists:carreras,id_carrera'],
            'nivel' => ['nullable', 'string', 'max:20'],
            'turno' => ['nullable', 'string', 'max:30'],
            'paralelo' => ['nullable', 'string', 'max:30'],
        ]);

        $combinacionesGrupos = $this->combinacionesGrupos();
        $gestionIds = $combinacionesGrupos->pluck('id_gestionacademica')->unique()->values();
        $gestiones = GestionAcademica::query()
            ->whereIn('id_gestionacademica', $gestionIds)
            ->orderByDesc('gestion')
            ->orderByDesc('periodo')
            ->get();

        // Prioriza una gestión activa y vigente; si no existe, usa primero una activa
        // y finalmente la más reciente registrada para mantener un filtro inicial útil.
        $gestionActual = GestionAcademica::query()
            ->where('activo', true)
            ->where('vigente', true)
            ->orderByDesc('gestion')
            ->orderByDesc('periodo')
            ->first();

        if (! $gestionActual) {
            $gestionActual = GestionAcademica::query()
                ->where('activo', true)
                ->orderByDesc('gestion')
                ->orderByDesc('periodo')
                ->first();
        }
        if (! $gestionActual) {
            $gestionActual = GestionAcademica::query()
                ->orderByDesc('gestion')
                ->orderByDesc('periodo')
                ->first();
        }

        if (! $request->filled('id_gestionacademica')) {
            $gestionInicial = $gestionActual && $gestionIds->contains($gestionActual->id_gestionacademica)
                ? $gestionActual->id_gestionacademica
                : $gestiones->first()?->id_gestionacademica;

            if ($gestionInicial) {
                $filters['id_gestionacademica'] = $gestionInicial;
            }
        }

        $combinacionesSeleccionadas = $combinacionesGrupos
            ->when(
                ! empty($filters['id_gestionacademica']),
                fn (Collection $grupos) => $grupos->where(
                    'id_gestionacademica',
                    (int) $filters['id_gestionacademica']
                )
            );
        $carreraIds = $combinacionesSeleccionadas->pluck('id_carrera')->unique()->values();
        $carreras = Carrera::query()
            ->whereIn('id_carrera', $carreraIds)
            ->orderBy('nombre')
            ->get();

        if (
            ! empty($filters['id_carrera'])
            && ! $carreraIds->contains((int) $filters['id_carrera'])
        ) {
            unset($filters['id_carrera']);
        }

        $combinacionesSeleccionadas = $combinacionesSeleccionadas
            ->when(
                ! empty($filters['id_carrera']),
                fn (Collection $grupos) => $grupos->where(
                    'id_carrera',
                    (int) $filters['id_carrera']
                )
            );
        $niveles = $combinacionesSeleccionadas
            ->filter(fn (object $grupo): bool => $grupo->nivel !== null)
            ->map(fn (object $grupo): array => [
                'valor' => $this->claveNivel($grupo->regimen, $grupo->nivel),
                'etiqueta' => Carrera::etiquetaNivel($grupo->regimen, $grupo->nivel),
                'numero' => (int) $grupo->nivel,
                'regimen' => Carrera::regimenEfectivo($grupo->regimen) ?? 0,
            ])
            ->unique('valor')
            ->sortBy(fn (array $nivel): string => sprintf('%02d:%02d', $nivel['numero'], $nivel['regimen']))
            ->values();

        if (
            ! empty($filters['nivel'])
            && ! $niveles->contains(fn (array $nivel): bool => $nivel['valor'] === $filters['nivel'])
        ) {
            unset($filters['nivel']);
        }

        $combinacionesTurnos = $combinacionesSeleccionadas->when(
            ! empty($filters['nivel']),
            fn (Collection $grupos) => $grupos->filter(
                fn (object $grupo): bool => $this->claveNivel($grupo->regimen, $grupo->nivel) === $filters['nivel']
            )
        );
        $turnos = $combinacionesTurnos
            ->sortBy(fn (object $grupo): int => (int) ($grupo->turno ?? PHP_INT_MAX))
            ->unique('turno_etiqueta')
            ->map(fn (object $grupo): string => $grupo->turno_etiqueta)
            ->values();

        if (
            ! empty($filters['turno'])
            && ! $turnos->contains($filters['turno'])
        ) {
            unset($filters['turno']);
        }

        $combinacionesParalelos = $combinacionesTurnos->when(
            ! empty($filters['turno']),
            fn (Collection $grupos) => $grupos->where('turno_etiqueta', $filters['turno'])
        );
        $paralelos = $combinacionesParalelos
            ->pluck('paralelo')
            ->unique()
            ->sort()
            ->values();

        if (
            ! empty($filters['paralelo'])
            && ! $paralelos->contains($filters['paralelo'])
        ) {
            unset($filters['paralelo']);
        }

        // El detalle alimenta tanto la tabla (con subtotales) como la hoja Excel.
        $filtrosBase = array_intersect_key($filters, array_flip(['id_gestionacademica', 'id_carrera']));
        $filasDetalle = $this->filasReporte($filtrosBase)
            ->filter(function (object $fila) use ($filters): bool {
                return (empty($filters['nivel'])
                        || $this->claveNivel($fila->regimen, $fila->nivel_numero) === $filters['nivel'])
                    && (empty($filters['turno']) || $fila->turno === $filters['turno'])
                    && (empty($filters['paralelo']) || $fila->paralelo === $filters['paralelo']);
            })
            ->values();
        $filasTabla = $this->filasConTotales($filasDetalle);
        $inscripcionesReporte = $filasDetalle
            ->flatMap(fn (object $fila): array => array_keys($fila->inscripciones_ids))
            ->unique();
        $estadisticas = [
            'inscripciones' => $inscripcionesReporte->count(),
            'carreras' => $filasDetalle->pluck('id_carrera')->unique()->count(),
            'combinaciones' => $filasDetalle->count(),
        ];

        // La descarga usa el mismo detalle filtrado que la vista, sin las filas
        // visuales de subtotal y total que se agregan para presentación.
        if ($request->input('formato') === 'excel') {
            $gestionExportar = isset($filters['id_gestionacademica'])
                ? GestionAcademica::find($filters['id_gestionacademica'])
                : null;

            return $this->exportarExcelFiltrado($filasDetalle, $gestionExportar);
        }

        return view('reportes.dashboard', compact(
            'filters',
            'gestionActual',
            'estadisticas',
            'filasTabla',
            'gestiones',
            'carreras',
            'niveles',
            'turnos',
            'paralelos'
        ));
    }

    /**
     * Devuelve solo combinaciones ofrecidas por grupos y materias activos.
     */
    private function combinacionesGrupos(): Collection
    {
        return DB::table('grupo as gr')
            ->join('materia as m', 'm.id_materia', '=', 'gr.id_materia')
            ->join('carreras as c', 'c.id_carrera', '=', 'm.id_carrera')
            ->where('gr.activo', true)
            ->where('m.activo', true)
            ->select(
                'gr.id_gestionacademica',
                'gr.codigo',
                'gr.turno',
                'm.nivel',
                'c.id_carrera',
                'c.regimen'
            )
            ->get()
            ->map(function (object $grupo): object {
                $grupo->paralelo = $this->extraerParalelo((string) $grupo->codigo);
                $grupo->turno_etiqueta = $this->turnoEtiqueta($grupo->turno);

                return $grupo;
            });
    }

    /**
     * Identifica el nivel junto con su régimen para distinguir años de semestres.
     */
    private function claveNivel(int|string|null $regimen, int|string|null $nivel): string
    {
        return (Carrera::regimenEfectivo($regimen) ?? 0).':'.(int) $nivel;
    }

    /**
     * Consulta base de inscripciones vigentes. Los filtros son opcionales para
     * permitir el resumen general; las inscripciones inactivas o reemplazadas
     * no se consideran en el dashboard.
     */
    private function inscripcionesQuery(array $filters): Builder
    {
        return DB::table('inscrito as i')
            ->when(
                ! empty($filters['id_gestionacademica']),
                fn (Builder $query) => $query->where('i.id_gestionacademica', $filters['id_gestionacademica'])
            )
            ->when(
                ! empty($filters['id_carrera']),
                fn (Builder $query) => $query->where('i.id_carrera', $filters['id_carrera'])
            )
            ->where('i.activo', true)
            ->whereNull('i.id_inscrito_reemplazo');
    }

    /**
     * Agrupa a las personas inscritas por gestión, carrera, nivel, paralelo y turno.
     * Las asignaciones a materias/grupos aportan el curso y grupo; cuando no hay
     * asignaciones disponibles, se recurre a los datos guardados en la inscripción.
     * Una persona se cuenta una sola vez dentro de cada combinación, aunque tenga
     * varias materias asignadas en el mismo nivel y grupo.
     */
    private function filasReporte(array $filters): Collection
    {
        // Las relaciones con cursa, inscrito_materia, materia y grupo se dejan como
        // left joins para no perder estudiantes que aún carezcan de esos detalles.
        $registros = $this->inscripcionesQuery($filters)
            ->join('personas as p', 'p.id_persona', '=', 'i.id_persona')
            ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
            ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
            ->leftJoin('cursa as cu', function ($join): void {
                $join->on('cu.id_persona', '=', 'i.id_persona')
                    ->on('cu.id_carrera', '=', 'i.id_carrera')
                    ->on('cu.id_gestionacademica', '=', 'i.id_gestionacademica');
            })
            ->leftJoin('inscrito_materia as im', 'im.id_inscrito', '=', 'i.id_inscrito')
            ->leftJoin('materia as m', function ($join): void {
                $join->on('m.id_materia', '=', 'im.id_materia')
                    ->on('m.id_carrera', '=', 'i.id_carrera');
            })
            ->leftJoin('grupo as gr', function ($join): void {
                $join->on('gr.id_grupo', '=', 'im.id_grupo')
                    ->on('gr.id_materia', '=', 'm.id_materia')
                    ->on('gr.id_gestionacademica', '=', 'i.id_gestionacademica');
            })
            ->select(
                'i.id_inscrito',
                'i.id_persona',
                'i.id_gestionacademica',
                'i.turno as turno_inscrito',
                'i.paralelo as paralelo_inscrito',
                'p.sexo',
                'c.id_carrera',
                'c.nombre as carrera',
                'c.resolucionministerial1',
                'c.resolucionministerial2',
                'c.resolucionministerial3',
                'c.regimen',
                'ga.gestion',
                'ga.periodo',
                'gr.id_grupo',
                'gr.codigo as codigo_grupo',
                'gr.turno'
            )
            ->selectRaw('COALESCE(m.nivel, cu.nivelinicio) as nivel')
            ->when(
                ! empty($filters['id_gestionacademica']),
                fn (Builder $query) => $query->where('i.id_gestionacademica', $filters['id_gestionacademica'])
            )
            ->when(
                ! empty($filters['id_carrera']),
                fn (Builder $query) => $query->where('i.id_carrera', $filters['id_carrera'])
            )
            ->orderBy('ga.gestion')
            ->orderBy('ga.periodo')
            ->orderBy('c.nombre')
            ->orderBy('nivel')
            ->get()
            ->map(function (object $registro): object {
                $registro->nombre_carrera_orden = $registro->carrera;
                $registro->carrera = Carrera::formatearNombreConResolucion(
                    $registro->carrera,
                    $registro->resolucionministerial1,
                    $registro->resolucionministerial2,
                    $registro->resolucionministerial3
                );

                return $registro;
            });

        // Se reúnen primero los registros por estudiante y nivel para evitar que
        // las múltiples materias de una inscripción inflen los conteos.
        $conteos = [];

        if ($registros->isNotEmpty()) {
            $gruposPorEstudianteNivel = $registros->groupBy(fn (object $registro): string => implode(':', [
                $registro->id_persona,
                $registro->id_gestionacademica,
                $registro->id_carrera,
                $registro->nivel ?? 'sin-nivel',
            ]));

            foreach ($gruposPorEstudianteNivel as $registrosNivel) {
                $asignaciones = $registrosNivel->filter(fn (object $registro): bool => $registro->id_grupo !== null);
                // Si hay grupos asignados, se contabiliza cada combinación encontrada;
                // si no, se conserva un solo registro para la información de inscripción.
                $registrosParaContar = $asignaciones->isNotEmpty()
                    ? $asignaciones
                    : $registrosNivel->take(1);

                foreach ($registrosParaContar as $registro) {
                    $paraleloRegistrado = trim((string) ($registro->paralelo_inscrito ?? ''));
                    $paralelo = $registro->id_grupo === null
                        ? ($paraleloRegistrado === '' ? 'Sin paralelo' : $this->extraerParalelo($paraleloRegistrado))
                        : $this->extraerParalelo((string) $registro->codigo_grupo);
                    $turno = $registro->id_grupo === null
                        ? $this->turnoEtiqueta($registro->turno_inscrito)
                        : $this->turnoEtiqueta($registro->turno);
                    $clave = implode('|', [
                        $registro->id_gestionacademica,
                        $registro->id_carrera,
                        $registro->nivel ?? 'sin-nivel',
                        $paralelo,
                        $turno,
                    ]);

                    if (! isset($conteos[$clave])) {
                        $conteos[$clave] = [
                            'id_gestionacademica' => (int) $registro->id_gestionacademica,
                            'gestion' => $registro->gestion,
                            'periodo' => $registro->periodo,
                            'id_carrera' => (int) $registro->id_carrera,
                            'carrera' => $registro->carrera,
                            'nombre_carrera_orden' => $registro->nombre_carrera_orden,
                            'regimen' => $registro->regimen,
                            'nivel_numero' => $registro->nivel,
                            'nivel' => $registro->nivel
                                ? Carrera::etiquetaNivel($registro->regimen, $registro->nivel)
                                : 'Sin curso',
                            'paralelo' => $paralelo,
                            'turno' => $turno,
                            'estudiantes' => [],
                            'inscripciones' => [],
                        ];
                    }

                    $conteos[$clave]['estudiantes'][$registro->id_persona] = mb_strtoupper(trim((string) $registro->sexo));
                    $conteos[$clave]['inscripciones'][$registro->id_inscrito] = true;
                }
            }
        }

        // También se incluyen grupos activos sin estudiantes para que el reporte
        // muestre la oferta existente con valores de inscritos iguales a cero.
        $this->agregarCombinacionesSinInscritos($conteos, $filters);

        return collect($conteos)
            ->map(function (array $fila): object {
                $sexos = collect($fila['estudiantes'])->values();
                $fila['inscritos_v'] = $sexos->filter(fn (string $sexo): bool => $sexo === 'M')->count();
                $fila['inscritos_m'] = $sexos->filter(fn (string $sexo): bool => $sexo === 'F')->count();
                $fila['inscritos_t'] = $sexos->count();
                $fila['estudiantes_ids'] = $fila['estudiantes'];
                $fila['inscripciones_ids'] = $fila['inscripciones'];
                unset($fila['estudiantes'], $fila['inscripciones']);

                return (object) $fila;
            })
            ->sortBy(fn (object $fila): string => implode('|', [
                mb_strtolower($fila->nombre_carrera_orden),
                sprintf('%02d', (int) $fila->nivel_numero),
                mb_strtolower($fila->paralelo),
                mb_strtolower($fila->turno),
            ]))
            ->map(function (object $fila): object {
                unset($fila->nombre_carrera_orden);

                return $fila;
            })
            ->values();
    }

    /**
     * Añade las combinaciones de carrera, nivel, paralelo y turno disponibles
     * en grupos y materias activos que todavía no tienen inscritos.
     */
    private function agregarCombinacionesSinInscritos(array &$conteos, array $filters): void
    {
        // Se aplican los mismos filtros del tablero para no introducir grupos de
        // otras gestiones o carreras en el conjunto reportado.
        $grupos = DB::table('grupo as gr')
            ->join('materia as m', 'm.id_materia', '=', 'gr.id_materia')
            ->join('carreras as c', 'c.id_carrera', '=', 'm.id_carrera')
            ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'gr.id_gestionacademica')
            ->where('gr.activo', true)
            ->where('m.activo', true)
            ->when(
                ! empty($filters['id_gestionacademica']),
                fn (Builder $query) => $query->where('gr.id_gestionacademica', $filters['id_gestionacademica'])
            )
            ->when(
                ! empty($filters['id_carrera']),
                fn (Builder $query) => $query->where('c.id_carrera', $filters['id_carrera'])
            )
            ->select(
                'gr.id_gestionacademica',
                'gr.codigo as codigo_grupo',
                'gr.turno',
                'm.nivel',
                'c.id_carrera',
                'c.nombre as nombre_carrera',
                'c.resolucionministerial1',
                'c.resolucionministerial2',
                'c.resolucionministerial3',
                'c.regimen',
                'ga.gestion',
                'ga.periodo'
            )
            ->get();

        foreach ($grupos as $grupo) {
            $nivel = $grupo->nivel;
            $paralelo = $this->extraerParalelo((string) $grupo->codigo_grupo);
            $turno = $this->turnoEtiqueta($grupo->turno);
            $clave = implode('|', [
                $grupo->id_gestionacademica,
                $grupo->id_carrera,
                $nivel ?? 'sin-nivel',
                $paralelo,
                $turno,
            ]);

            if (isset($conteos[$clave])) {
                continue;
            }

            $nombreCarrera = (string) $grupo->nombre_carrera;
            $conteos[$clave] = [
                'id_gestionacademica' => (int) $grupo->id_gestionacademica,
                'gestion' => $grupo->gestion,
                'periodo' => $grupo->periodo,
                'id_carrera' => (int) $grupo->id_carrera,
                'carrera' => Carrera::formatearNombreConResolucion(
                    $nombreCarrera,
                    $grupo->resolucionministerial1,
                    $grupo->resolucionministerial2,
                    $grupo->resolucionministerial3
                ),
                'nombre_carrera_orden' => $nombreCarrera,
                'regimen' => $grupo->regimen,
                'nivel_numero' => $nivel,
                'nivel' => $nivel
                    ? Carrera::etiquetaNivel($grupo->regimen, $nivel)
                    : 'Sin curso',
                'paralelo' => $paralelo,
                'turno' => $turno,
                'estudiantes' => [],
                'inscripciones' => [],
            ];
        }
    }

    /**
     * Intercala filas de detalle con subtotales por carrera y el total general.
     * Los conteos de estudiantes se deduplican por persona al sumar filas.
     */
    private function filasConTotales(Collection $filasDetalle): Collection
    {
        $filas = collect();
        $totalesGenerales = ['v' => 0, 'm' => 0, 't' => 0];

        foreach ($filasDetalle->groupBy('id_carrera') as $filasCarrera) {
            foreach ($filasCarrera as $fila) {
                $fila->tipo_fila = 'detalle';
                $filas->push($fila);
            }

            $totalesCarrera = $this->sumarInscritos($filasCarrera);
            foreach ($totalesCarrera as $sexo => $total) {
                $totalesGenerales[$sexo] += $total;
            }

            $filas->push((object) [
                'tipo_fila' => 'subtotal',
                'carrera' => 'Total '.$filasCarrera->first()->carrera,
                'nivel' => '',
                'paralelo' => '',
                'turno' => '',
                'inscritos_v' => $totalesCarrera['v'],
                'inscritos_m' => $totalesCarrera['m'],
                'inscritos_t' => $totalesCarrera['t'],
            ]);
        }

        if ($filasDetalle->isNotEmpty()) {
            $filas->push((object) [
                'tipo_fila' => 'total',
                'carrera' => 'TOTAL INSCRITOS',
                'nivel' => '',
                'paralelo' => '',
                'turno' => '',
                'inscritos_v' => $totalesGenerales['v'],
                'inscritos_m' => $totalesGenerales['m'],
                'inscritos_t' => $totalesGenerales['t'],
            ]);
        }

        return $filas;
    }

    /**
     * Suma personas únicas por sexo dentro del conjunto recibido, evitando
     * contar dos veces a quien figure en más de una combinación del mismo nivel.
     */
    private function sumarInscritos(Collection $filas): array
    {
        $estudiantes = [];
        foreach ($filas as $fila) {
            foreach ($fila->estudiantes_ids ?? [] as $idPersona => $sexo) {
                $estudiantes[$idPersona] = $sexo;
            }
        }

        $sexos = collect($estudiantes);

        return [
            'v' => $sexos->filter(fn (string $sexo): bool => $sexo === 'M')->count(),
            'm' => $sexos->filter(fn (string $sexo): bool => $sexo === 'F')->count(),
            't' => $sexos->count(),
        ];
    }

    /**
     * Extrae el identificador final del paralelo desde el código del grupo;
     * los códigos que no terminan en letras se presentan como "Sin paralelo".
     */
    private function extraerParalelo(string $codigo): string
    {
        if (preg_match('/(?:^|-)([A-Z]{1,2})$/i', trim($codigo), $matches)) {
            return mb_strtoupper($matches[1]);
        }

        return 'Sin paralelo';
    }

    /**
     * Convierte el código numérico almacenado a su etiqueta visible.
     */
    private function turnoEtiqueta(int|string|null $turno): string
    {
        return match ((int) $turno) {
            1 => 'Mañana',
            2 => 'Tarde',
            3 => 'Noche',
            default => 'Sin turno',
        };
    }

    /**
     * Transmite la versión Excel del detalle del dashboard y conserva el filtro
     * de gestión en el encabezado del archivo cuando se seleccionó una.
     */
    private function exportarExcelFiltrado(Collection $filas, ?GestionAcademica $gestion)
    {
        $libro = new Spreadsheet;
        $this->prepararHojaExcel(
            $libro->getActiveSheet(),
            $gestion ? $this->nombreHoja($gestion->gestion, $gestion->periodo) : 'Reporte',
            $gestion,
            $filas
        );

        return response()->streamDownload(
            fn () => (new Xlsx($libro))->save('php://output'),
            'reporte-estadistico-academico.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Da formato a la tabla de exportación, agrupa visualmente las filas por
     * carrera y agrega los subtotales y el total general. Los grupos de columnas
     * académicas adicionales permanecen vacíos porque el sistema no registra
     * los resultados de aprobación, reprobación, abandono ni efectividad.
     */
    private function prepararHojaExcel($hoja, string $nombre, ?object $gestion, Collection $filas): void
    {
        $hoja->setTitle($nombre);
        $ultimaColumna = 'S';
        $hoja->mergeCells('A1:'.$ultimaColumna.'1');
        $hoja->setCellValue('A1', 'REPORTE ESTADÍSTICO ACADÉMICO POR CARRERA');
        $hoja->mergeCells('A2:'.$ultimaColumna.'2');
        $hoja->setCellValue(
            'A2',
            $gestion
                ? 'GESTIÓN '.$gestion->gestion.' · PERIODO '.$gestion->periodo
                : 'RESUMEN GENERAL'
        );

        foreach (['A' => 'CARRERA', 'B' => 'CURSO', 'C' => 'PARALELO', 'D' => 'TURNO'] as $columna => $titulo) {
            $hoja->mergeCells($columna.'4:'.$columna.'5');
            $hoja->setCellValue($columna.'4', $titulo);
        }

        foreach ([
            ['INSCRITOS', 'E', 'G'],
            ['APROBADOS', 'H', 'J'],
            ['REPROBADOS', 'K', 'M'],
            ['ABANDONO', 'N', 'P'],
            ['EFECTIVOS', 'Q', 'S'],
        ] as [$titulo, $inicio, $fin]) {
            $hoja->mergeCells($inicio.'4:'.$fin.'4');
            $hoja->setCellValue($inicio.'4', $titulo);
            foreach (['V', 'M', 'T'] as $indice => $sexo) {
                $hoja->setCellValue(chr(ord($inicio) + $indice).'5', $sexo);
            }
        }

        $filaActual = 6;
        $totalesGenerales = ['v' => 0, 'm' => 0, 't' => 0];
        foreach ($filas->groupBy('id_carrera') as $filasCarrera) {
            $filaInicioCarrera = $filaActual;
            foreach ($filasCarrera as $fila) {
                $this->escribirFilaExcel($hoja, $filaActual, [
                    $fila->carrera,
                    $fila->nivel,
                    $fila->paralelo,
                    $fila->turno,
                    $fila->inscritos_v,
                    $fila->inscritos_m,
                    $fila->inscritos_t,
                    null, null, null,
                    null, null, null,
                    null, null, null,
                    null, null, null,
                ]);
                $filaActual++;
            }

            $filaFinCarrera = $filaActual - 1;
            if ($filaFinCarrera > $filaInicioCarrera) {
                $hoja->mergeCells('A'.$filaInicioCarrera.':A'.$filaFinCarrera);
                $hoja->getStyle('A'.$filaInicioCarrera)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }

            $totalesCarrera = $this->sumarInscritos($filasCarrera);
            foreach ($totalesCarrera as $sexo => $total) {
                $totalesGenerales[$sexo] += $total;
            }

            $this->escribirFilaExcel($hoja, $filaActual, [
                'TOTAL '.$filasCarrera->first()->carrera,
                null, null, null,
                $totalesCarrera['v'],
                $totalesCarrera['m'],
                $totalesCarrera['t'],
                null, null, null,
                null, null, null,
                null, null, null,
                null, null, null,
            ]);
            $hoja->getStyle('A'.$filaActual.':S'.$filaActual)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAF7');
            $hoja->getStyle('A'.$filaActual.':S'.$filaActual)->getFont()->setBold(true);
            $filaActual++;
        }

        if ($filas->isNotEmpty()) {
            $this->escribirFilaExcel($hoja, $filaActual, [
                'TOTAL INSCRITOS',
                null, null, null,
                $totalesGenerales['v'],
                $totalesGenerales['m'],
                $totalesGenerales['t'],
                null, null, null,
                null, null, null,
                null, null, null,
                null, null, null,
            ]);
            $hoja->getStyle('A'.$filaActual.':S'.$filaActual)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('B4C7E7');
            $hoja->getStyle('A'.$filaActual.':S'.$filaActual)->getFont()->setBold(true);
        }

        $ultimaFila = max(5, $filaActual);
        $hoja->getStyle('A1:S1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
        $hoja->getStyle('A1:S1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('174B37');
        $hoja->getStyle('A1:S1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $hoja->getStyle('A2:S2')->getFont()->setBold(true);
        $hoja->getStyle('A2:S2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $hoja->getStyle('A4:S5')->getFont()->setBold(true);
        $hoja->getStyle('A4:S5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAF7');
        $hoja->getStyle('A4:S5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $hoja->getStyle('A4:S'.$ultimaFila)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $hoja->getStyle('A6:S'.$ultimaFila)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $hoja->getStyle('E6:S'.$ultimaFila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $hoja->getStyle('A6:S'.$ultimaFila)->getAlignment()->setWrapText(true);
        $hoja->freezePane('E6');
        $hoja->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $hoja->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        foreach (range('A', 'S') as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }
    }

    /**
     * Escribe los valores de una fila consecutivamente desde la columna A.
     */
    private function escribirFilaExcel($hoja, int $fila, array $valores): void
    {
        foreach ($valores as $indice => $valor) {
            $hoja->setCellValue(chr(ord('A') + $indice).$fila, $valor);
        }
    }

    /**
     * Genera un título de hoja legible dentro del límite admitido por Excel.
     */
    private function nombreHoja(int|string $gestion, int|string $periodo): string
    {
        return substr('G'.$gestion.'-P'.$periodo, 0, 31);
    }
}
