<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Empleado;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AsignacionDocenteController extends Controller
{
    public function index(Request $request)
    {
        $gestiones = GestionAcademica::orderByDesc('gestion')->orderBy('periodo')->get();
        $carreras = Carrera::orderBy('nombre')->get();
        $grupos = Grupo::with(['materia.carrera', 'docente'])
            ->when($request->filled('id_gestionacademica'), fn ($query) =>
                $query->where('id_gestionacademica', $request->id_gestionacademica))
            ->when($request->filled('id_carrera'), fn ($query) =>
                $query->whereHas('materia', fn ($materia) =>
                    $materia->where('id_carrera', $request->id_carrera)))
            ->when($request->filled('buscar'), fn ($query) =>
                $query->where(function ($search) use ($request): void {
                    $term = '%'.$request->buscar.'%';

                    $search->where('codigo', 'like', $term)
                        ->orWhereHas('materia', fn ($materia) => $materia
                            ->where('nombre', 'like', $term)
                            ->orWhere('codigo', 'like', $term))
                        ->orWhereHas('docente', fn ($docente) => $docente
                            ->where('nombre', 'like', $term)
                            ->orWhere('primerapellido', 'like', $term)
                            ->orWhere('segundoapellido', 'like', $term));
                }))
            ->orderBy('codigo')
            ->get();
        $docentes = $this->docentes($grupos->pluck('id_persona')->filter()->all());

        return view('gestiones.asignacion-docente.index', compact(
            'gestiones', 'carreras', 'grupos', 'docentes'
        ));
    }

    public function create(Request $request)
    {
        $grupo = Grupo::with('materia')->findOrFail($request->integer('id_grupo'));
        $this->ensureAssignable($grupo);
        $docentes = $this->docentes([$grupo->id_persona]);

        return view('gestiones.asignacion-docente.create', compact('grupo', 'docentes'));
    }

    public function edit($id)
    {
        $grupo = Grupo::with('materia')->findOrFail($id);
        $this->ensureAssignable($grupo);
        $docentes = $this->docentes([$grupo->id_persona]);

        return view('gestiones.asignacion-docente.edit', compact('grupo', 'docentes'));
    }

    public function asignar(Request $request, $id)
    {
        $grupo = Grupo::with('materia')->findOrFail($id);
        $this->ensureAssignable($grupo);
        $validated = $request->validate([
            'id_persona' => [
                'nullable',
                Rule::exists('empleados', 'id_persona')
                    ->where(fn ($query) => $query
                        ->where('activo', true)
                        ->orWhere('id_persona', $grupo->id_persona)),
            ],
        ]);

        $grupo->update(['id_persona' => $validated['id_persona'] ?? null]);

        return redirect()->route('admin.asignacion-docente.index', [
            'id_gestionacademica' => $grupo->id_gestionacademica,
        ])->with('success', 'Docente asignado correctamente.');
    }

    private function docentes(array $actuales = [])
    {
        $empleadosActivos = Empleado::activos()->pluck('id_persona');
        $actuales = collect($actuales)->filter()->unique()->values();

        return Persona::where('tipo', 'empleado')
            ->where(function ($query) use ($empleadosActivos, $actuales): void {
                $query->whereIn('id_persona', $empleadosActivos);
                if ($actuales->isNotEmpty()) {
                    $query->orWhereIn('id_persona', $actuales);
                }
            })
            ->orderBy('primerapellido')
            ->orderBy('nombre')
            ->get();
    }

    private function ensureAssignable(Grupo $grupo): void
    {
        if (!GestionAcademica::habilitadas()->whereKey($grupo->id_gestionacademica)->exists()) {
            throw ValidationException::withMessages([
                'id_gestionacademica' => 'La gestión académica seleccionada está deshabilitada.',
            ]);
        }
        if (! $grupo->activo || ! $grupo->materia?->activo || ! $grupo->materia?->carrera?->activo) {
            throw ValidationException::withMessages([
                'id_grupo' => 'No se pueden asignar docentes a grupos, materias o carreras deshabilitados.',
            ]);
        }
    }
}
