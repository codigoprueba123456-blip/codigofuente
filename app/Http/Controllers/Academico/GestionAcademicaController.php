<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Models\GestionAcademica;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GestionAcademicaController extends Controller
{
    /**
     * Busca gestiones por año, periodo o identificador cuando se recibe el parámetro buscar.
     * Ordena desde el año más reciente y entrega los registros a la tabla de administración.
     */
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

    /**
     * Muestra el formulario de alta; la validación y persistencia se realizan en store().
     */
    public function create()
    {
        return view('gestiones.gestiones_academicas.create');
    }

    /**
     * Aplica las reglas comunes de periodo, régimen, evaluación y fechas y crea la gestión.
     * Tras guardar, vuelve al listado y muestra una notificación de éxito.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());
        GestionAcademica::create($validated);

        return redirect()->route('admin.gestionacademica.index')
            ->with('success', 'Gestión académica creada correctamente.');
    }

    /**
     * Busca la gestión por su ID y entrega sus datos actuales al formulario de edición.
     * Si el registro no existe, Laravel responde con 404.
     */
    public function edit($id)
    {
        $gestion = GestionAcademica::findOrFail($id);

        return view('gestiones.gestiones_academicas.edit', compact('gestion'));
    }

    /**
     * Valida y actualiza datos de periodo y estado de vigencia de una gestión existente.
     * Impide que una gestión deshabilitada se marque vigente hasta volver a habilitarla.
     */
    public function update(Request $request, $id)
    {
        $gestion = GestionAcademica::findOrFail($id);
        $validated = $request->validate($this->rules(), $this->messages());
        // La vigencia indica qué gestión puede operar; por eso una gestión inactiva no puede
        // marcarse como vigente aunque el formulario envíe ese valor.
        if (! $gestion->activo && (bool) ($validated['vigente'] ?? false)) {
            throw ValidationException::withMessages([
                'vigente' => 'Primero habilita la gestión antes de marcarla como vigente.',
            ]);
        }
        $gestion->update($validated);

        return redirect()->route('admin.gestionacademica.index')
            ->with('success', 'Gestión académica actualizada correctamente.');
    }

    /**
     * Cambia el estado activo recibido y mantiene la vigencia solo al habilitar la gestión.
     * Deshabilitarla elimina su vigencia; volver a habilitarla no la marca vigente automáticamente.
     */
    public function cambiarEstado(Request $request, $id)
    {
        $validated = $request->validate([
            'activo' => ['required', 'boolean'],
        ]);
        $gestion = GestionAcademica::findOrFail($id);
        // Deshabilitar también quita la vigencia; al volver a habilitarla no se restaura automáticamente.
        $gestion->update([
            'activo' => $validated['activo'],
            'vigente' => $validated['activo'] ? $gestion->vigente : false,
        ]);

        return redirect()->route('admin.gestionacademica.index')
            ->with('success', 'Estado de la gestión académica actualizado correctamente.');
    }

    /**
     * Comparte reglas de validación entre alta y edición.
     * Limita el año y el régimen, permite fechas vacías y exige que fin no anteceda a inicio.
     */
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

    /**
     * Traduce a texto claro la regla cronológica entre fechas de inicio y finalización.
     */
    private function messages(): array
    {
        return [
            'fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ];
    }
}
