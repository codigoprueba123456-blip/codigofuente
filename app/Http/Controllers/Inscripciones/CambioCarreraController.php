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
    public function index()
    {
        $carreras = Carrera::activas()
            ->orderBy('nombre')
            ->get(['id_carrera', 'nombre', 'resolucionministerial1', 'resolucionministerial2', 'resolucionministerial3']);

        return view('Inscripciones.cambio_carrera.index', compact('carreras'));
    }

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
                $inscripciones = DB::table('inscrito as i')
                    ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
                    ->join('gestionacademica as ga', 'ga.id_gestionacademica', '=', 'i.id_gestionacademica')
                    ->where('i.id_persona', $estudiante->id_persona)
                    ->where('i.activo', true)
                    ->whereNull('i.id_inscrito_reemplazo')
                    ->orderByDesc('ga.gestion')
                    ->orderByDesc('ga.periodo')
                    ->orderBy('c.nombre')
                    ->get([
                        'i.id_inscrito',
                        'i.id_carrera',
                        'i.id_gestionacademica',
                        'c.nombre as carrera',
                        'ga.gestion',
                        'ga.periodo',
                    ])
                    ->map(fn ($inscripcion): array => [
                        'id' => (int) $inscripcion->id_inscrito,
                        'id_carrera' => (int) $inscripcion->id_carrera,
                        'id_gestionacademica' => (int) $inscripcion->id_gestionacademica,
                        'carrera' => $inscripcion->carrera,
                        'gestion' => $inscripcion->gestion,
                        'periodo' => $inscripcion->periodo,
                    ])
                    ->all();

                return [
                    'id' => (int) $estudiante->id_persona,
                    'ci' => $estudiante->ci,
                    'nombre' => trim(implode(' ', array_filter([
                        $estudiante->nombre,
                        $estudiante->primerapellido,
                        $estudiante->segundoapellido,
                    ]))),
                    'email' => $estudiante->email ?? '',
                    'celular' => $estudiante->celular ?? '',
                    'inscripciones' => $inscripciones,
                ];
            });

        return response()->json($estudiantes);
    }

    public function store(Request $request)
    {
        $deposito = $request->input('deposito');
        if (is_string($deposito)) {
            $deposito = trim($deposito) ?: null;
        }

        $request->merge([
            'becado' => $this->normalizarBooleano($request->input('becado')),
            'deposito' => $deposito,
        ]);

        $validated = $request->validate([
            'id_persona' => 'required|integer|exists:estudiante,id_persona',
            'id_inscrito_origen' => 'required|integer|exists:inscrito,id_inscrito',
            'fecha' => 'required|date',
            'becado' => 'required|boolean',
            'deposito' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0|max:9999999999.99|decimal:0,2',
            'id_carrera' => [
                'required',
                'integer',
                Rule::exists('carreras', 'id_carrera')->where('activo', true),
            ],
        ]);

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
                ->first(['id_inscrito', 'id_carrera', 'id_gestionacademica']);

            if (! $inscripcionOrigen) {
                throw ValidationException::withMessages([
                    'id_inscrito_origen' => 'La inscripción seleccionada no pertenece al estudiante o ya fue reemplazada.',
                ]);
            }

            if ((int) $inscripcionOrigen->id_carrera === (int) $validated['id_carrera']) {
                throw ValidationException::withMessages([
                    'id_carrera' => 'La nueva carrera debe ser distinta de la carrera que se cambiará.',
                ]);
            }

            $gestionId = (int) $inscripcionOrigen->id_gestionacademica;
            $carreraId = (int) $validated['id_carrera'];
            $personaId = (int) $estudiante->id_persona;

            if (DB::table('inscrito')
                ->where('id_persona', $personaId)
                ->where('id_carrera', $carreraId)
                ->where('id_gestionacademica', $gestionId)
                ->where('activo', true)
                ->whereNull('id_inscrito_reemplazo')
                ->exists()) {
                throw ValidationException::withMessages([
                    'id_carrera' => 'El estudiante ya está inscrito en esta carrera durante la gestión seleccionada.',
                ]);
            }

            $deposito = trim($validated['deposito']);
            $depositoNormalizado = mb_strtolower($deposito);
            $depositoRegistrado = DB::table('inscrito')
                ->where('id_gestionacademica', $gestionId)
                ->where('activo', true)
                ->whereRaw('LOWER(deposito) = ?', [$depositoNormalizado])
                ->exists()
                || DB::table('comprobante')
                    ->where('id_gestionacademica', $gestionId)
                    ->where('activo', true)
                    ->whereRaw('LOWER(deposito) = ?', [$depositoNormalizado])
                    ->exists();

            if ($depositoRegistrado) {
                throw ValidationException::withMessages([
                    'deposito' => 'Este número de depósito ya está registrado en la gestión seleccionada.',
                ]);
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
                'deposito' => $deposito,
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

            $monto = (float) $validated['monto'];
            $idPago = DB::table('pago')->insertGetId([
                'concepto' => null,
                'monto' => $monto,
                'pagado' => false,
                'estado' => true,
                'id_inscrito' => $idInscrito,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
            DB::table('comprobante')->insert([
                'deposito' => $deposito,
                'fecha' => $validated['fecha'],
                'valido' => false,
                'id_inscrito' => $idInscrito,
                'id_usuario' => auth()->id(),
                'id_estudiante' => $personaId,
                'id_carrera' => $carreraId,
                'id_gestionacademica' => $gestionId,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
            DB::table('detalle')->insert([
                'nombre' => 'Cambio de carrera',
                'monto' => $monto,
                'id_pago' => $idPago,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

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
