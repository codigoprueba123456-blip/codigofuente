<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Models\GestionAcademica;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GestionAcademicaController extends Controller
{
    public function index(Request $request)
    {
        $gestiones = GestionAcademica::query()
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $buscar = $request->string('buscar');
                $term = "%{$buscar}%";
                $query->where(function ($query) use ($term): void {
                    $query->where('gestion', 'like', $term)
                        ->orWhere('periodo', 'like', $term)
                        ->orWhere('id_gestionacademica', 'like', $term);
                });
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
        $validated = $request->validate($this->rules(), $this->messages());
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
        $validated = $request->validate($this->rules(), $this->messages());
        if (! $gestion->activo && (bool) ($validated['vigente'] ?? false)) {
            throw ValidationException::withMessages([
                'vigente' => 'Primero habilita la gestión antes de marcarla como vigente.',
            ]);
        }
        $gestion->update($validated);

        return redirect()->route('admin.gestionacademica.index')
            ->with('success', 'Gestión académica actualizada correctamente.');
    }

    public function cambiarEstado(Request $request, $id)
    {
        $validated = $request->validate([
            'activo' => ['required', 'boolean'],
        ]);
        $gestion = GestionAcademica::findOrFail($id);
        $gestion->update([
            'activo' => $validated['activo'],
            'vigente' => $validated['activo'] ? $gestion->vigente : false,
        ]);

        return redirect()->route('admin.gestionacademica.index')
            ->with('success', 'Estado de la gestión académica actualizado correctamente.');
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

    private function messages(): array
    {
        return [
            'fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ];
    }
}
