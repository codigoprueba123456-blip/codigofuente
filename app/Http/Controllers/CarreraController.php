<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Empleado;
use App\Models\Instituto;
use App\Support\CareerCreditageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CarreraController extends Controller
{
    public function index()
    {
        $carreras = Carrera::with(['instituto', 'jefeCarrera.persona'])->get();
        return view('planes.carreras.index', compact('carreras'));
    }

    public function create()
    {
        return view('planes.carreras.create', $this->formData());
    }

    public function store(Request $request, CareerCreditageService $creditage)
    {
        $validated = $request->validate($this->rules());
        $instituto = Instituto::first();
        if (!$instituto) {
            return back()
                ->withErrors(['id_instituto' => 'Primero configure los datos del instituto.'])
                ->withInput();
        }

        $carrera = DB::transaction(function () use ($validated, $instituto, $creditage): Carrera {
            $validated['id_instituto'] = $instituto->id_instituto;
            $carrera = Carrera::create($validated);
            $creditage->syncCareer($carrera);

            return $carrera;
        });

        return redirect()->route('planes.carreras.index')->with('success', 'Carrera creada correctamente.');
    }

    public function edit($id)
    {
        $carrera = Carrera::findOrFail($id);

        return view('planes.carreras.edit', array_merge(['carrera' => $carrera], $this->formData()));
    }

    public function update(Request $request, $id, CareerCreditageService $creditage)
    {
        $carrera = Carrera::findOrFail($id);
        $validated = $request->validate($this->rules($carrera->id_carrera));
        $instituto = Instituto::first();
        if (!$instituto) {
            return back()
                ->withErrors(['id_instituto' => 'Primero configure los datos del instituto.'])
                ->withInput();
        }

        DB::transaction(function () use ($carrera, $validated, $instituto, $creditage): void {
            $validated['id_instituto'] = $instituto->id_instituto;
            $carrera->update($validated);
            $creditage->syncCareer($carrera);
        });

        return redirect()->route('planes.carreras.index')->with('success', 'Carrera actualizada correctamente.');
    }

    public function destroy($id)
    {
        Carrera::findOrFail($id)->delete();

        return redirect()->route('planes.carreras.index')->with('success', 'Carrera eliminada correctamente.');
    }

    public function cambiarEstado(Request $request, $id)
    {
        $validated = $request->validate([
            'activo' => ['required', 'boolean'],
        ]);

        $carrera = Carrera::findOrFail($id);
        $carrera->update(['activo' => $validated['activo']]);

        return redirect()->route('planes.carreras.index')
            ->with('success', 'Estado de la carrera actualizado correctamente.');
    }

    private function formData(): array
    {
        return [
            'instituto' => Instituto::first(),
            'jefesCarrera' => Empleado::with('persona')->get(),
        ];
    }

    private function rules(?int $id = null): array
    {
        return [
            'codigo' => ['required', 'string', 'max:255', Rule::unique('carreras', 'codigo')->ignore($id, 'id_carrera')],
            'nombre' => ['required', 'string', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
            'nivelacademico' => ['nullable', 'integer', 'min:0'],
            'regimen' => ['nullable', 'integer', 'min:0'],
            'resolucionministerial1' => ['nullable', 'string', 'max:255'],
            'resolucionministerial2' => ['nullable', 'string', 'max:255'],
            'resolucionministerial3' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'integer', 'min:0'],
            'id_jefecarrera' => ['nullable', 'exists:empleados,id_persona'],
        ];
    }
}
