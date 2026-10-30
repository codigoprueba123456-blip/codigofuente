<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GrupoController extends Controller
{
    public function index(Request $request)
    {
        $gestiones = GestionAcademica::habilitadas()->orderByDesc('gestion')->orderBy('periodo')->get();
        $carreras = Carrera::orderBy('nombre')->get();
        $grupos = $this->query($request)->get();

        return view('gestiones.grupos.index', compact('gestiones', 'carreras', 'grupos'));
    }

    public function programar(Request $request)
    {
        $gestiones = GestionAcademica::habilitadas()->orderByDesc('gestion')->orderBy('periodo')->get();
        $carreras = Carrera::orderBy('nombre')->get();
        $materias = Materia::with('carrera')->orderBy('nombre')->get();

        return view('gestiones.grupos.create', compact(
            'gestiones', 'carreras', 'materias'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $this->ensureEnabled($validated['id_gestionacademica']);
        Grupo::create($validated);

        return redirect()->route('admin.grupos.index', [
            'id_gestionacademica' => $validated['id_gestionacademica'],
        ])->with('success', 'Grupo creado correctamente.');
    }

    public function edit($id)
    {
        $grupo = Grupo::with('materia')->findOrFail($id);
        $this->ensureEnabled($grupo->id_gestionacademica);
        $gestiones = GestionAcademica::habilitadas()->orderByDesc('gestion')->orderBy('periodo')->get();
        $materias = Materia::orderBy('nombre')->get();

        return view('gestiones.grupos.edit', compact('grupo', 'gestiones', 'materias'));
    }

    public function update(Request $request, $id)
    {
        $grupo = Grupo::findOrFail($id);
        $this->ensureEnabled($grupo->id_gestionacademica);
        $validated = $request->validate($this->rules());
        $this->ensureEnabled($validated['id_gestionacademica']);
        $grupo->update($validated);

        return redirect()->route('admin.grupos.index', [
            'id_gestionacademica' => $validated['id_gestionacademica'],
        ])->with('success', 'Grupo actualizado correctamente.');
    }

    public function destroy($id)
    {
        $grupo = Grupo::findOrFail($id);
        $gestion = $grupo->id_gestionacademica;
        $this->ensureEnabled($gestion);
        $grupo->delete();

        return redirect()->route('admin.grupos.index', [
            'id_gestionacademica' => $gestion,
        ])->with('success', 'Grupo eliminado correctamente.');
    }

    private function query(Request $request)
    {
        return Grupo::with(['materia', 'docente'])
            ->when($request->filled('id_gestionacademica'), fn ($query) =>
                $query->where('id_gestionacademica', $request->id_gestionacademica))
            ->when($request->filled('id_carrera'), fn ($query) =>
                $query->whereHas('materia', fn ($materia) =>
                    $materia->where('id_carrera', $request->id_carrera)))
            ->when($request->filled('buscar'), fn ($query) =>
                $query->where('codigo', 'like', '%' . $request->buscar . '%'))
            ->orderBy('codigo');
    }

    private function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:255'],
            'capacidad' => ['nullable', 'integer', 'min:1'],
            'turno' => ['nullable', 'integer', 'in:1,2,3'],
            'abierto' => ['sometimes', 'boolean'],
            'id_gestionacademica' => ['required', 'exists:gestionacademica,id_gestionacademica'],
            'id_materia' => ['nullable', 'exists:materia,id_materia'],
            'id_persona' => ['nullable', 'exists:personas,id_persona'],
        ];
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
