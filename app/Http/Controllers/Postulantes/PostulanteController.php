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
    public function __construct(
        private VentanaPostulacionPublica $ventanaPostulacion,
        private DisponibilidadTurnos $disponibilidadTurnos
    ) {
    }

    public function bienvenida(Request $request): View
    {
        $gestionActual = $this->gestionActiva();
        $actividadPostulacion = $this->ventanaPostulacion->actividadAbierta();
        $postulacionAbierta = $actividadPostulacion !== null;

        $tutorialVideoUrl = $this->tutorialVideoUrl();

        // Manejo de la búsqueda por CI dentro de la pantalla de bienvenida (Delimitado a la gestión activa)
        $postulanteBase = null;
        $postulaciones = collect();
        $hayHistorialAnterior = false;

        if ($request->filled('ci_consulta') && $gestionActual && $postulacionAbierta) {
            $ciLimpio = preg_replace('/[^A-Za-z0-9\-]/', '', $request->input('ci_consulta'));

            $postulaciones = Postulante::with(['carrera', 'gestion'])
                ->where('ci', $ciLimpio)
                ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
                ->orderByDesc('id_postulante')
                ->get();
            $postulanteBase = $postulaciones->first();

            if (! $postulanteBase) {
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

    public function create(Request $request): View|RedirectResponse
    {
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva) {
            return redirect()
                ->route('postulantes.bienvenida')
                ->with('error', 'No hay una gestión académica activa para iniciar el registro.');
        }

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

            $postulanteBase = Postulante::where('ci', $ciLimpio)
                ->where('id_gestionacademica', $gestionActiva->id_gestionacademica)
                ->orderByDesc('id_postulante')
                ->first()
                ?? $this->buscarPostulanteHistorico($ciLimpio);

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
     * Muestra el formulario para editar a un postulante existente.
     */
    public function edit($id): View
    {
        $postulante = Postulante::findOrFail($id);
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva || (int) $postulante->id_gestionacademica !== (int) $gestionActiva->id_gestionacademica) {
            abort(404);
        }

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
     * Actualiza los datos del postulante y procesa archivos opcionales.
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

    public function destroy(int $id): RedirectResponse
    {
        $postulante = Postulante::findOrFail($id);
        $gestionActiva = $this->gestionActiva();

        if (! $gestionActiva || (int) $postulante->id_gestionacademica !== (int) $gestionActiva->id_gestionacademica) {
            abort(404);
        }

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

            $postulante = Postulante::create($datos);
            $postulante->update([
                'codigo' => 'POST-'.now()->format('Y').'-'.str_pad(
                    (string) $postulante->id_postulante,
                    5,
                    '0',
                    STR_PAD_LEFT
                ),
            ]);

            $monto = (float) (DB::table('carreras as c')
                ->join('instituto as i', 'i.id_instituto', '=', 'c.id_instituto')
                ->where('c.id_carrera', $postulante->id_carrera)
                ->value(DB::raw('COALESCE(c.creditajematricula, 0) * COALESCE(i.preciocredito, 0)')) ?? 0);
            $idPago = DB::table('pago')->insertGetId([
                'concepto' => null,
                'monto' => $monto,
                'pagado' => false,
                'estado' => true,
                'id_postulante' => $postulante->id_postulante,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('comprobante')->insert([
                'deposito' => $postulante->deposito,
                'fecha' => $postulante->fecha,
                'valido' => false,
                'id_postulante' => $postulante->id_postulante,
                'id_usuario' => auth()->id(),
                'id_carrera' => $postulante->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
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
            $postulante->update($datos);
            DB::table('comprobante')->where('id_postulante', $postulante->id_postulante)->update([
                'deposito' => $postulante->deposito,
                'fecha' => $postulante->fecha,
                'updated_at' => now(),
            ]);
        });

        return $postulante;
    }

    private function validarRegistro(
        Request $request,
        GestionAcademica $gestion,
        ?Postulante $postulanteExistente
    ): array {
        $request->merge(['id_gestionacademica' => $gestion->id_gestionacademica]);

        $rules = $this->reglasComunes();
        $rules['deposito'] = $this->reglaComprobanteUnico($gestion);
        $rules += $this->reglasDocumentosRequeridos($postulanteExistente, $request);

        $datos = Validator::make($request->all(), $rules, [
            'deposito.unique' => 'El número de comprobante/depósito bancario ya ha sido utilizado en esta gestión académica. Debe utilizar un comprobante único y exclusivo por cada postulación.',
        ])->validate();

        $this->validarPesoTotalDocumentos($request);

        return $datos;
    }

    private function validarActualizacion(
        Request $request,
        GestionAcademica $gestion,
        Postulante $postulante
    ): array {
        $request->merge(['id_gestionacademica' => $gestion->id_gestionacademica]);

        $rules = $this->reglasComunes($postulante->id_carrera);
        $rules['deposito'] = $this->reglaComprobanteUnico($gestion, $postulante);
        $rules += $this->reglasDocumentosOpcionales();

        $datos = Validator::make($request->all(), $rules, [
            'deposito.unique' => 'El número de comprobante/depósito bancario ya se encuentra registrado por otro postulante en esta gestión académica.',
        ])->validate();

        $this->validarPesoTotalDocumentos($request);

        return $datos;
    }

    private function reglasComunes(?int $carreraActualId = null): array
    {
        $carreraActiva = Rule::exists('carreras', 'id_carrera')
            ->where(function ($query) use ($carreraActualId): void {
                $query->where('activo', true);

                if ($carreraActualId !== null) {
                    $query->orWhere('id_carrera', $carreraActualId);
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
            'id_carrera' => ['required', $carreraActiva],
            'turno' => ['required', 'integer', 'in:1,2,3'],
            'paralelo' => ['required', 'string', 'max:20'],
            'diplomabachiller' => ['nullable', 'boolean'],
        ];
    }

    private function validarTurnoNoRepetido(
        string $ci,
        int $gestionId,
        int $turno,
        ?int $postulanteExcluir = null
    ): void {
        $postulaciones = Postulante::where('ci', $ci)
            ->where('id_gestionacademica', $gestionId)
            ->where('turno', $turno);

        if ($postulanteExcluir !== null) {
            $postulaciones->where('id_postulante', '!=', $postulanteExcluir);
        }

        if ($postulaciones->exists()) {
            throw ValidationException::withMessages([
                'turno' => 'La persona ya tiene una postulación en ese turno para la gestión vigente.',
            ]);
        }
    }

    private function reglaComprobanteUnico(
        GestionAcademica $gestion,
        ?Postulante $postulanteActual = null
    ): array {
        $regla = Rule::unique('postulante', 'deposito')
            ->where(fn ($query) => $query->where('id_gestionacademica', $gestion->id_gestionacademica));

        if ($postulanteActual) {
            $regla->ignore($postulanteActual->id_postulante, 'id_postulante');
        }

        return ['required', 'string', 'max:255', $regla];
    }

    private function reglasDocumentosRequeridos(
        ?Postulante $postulanteExistente,
        Request $request
    ): array
    {
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

    private function validarVoucherDisponible(
        string $voucher,
        GestionAcademica $gestion,
        ?Postulante $postulanteActual = null
    ): void {
        $consulta = DB::table('comprobante')
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->whereRaw('LOWER(deposito) = ?', [mb_strtolower(trim($voucher))]);

        if ($postulanteActual) {
            $consulta->where(function ($query) use ($postulanteActual): void {
                $query->whereNull('id_postulante')
                    ->orWhere('id_postulante', '!=', $postulanteActual->id_postulante);
            });
        }

        if ($consulta->exists()) {
            throw ValidationException::withMessages([
                'deposito' => 'Este comprobante ya está registrado en la gestión seleccionada.',
            ]);
        }
    }

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

    private function prepararDocumentos(
        Request $request,
        array $datos,
        string $ci,
        GestionAcademica $gestion,
        ?Postulante $postulanteExistente
    ): array {
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
                $datos[$atributo] = $this->guardarImagen($request->file($campo), $rutaBase);
            } elseif ($request->boolean('eliminar_'.$atributo)) {
                $datos[$atributo] = null;
            } elseif ($postulanteExistente) {
                $datos[$atributo] = $postulanteExistente->$atributo;
            }

            unset($datos['eliminar_'.$atributo]);
        }

        unset($datos['fotografia']);

        return $datos;
    }

    private function guardarImagen($archivo, string $rutaBase): string
    {
        $extension = match ($archivo->getMimeType()) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            default => throw ValidationException::withMessages([
                'documentos' => 'Solo se aceptan imágenes JPG o PNG.',
            ]),
        };

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

    private function gestionActiva(): ?GestionAcademica
    {
        return GestionAcademica::where('vigente', true)
            ->where('activo', true)
            ->orderByDesc('id_gestionacademica')
            ->first();
    }

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

    private function tutorialVideoUrl(): ?string
    {
        return is_file(public_path('videos/guia_inscripcion.mp4'))
            ? url('/videos/guia_inscripcion.mp4')
            : null;
    }
}
