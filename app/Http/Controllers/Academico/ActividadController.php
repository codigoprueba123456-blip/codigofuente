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
    /**
     * Carga gestiones para los filtros y consulta las actividades ordenadas por fecha de inicio.
     * Aplica coincidencia parcial por nombre y, salvo para administradores, filtra por audiencia
     * usando el scope del modelo antes de entregar ambos conjuntos a la vista.
     */
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

    /**
     * Prepara el alta limitando las gestiones a las habilitadas y las carreras a las activas.
     * También obtiene usuarios elegibles y recursos de menú que el calendario puede habilitar.
     */
    public function create(Request $request)
    {
        $gestiones = GestionAcademica::habilitadas()->orderByDesc('gestion')->orderBy('periodo')->get();
        $gestionSeleccionada = $request->input('id_gestionacademica');
        $carreras = Carrera::activas()->orderBy('nombre')->get();
        $usuarios = $this->usuariosDisponibles();
        $funcionalidades = $this->funcionalidades();

        return view('gestiones.calendario.create', compact('gestiones', 'gestionSeleccionada', 'carreras', 'usuarios', 'funcionalidades'));
    }

    /**
     * Valida fechas, gestión, audiencia y recursos antes de crear la actividad.
     * Guarda el registro, sus vínculos de audiencia y sus funcionalidades juntos para evitar
     * que una falla deje la actividad creada con permisos incompletos.
     */
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

    /**
     * Busca actividad y relaciones actuales, y bloquea la edición si su gestión no está habilitada.
     * Conserva carreras, usuarios y funcionalidades existentes en las opciones del formulario,
     * incluso si una opción dejó de estar disponible para nuevas asignaciones.
     */
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
        $idsFuncionalidadesDisponibles = $funcionalidades->pluck('id_recurso');
        $funcionalidadesSeleccionadas = $actividad->funcionalidades
            ->whereIn('id_recurso', $idsFuncionalidadesDisponibles)
            ->pluck('id_recurso')
            ->all();
        if ($funcionalidadesSeleccionadas === []
            && $actividad->funcionalidad !== null
            && $idsFuncionalidadesDisponibles->contains($actividad->funcionalidad)) {
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

    /**
     * Valida la actividad y comprueba tanto la gestión de origen como la de destino.
     * En una transacción actualiza los datos principales, limpia el vínculo de funcionalidad heredado
     * y sincroniza los vínculos actuales de audiencia y recursos.
     */
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

    /**
     * Cambia únicamente el indicador activo de la actividad.
     * Preserva audiencia, fechas y funcionalidades, y vuelve a la lista filtrada por su gestión.
     */
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

    /**
     * Centraliza la validación del formulario de alta y edición.
     * Acepta carreras y usuarios actuales aunque ya no cumplan la regla para nuevas selecciones,
     * pero excluye de las funcionalidades las rutas de reportes y del portal estudiantil.
     */
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
            // El calendario no habilita pantallas de reportes ni rutas del portal de estudiantes.
            'funcionalidades.*' => [
                'integer',
                'distinct',
                Rule::exists('recurso', 'id_recurso')
                    ->where(fn ($query) => $query
                        ->where('urlpattern', 'not like', 'reportes/%')
                        ->where('urlpattern', 'not like', 'estudiantes/%')
                        ->where('urlpattern', 'not like', '%dashboard%')),
            ],
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

    /**
     * Devuelve usuarios ordenados por username para el selector de audiencia específica.
     * Excluye empleados inactivos en altas, pero conserva a quienes ya estaban ligados al editar.
     */
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

    /**
     * Proporciona mensajes legibles para reglas que no deben mostrar el texto técnico de Laravel.
     * Actualmente explica el error cuando la fecha final precede a la fecha inicial.
     */
    private function messages(): array
    {
        return [
            'fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ];
    }

    /**
     * Persiste los vínculos de audiencia que determinan quién puede ver la actividad.
     * Solo conserva carreras para audiencia por carrera, solo usuarios para audiencia individual
     * y elimina ambos conjuntos cuando la actividad es general.
     */
    private function syncVisibility(Actividad $actividad, array $validated): void
    {
        // La audiencia es excluyente: general no guarda vínculos, por carrera sincroniza solo carreras
        // y por usuario solo usuarios. sync([]) elimina vínculos que hayan quedado de una edición previa.
        $actividad->carreras()->sync($validated['audiencia'] === 'carrera' ? $validated['carreras'] : []);
        $actividad->usuarios()->sync($validated['audiencia'] === 'usuario' ? $validated['usuarios'] : []);
    }

    /**
     * Delega al modelo User la comprobación de privilegio administrativo.
     * Un usuario nulo o no autenticado se considera no administrador.
     */
    private function isAdministrator(?User $user): bool
    {
        return $user?->isAdministrator() ?? false;
    }

    /**
     * Comprueba que la gestión exista y esté activa y vigente antes de usarla.
     * En caso contrario agrega un error de validación al campo de gestión del formulario.
     */
    private function ensureEnabled(int $idGestion): void
    {
        // La gestión debe estar activa y vigente para crear, editar o mover actividades dentro de ella.
        if (!GestionAcademica::habilitadas()->whereKey($idGestion)->exists()) {
            throw ValidationException::withMessages([
                'id_gestionacademica' => 'La gestión académica seleccionada está deshabilitada.',
            ]);
        }
    }

    /**
     * Actualiza primero el catálogo desde el menú y luego selecciona recursos válidos para calendario.
     * Omite rutas administrativas, de reportes, dashboard, calendario/gestiones y portal estudiantil,
     * para que una actividad no exponga secciones fuera de este flujo.
     */
    private function funcionalidades()
    {
        MenuResourceSync::sync();

        return Recurso::whereNotIn('urlpattern', ['planes/calendario', 'planes/gestiones'])
            ->where('urlpattern', 'not like', 'admin/%')
            ->where('urlpattern', 'not like', 'reportes/%')
            ->where('urlpattern', 'not like', 'estudiantes/%')
            ->where('urlpattern', 'not like', '%dashboard%')
            ->orderBy('id_recurso')
            ->get();
    }
}
