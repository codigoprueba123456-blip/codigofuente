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
     * Consulta carreras junto con instituto y jefe de carrera para evitar cargas posteriores por fila.
     * Si se proporciona q, busca coincidencias parciales en código, nombre y las tres resoluciones.
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
     * Renderiza el alta con el instituto configurado y empleados elegibles como jefes de carrera.
     * Los datos comunes de selección se construyen en formData().
     */
    public function create()
    {
        return view('planes.carreras.create', $this->formData());
    }

    /**
     * Valida datos de carrera y exige que exista primero la configuración del instituto.
     * Crea la carrera, asigna el instituto y recalcula sus creditajes en una transacción única;
     * al terminar vuelve al catálogo con un mensaje de resultado.
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
     * Busca la carrera solicitada o responde 404 y carga los datos de las listas del formulario.
     * Pasa el jefe actual a formData() para que siga disponible aunque se haya desactivado.
     */
    public function edit($id)
    {
        $carrera = Carrera::findOrFail($id);

        return view('planes.carreras.edit', array_merge([
            'carrera' => $carrera,
        ], $this->formData($carrera->id_jefecarrera)));
    }

    /**
     * Valida la edición conservando la unicidad propia de la carrera y el jefe ya asignado.
     * Actualiza la carrera y recalcula los importes derivados dentro de una transacción para
     * mantener consistentes los campos automáticos y el plan curricular.
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
     * Valida y cambia solo el estado activo; no modifica datos académicos ni creditajes.
     * Devuelve a la lista de carreras con una notificación del nuevo estado.
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
     * Construye las opciones compartidas por los formularios de alta y edición.
     * Incluye el instituto configurado y empleados activos; al editar añade al jefe actual aun
     * inactivo para conservar la relación existente hasta que el usuario decida cambiarla.
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
     * Define validaciones compartidas para identidad, régimen, nivel, resoluciones y creditaje manual.
     * En edición excluye la carrera de la regla única y permite conservar al jefe actual inactivo;
     * los demás jefes deben seguir siendo empleados activos.
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
