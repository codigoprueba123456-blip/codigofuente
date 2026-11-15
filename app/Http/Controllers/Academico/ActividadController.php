<?php

namespace App\Http\Controllers\Academico;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Recurso;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\MenuResourceSync;

class ActividadController extends Controller
{
    public function index(Request $request)
    {
        $gestiones = GestionAcademica::query()->orderByDesc('gestion')->orderBy('periodo')->get();
        $query = Actividad::with(['gestionAcademica', 'carreras', 'usuarios', 'funcionalidadRecurso', 'funcionalidades'])
            ->when($request->filled('id_gestionacademica'), fn ($query) =>
                $query->where('id_gestionacademica', $request->id_gestionacademica))
            ->when($request->filled('buscar'), fn ($query) =>
                $query->where('nombre', 'like', '%' . $request->buscar . '%'))
            ->orderBy('inicio');

        if (!$this->isAdministrator($request->user())) {
            $query->visibleTo($request->user());
        }
        $actividades = $query->get();

        return view('gestiones.calendario.index', compact('gestiones', 'actividades'));
    }

    public function create(Request $request)
    {
        $gestiones = GestionAcademica::habilitadas()->orderByDesc('gestion')->orderBy('periodo')->get();
        $gestionSeleccionada = $request->input('id_gestionacademica');
        $carreras = Carrera::activas()->orderBy('nombre')->get();
        $usuarios = $this->usuariosDisponibles();
        $funcionalidades = $this->funcionalidades();

        return view('gestiones.calendario.create', compact('gestiones', 'gestionSeleccionada', 'carreras', 'usuarios', 'funcionalidades'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $this->ensureEnabled($validated['id_gestionacademica']);
        $actividad = DB::transaction(function () use ($validated): Actividad {
            $actividad = Actividad::create(Arr::except($validated, ['audiencia', 'carreras', 'usuarios', 'funcionalidades']));
            $this->syncVisibility($actividad, $validated);
            $actividad->funcionalidades()->sync($validated['funcionalidades'] ?? []);

            return $actividad;
        });

        return redirect()->route('admin.actividad.index', [
            'id_gestionacademica' => $validated['id_gestionacademica'],
        ])->with('success', 'Actividad creada correctamente.');
    }

    public function edit($id)
    {
        $actividad = Actividad::with(['carreras', 'usuarios', 'funcionalidades'])->findOrFail($id);
        $this->ensureEnabled($actividad->id_gestionacademica);
        $gestiones = GestionAcademica::habilitadas()->orderByDesc('gestion')->orderBy('periodo')->get();
        $carreras = Carrera::query()
            ->where('activo', true)
            ->orWhereIn('id_carrera', $actividad->carreras->pluck('id_carrera'))
            ->orderBy('nombre')
            ->get();
        $usuarios = $this->usuariosDisponibles($actividad);
        $funcionalidades = $this->funcionalidades();
        $funcionalidadesSeleccionadas = $actividad->funcionalidades->pluck('id_recurso')->all();
        if ($funcionalidadesSeleccionadas === [] && $actividad->funcionalidad !== null) {
            $funcionalidadesSeleccionadas[] = $actividad->funcionalidad;
        }

        return view('gestiones.calendario.edit', compact(
            'actividad',
            'gestiones',
            'carreras',
            'usuarios',
            'funcionalidades',
            'funcionalidadesSeleccionadas'
        ));
    }

    public function update(Request $request, $id)
    {
        $actividad = Actividad::findOrFail($id);
        $this->ensureEnabled($actividad->id_gestionacademica);
        $validated = $request->validate($this->rules($actividad), $this->messages());
        $this->ensureEnabled($validated['id_gestionacademica']);
        DB::transaction(function () use ($actividad, $validated): void {
            $actividad->update(Arr::except($validated, ['audiencia', 'carreras', 'usuarios', 'funcionalidades']));
            $actividad->funcionalidad = null;
            $actividad->save();
            $this->syncVisibility($actividad, $validated);
            $actividad->funcionalidades()->sync($validated['funcionalidades'] ?? []);
        });

        return redirect()->route('admin.actividad.index', [
            'id_gestionacademica' => $validated['id_gestionacademica'],
        ])->with('success', 'Actividad actualizada correctamente.');
    }

    public function cambiarEstado(Request $request, $id)
    {
        $actividad = Actividad::findOrFail($id);
        $gestion = $actividad->id_gestionacademica;
        $validated = $request->validate([
            'activo' => ['required', 'boolean'],
        ]);
        $actividad->update(['activo' => $validated['activo']]);

        return redirect()->route('admin.actividad.index', [
            'id_gestionacademica' => $gestion,
        ])->with('success', 'Estado de la actividad actualizado correctamente.');
    }

    private function rules(?Actividad $actividad = null): array
    {
        $carrerasActuales = $actividad?->carreras->pluck('id_carrera')->all() ?? [];
        $usuariosActuales = $actividad?->usuarios->pluck('id_persona')->all() ?? [];

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'inicio' => ['nullable', 'date'],
            'fin' => ['nullable', 'date', 'after_or_equal:inicio'],
            'funcionalidades' => ['nullable', 'array'],
            'funcionalidades.*' => ['integer', 'distinct', 'exists:recurso,id_recurso'],
            'id_gestionacademica' => ['required', 'exists:gestionacademica,id_gestionacademica'],
            'audiencia' => ['required', 'in:general,carrera,usuario'],
            'carreras' => ['required_if:audiencia,carrera', 'array'],
            'carreras.*' => [
                Rule::exists('carreras', 'id_carrera')
                    ->where(fn ($query) => $query
                        ->where('activo', true)
                        ->orWhereIn('id_carrera', $carrerasActuales)),
            ],
            'usuarios' => ['required_if:audiencia,usuario', 'array'],
            'usuarios.*' => [
                Rule::exists('users', 'id_persona')
                    ->where(fn ($query) => $query->where(function ($query) use ($usuariosActuales): void {
                        $query->whereNotIn('id_persona', DB::table('empleados')
                            ->where('activo', false)
                            ->select('id_persona'))
                            ->orWhereIn('id_persona', $usuariosActuales);
                    })),
            ],
        ];
    }

    private function usuariosDisponibles(?Actividad $actividad = null)
    {
        $usuariosActuales = $actividad?->usuarios->pluck('id_persona') ?? collect();

        return User::query()
            ->where(function ($query) use ($usuariosActuales): void {
                $query->whereDoesntHave('empleado')
                    ->orWhereHas('empleado', fn ($empleados) => $empleados->where('activo', true));
                if ($usuariosActuales->isNotEmpty()) {
                    $query->orWhereIn('id_persona', $usuariosActuales);
                }
            })
            ->with('persona')
            ->orderBy('usuario')
            ->get();
    }

    private function messages(): array
    {
        return [
            'fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ];
    }

    private function syncVisibility(Actividad $actividad, array $validated): void
    {
        $actividad->carreras()->sync($validated['audiencia'] === 'carrera' ? $validated['carreras'] : []);
        $actividad->usuarios()->sync($validated['audiencia'] === 'usuario' ? $validated['usuarios'] : []);
    }

    private function isAdministrator(?User $user): bool
    {
        return $user?->isAdministrator() ?? false;
    }

    private function ensureEnabled(int $idGestion): void
    {
        if (!GestionAcademica::habilitadas()->whereKey($idGestion)->exists()) {
            throw ValidationException::withMessages([
                'id_gestionacademica' => 'La gestión académica seleccionada está deshabilitada.',
            ]);
        }
    }

    private function funcionalidades()
    {
        MenuResourceSync::sync();

        return Recurso::whereNotIn('urlpattern', ['planes/calendario', 'planes/gestiones'])
            ->where('urlpattern', 'not like', 'admin/%')
            ->orderBy('id_recurso')
            ->get();
    }
}
