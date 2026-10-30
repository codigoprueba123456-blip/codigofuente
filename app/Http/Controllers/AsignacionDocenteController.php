<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AsignacionDocenteController extends Controller
{
    public function index(Request $request)
    {
        $gestiones = GestionAcademica::orderByDesc('gestion')->orderBy('periodo')->get();
        $carreras = Carrera::orderBy('nombre')->get();
        $docentes = Persona::where('tipo', 'empleado')->orderBy('primerapellido')->orderBy('nombre')->get();
        $grupos = Grupo::with(['materia', 'docente'])
            ->when($request->filled('id_gestionacademica'), fn ($query) =>
                $query->where('id_gestionacademica', $request->id_gestionacademica))
            ->when($request->filled('id_carrera'), fn ($query) =>
                $query->whereHas('materia', fn ($materia) =>
                    $materia->where('id_carrera', $request->id_carrera)))
            ->when($request->filled('buscar'), fn ($query) =>
                $query->where('codigo', 'like', '%' . $request->buscar . '%'))
            ->orderBy('codigo')
            ->get();

        return view('gestiones.horarios.index', compact(
            'gestiones', 'carreras', 'grupos', 'docentes'
        ));
    }

    public function create(Request $request)
    {
        $grupo = Grupo::with('materia')->findOrFail($request->integer('id_grupo'));
        $this->ensureEnabled($grupo->id_gestionacademica);
        $docentes = $this->docentes();

        return view('gestiones.horarios.create', compact('grupo', 'docentes'));
    }

    public function edit($id)
    {
        $grupo = Grupo::with('materia')->findOrFail($id);
        $this->ensureEnabled($grupo->id_gestionacademica);
        $docentes = $this->docentes();

        return view('gestiones.horarios.edit', compact('grupo', 'docentes'));
    }

    public function asignar(Request $request, $id)
    {
        $validated = $request->validate([
            'id_persona' => ['nullable', 'exists:personas,id_persona'],
        ]);

        $grupo = Grupo::findOrFail($id);
        $this->ensureEnabled($grupo->id_gestionacademica);
        $grupo->update(['id_persona' => $validated['id_persona'] ?? null]);

        return redirect()->route('admin.asignacion-docente.index', [
            'id_gestionacademica' => $grupo->id_gestionacademica,
        ])->with('success', 'Docente asignado correctamente.');
    }

    private function docentes()
    {
        return Persona::where('tipo', 'empleado')
            ->orderBy('primerapellido')
            ->orderBy('nombre')
            ->get();
    }

    private function ensureEnabled(int $idGestion): void
    {
        if (!GestionAcademica::habilitadas()->whereKey($idGestion)->exists()) {
            throw ValidationException::withMessages([
                'id_gestionacademica' => 'La gestión académica seleccionada está deshabilitada.',
            ]);
        }
    }
}
