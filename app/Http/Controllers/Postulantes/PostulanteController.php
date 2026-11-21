<?php

namespace App\Http\Controllers\Postulantes;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Postulante;
use App\Services\Inscripciones\DisponibilidadTurnos;
use App\Services\Postulantes\VentanaPostulacionPublica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PostulanteController extends Controller
{
    /**
     * Recibe los servicios para revisar las fechas de postulación y los cupos de cada grupo.
     * Se reutilizan en las acciones del controlador para mantener las reglas en un solo lugar.
     */
    public function __construct(
        private VentanaPostulacionPublica $ventanaPostulacion,
        private DisponibilidadTurnos $disponibilidadTurnos
    ) {
    }

    /**
     * Muestra la página pública de bienvenida y consulta por CI cuando se envía uno.
     * Primero busca postulaciones de la gestión vigente; si no encuentra, recupera el registro histórico
     * más reciente para precargar los datos en una nueva postulación.
     */
    public function bienvenida(Request $request): View
    {
        $gestionActual = $this->gestionActiva();
        $actividadPostulacion = $this->ventanaPostulacion->actividadAbierta();
        $postulacionAbierta = $actividadPostulacion !== null;

        $tutorialVideoUrl = $this->tutorialVideoUrl();

        // Estos valores permiten mostrar la consulta vacía cuando todavía no se buscó un CI.
        $postulanteBase = null;
        $postulaciones = collect();
        $hayHistorialAnterior = false;

        if ($request->filled('ci_consulta') && $gestionActual && $postulacionAbierta) {
            // Conserva solo letras, números y guiones para comparar el CI de forma consistente.
            $ciLimpio = preg_replace('/[^A-Za-z0-9\-]/', '', $request->input('ci_consulta'));

            // La búsqueda principal se limita a la gestión actual y carga carrera y gestión para la vista.
            $postulaciones = Postulante::with(['carrera', 'gestion'])
                ->where('ci', $ciLimpio)
                ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
                ->orderByDesc('id_postulante')
                ->get();
            $postulanteBase = $postulaciones->first();

            if (! $postulanteBase) {
                // Si no hay una postulación vigente, busca el historial para reutilizar sus datos personales.
                $postulanteBase = $this->buscarPostulanteHistorico($ciLimpio);
                $hayHistorialAnterior = $postulanteBase !== null;
            }
        }

        return view('postulantes.bienvenida', compact(
            'gestionActual',
            'actividadPostulacion',
            'postulacionAbierta',
            'tutorialVideoUrl',
            'postulanteBase',
            'postulaciones',
            'hayHistorialAnterior'
        ));
    }

    /**
     * Prepara el formulario de una nueva postulación.
     * Envía solo carreras con turnos disponibles y, cuando se recibe un CI,
     * excluye las carreras ya cursadas o postuladas por esa persona.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva) {
            return redirect()
                ->route('postulantes.bienvenida')
                ->with('error', 'No hay una gestión académica activa para iniciar el registro.');
        }

        // Calcula los turnos y paralelos disponibles para cada carrera en la gestión vigente.
        $opcionesTurnos = Carrera::activas()
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn (Carrera $carrera): array => [
                $carrera->id_carrera => $this->disponibilidadTurnos->opciones(
                    (int) $carrera->id_carrera,
                    (int) $gestionActiva->id_gestionacademica
                ),
            ])
            ->all();
        // En el selector solo aparecen carreras que tienen al menos una opción de grupo disponible.
        $carreras = Carrera::activas()
            ->whereIn('id_carrera', collect($opcionesTurnos)
                ->filter(fn (array $opciones): bool => $opciones !== [])
                ->keys())
            ->orderBy('nombre')
            ->get();

        $ciBuscado = $request->query('ci_consulta') ?? $request->query('ci');
        $postulanteBase = null;
        $carrerasExcluidasPorHistorial = false;

        if ($ciBuscado && $gestionActiva) {
            $ciLimpio = preg_replace('/[^A-Za-z0-9\-]/', '', $ciBuscado);

            // Busca primero en la gestión vigente y usa el historial solo si aún no hay registro actual.
            $postulanteBase = Postulante::where('ci', $ciLimpio)
                ->where('id_gestionacademica', $gestionActiva->id_gestionacademica)
                ->orderByDesc('id_postulante')
                ->first()
                ?? $this->buscarPostulanteHistorico($ciLimpio);

            // Obtiene las carreras ya asociadas a la persona para evitar postulaciones repetidas.
            $idPersona = DB::table('personas')->where('ci', $ciLimpio)->value('id_persona');
            $carrerasAsociadas = $idPersona
                ? $this->carrerasRegistradasPorPersona((int) $idPersona)
                : collect();
            $carrerasExcluidasPorHistorial = $carrerasAsociadas->isNotEmpty();
            $carrerasYaPostuladas = Postulante::where('ci', $ciLimpio)
                ->where('id_gestionacademica', $gestionActiva->id_gestionacademica)
                ->pluck('id_carrera')
                ->map(fn ($id): int => (int) $id);
            $carrerasBloqueadas = $carrerasAsociadas
                ->merge($carrerasYaPostuladas)
                ->unique();
            $carreras = $carreras
                ->reject(fn (Carrera $carrera): bool => $carrerasBloqueadas->contains((int) $carrera->id_carrera))
                ->values();
        }
        $tutorialVideoUrl = $this->tutorialVideoUrl();

        return view('postulantes.crear', compact(
            'gestionActiva',
            'carreras',
            'opcionesTurnos',
            'carrerasExcluidasPorHistorial',
            'postulanteBase',
            'ciBuscado',
            'tutorialVideoUrl'
        ));
    }

    /**
     * Registra una nueva postulación para la gestión activa.
     * Si no hay una gestión vigente, vuelve a la bienvenida con un mensaje; si el registro resulta correcto,
     * dirige a la ficha imprimible del postulante.
     */
    public function store(Request $request): RedirectResponse
    {
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva) {
            return redirect()
                ->route('postulantes.bienvenida')
                ->with('error', 'No hay una gestión académica activa para registrar postulaciones.');
        }

        $postulante = $this->registrarPostulante($request, $gestionActiva);

        return redirect()
            ->route('postulantes.pdf', $postulante->id_postulante);
    }

    /**
     * Prepara la edición de una postulación de la gestión vigente.
     * También calcula los grupos disponibles y evita mostrar carreras ya inscritas o usadas
     * en otras postulaciones de la misma persona, sin bloquear la carrera de esta ficha.
     */
    public function edit($id): View
    {
        $postulante = Postulante::findOrFail($id);
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva || (int) $postulante->id_gestionacademica !== (int) $gestionActiva->id_gestionacademica) {
            abort(404);
        }

        // Incluye el postulante actual al revisar la disponibilidad para conservar su grupo cuando corresponda.
        $opcionesTurnos = Carrera::activas()
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn (Carrera $carrera): array => [
                $carrera->id_carrera => $this->disponibilidadTurnos->opciones(
                    (int) $carrera->id_carrera,
                    (int) $gestionActiva->id_gestionacademica,
                    (int) $postulante->id_postulante
                ),
            ])
            ->all();
        $idPersona = DB::table('personas')
            ->where('ci', $this->normalizarCi((string) $postulante->ci))
            ->value('id_persona');
        // Las carreras del historial estudiantil y las otras postulaciones no deben duplicarse.
        $carrerasAsociadas = $idPersona
            ? $this->carrerasRegistradasPorPersona((int) $idPersona)
            : collect();
        $otrasPostulaciones = Postulante::where('ci', $postulante->ci)
            ->where('id_gestionacademica', $gestionActiva->id_gestionacademica)
            ->where('id_postulante', '!=', $postulante->id_postulante)
            ->pluck('id_carrera')
            ->map(fn ($id): int => (int) $id);
        $carrerasBloqueadas = $carrerasAsociadas
            ->reject(fn ($id): bool => (int) $id === (int) $postulante->id_carrera)
            ->merge($otrasPostulaciones)
            ->unique();
        $carreras = Carrera::query()
            ->where('activo', true)
            ->orWhere('id_carrera', $postulante->id_carrera)
            ->orderBy('nombre')
            ->get()
            ->reject(fn (Carrera $carrera): bool => $carrerasBloqueadas->contains((int) $carrera->id_carrera))
            ->values();

        return view('postulantes.editar', compact('postulante', 'carreras', 'gestionActiva', 'opcionesTurnos'));
    }

    /**
     * Muestra la ficha imprimible de una postulación.
     * Solo permite consultar registros de la gestión activa y carga sus relaciones para completar el documento.
     */
    public function imprimir(int $id): View
    {
        $postulante = Postulante::with(['carrera', 'gestionAcademica'])->findOrFail($id);
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva || (int) $postulante->id_gestionacademica !== (int) $gestionActiva->id_gestionacademica) {
            abort(404);
        }

        return view('postulantes.pdf', compact('postulante'));
    }

    /**
     * Actualiza una postulación existente de la gestión vigente.
     * Los archivos son opcionales: si no se reemplazan ni se eliminan, se conserva la copia guardada.
     * Al finalizar, dirige a la ficha actualizada para que pueda revisarse o imprimirse.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $postulante = Postulante::findOrFail($id);
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva || (int) $postulante->id_gestionacademica !== (int) $gestionActiva->id_gestionacademica) {
            abort(404);
        }

        $postulante = $this->actualizarPostulante($request, $postulante, $gestionActiva);

        return redirect()
            ->route('postulantes.pdf', $postulante->id_postulante);
    }

    /**
     * Elimina una postulación de la gestión vigente cuando todavía no forma parte de una inscripción activa.
     * Si un estudiante ya depende de esa ficha, conserva el registro y muestra el motivo del rechazo.
     */
    public function destroy(int $id): RedirectResponse
    {
        $postulante = Postulante::findOrFail($id);
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva || (int) $postulante->id_gestionacademica !== (int) $gestionActiva->id_gestionacademica) {
            abort(404);
        }

        // No se debe borrar una ficha que todavía está vinculada al historial activo de un estudiante.
        $inscrito = DB::table('inscrito')
            ->where('id_postulante_origen', $postulante->id_postulante)
            ->where('activo', true)
            ->whereNull('id_inscrito_reemplazo')
            ->exists();

        if ($inscrito) {
            return redirect()
                ->route('postulantes.bienvenida', ['ci_consulta' => $postulante->ci])
                ->with('error', 'No se puede eliminar esta postulación porque forma parte del historial de un estudiante.');
        }

        $ci = $postulante->ci;
        $postulante->delete();

        return redirect()
            ->route('postulantes.bienvenida', ['ci_consulta' => $ci])
            ->with('success', 'La ficha de postulación se eliminó correctamente.');
    }

    /**
     * Valida y registra una postulación nueva junto con sus documentos y datos de pago.
     * Las comprobaciones de cupos y duplicados se repiten dentro de la transacción para evitar
     * que dos envíos simultáneos ocupen el mismo lugar o registren la misma carrera.
     */
    private function registrarPostulante(Request $request, GestionAcademica $gestion): Postulante
    {
        $request->merge(['id_gestionacademica' => $gestion->id_gestionacademica]);
        $ci = $this->normalizarCi((string) $request->input('ci', ''));

        $this->validarCarreraNoRepetida($ci, $request->input('id_carrera'), $gestion);

        $postulanteExistente = Postulante::where('ci', $ci)
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->orderByDesc('id_postulante')
            ->first()
            ?? $this->buscarPostulanteHistorico($ci);

        $datos = $this->validarRegistro($request, $gestion, $postulanteExistente);
        $this->validarVoucherDisponible(
            (string) $datos['deposito'],
            $gestion,
            $postulanteExistente
        );
        $datos = $this->prepararDocumentos($request, $datos, $ci, $gestion, $postulanteExistente);
        $datos['ci'] = $ci;
        $datos['turno'] = (int) $datos['turno'];
        $datos['paralelo'] = mb_strtoupper(trim($datos['paralelo']));
        $datos['diplomabachiller'] = $request->boolean('diplomabachiller');

        return DB::transaction(function () use ($datos, $gestion): Postulante {
            // Bloquea la gestión durante la última validación para reducir conflictos entre registros simultáneos.
            DB::table('gestionacademica')
                ->where('id_gestionacademica', $gestion->id_gestionacademica)
                ->lockForUpdate()
                ->first();
            $this->validarCarreraNoRepetida($datos['ci'], $datos['id_carrera'], $gestion);
            $this->disponibilidadTurnos->validarDisponible(
                (int) $datos['id_carrera'],
                (int) $gestion->id_gestionacademica,
                (int) $datos['turno'],
                (string) $datos['paralelo']
            );
            $this->validarTurnoNoRepetido(
                (string) $datos['ci'],
                (int) $gestion->id_gestionacademica,
                (int) $datos['turno']
            );

            // Crea primero la ficha y luego asigna un código que incluye su identificador generado.
            $postulante = Postulante::create($datos);
            $postulante->update([
                'codigo' => 'POST-'.now()->format('Y').'-'.str_pad(
                    (string) $postulante->id_postulante,
                    5,
                    '0',
                    STR_PAD_LEFT
                ),
            ]);

            // Una nueva postulación inicia en el primer nivel de la carrera.
            $monto = $this->montoInscripcion(
                (int) $postulante->id_carrera,
                (int) $gestion->id_gestionacademica,
                1
            );
            $idPago = DB::table('pago')->insertGetId([
                'concepto' => null,
                'monto' => $monto,
                'pagado' => false,
                'estado' => true,
                'id_postulante' => $postulante->id_postulante,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // La postulación es pública: el comprobante se vincula al postulante, sin asignar un usuario autenticado.
            DB::table('comprobante')->insert([
                'deposito' => $postulante->deposito,
                'fecha' => $postulante->fecha,
                'valido' => false,
                'id_postulante' => $postulante->id_postulante,
                'id_carrera' => $postulante->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // Vincula el detalle del concepto con el pago creado para esta postulación.
            DB::table('detalle')->insert([
                'concepto' => null,
                'codigo' => $postulante->codigo,
                'nombre' => 'Inscripción de postulante',
                'monto' => $monto,
                'id_pago' => $idPago,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $postulante;
        });
    }

    /**
     * Valida y actualiza la información y los documentos de una postulación existente.
     * La operación vuelve a comprobar carrera, turno y cupos dentro de una transacción,
     * y sincroniza el comprobante con los datos que quedaron guardados en la ficha.
     */
    private function actualizarPostulante(
        Request $request,
        Postulante $postulante,
        GestionAcademica $gestion
    ): Postulante {
        $request->merge(['id_gestionacademica' => $gestion->id_gestionacademica]);
        $ci = $this->normalizarCi((string) $request->input('ci', $postulante->ci));

        $this->validarCarreraNoRepetida(
            $ci,
            $request->input('id_carrera'),
            $gestion,
            $postulante
        );

        $datos = $this->validarActualizacion($request, $gestion, $postulante);
        $this->validarVoucherDisponible((string) $datos['deposito'], $gestion, $postulante);
        $datos = $this->prepararDocumentos($request, $datos, $ci, $gestion, $postulante);
        $datos['ci'] = $ci;
        $datos['turno'] = (int) $datos['turno'];
        $datos['paralelo'] = mb_strtoupper(trim($datos['paralelo']));
        $datos['diplomabachiller'] = $request->boolean('diplomabachiller');

        DB::transaction(function () use ($datos, $postulante, $gestion): void {
            // Evita validar cupos con una gestión que pueda cambiar durante la actualización.
            DB::table('gestionacademica')
                ->where('id_gestionacademica', $gestion->id_gestionacademica)
                ->lockForUpdate()
                ->first();
            $this->validarCarreraNoRepetida(
                $datos['ci'],
                $datos['id_carrera'],
                $gestion,
                $postulante
            );
            $this->disponibilidadTurnos->validarDisponible(
                (int) $datos['id_carrera'],
                (int) $gestion->id_gestionacademica,
                (int) $datos['turno'],
                (string) $datos['paralelo'],
                (int) $postulante->id_postulante
            );
            $this->validarTurnoNoRepetido(
                (string) $datos['ci'],
                (int) $gestion->id_gestionacademica,
                (int) $datos['turno'],
                (int) $postulante->id_postulante
            );
            // Guarda la ficha y mantiene el comprobante relacionado con sus datos actualizados.
            $postulante->update($datos);
            DB::table('comprobante')->where('id_postulante', $postulante->id_postulante)->update([
                'deposito' => $postulante->deposito,
                'fecha' => $postulante->fecha,
                'updated_at' => now(),
            ]);
        });

        return $postulante;
    }

    /**
     * Valida los datos para una postulación nueva y exige los documentos que no se pueden conservar.
     * Después verifica que el tamaño combinado de las imágenes no supere el límite permitido.
     */
    private function validarRegistro(
        Request $request,
        GestionAcademica $gestion,
        ?Postulante $postulanteExistente
    ): array {
        $request->merge(['id_gestionacademica' => $gestion->id_gestionacademica]);

        $reglas = $this->reglasComunes();
        $reglas['deposito'] = $this->reglaComprobanteUnico($gestion);
        $reglas += $this->reglasDocumentosRequeridos($postulanteExistente, $request);

        $datos = Validator::make($request->all(), $reglas, [
            'deposito.unique' => 'El número de comprobante/depósito bancario ya ha sido utilizado en esta gestión académica. Debe utilizar un comprobante único y exclusivo por cada postulación.',
        ])->validate();

        $this->validarPesoTotalDocumentos($request);

        return $datos;
    }

    /**
     * Valida una edición: mantiene las reglas de los datos principales, permite omitir archivos
     * que ya existen y no considera duplicado el comprobante de la misma postulación.
     */
    private function validarActualizacion(
        Request $request,
        GestionAcademica $gestion,
        Postulante $postulante
    ): array {
        $request->merge(['id_gestionacademica' => $gestion->id_gestionacademica]);

        $reglas = $this->reglasComunes($postulante->id_carrera);
        $reglas['deposito'] = $this->reglaComprobanteUnico($gestion, $postulante);
        $reglas += $this->reglasDocumentosOpcionales();

        $datos = Validator::make($request->all(), $reglas, [
            'deposito.unique' => 'El número de comprobante/depósito bancario ya se encuentra registrado por otro postulante en esta gestión académica.',
        ])->validate();

        $this->validarPesoTotalDocumentos($request);

        return $datos;
    }

    /**
     * Define las reglas compartidas para los campos personales, escolares y de admisión.
     * En una edición permite conservar la carrera actual aunque ya no esté activa;
     * para una postulación nueva solo acepta carreras activas.
     */
    private function reglasComunes(?int $idCarreraActual = null): array
    {
        $reglaCarreraValida = Rule::exists('carreras', 'id_carrera')
            ->where(function ($consulta) use ($idCarreraActual): void {
                $consulta->where('activo', true);

                if ($idCarreraActual !== null) {
                    $consulta->orWhere('id_carrera', $idCarreraActual);
                }
            });

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'primerapellido' => ['required', 'string', 'max:100'],
            'segundoapellido' => ['nullable', 'string', 'max:100'],
            'ci' => ['required', 'string', 'max:30'],
            'lugarexpedicion' => ['required', 'string', 'max:20'],
            'fechanacimiento' => ['required', 'date'],
            'lugarnacimiento' => ['required', 'string', 'max:255'],
            'nacionalidad' => ['required', 'string', 'max:100'],
            'sexo' => ['required', 'in:M,F'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'celular' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'nombrecontacto' => ['required', 'string', 'max:255'],
            'celularcontacto' => ['required', 'string', 'max:255'],
            'parentescocontacto' => ['required', 'string', 'max:255'],
            'nombrecolegio' => ['required', 'string', 'max:255'],
            'caractercolegio' => ['required', 'in:Fiscal,Particular,Convenio'],
            'egresocolegio' => ['required', 'integer', 'between:1900,2100'],
            'id_gestionacademica' => ['required', 'exists:gestionacademica,id_gestionacademica'],
            'id_carrera' => ['required', $reglaCarreraValida],
            'turno' => ['required', 'integer', 'in:1,2,3'],
            'paralelo' => ['required', 'string', 'max:20'],
            'diplomabachiller' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Impide que la misma persona tenga más de una postulación en el mismo turno
     * durante una gestión. En una edición, excluye la ficha que se está modificando.
     */
    private function validarTurnoNoRepetido(
        string $ci,
        int $gestionId,
        int $turno,
        ?int $idPostulanteExcluir = null
    ): void {
        $postulaciones = Postulante::where('ci', $ci)
            ->where('id_gestionacademica', $gestionId)
            ->where('turno', $turno);

        if ($idPostulanteExcluir !== null) {
            $postulaciones->where('id_postulante', '!=', $idPostulanteExcluir);
        }

        if ($postulaciones->exists()) {
            throw ValidationException::withMessages([
                'turno' => 'La persona ya tiene una postulación en ese turno para la gestión vigente.',
            ]);
        }
    }

    /**
     * Construye la regla que exige un comprobante único dentro de la gestión seleccionada.
     * Si se está editando una ficha, ignora el comprobante de esa misma ficha.
     */
    private function reglaComprobanteUnico(
        GestionAcademica $gestion,
        ?Postulante $postulanteActual = null
    ): array {
        $regla = Rule::unique('postulante', 'deposito')
            ->where(fn ($consulta) => $consulta->where('id_gestionacademica', $gestion->id_gestionacademica));

        if ($postulanteActual) {
            $regla->ignore($postulanteActual->id_postulante, 'id_postulante');
        }

        return ['required', 'string', 'max:255', $regla];
    }

    /**
     * Define validaciones para los documentos obligatorios al registrar.
     * Un archivo existente puede conservarse; si se pidió eliminarlo, vuelve a ser obligatorio.
     */
    private function reglasDocumentosRequeridos(
        ?Postulante $postulanteExistente,
        Request $request
    ): array
    {
        // Relaciona cada campo de carga con el atributo del postulante y su tamaño máximo en KB.
        $documentos = [
            'fotografia' => ['foto', 150],
            'path_ci_frontal' => ['path_ci_frontal', 350],
            'path_ci_reverso' => ['path_ci_reverso', 350],
            'path_nacimiento' => ['path_nacimiento', 350],
            'path_diploma' => ['path_diploma', 350],
        ];
        $reglas = [];

        foreach ($documentos as $campo => [$atributo, $maximo]) {
            $imagenExistente = $postulanteExistente?->$atributo;
            // Solo se considera disponible el archivo guardado si no se marcó para eliminar.
            $imagenSeConserva = $imagenExistente && ! $request->boolean('eliminar_'.$atributo);
            $reglas[$campo] = [
                $imagenSeConserva ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png',
                "max:{$maximo}",
            ];
        }

        return $reglas + $this->reglasEliminacionDocumentos();
    }

    /**
     * Define formatos y tamaños de los archivos que pueden reemplazarse durante una edición.
     * Todos son opcionales porque la persona puede conservar las copias actuales.
     */
    private function reglasDocumentosOpcionales(): array
    {
        return [
            'fotografia' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:150'],
            'path_ci_frontal' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:350'],
            'path_ci_reverso' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:350'],
            'path_nacimiento' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:350'],
            'path_diploma' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:350'],
        ] + $this->reglasEliminacionDocumentos();
    }

    /**
     * Acepta las marcas enviadas por la vista para indicar qué archivos existentes se quitarán.
     */
    private function reglasEliminacionDocumentos(): array
    {
        return [
            'eliminar_foto' => ['nullable', 'boolean'],
            'eliminar_path_ci_frontal' => ['nullable', 'boolean'],
            'eliminar_path_ci_reverso' => ['nullable', 'boolean'],
            'eliminar_path_nacimiento' => ['nullable', 'boolean'],
            'eliminar_path_diploma' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Suma el tamaño de las imágenes recibidas y rechaza el envío si supera 1.550 KB.
     * Los campos sin archivo nuevo aportan cero al total.
     */
    private function validarPesoTotalDocumentos(Request $request): void
    {
        $camposArchivo = [
            'fotografia',
            'path_ci_frontal',
            'path_ci_reverso',
            'path_nacimiento',
            'path_diploma',
        ];

        $pesoTotal = collect($camposArchivo)
            ->sum(fn (string $campo) => $request->file($campo)?->getSize() ?? 0);

        if ($pesoTotal > 1550 * 1024) {
            throw ValidationException::withMessages([
                'documentos' => 'El conjunto de imágenes supera el límite total de 1.550 KB. Vuelve a seleccionar imágenes más pequeñas.',
            ]);
        }
    }

    /**
     * Limpia el CI para usarlo en búsquedas y comparaciones, manteniendo letras, números y guiones.
     * Rechaza valores vacíos o compuestos únicamente por guiones.
     */
    private function normalizarCi(string $ci): string
    {
        $ciLimpio = preg_replace('/[^A-Za-z0-9\\-]/', '', $ci) ?? '';

        if ($ciLimpio === '' || trim($ciLimpio, '-') === '') {
            throw ValidationException::withMessages([
                'ci' => 'Ingrese un C.I. válido con letras o números.',
            ]);
        }

        return $ciLimpio;
    }

    /**
     * Comprueba que el depósito no esté asociado a otro comprobante de la misma gestión.
     * La comparación no distingue mayúsculas y, al editar, excluye el comprobante propio.
     */
    private function validarVoucherDisponible(
        string $voucher,
        GestionAcademica $gestion,
        ?Postulante $postulanteActual = null
    ): void {
        $consulta = DB::table('comprobante')
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->whereRaw('LOWER(deposito) = ?', [mb_strtolower(trim($voucher))]);

        if ($postulanteActual) {
            $consulta->where(function ($subconsulta) use ($postulanteActual): void {
                $subconsulta->whereNull('id_postulante')
                    ->orWhere('id_postulante', '!=', $postulanteActual->id_postulante);
            });
        }

        if ($consulta->exists()) {
            throw ValidationException::withMessages([
                'deposito' => 'Este comprobante ya está registrado en la gestión seleccionada.',
            ]);
        }
    }

    /**
     * Evita registrar dos veces la misma carrera en una gestión y bloquea carreras
     * que ya forman parte del historial estudiantil de la persona.
     * Al editar, excluye la ficha actual para permitir conservar su misma carrera.
     */
    private function validarCarreraNoRepetida(
        string $ci,
        mixed $carreraId,
        GestionAcademica $gestion,
        ?Postulante $postulanteActual = null
    ): void {
        $consulta = Postulante::where('ci', $ci)
            ->where('id_carrera', $carreraId)
            ->where('id_gestionacademica', $gestion->id_gestionacademica);

        if ($postulanteActual) {
            $consulta->where('id_postulante', '!=', $postulanteActual->id_postulante);
        }

        if (! $consulta->exists()) {
            // Esta condición permite guardar sin cambios la misma carrera y el mismo CI al editar.
            $edicionConMismaIdentidadYCarrera = $postulanteActual
                && $this->normalizarCi((string) $postulanteActual->ci) === $ci
                && (int) $postulanteActual->id_carrera === (int) $carreraId;
            if (! $edicionConMismaIdentidadYCarrera && $this->tieneHistorialEstudiantilEnCarrera($ci, $carreraId)) {
                $carrera = Carrera::where('id_carrera', $carreraId)->first();
                $nombreCarrera = $carrera ? $carrera->nombre : 'la carrera seleccionada';

                throw ValidationException::withMessages([
                    'id_carrera' => "El estudiante con C.I. {$ci} ya tiene historial de inscripción en {$nombreCarrera} y no puede volver a postularse a esa carrera.",
                ]);
            }

            return;
        }

        if (! $postulanteActual) {
            $carrera = Carrera::where('id_carrera', $carreraId)->first();
            $nombreCarrera = $carrera ? $carrera->nombre : 'la carrera seleccionada';
            $mensaje = "El estudiante con C.I. {$ci} ya se encuentra registrado en {$nombreCarrera} para esta gestión académica. No está permitido registrarse dos veces en la misma carrera.";
        } else {
            $mensaje = "El estudiante con C.I. {$ci} ya se encuentra registrado en esta carrera para esta gestión académica. No está permitido registrarse dos veces en la misma carrera.";
        }

        throw ValidationException::withMessages(['id_carrera' => $mensaje]);
    }

    /**
     * Busca si la persona ya tiene una inscripción estudiantil en la carrera indicada.
     * Primero valida el identificador de carrera y luego relaciona el CI con su persona.
     */
    private function tieneHistorialEstudiantilEnCarrera(string $ci, mixed $carreraId): bool
    {
        if (! is_scalar($carreraId) || ! ctype_digit((string) $carreraId)) {
            return false;
        }

        $idPersona = DB::table('personas')->where('ci', $ci)->value('id_persona');
        if (! $idPersona) {
            return false;
        }

        return $this->carrerasRegistradasPorPersona((int) $idPersona)
            ->contains((int) $carreraId);
    }

    /**
     * Reúne las carreras vinculadas a la persona desde sus registros de estudiante,
     * cursadas e inscripciones, y devuelve identificadores únicos.
     */
    private function carrerasRegistradasPorPersona(int $idPersona): \Illuminate\Support\Collection
    {
        return DB::table('estudiante')
            ->where('id_persona', $idPersona)
            ->pluck('id_carrera')
            ->merge(DB::table('cursa')->where('id_persona', $idPersona)->pluck('id_carrera'))
            ->merge(DB::table('inscrito')->where('id_persona', $idPersona)->pluck('id_carrera'))
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Prepara los documentos antes de guardar la postulación.
     * Da prioridad al archivo nuevo, luego a la solicitud de eliminación y, si no hay ninguna,
     * conserva el documento que ya estaba asociado al postulante.
     */
    private function prepararDocumentos(
        Request $request,
        array $datos,
        string $ci,
        GestionAcademica $gestion,
        ?Postulante $postulanteExistente
    ): array {
        // Separa los archivos por año y gestión para facilitar su organización en el almacenamiento.
        $directorio = 'postulantes/'.date('Y').'_G'.$gestion->id_gestionacademica;
        $documentos = [
            'fotografia' => ['foto', "{$directorio}/{$ci}_foto"],
            'path_ci_frontal' => ['path_ci_frontal', "{$directorio}/{$ci}_ci_frontal"],
            'path_ci_reverso' => ['path_ci_reverso', "{$directorio}/{$ci}_ci_reverso"],
            'path_nacimiento' => ['path_nacimiento', "{$directorio}/{$ci}_nacimiento"],
            'path_diploma' => ['path_diploma', "{$directorio}/{$ci}_diploma"],
        ];

        foreach ($documentos as $campo => [$atributo, $rutaBase]) {
            if ($request->hasFile($campo)) {
                // Un archivo nuevo reemplaza la ruta guardada por una copia con nombre único.
                $datos[$atributo] = $this->guardarImagen($request->file($campo), $rutaBase);
            } elseif ($request->boolean('eliminar_'.$atributo)) {
                // Si no se subió un reemplazo y se pidió quitarlo, guarda el atributo vacío.
                $datos[$atributo] = null;
            } elseif ($postulanteExistente) {
                // En los demás casos se conserva el documento de la ficha previa.
                $datos[$atributo] = $postulanteExistente->$atributo;
            }

            unset($datos['eliminar_'.$atributo]);
        }

        unset($datos['fotografia']);

        return $datos;
    }

    /**
     * Guarda una imagen JPG o PNG en el disco público con un nombre único.
     * Si el formato no es compatible o el almacenamiento falla, informa el error en vez de guardar una ruta inválida.
     */
    private function guardarImagen($archivo, string $rutaBase): string
    {
        // La extensión se determina por el tipo real del archivo, no por el nombre recibido del navegador.
        $extension = match ($archivo->getMimeType()) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            default => throw ValidationException::withMessages([
                'documentos' => 'Solo se aceptan imágenes JPG o PNG.',
            ]),
        };

        // El UUID evita que una carga nueva sobrescriba el archivo de otro postulante o una copia anterior.
        $ruta = $archivo->storeAs(
            dirname($rutaBase),
            basename($rutaBase).'-'.Str::uuid().'.'.$extension,
            'public'
        );

        if (! $ruta) {
            throw new \RuntimeException('No fue posible guardar una de las imágenes del postulante.');
        }

        return $ruta;
    }

    /**
     * Devuelve la gestión marcada como vigente y activa con el identificador más alto.
     * Si no hay una, devuelve null para que las acciones públicas puedan detener el registro.
     */
    private function gestionActiva(): ?GestionAcademica
    {
        return GestionAcademica::where('vigente', true)
            ->where('activo', true)
            ->orderByDesc('id_gestionacademica')
            ->first();
    }

    /**
     * Busca la postulación anterior más reciente para el CI indicado.
     * Ordena por gestión, periodo e identificador para elegir un registro cuando hay varios.
     */
    private function buscarPostulanteHistorico(string $ci): ?Postulante
    {
        return Postulante::query()
            ->leftJoin('gestionacademica as g', 'g.id_gestionacademica', '=', 'postulante.id_gestionacademica')
            ->where('postulante.ci', $ci)
            ->orderByDesc('g.gestion')
            ->orderByDesc('g.periodo')
            ->orderByDesc('postulante.id_postulante')
            ->select('postulante.*')
            ->first();
    }

    /**
     * Devuelve la dirección pública del video de guía si el archivo está instalado.
     * Si no está disponible, devuelve null para que la vista no presente un enlace roto.
     */
    private function tutorialVideoUrl(): ?string
    {
        return is_file(public_path('videos/guia_inscripcion.mp4'))
            ? url('/videos/guia_inscripcion.mp4')
            : null;
    }
}
