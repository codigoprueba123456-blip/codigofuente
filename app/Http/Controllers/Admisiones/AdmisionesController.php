<?php

namespace App\Http\Controllers\Admisiones;

use App\Http\Controllers\Controller;
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
        ]);
        $query = Postulante::with(['carrera', 'gestionAcademica']);

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

        // -------------------------------------------------------------
        // 2.1. NUEVO FILTRO POR CATEGORÍA (Múltiples Carreras vs Única)
        // -------------------------------------------------------------
        if ($request->filled('categoria')) {
            $multiplesCIs = Postulante::when($request->filled('id_gestionacademica'), function($q) use ($request) {
                    $q->where('id_gestionacademica', $request->id_gestionacademica);
                })
                ->select('ci')
                ->groupBy('ci')
                ->havingRaw('COUNT(DISTINCT id_carrera) > 1')
                ->pluck('ci');

            if ($request->input('categoria') === 'multiples') {
                $query->whereIn('ci', $multiplesCIs);
            } elseif ($request->input('categoria') === 'unica') {
                $query->whereNotIn('ci', $multiplesCIs);
            }
        }

        // -------------------------------------------------------------
        // 3. EXPORTACIÓN A EXCEL O PDF
        // -------------------------------------------------------------
        if (! empty($export['exportar'])) {
            $tipo = $export['exportar'];
            $formato = $export['formato'] ?? 'excel';

            $postulantesData = ($tipo === 'todo')
                ? Postulante::with(['carrera', 'gestionAcademica'])->orderBy('id_postulante', 'desc')->get()
                : $query->orderBy('id_postulante', 'desc')->get();

            $carreraSeleccionada = $request->filled('id_carrera') ? DB::table('carreras')->where('id_carrera', $request->id_carrera)->first() : null;
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
                            $postulante->carrera?->nombre,
                            $postulante->gestionAcademica?->gestion,
                        ])->all();

                return $this->exportarExcel(
                    $nombreArchivo.'_'.now()->format('Y-m-d_H-i').'.xlsx',
                    ['Código', 'Fecha', 'Apellidos', 'Nombres', 'CI', 'Celular', 'Carrera', 'Gestión'],
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

        // Conteo de postulantes únicos con más de una carrera inscrita
        $CIsMultiples = (clone $statsQuery)
            ->select('ci')
            ->groupBy('ci')
            ->havingRaw('COUNT(DISTINCT id_carrera) > 1')
            ->pluck('ci');

        $multiplesCarrerasCount = (clone $statsQuery)->whereIn('ci', $CIsMultiples)->count();

        // -------------------------------------------------------------
        // 5. PAGINACIÓN Y CARGA DE VISTA PRINCIPAL
        // -------------------------------------------------------------
        $perPage = $request->get('per_page', 10);

        $postulantes = $query->orderBy('id_postulante', 'desc')
            ->paginate($perPage)
            ->appends($request->all());

        $carreras = DB::table('carreras')->select('id_carrera', 'nombre', 'activo')->get();
        $gestiones = DB::table('gestionacademica')->select('id_gestionacademica', 'gestion', 'periodo')->get();

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