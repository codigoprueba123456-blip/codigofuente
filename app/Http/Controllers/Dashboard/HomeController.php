<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $canViewDashboard = $user->isAdministrator()
            || $user->canAccess('inscripciones/inscriptos');

        if (! $canViewDashboard) {
            return view('home');
        }

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

        $inscripciones = DB::table('inscrito as i')
            ->where('i.activo', true)
            ->whereNull('i.id_inscrito_reemplazo')
            ->when(
                $gestionActual,
                fn (Builder $query) => $query->where('i.id_gestionacademica', $gestionActual->id_gestionacademica)
            );
        $estadisticas = [
            'inscripciones' => (clone $inscripciones)->distinct()->count('i.id_inscrito'),
            'postulantes' => DB::table('postulante')
                ->when(
                    $gestionActual,
                    fn (Builder $query) => $query->where('id_gestionacademica', $gestionActual->id_gestionacademica)
                )
                ->count(),
            'carreras' => Carrera::query()->where('activo', true)->count(),
            'grupos' => DB::table('grupo')
                ->where('activo', true)
                ->when(
                    $gestionActual,
                    fn (Builder $query) => $query->where('id_gestionacademica', $gestionActual->id_gestionacademica)
                )
                ->count(),
        ];
        $inscripcionesPorCarrera = (clone $inscripciones)
            ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
            ->select(
                'c.nombre as carrera',
                'c.resolucionministerial1',
                'c.resolucionministerial2',
                'c.resolucionministerial3'
            )
            ->selectRaw('COUNT(DISTINCT i.id_persona) as estudiantes')
            ->groupBy(
                'c.id_carrera',
                'c.nombre',
                'c.resolucionministerial1',
                'c.resolucionministerial2',
                'c.resolucionministerial3'
            )
            ->orderByDesc('estudiantes')
            ->get()
            ->map(function (object $carrera): object {
                $carrera->carrera = Carrera::formatearNombreConResolucion(
                    $carrera->carrera,
                    $carrera->resolucionministerial1,
                    $carrera->resolucionministerial2,
                    $carrera->resolucionministerial3
                );

                return $carrera;
            });
        $maxEstudiantesCarrera = max(1, (int) $inscripcionesPorCarrera->max('estudiantes'));

        return view('dashboard.home', compact(
            'gestionActual',
            'estadisticas',
            'inscripcionesPorCarrera',
            'maxEstudiantesCarrera'
        ));
    }
}
