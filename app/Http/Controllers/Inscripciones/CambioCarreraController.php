<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CambioCarreraController extends Controller
{
    /**
     * Prepara el formulario con las carreras habilitadas para recibir un cambio.
     */
    public function index()
    {
        $carreras = Carrera::activas()
            ->orderBy('nombre')
            ->get(['id_carrera', 'nombre', 'resolucionministerial1', 'resolucionministerial2', 'resolucionministerial3']);

        return view('Inscripciones.cambio_carrera.index', compact('carreras'));
    }

    /**
     * Busca estudiantes por CI o nombre y devuelve sus inscripciones activas para elegir cuál cambiar.
     * La respuesta incluye el comprobante y si la inscripción ya tiene materias asignadas.
     */
    public function buscarEstudiantes(Request $request)
    {
        $termino = trim((string) $request->query('q', ''));
        if (mb_strlen($termino) < 2) {
            return response()->json([]);
        }

        $palabras = preg_split('/\s+/', $termino, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $estudiantes = DB::table('estudiante as e')
            ->join('personas as p', 'p.id_persona', '=', 'e.id_persona')
            ->where(function ($query) use ($palabras): void {
                foreach ($palabras as $palabra) {
                    $query->where(function ($subquery) use ($palabra): void {
                        foreach (['p.ci', 'p.nombre', 'p.primerapellido', 'p.segundoapellido'] as $columna) {
                            $subquery->orWhere($columna, 'like', '%'.$palabra.'%');
                        }
                    });
                }
            })
            ->orderBy('p.primerapellido')
            ->orderBy('p.segundoapellido')
            ->orderBy('p.nombre')
            ->limit(20)
            ->get([
                'e.id_persona',
                'p.ci',
                'p.nombre',
                'p.primerapellido',
                'p.segundoapellido',
                'p.email',
                'p.celular',
            ])
            ->map(function ($estudiante): array {
                $consultaInscripciones = DB::table('inscrito as i')
                    ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
                    ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
                    ->where('i.id_persona', $estudiante->id_persona)
                    ->where('i.activo', true)
                    ->whereNull('i.id_inscrito_reemplazo');
                $carrerasInscritas = (clone $consultaInscripciones)
                    ->distinct()
                    ->orderBy('i.id_carrera')
                    ->pluck('i.id_carrera')
                    ->map(fn ($id): int => (int) $id)
                    ->all();
                $gestionMasReciente = $this->gestionMasReciente((int) $estudiante->id_persona);

                if ($gestionMasReciente) {
                    $consultaInscripciones
                        ->where('i.id_gestionacademica', $gestionMasReciente->id_gestionacademica);
                }

                $inscripciones = $consultaInscripciones
                    ->orderByDesc('ga.gestion')
                    ->orderByDesc('ga.periodo')
                    ->orderBy('c.nombre')
                    ->get([
                        'i.id_inscrito',
                        'i.id_carrera',
                        'i.id_gestionacademica',
                        'i.deposito',
                        'c.nombre as carrera',
                        'ga.gestion',
                        'ga.periodo',
                    ])
                    ->map(function ($inscripcion): array {
                        $deposito = trim((string) ($inscripcion->deposito ?? ''));
                        if ($deposito === '') {
                            $deposito = (string) (DB::table('comprobante')
                                ->where('id_inscrito', $inscripcion->id_inscrito)
                                ->where('activo', true)
                                ->orderByDesc('id_comprobante')
                                ->value('deposito') ?? '');
                        }

                        return [
                            'id' => (int) $inscripcion->id_inscrito,
                            'id_carrera' => (int) $inscripcion->id_carrera,
                            'id_gestionacademica' => (int) $inscripcion->id_gestionacademica,
                            'carrera' => $inscripcion->carrera,
                            'gestion' => $inscripcion->gestion,
                            'periodo' => $inscripcion->periodo,
                            'tiene_materias' => DB::table('inscrito_materia')
                                ->where('id_inscrito', $inscripcion->id_inscrito)
                                ->exists(),
                            'deposito' => $deposito,
                        ];
                    })
                    ->all();

                return [
                    'id' => (int) $estudiante->id_persona,
                    'ci' => $estudiante->ci,
                    'nombre' => trim(implode(' ', array_filter([
                        $estudiante->nombre,
                        $estudiante->primerapellido,
                        $estudiante->segundoapellido,
                    ]))),
                    'carreras_inscritas' => $carrerasInscritas,
                    'email' => $estudiante->email ?? '',
                    'celular' => $estudiante->celular ?? '',
                    'inscripciones' => $inscripciones,
                ];
            });

        return response()->json($estudiantes);
    }

    /**
     * Reemplaza una inscripción reciente por otra carrera sin crear un pago nuevo.
     * Valida que el estudiante, la inscripción de origen y la carrera destino sean compatibles,
     * y guarda el cambio dentro de una transacción para conservar juntos sus registros relacionados.
     */
    public function store(Request $request)
    {
        $request->merge([
            'becado' => $this->normalizarBooleano($request->input('becado')),
        ]);

        $validated = $request->validate([
            'id_persona' => 'required|integer|exists:estudiante,id_persona',
            'id_inscrito_origen' => 'required|integer|exists:inscrito,id_inscrito',
            'fecha' => 'required|date',
            'becado' => 'required|boolean',
            'id_carrera' => [
                'required',
                'integer',
                Rule::exists('carreras', 'id_carrera')->where('activo', true),
            ],
        ]);

        // Bloquea al estudiante y la inscripción durante la operación para evitar reemplazos simultáneos.
        DB::transaction(function () use ($validated): void {
            $estudiante = DB::table('estudiante')
                ->where('id_persona', (int) $validated['id_persona'])
                ->lockForUpdate()
                ->first(['id_persona']);

            if (! $estudiante) {
                throw ValidationException::withMessages([
                    'id_persona' => 'No se encontró el estudiante seleccionado.',
                ]);
            }

            $inscripcionOrigen = DB::table('inscrito')
                ->where('id_inscrito', (int) $validated['id_inscrito_origen'])
                ->where('id_persona', (int) $validated['id_persona'])
                ->where('activo', true)
                ->whereNull('id_inscrito_reemplazo')
                ->lockForUpdate()
                ->first(['id_inscrito', 'id_carrera', 'id_gestionacademica', 'deposito']);

            if (! $inscripcionOrigen) {
                throw ValidationException::withMessages([
                    'id_inscrito_origen' => 'La inscripción seleccionada no pertenece al estudiante o ya fue reemplazada.',
                ]);
            }

            $gestionId = (int) $inscripcionOrigen->id_gestionacademica;
            $carreraId = (int) $validated['id_carrera'];
            $personaId = (int) $estudiante->id_persona;
            $gestionMasReciente = $this->gestionMasReciente($personaId);

            // Solo se permite cambiar la inscripción de la gestión más reciente y que aún no tenga materias.
            if (! $gestionMasReciente
                || $gestionId !== (int) $gestionMasReciente->id_gestionacademica) {
                throw ValidationException::withMessages([
                    'id_inscrito_origen' => 'Solo se puede cambiar una inscripción del período más reciente del estudiante.',
                ]);
            }

            if (DB::table('inscrito_materia')
                ->where('id_inscrito', $inscripcionOrigen->id_inscrito)
                ->exists()) {
                throw ValidationException::withMessages([
                    'id_inscrito_origen' => 'Esta inscripción tiene materias asignadas. Retírelas antes de cambiar de carrera.',
                ]);
            }

            if ((int) $inscripcionOrigen->id_carrera === (int) $validated['id_carrera']) {
                throw ValidationException::withMessages([
                    'id_carrera' => 'La nueva carrera debe ser distinta de la carrera que se cambiará.',
                ]);
            }

            if (DB::table('inscrito')
                ->where('id_persona', $personaId)
                ->where('id_carrera', $carreraId)
                ->where('activo', true)
                ->whereNull('id_inscrito_reemplazo')
                ->exists()) {
                throw ValidationException::withMessages([
                    'id_carrera' => 'El estudiante ya tiene una inscripción activa en esta carrera.',
                ]);
            }

            // Se conserva el comprobante de origen; el cambio de carrera no genera un pago nuevo.
            $deposito = trim((string) ($inscripcionOrigen->deposito ?? ''));
            if ($deposito === '') {
                $deposito = trim((string) (DB::table('comprobante')
                    ->where('id_inscrito', $inscripcionOrigen->id_inscrito)
                    ->where('activo', true)
                    ->orderByDesc('id_comprobante')
                    ->value('deposito') ?? ''));
            }

            $ahora = now();
            $becado = (int) $validated['becado'];
            $numeroInscripcion = DB::table('inscrito')
                ->where('id_persona', $personaId)
                ->where('id_carrera', $carreraId)
                ->count() + 1;

            $idInscrito = DB::table('inscrito')->insertGetId([
                'codigo' => date('Y').$personaId,
                'fecha' => $validated['fecha'],
                'numero' => $numeroInscripcion,
                'tipo' => 2,
                'becado' => $becado,
                'deposito' => $deposito !== '' ? $deposito : null,
                'id_carrera' => $carreraId,
                'id_persona' => $personaId,
                'id_gestionacademica' => $gestionId,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
            DB::table('inscrito')
                ->where('id_inscrito', $inscripcionOrigen->id_inscrito)
                ->update([
                    'id_inscrito_reemplazo' => $idInscrito,
                    'updated_at' => $ahora,
                ]);

            DB::table('cursa')->updateOrInsert(
                [
                    'id_persona' => $personaId,
                    'id_carrera' => $carreraId,
                    'id_gestionacademica' => $gestionId,
                ],
                [
                    'nivelinicio' => 1,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]
            );

            DB::table('estudiante')
                ->where('id_persona', $personaId)
                ->where('id_carrera', $inscripcionOrigen->id_carrera)
                ->where(function ($query) use ($gestionId): void {
                    $query->where('id_gestionacademica', $gestionId)
                        ->orWhereNull('id_gestionacademica');
                })
                ->update([
                    'id_carrera' => $carreraId,
                    'updated_at' => $ahora,
                ]);
        });

        return redirect()
            ->route('cambio_carrera.index')
            ->with('success', 'El cambio de carrera se registró correctamente.');
    }

    /**
     * Obtiene la gestión más reciente con una inscripción activa que no haya sido reemplazada.
     */
    private function gestionMasReciente(int $personaId): ?object
    {
        return DB::table('inscrito as i')
            ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
            ->where('i.id_persona', $personaId)
            ->where('i.activo', true)
            ->whereNull('i.id_inscrito_reemplazo')
            ->orderByDesc('ga.gestion')
            ->orderByDesc('ga.periodo')
            ->first(['ga.id_gestionacademica', 'ga.gestion', 'ga.periodo']);
    }

    /**
     * Convierte las respuestas habituales del formulario a valores booleanos aceptados por Laravel.
     */
    private function normalizarBooleano(mixed $valor): mixed
    {
        if (! is_scalar($valor)) {
            return $valor;
        }

        return match (mb_strtolower((string) $valor)) {
            'si', 'sí', '1', 'true' => 1,
            'no', '0', 'false' => 0,
            default => $valor,
        };
    }
}
