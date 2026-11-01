<?php

namespace App\Http\Controllers\Postulantes;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Postulante;
use App\Servicios\Postulantes\GestionarPostulacionesServicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PostulanteController extends Controller
{
    public function bienvenida(Request $request): View
    {
        $gestionActual = $this->gestionActiva();

        $tutorialVideoUrl = $this->tutorialVideoUrl();

        // Manejo de la búsqueda por CI dentro de la pantalla de bienvenida (Delimitado a la gestión activa)
        $postulanteBase = null;
        $postulaciones = [];

        if ($request->filled('ci_consulta') && $gestionActual) {
            $ciLimpio = preg_replace('/[^A-Za-z0-9\-]/', '', $request->input('ci_consulta'));

            // Obtener el registro base delimitado exclusivamente a la gestión académica activa
            $postulanteBase = Postulante::where('ci', $ciLimpio)
                ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
                ->orderByDesc('id_postulante')
                ->first();

            if ($postulanteBase) {
                // Obtener todas las postulaciones asociadas al CI en la gestión activa
                $postulaciones = Postulante::with(['carrera', 'gestion'])
                    ->where('ci', $ciLimpio)
                    ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
                    ->orderByDesc('id_postulante')
                    ->get();
            }
        }

        return view('postulantes.bienvenida', compact(
            'gestionActual',
            'tutorialVideoUrl',
            'postulanteBase',
            'postulaciones'
        ));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva) {
            return redirect()
                ->route('postulantes.bienvenida')
                ->with('error', 'No hay una gestión académica activa para iniciar el registro.');
        }

        $carreras = Carrera::activas()->orderBy('nombre')->get();

        $ciBuscado = $request->query('ci_consulta') ?? $request->query('ci');
        $postulanteBase = null;

        if ($ciBuscado && $gestionActiva) {
            $ciLimpio = preg_replace('/[^A-Za-z0-9\-]/', '', $ciBuscado);

            // Buscar postulante previo solo en la gestión activa para autocompletar datos o validar
            $postulanteBase = Postulante::where('ci', $ciLimpio)
                ->where('id_gestionacademica', $gestionActiva->id_gestionacademica)
                ->orderByDesc('id_postulante')
                ->first();
        }
        $tutorialVideoUrl = $this->tutorialVideoUrl();

        return view('postulantes.crear', compact(
            'gestionActiva',
            'carreras',
            'postulanteBase',
            'ciBuscado',
            'tutorialVideoUrl'
        ));
    }

    public function store(Request $request, GestionarPostulacionesServicio $servicio): RedirectResponse
    {
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva) {
            return redirect()
                ->route('postulantes.bienvenida')
                ->with('error', 'No hay una gestión académica activa para registrar postulaciones.');
        }

        $postulante = $servicio->registrar($request, $gestionActiva);

        return redirect()
            ->route('postulantes.pdf', $postulante->id_postulante);
    }

    /**
     * Muestra el formulario para editar a un postulante existente.
     */
    public function edit($id): View
    {
        $postulante = Postulante::findOrFail($id);
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva || (int) $postulante->id_gestionacademica !== (int) $gestionActiva->id_gestionacademica) {
            abort(404);
        }

        $carreras = Carrera::query()
            ->where('activo', true)
            ->orWhere('id_carrera', $postulante->id_carrera)
            ->orderBy('nombre')
            ->get();

        return view('postulantes.editar', compact('postulante', 'carreras', 'gestionActiva'));
    }

    public function imprimir(int $id): View
    {
        $postulante = Postulante::with(['carrera', 'gestionAcademica'])->findOrFail($id);
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva || (int) $postulante->id_gestionacademica !== (int) $gestionActiva->id_gestionacademica) {
            abort(404);
        }

        return view('postulantes.pdf', compact('postulante'));
    }

    /**
     * Actualiza los datos del postulante y procesa archivos opcionales.
     */
    public function update(Request $request, GestionarPostulacionesServicio $servicio, $id): RedirectResponse
    {
        $postulante = Postulante::findOrFail($id);
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva || (int) $postulante->id_gestionacademica !== (int) $gestionActiva->id_gestionacademica) {
            abort(404);
        }

        $postulante = $servicio->actualizar($request, $postulante, $gestionActiva);

        return redirect()
            ->route('postulantes.pdf', $postulante->id_postulante);
    }

    public function destroy(int $id): RedirectResponse
    {
        $postulante = Postulante::findOrFail($id);
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva || (int) $postulante->id_gestionacademica !== (int) $gestionActiva->id_gestionacademica) {
            abort(404);
        }

        $inscrito = DB::table('inscrito')
            ->where('id_postulante_origen', $postulante->id_postulante)
            ->exists();

        if ($inscrito) {
            return redirect()
                ->route('postulantes.bienvenida', ['ci_consulta' => $postulante->ci])
                ->with('error', 'No se puede eliminar esta postulación porque forma parte del historial de un estudiante.');
        }

        $ci = $postulante->ci;
        $postulante->delete();

        return redirect()
            ->route('postulantes.bienvenida', ['ci_consulta' => $ci])
            ->with('success', 'La ficha de postulación se eliminó correctamente.');
    }

    private function gestionActiva(): ?GestionAcademica
    {
        return GestionAcademica::where('vigente', 1)
            ->orderByDesc('id_gestionacademica')
            ->first();
    }

    private function tutorialVideoUrl(): ?string
    {
        return is_file(public_path('videos/guia_inscripcion.mp4'))
            ? url('/videos/guia_inscripcion.mp4')
            : null;
    }
}
