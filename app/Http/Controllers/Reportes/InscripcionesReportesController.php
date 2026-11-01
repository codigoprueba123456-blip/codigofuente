<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InscripcionesReportesController extends Controller
{
    public function index(Request $request, string $reporte)
    {
        $reportes = $this->reportes();
        abort_unless(isset($reportes[$reporte]), 404);

        $configuracion = $reportes[$reporte];
        $formato = $request->input('formato');
        $generado = $request->boolean('generar') || in_array($formato, ['excel', 'pdf'], true);
        $filtros = $this->validarFiltros($request, $configuracion, $generado);
        $opciones = $this->opciones($request);
        $filas = collect();

        if ($generado) {
            $aliasPersona = $reporte === 'lista-postulantes' ? 'po' : 'p';
            $filas = $this->consulta($reporte, $filtros)
                ->orderBy($aliasPersona.'.primerapellido')
                ->orderBy($aliasPersona.'.segundoapellido')
                ->orderBy($aliasPersona.'.nombre')
                ->get()
                ->values()
                ->map(function (object $fila, int $indice): object {
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

                    return $fila;
                });
        }

        if ($formato === 'pdf') {
            return Pdf::loadView('reportes.inscripciones.pdf', compact(
                'configuracion',
                'filas',
                'filtros'
            ))->setPaper('a4', 'landscape')
                ->download('Reporte_'.$reporte.'_'.now()->format('Y-m-d_H-i').'.pdf');
        }

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

    private function reportes(): array
    {
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

    private function validarFiltros(Request $request, array $configuracion, bool $generado): array
    {
        $rules = [
            'generar' => ['nullable', 'boolean'],
            'formato' => ['nullable', Rule::in(['excel', 'pdf'])],
        ];

        foreach ($configuracion['filtros'] as $filtro) {
            $required = $generado ? 'required' : 'nullable';
            $rules[$filtro['name']] = match ($filtro['name']) {
                'id_gestionacademica' => [$required, 'integer', 'exists:gestionacademica,id_gestionacademica'],
                'id_carrera' => [$required, 'integer', 'exists:carreras,id_carrera'],
                'nivel' => [
                    $required,
                    'integer',
                    'min:1',
                    Rule::exists('materia', 'nivel')->where('id_carrera', $request->input('id_carrera')),
                ],
                'turno' => [$required, 'integer', Rule::in([1, 2, 3])],
                'paralelo' => [$required, 'string', 'max:255', Rule::exists('grupo', 'codigo')],
                'id_grupo' => [$required, 'integer', 'exists:grupo,id_grupo'],
                'gestion' => [
                    $required,
                    'integer',
                    Rule::exists('gestionacademica', 'gestion')
                        ->where('periodo', $request->input('periodo')),
                ],
                'periodo' => [
                    $required,
                    'integer',
                    Rule::exists('gestionacademica', 'periodo')
                        ->where('gestion', $request->input('gestion')),
                ],
                default => [$required],
            };
        }

        return $request->validate($rules);
    }

    private function opciones(Request $request): array
    {
        $gestiones = DB::table('gestionacademica')
            ->orderByDesc('gestion')
            ->orderByDesc('periodo')
            ->get(['id_gestionacademica', 'gestion', 'periodo'])
            ->map(fn (object $gestion): object => (object) [
                'valor' => $gestion->id_gestionacademica,
                'etiqueta' => 'Gestión '.$gestion->gestion.' - Periodo '.$gestion->periodo,
            ]);
        $carreras = DB::table('carreras')
            ->orderBy('nombre')
            ->get(['id_carrera', 'nombre'])
            ->map(fn (object $carrera): object => (object) [
                'valor' => $carrera->id_carrera,
                'etiqueta' => $carrera->nombre,
            ]);
        $materias = DB::table('materia')
            ->when($request->filled('id_carrera'), fn (Builder $query) => $query->where('id_carrera', $request->input('id_carrera')));
        $niveles = (clone $materias)->whereNotNull('nivel')->distinct()->orderBy('nivel')->pluck('nivel')
            ->map(fn ($nivel): object => (object) ['valor' => $nivel, 'etiqueta' => 'Nivel '.$nivel]);
        $grupos = DB::table('grupo as gr')
            ->join('materia as m', 'm.id_materia', '=', 'gr.id_materia')
            ->when($request->filled('id_gestionacademica'), fn (Builder $query) => $query->where('gr.id_gestionacademica', $request->input('id_gestionacademica')))
            ->when($request->filled('id_carrera'), fn (Builder $query) => $query->where('m.id_carrera', $request->input('id_carrera')))
            ->when($request->filled('nivel'), fn (Builder $query) => $query->where('m.nivel', $request->input('nivel')))
            ->when($request->filled('turno'), fn (Builder $query) => $query->where('gr.turno', $request->input('turno')))
            ->orderBy('gr.codigo')
            ->orderBy('m.nombre')
            ->get(['gr.id_grupo', 'gr.codigo', 'gr.turno', 'm.nivel', 'm.nombre as materia']);
        $grupos = $grupos->map(function (object $grupo): object {
            $turno = [1 => 'Mañana', 2 => 'Tarde', 3 => 'Noche'][$grupo->turno] ?? 'Sin turno';
            $grupo->valor = $grupo->id_grupo;
            $grupo->etiqueta = $grupo->codigo.' - '.$grupo->materia.' (Nivel '.$grupo->nivel.', '.$turno.')';

            return $grupo;
        });

        $turnos = DB::table('grupo')
            ->whereNotNull('turno')
            ->distinct()
            ->orderBy('turno')
            ->pluck('turno')
            ->map(fn ($turno): object => (object) [
                'valor' => $turno,
                'etiqueta' => [1 => 'Mañana', 2 => 'Tarde', 3 => 'Noche'][$turno] ?? $turno,
            ]);

        $paralelos = DB::table('grupo as gr')
            ->join('materia as m', 'm.id_materia', '=', 'gr.id_materia')
            ->when($request->filled('id_gestionacademica'), fn (Builder $query) => $query->where('gr.id_gestionacademica', $request->input('id_gestionacademica')))
            ->when($request->filled('id_carrera'), fn (Builder $query) => $query->where('m.id_carrera', $request->input('id_carrera')))
            ->when($request->filled('nivel'), fn (Builder $query) => $query->where('m.nivel', $request->input('nivel')))
            ->when($request->filled('turno'), fn (Builder $query) => $query->where('gr.turno', $request->input('turno')))
            ->whereNotNull('gr.codigo')
            ->distinct()
            ->orderBy('gr.codigo')
            ->pluck('gr.codigo')
            ->map(fn ($paralelo): object => (object) ['valor' => $paralelo, 'etiqueta' => $paralelo]);

        $gestionesNumericas = DB::table('gestionacademica')
            ->select('gestion')
            ->distinct()
            ->orderByDesc('gestion')
            ->pluck('gestion')
            ->map(fn ($gestion): object => (object) ['valor' => $gestion, 'etiqueta' => $gestion]);
        $periodos = DB::table('gestionacademica')
            ->when($request->filled('gestion'), fn (Builder $query) => $query->where('gestion', $request->input('gestion')))
            ->select('periodo')
            ->distinct()
            ->orderBy('periodo')
            ->pluck('periodo')
            ->map(fn ($periodo): object => (object) ['valor' => $periodo, 'etiqueta' => $periodo]);

        return compact('gestiones', 'carreras', 'niveles', 'turnos', 'paralelos', 'grupos', 'gestionesNumericas', 'periodos');
    }

    private function consulta(string $reporte, array $filtros): Builder
    {
        if ($reporte === 'lista-postulantes') {
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
                ->select('m.nivel', 'gr.turno', 'gr.codigo as paralelo', 'm.nombre as materia');

            if ($reporte === 'lista-inscritos-paralelo') {
                return $query->where('gr.turno', $filtros['turno'])
                    ->where('gr.codigo', $filtros['paralelo']);
            }

            return $query->where('gr.id_grupo', $filtros['id_grupo']);
        }

        $query = $this->consultaInscritos();

        if ($reporte === 'libro-inscripciones') {
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
            $query->whereExists(function (Builder $subquery): void {
                $subquery->selectRaw('1')
                    ->from('inscrito as otra')
                    ->whereColumn('otra.id_persona', 'i.id_persona')
                    ->whereColumn('otra.id_gestionacademica', 'i.id_gestionacademica')
                    ->groupBy('otra.id_persona', 'otra.id_gestionacademica')
                    ->havingRaw('COUNT(DISTINCT otra.id_carrera) > 1');
            });
        }

        return $query;
    }

    private function consultaInscritos(): Builder
    {
        return DB::table('inscrito as i')
            ->join('personas as p', 'p.id_persona', '=', 'i.id_persona')
            ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
            ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
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
}
