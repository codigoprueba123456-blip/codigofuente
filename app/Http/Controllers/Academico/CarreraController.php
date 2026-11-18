<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Empleado;
use App\Models\Instituto;
use App\Support\CareerCreditageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CarreraController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('q', ''));
        $carreras = Carrera::with(['instituto', 'jefeCarrera.persona'])
            ->when($buscar !== '', fn ($query) => $query->where(function ($query) use ($buscar): void {
                $term = '%'.$buscar.'%';
                $query->where('codigo', 'like', $term)
                    ->orWhere('nombre', 'like', $term)
                    ->orWhere('resolucionministerial1', 'like', $term)
                    ->orWhere('resolucionministerial2', 'like', $term)
                    ->orWhere('resolucionministerial3', 'like', $term);
            }))
            ->get();
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

        return view('planes.carreras.edit', array_merge([
            'carrera' => $carrera,
        ], $this->formData($carrera->id_jefecarrera)));
    }

    public function update(Request $request, $id, CareerCreditageService $creditage)
    {
        $carrera = Carrera::findOrFail($id);
        $validated = $request->validate($this->rules($carrera->id_carrera, $carrera->id_jefecarrera));
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

    private function formData(?int $jefeActual = null): array
    {
        return [
            'instituto' => Instituto::first(),
            'jefesCarrera' => Empleado::with('persona')
                ->where('activo', true)
                ->when($jefeActual, fn ($query) => $query->orWhere('id_persona', $jefeActual))
                ->get(),
        ];
    }

    private function rules(?int $id = null, ?int $jefeActual = null): array
    {
        return [
            'codigo' => ['required', 'string', 'max:255', Rule::unique('carreras', 'codigo')->ignore($id, 'id_carrera')],
            'nombre' => ['required', 'string', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
            'nivelacademico' => ['nullable', 'integer', Rule::in([
                Carrera::NIVEL_TECNICO_SUPERIOR,
                Carrera::NIVEL_TECNICO_MEDIO,
            ])],
            'regimen' => ['nullable', 'integer', Rule::in([
                Carrera::REGIMEN_ANUAL,
                Carrera::REGIMEN_SEMESTRAL,
            ])],
            'resolucionministerial1' => ['nullable', 'string', 'max:255'],
            'resolucionministerial2' => ['nullable', 'string', 'max:255'],
            'resolucionministerial3' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'integer', 'min:0'],
            'creditajeadmision_manual' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'creditajematricula_manual' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'id_jefecarrera' => [
                'nullable',
                Rule::exists('empleados', 'id_persona')
                    ->where(fn ($query) => $query
                        ->where('activo', true)
                        ->orWhere('id_persona', $jefeActual)),
            ],
        ];
    }
}
