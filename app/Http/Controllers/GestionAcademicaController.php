<?php

namespace App\Http\Controllers;

use App\Models\GestionAcademica;
use Illuminate\Http\Request;

class GestionAcademicaController extends Controller
{
    public function index(Request $request)
    {
        $gestiones = GestionAcademica::query()
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $buscar = $request->string('buscar');
                $query->where('gestion', 'like', "%{$buscar}%")
                    ->orWhere('periodo', 'like', "%{$buscar}%");
            })
            ->orderByDesc('gestion')
            ->orderBy('periodo')
            ->get();

        return view('gestiones.gestiones_academicas.index', compact('gestiones'));
    }

    public function create()
    {
        return view('gestiones.gestiones_academicas.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        GestionAcademica::create($validated);

        return redirect()->route('admin.gestionacademica.index')
            ->with('success', 'Gestión académica creada correctamente.');
    }

    public function edit($id)
    {
        $gestion = GestionAcademica::findOrFail($id);

        return view('gestiones.gestiones_academicas.edit', compact('gestion'));
    }

    public function update(Request $request, $id)
    {
        $gestion = GestionAcademica::findOrFail($id);
        $gestion->update($request->validate($this->rules()));

        return redirect()->route('admin.gestionacademica.index')
            ->with('success', 'Gestión académica actualizada correctamente.');
    }

    public function destroy($id)
    {
        GestionAcademica::findOrFail($id)->delete();

        return redirect()->route('admin.gestionacademica.index')
            ->with('success', 'Gestión académica eliminada correctamente.');
    }

    private function rules(): array
    {
        return [
            'gestion' => ['required', 'integer', 'min:2000', 'max:2100'],
            'periodo' => ['required', 'integer', 'min:1'],
            'regimen' => ['required', 'integer', 'in:1,2'],
            'inicio' => ['nullable', 'date'],
            'fin' => ['nullable', 'date', 'after_or_equal:inicio'],
            'vigente' => ['sometimes', 'boolean'],
            'modalidadevaluacion' => ['nullable', 'integer', 'in:1,2'],
        ];
    }
}
