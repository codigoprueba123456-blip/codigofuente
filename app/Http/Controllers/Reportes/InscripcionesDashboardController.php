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
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        abort_unless(
            $request->user()->isAdministrator()
                || $request->user()->canAccess('reportes/inscripciones/lista-inscritos-carrera'),
            403
        );
        $user = $request->user();
        $canViewDashboard = $user->isAdministrator()
            || $user->canAccess('inscripciones/inscriptos');

        if (! $canViewDashboard) {
            return view('home');
        }

        if ($request->input('formato') === 'excel') {
            return $this->exportarExcelCompleto();
        }

        $filters = $request->validate([
            'id_gestionacademica' => ['nullable', 'integer', 'exists:gestionacademica,id_gestionacademica'],
            'id_carrera' => ['nullable', 'integer', 'exists:carreras,id_carrera'],
        ]);

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

        if (! $request->filled('id_gestionacademica') && $gestionActual) {
            $filters['id_gestionacademica'] = $gestionActual->id_gestionacademica;
        }

        $filasDetalle = $this->filasReporte($filters);
        $filasTabla = $this->filasConTotales($filasDetalle);
        $estadisticas = [
            'inscripciones' => $this->inscripcionesQuery($filters)->distinct()->count('i.id_inscrito'),
            'carreras' => $filasDetalle->pluck('id_carrera')->unique()->count(),
            'combinaciones' => $filasDetalle->count(),
        ];
        $gestiones = GestionAcademica::query()
            ->orderByDesc('gestion')
            ->orderByDesc('periodo')
            ->get();
        $carreras = Carrera::query()->orderBy('nombre')->get();

        return view('reportes.dashboard', compact(
            'filters',
            'gestionActual',
            'estadisticas',
            'filasTabla',
            'gestiones',
            'carreras'
        ));
    }

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

    private function filasReporte(array $filters): Collection
    {
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

        if ($registros->isEmpty()) {
            return collect();
        }

        $gruposPorEstudianteNivel = $registros->groupBy(fn (object $registro): string => implode(':', [
            $registro->id_persona,
            $registro->id_gestionacademica,
            $registro->id_carrera,
            $registro->nivel ?? 'sin-nivel',
        ]));
        $conteos = [];

        foreach ($gruposPorEstudianteNivel as $registrosNivel) {
            $asignaciones = $registrosNivel->filter(fn (object $registro): bool => $registro->id_grupo !== null);
            $registrosParaContar = $asignaciones->isNotEmpty()
                ? $asignaciones
                : $registrosNivel->take(1);

            foreach ($registrosParaContar as $registro) {
                $paralelo = $registro->id_grupo === null
                    ? 'Sin paralelo'
                    : $this->extraerParalelo((string) $registro->codigo_grupo);
                $turno = $registro->id_grupo === null
                    ? 'Sin turno'
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
                    ];
                }

                $conteos[$clave]['estudiantes'][$registro->id_persona] = mb_strtoupper(trim((string) $registro->sexo));
            }
        }

        return collect($conteos)
            ->map(function (array $fila): object {
                $sexos = collect($fila['estudiantes'])->values();
                $fila['inscritos_v'] = $sexos->filter(fn (string $sexo): bool => $sexo === 'M')->count();
                $fila['inscritos_m'] = $sexos->filter(fn (string $sexo): bool => $sexo === 'F')->count();
                $fila['inscritos_t'] = $sexos->count();
                $fila['estudiantes_ids'] = $fila['estudiantes'];
                unset($fila['estudiantes']);

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

    private function extraerParalelo(string $codigo): string
    {
        if (preg_match('/(?:^|-)([A-Z]{1,2})$/i', trim($codigo), $matches)) {
            return mb_strtoupper($matches[1]);
        }

        return 'Sin paralelo';
    }

    private function turnoEtiqueta(int|string|null $turno): string
    {
        return match ((int) $turno) {
            1 => 'Mañana',
            2 => 'Tarde',
            3 => 'Noche',
            default => 'Sin turno',
        };
    }

    private function exportarExcelCompleto()
    {
        $filas = $this->filasReporte([]);
        $gestiones = $filas->groupBy('id_gestionacademica');
        $libro = new Spreadsheet;
        $hojaInicial = $libro->getActiveSheet();
        $primeraHoja = true;

        foreach ($gestiones as $filasGestion) {
            $gestion = $filasGestion->first();
            $nombreHoja = $this->nombreHoja($gestion->gestion, $gestion->periodo);
            $hoja = $primeraHoja ? $hojaInicial : $libro->createSheet();
            $primeraHoja = false;
            $this->prepararHojaExcel($hoja, $nombreHoja, $gestion, $filasGestion);
        }

        if ($primeraHoja) {
            $hoja = $hojaInicial;
            $gestion = GestionAcademica::query()
                ->where('vigente', true)
                ->orderByDesc('gestion')
                ->orderByDesc('periodo')
                ->first();
            $this->prepararHojaExcel($hoja, 'Reporte', $gestion, collect());
        }

        return response()->streamDownload(
            fn () => (new Xlsx($libro))->save('php://output'),
            'reporte-estadistico-academico.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

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

    private function escribirFilaExcel($hoja, int $fila, array $valores): void
    {
        foreach ($valores as $indice => $valor) {
            $hoja->setCellValue(chr(ord('A') + $indice).$fila, $valor);
        }
    }

    private function nombreHoja(int|string $gestion, int|string $periodo): string
    {
        return substr('G'.$gestion.'-P'.$periodo, 0, 31);
    }
}
