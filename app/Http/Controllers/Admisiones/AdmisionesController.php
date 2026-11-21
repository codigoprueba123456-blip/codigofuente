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
    // Convierte los códigos numéricos guardados para el turno en nombres fáciles de mostrar.
    private const TURNOS = [
        1 => 'Mañana',
        2 => 'Tarde',
        3 => 'Noche',
    ];

    /**
     * Redirige al listado para descargar el reporte en Excel.
     * Conserva los filtros que ya venían en la dirección y agrega los datos
     * que indican al listado que debe preparar una exportación filtrada.
     */
    public function excel(Request $request)
    {
        return redirect()->route('admisiones.postulantes.index', array_merge(
            $request->query(),
            ['exportar' => 'filtrado', 'formato' => 'excel']
        ));
    }

    /**
     * Redirige al listado para mostrar la versión imprimible del reporte.
     * Al igual que Excel, mantiene los filtros actuales para que el PDF corresponda
     * a los mismos resultados que la persona está consultando.
     */
    public function pdf(Request $request)
    {
        return redirect()->route('admisiones.postulantes.index', array_merge(
            $request->query(),
            ['exportar' => 'filtrado', 'formato' => 'pdf']
        ));
    }

    /**
     * Prepara el listado administrativo de postulantes, sus filtros, estadísticas y exportaciones.
     * Cuando no se solicita una exportación, carga los datos paginados para la vista principal.
     */
    public function index(Request $request)
    {
        // Acepta solo los valores previstos para exportación y filtros antes de usarlos en las consultas.
        $export = $request->validate([
            'exportar' => ['nullable', 'in:filtrado,todo'],
            'formato' => ['nullable', 'in:excel,pdf'],
            'estado' => ['nullable', 'in:pendiente,inscrito'],
            'categoria' => ['nullable', 'in:multiples,unica'],
            'turno' => ['nullable', 'integer', 'in:1,2,3'],
            'paralelo' => ['nullable', 'string', 'max:20'],
        ]);

        // Carga carrera y gestión junto con cada postulante. withExists obtiene si hay inscripción
        // relacionada sin cargar todas las filas de inscripción.
        $query = Postulante::with(['carrera', 'gestionAcademica'])
            ->withExists('inscripcionOrigen');

        // -------------------------------------------------------------
        // 1. Búsqueda rápida por CI, nombre o cualquiera de los apellidos.
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
        // 2. Limita la consulta a la gestión, carrera, turno o paralelo elegidos.
        // Si un filtro está vacío, no restringe los resultados por ese dato.
        // -------------------------------------------------------------
        if ($request->filled('id_carrera')) {
            $query->where('id_carrera', $request->id_carrera);
        }

        if ($request->filled('id_gestionacademica')) {
            $query->where('id_gestionacademica', $request->id_gestionacademica);
        }

        if ($request->filled('turno')) {
            $query->where('turno', $export['turno']);
        }

        if ($request->filled('paralelo')) {
            $query->whereRaw('UPPER(TRIM(paralelo)) = ?', [mb_strtoupper(trim($export['paralelo']))]);
        }

        if ($request->input('estado') === 'inscrito') {
            // Muestra solo postulantes que ya tienen una inscripción de origen vinculada.
            $query->whereHas('inscripcionOrigen');
        } elseif ($request->input('estado') === 'pendiente') {
            // Muestra solo postulantes que todavía no tienen esa inscripción vinculada.
            $query->whereDoesntHave('inscripcionOrigen');
        }

        // Esta consulta identifica a la persona que tiene más de una carrera postulada
        // dentro de la misma gestión; se reutiliza para el filtro y la estadística.
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
        // 2.1. Filtra por postulantes con varias carreras o con una sola carrera en la gestión.
        // -------------------------------------------------------------
        if ($request->input('categoria') === 'multiples') {
            $query->whereExists($postulanteTieneMultiplesCarrerasEnGestion);
        } elseif ($request->input('categoria') === 'unica') {
            $query->whereNotExists($postulanteTieneMultiplesCarrerasEnGestion);
        }

        // -------------------------------------------------------------
        // 3. Si se pidió exportar, prepara los mismos datos filtrados para PDF o Excel
        // y termina aquí la respuesta, sin cargar la página de listado.
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

            // El PDF es una vista imprimible que recibe los resultados, los filtros elegidos y los nombres de turno.
            if ($formato === 'pdf') {
                $turnos = self::TURNOS;

                return view('admisiones.postulantes.pdf', compact(
                    'postulantesData',
                    'carreraSeleccionada',
                    'gestionSeleccionada',
                    'turnos'
                ));
            }

            // Para Excel, convierte cada postulante en una fila con columnas en el mismo orden que los encabezados.
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
                            self::TURNOS[(int) $postulante->turno] ?? 'Sin turno',
                            $postulante->paralelo ?? 'Sin paralelo',
                            $postulante->inscripcion_origen_exists ? 'Inscrito como estudiante' : 'Pendiente de inscripción',
                        ])->all();

                return $this->exportarExcel(
                    $nombreArchivo.'_'.now()->format('Y-m-d_H-i').'.xlsx',
                    ['Código', 'Fecha', 'Apellidos', 'Nombres', 'CI', 'Celular', 'Carrera', 'Gestión', 'Periodo', 'Turno', 'Paralelo', 'Estado'],
                    $rows
                );
            }
        }

        // 4. Calcula las tarjetas de resumen. Si se eligió una gestión, las cifras se limitan a ella;
        // los demás filtros del listado no cambian estos totales.
        $statsQuery = Postulante::when($request->filled('id_gestionacademica'), function($q) use ($request) {
            $q->where('id_gestionacademica', $request->id_gestionacademica);
        });

        $totalPostulantes = (clone $statsQuery)->count();
        $conCelular = (clone $statsQuery)->whereNotNull('celular')->where('celular', '!=', '')->count();
        
        // Esta cifra cuenta fichas que tienen cargado el anverso del CI.
        $conDocumentos = (clone $statsQuery)->where(function($q) {
            $q->whereNotNull('path_ci_frontal');
        })->count();

        // Cuenta personas distintas con más de una carrera postulada en la misma gestión.
        $multiplesCarrerasCount = (clone $statsQuery)
            ->whereExists($postulanteTieneMultiplesCarrerasEnGestion)
            ->distinct()
            ->count('ci');

        // 5. Pagina los resultados principales y conserva los filtros en los enlaces de páginas siguientes.
        $perPage = $request->get('per_page', 10);

        $postulantes = $query->orderBy('id_postulante', 'desc')
            ->paginate($perPage)
            ->appends($request->all());

        // Prepara listas únicas para los selectores y normaliza los paralelos para evitar duplicados por mayúsculas.
        $paralelos = Postulante::query()
            ->whereNotNull('paralelo')
            ->where('paralelo', '!=', '')
            ->distinct()
            ->orderBy('paralelo')
            ->pluck('paralelo')
            ->map(fn ($paralelo): string => mb_strtoupper(trim((string) $paralelo)))
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $carreras = Carrera::orderBy('nombre')->get();
        $gestiones = GestionAcademica::orderByDesc('gestion')->orderByDesc('periodo')->get();
        $turnos = self::TURNOS;

        // Envía a la vista los resultados, las opciones de filtro y las cifras de resumen.
        return view('admisiones.postulantes.index', compact(
            'postulantes',
            'carreras',
            'gestiones',
            'turnos',
            'paralelos',
            'totalPostulantes',
            'conCelular',
            'conDocumentos',
            'multiplesCarrerasCount'
        ));
    }
}