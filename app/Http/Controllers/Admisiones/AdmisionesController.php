<?php

namespace App\Http\Controllers\Admisiones;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Postulante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmisionesController extends Controller
{
    public function excel(Request $request)
    {
        return redirect()->route('admisiones.postulantes.index', array_merge(
            $request->query(),
            ['exportar' => 'filtrado', 'formato' => 'excel']
        ));
    }

    public function pdf(Request $request)
    {
        return redirect()->route('admisiones.postulantes.index', array_merge(
            $request->query(),
            ['exportar' => 'filtrado', 'formato' => 'pdf']
        ));
    }

    public function index(Request $request)
    {
        $export = $request->validate([
            'exportar' => ['nullable', 'in:filtrado,todo'],
            'formato' => ['nullable', 'in:excel,pdf'],
            'estado' => ['nullable', 'in:pendiente,inscrito'],
            'categoria' => ['nullable', 'in:multiples,unica'],
        ]);
        $query = Postulante::with(['carrera', 'gestionAcademica'])
            ->withExists('inscripcionOrigen');

        // -------------------------------------------------------------
        // 1. BARRA DE BÚSQUEDA RÁPIDA (Buscador Global)
        // -------------------------------------------------------------
        if ($request->filled('buscar_rapido')) {
            $buscar = trim($request->buscar_rapido);

            $query->where(function ($q) use ($buscar) {
                $q->where('ci', 'LIKE', "%{$buscar}%")
                    ->orWhere('primerapellido', 'LIKE', "%{$buscar}%")
                    ->orWhere('segundoapellido', 'LIKE', "%{$buscar}%")
                    ->orWhere('nombre', 'LIKE', "%{$buscar}%");
            });
        }

        // -------------------------------------------------------------
        // 2. FILTROS POR GESTIÓN Y CARRERA
        // -------------------------------------------------------------
        if ($request->filled('id_carrera')) {
            $query->where('id_carrera', $request->id_carrera);
        }

        if ($request->filled('id_gestionacademica')) {
            $query->where('id_gestionacademica', $request->id_gestionacademica);
        }

        if ($request->input('estado') === 'inscrito') {
            $query->whereHas('inscripcionOrigen');
        } elseif ($request->input('estado') === 'pendiente') {
            $query->whereDoesntHave('inscripcionOrigen');
        }

        $postulanteTieneMultiplesCarrerasEnGestion = static function ($subquery): void {
            $subquery->selectRaw('1')
                ->from('postulante as postulantes_multiples')
                ->whereColumn('postulantes_multiples.ci', 'postulante.ci')
                ->whereColumn(
                    'postulantes_multiples.id_gestionacademica',
                    'postulante.id_gestionacademica'
                )
                ->groupBy(
                    'postulantes_multiples.ci',
                    'postulantes_multiples.id_gestionacademica'
                )
                ->havingRaw('COUNT(DISTINCT postulantes_multiples.id_carrera) > 1');
        };

        // -------------------------------------------------------------
        // 2.1. CATEGORÍA POR NÚMERO DE CARRERAS EN LA MISMA GESTIÓN
        // -------------------------------------------------------------
        if ($request->input('categoria') === 'multiples') {
            $query->whereExists($postulanteTieneMultiplesCarrerasEnGestion);
        } elseif ($request->input('categoria') === 'unica') {
            $query->whereNotExists($postulanteTieneMultiplesCarrerasEnGestion);
        }

        // -------------------------------------------------------------
        // 3. EXPORTACIÓN A EXCEL O PDF
        // -------------------------------------------------------------
        if (! empty($export['exportar'])) {
            $tipo = $export['exportar'];
            $formato = $export['formato'] ?? 'excel';

            $postulantesData = ($tipo === 'todo')
                ? Postulante::with(['carrera', 'gestionAcademica'])
                    ->withExists('inscripcionOrigen')
                    ->orderBy('id_postulante', 'desc')
                    ->get()
                : $query->orderBy('id_postulante', 'desc')->get();

            $carreraSeleccionada = $request->filled('id_carrera') ? Carrera::find($request->id_carrera) : null;
            $gestionSeleccionada = $request->filled('id_gestionacademica') ? DB::table('gestionacademica')->where('id_gestionacademica', $request->id_gestionacademica)->first() : null;

            // Retornar vista PDF
            if ($formato === 'pdf') {
                return view('admisiones.postulantes.pdf', compact('postulantesData', 'carreraSeleccionada', 'gestionSeleccionada'));
            }

            // Retornar vista Excel descargable
            if ($formato === 'excel') {
                $nombreArchivo = ($tipo === 'todo') ? 'Lista_Completa_Postulantes' : 'Reporte_Postulantes_Filtrado';
                $rows = $postulantesData->map(fn (Postulante $postulante): array => [
                            $postulante->codigo,
                            $postulante->fecha,
                            trim($postulante->primerapellido.' '.($postulante->segundoapellido ?? '')),
                            $postulante->nombre,
                            $postulante->ci,
                            $postulante->celular,
                            $postulante->carrera?->nombreConResolucionMinisterial(),
                            $postulante->gestionAcademica?->gestion,
                            $postulante->gestionAcademica?->periodo,
                            $postulante->inscripcion_origen_exists ? 'Inscrito como estudiante' : 'Pendiente de inscripción',
                        ])->all();

                return $this->exportarExcel(
                    $nombreArchivo.'_'.now()->format('Y-m-d_H-i').'.xlsx',
                    ['Código', 'Fecha', 'Apellidos', 'Nombres', 'CI', 'Celular', 'Carrera', 'Gestión', 'Periodo', 'Estado'],
                    $rows
                );
            }
        }

        // -------------------------------------------------------------
        // 4. ESTADÍSTICAS GENERALES (KPIs) Y MÚLTIPLES CARRERAS
        // -------------------------------------------------------------
        $statsQuery = Postulante::when($request->filled('id_gestionacademica'), function($q) use ($request) {
            $q->where('id_gestionacademica', $request->id_gestionacademica);
        });

        $totalPostulantes = (clone $statsQuery)->count();
        $conCelular = (clone $statsQuery)->whereNotNull('celular')->where('celular', '!=', '')->count();
        
        $conDocumentos = (clone $statsQuery)->where(function($q) {
            $q->whereNotNull('path_ci_frontal');
        })->count();

        $multiplesCarrerasCount = (clone $statsQuery)
            ->whereExists($postulanteTieneMultiplesCarrerasEnGestion)
            ->distinct()
            ->count('ci');

        // -------------------------------------------------------------
        // 5. PAGINACIÓN Y CARGA DE VISTA PRINCIPAL
        // -------------------------------------------------------------
        $perPage = $request->get('per_page', 10);

        $postulantes = $query->orderBy('id_postulante', 'desc')
            ->paginate($perPage)
            ->appends($request->all());

        $carreras = Carrera::orderBy('nombre')->get();
        $gestiones = GestionAcademica::orderByDesc('gestion')->orderByDesc('periodo')->get();

        return view('admisiones.postulantes.index', compact(
            'postulantes',
            'carreras',
            'gestiones',
            'totalPostulantes',
            'conCelular',
            'conDocumentos',
            'multiplesCarrerasCount'
        ));
    }
}