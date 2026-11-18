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
    /**
     * Lista carreras con sus responsables y permite filtrar por código, nombre o resolución.
     */
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

    /**
     * Prepara los datos institucionales y los jefes de carrera para el formulario de alta.
     */
    public function create()
    {
        return view('planes.carreras.create', $this->formData());
    }

    /**
     * Valida y crea una carrera asociada al instituto configurado; sincroniza el creditaje
     * y revierte ambos cambios si falla cualquier paso.
     */
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
            // La carrera y su distribución curricular se guardan juntas: así, si el recálculo falla,
            // tampoco queda persistida una carrera con creditajes derivados desactualizados.
            $creditage->syncCareer($carrera);

            return $carrera;
        });

        return redirect()->route('planes.carreras.index')->with('success', 'Carrera creada correctamente.');
    }

    /**
     * Carga la carrera y prepara las opciones del formulario, incluyendo al jefe actual.
     */
    public function edit($id)
    {
        $carrera = Carrera::findOrFail($id);

        return view('planes.carreras.edit', array_merge([
            'carrera' => $carrera,
        ], $this->formData($carrera->id_jefecarrera)));
    }

    /**
     * Actualiza la carrera y recalcula sus valores de creditaje en la misma transacción.
     */
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
            // Un cambio de precio, creditaje manual o composición curricular afecta los valores derivados.
            $creditage->syncCareer($carrera);
        });

        return redirect()->route('planes.carreras.index')->with('success', 'Carrera actualizada correctamente.');
    }

    /**
     * Cambia únicamente el estado de habilitación de la carrera.
     */
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

    /**
     * Devuelve el instituto y los empleados activos que pueden ser jefes; conserva también
     * al jefe actual aunque haya dejado de estar activo para no perder la selección al editar.
     */
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

    /**
     * Construye reglas comunes para alta y edición, permitiendo excluir de la validación única
     * la carrera actual y mantener al jefe asignado aunque esté inactivo.
     */
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
