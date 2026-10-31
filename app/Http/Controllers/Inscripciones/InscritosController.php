<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InscritosController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'id_gestionacademica' => ['nullable', 'integer', 'exists:gestionacademica,id_gestionacademica'],
            'id_carrera' => ['nullable', 'integer', 'exists:carreras,id_carrera'],
            'nivel' => ['nullable', 'integer', 'min:1'],
            'bth' => ['nullable', 'in:0,1'],
            'traspaso_convalidacion' => ['nullable', 'in:0,1'],
            'turno' => ['nullable', 'string', 'max:30'],
            'paralelo' => ['nullable', 'string', 'max:20'],
            'buscar_rapido' => ['nullable', 'string', 'max:100'],
        ]);

        $query = $this->inscritosQuery();

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
                    ->leftJoin('grupo as gr', 'gr.id_grupo', '=', 'im.id_grupo')
                    ->whereColumn('im.id_inscrito', 'i.id_inscrito');

                if (! empty($filters['turno'])) {
                    $assignment->where('gr.turno', $filters['turno']);
                }

                if (! empty($filters['paralelo'])) {
                    $assignment->where('gr.codigo', $filters['paralelo']);
                }
            });
        }

        if ($request->has('formato')) {
            $exportRequest = $request->validate([
                'formato' => ['required', 'in:excel,pdf'],
            ]);
            $inscritosData = (clone $query)->orderByDesc('i.fecha')->orderByDesc('i.id_inscrito')->get();

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
                    ])->all();

            return $this->exportarExcel(
                'Inscriptos_'.now()->format('Y-m-d_H-i').'.xlsx',
                ['Código', 'CI', 'Nombre', 'Carrera', 'Gestión', 'Período', 'Fecha', 'Becado'],
                $rows
            );
        }

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
                    ->groupBy('otra.id_persona', 'otra.id_gestionacademica')
                    ->havingRaw('COUNT(DISTINCT otra.id_carrera) > 1');
            })
            ->distinct()
            ->count('i.id_persona');

        $inscritos = $query->orderByDesc('i.fecha')
            ->orderByDesc('i.id_inscrito')
            ->paginate(10)
            ->withQueryString();

        $carrerasPorPersonaGestion = $this->carrerasPorPersonaGestion($inscritos->getCollection());
        foreach ($inscritos as $inscrito) {
            $clave = $this->clavePersonaGestion($inscrito->id_persona, $inscrito->id_gestionacademica);
            $inscrito->carreras_inscritas = $carrerasPorPersonaGestion[$clave] ?? collect();
        }

        $gestiones = GestionAcademica::orderByDesc('id_gestionacademica')->get();
        $carreras = Carrera::orderBy('nombre')->get();
        $niveles = DB::table('cursa')->whereNotNull('nivelinicio')
            ->select('nivelinicio')->distinct()->orderBy('nivelinicio')->pluck('nivelinicio');
        $turnos = DB::table('grupo')->whereNotNull('turno')->where('turno', '!=', '')
            ->select('turno')->distinct()->orderBy('turno')->pluck('turno');
        $paralelos = DB::table('grupo')->whereNotNull('codigo')->where('codigo', '!=', '')
            ->select('codigo')->distinct()->orderBy('codigo')->pluck('codigo');

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
        ));
    }

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
                'p.primerapellido',
                'p.segundoapellido',
                'p.nombre',
                'p.ci',
                'p.celular',
                'p.foto',
                'c.id_carrera',
                'c.nombre as carrera',
                'g.gestion',
                'g.periodo',
                'e.path_ci',
                'e.path_ci_reverso',
                'e.path_nacimiento',
                'e.path_diploma',
                'e.path_bth',
            )
            ->selectSub(
                DB::table('inscrito as otra')
                    ->selectRaw('COUNT(DISTINCT otra.id_carrera)')
                    ->whereColumn('otra.id_persona', 'i.id_persona')
                    ->whereColumn('otra.id_gestionacademica', 'i.id_gestionacademica'),
                'cantidad_carreras_gestion'
            );
    }

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
            ->orderBy('c.nombre')
            ->get(['i.id_persona', 'i.id_gestionacademica', 'c.nombre']);

        return $carreras
            ->groupBy(fn ($carrera): string => $this->clavePersonaGestion(
                $carrera->id_persona,
                $carrera->id_gestionacademica
            ))
            ->map(fn ($registros) => $registros->pluck('nombre')->unique()->values())
            ->all();
    }

    private function clavePersonaGestion(int|string $idPersona, int|string $idGestion): string
    {
        return $idPersona.':'.$idGestion;
    }
}
