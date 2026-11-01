<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
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
        $gestiones = GestionAcademica::query()
            ->orderByDesc('gestion')
            ->orderByDesc('periodo')
            ->get(['id_gestionacademica', 'gestion', 'periodo', 'vigente']);

        return view('Inscripciones.cambio_carrera.index', compact('carreras', 'gestiones'));
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
            ->leftJoin('carreras as c', 'c.id_carrera', '=', 'e.id_carrera')
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
                'c.nombre as carrera_actual',
            ])
            ->map(fn ($estudiante): array => [
                'id' => (int) $estudiante->id_persona,
                'ci' => $estudiante->ci,
                'nombre' => trim(implode(' ', array_filter([
                    $estudiante->nombre,
                    $estudiante->primerapellido,
                    $estudiante->segundoapellido,
                ]))),
                'email' => $estudiante->email ?? '',
                'celular' => $estudiante->celular ?? '',
                'carrera_actual' => $estudiante->carrera_actual ?? 'Sin carrera registrada',
            ]);

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
            'traspaso_convalidacion' => $this->normalizarBooleano($request->input('traspaso_convalidaciones')),
            'deposito' => $deposito,
        ]);

        $validated = $request->validate([
            'id_persona' => 'required|integer|exists:estudiante,id_persona',
            'fecha' => 'required|date',
            'becado' => 'required|boolean',
            'traspaso_convalidacion' => 'required|boolean',
            'deposito' => 'required|string|max:255',
            'id_carrera' => [
                'required',
                'integer',
                Rule::exists('carreras', 'id_carrera')->where('activo', true),
            ],
            'id_gestionacademica' => 'required|integer|exists:gestionacademica,id_gestionacademica',
        ]);

        DB::transaction(function () use ($validated): void {
            $estudiante = DB::table('estudiante as e')
                ->join('personas as p', 'p.id_persona', '=', 'e.id_persona')
                ->where('e.id_persona', (int) $validated['id_persona'])
                ->lockForUpdate()
                ->first([
                    'e.id_persona',
                    'e.id_carrera',
                    'p.ci',
                ]);

            if (! $estudiante) {
                throw ValidationException::withMessages([
                    'id_persona' => 'No se encontró el estudiante seleccionado.',
                ]);
            }

            if (! $estudiante->id_carrera) {
                throw ValidationException::withMessages([
                    'id_persona' => 'El estudiante no tiene una carrera actual para cambiar.',
                ]);
            }

            if ((int) $estudiante->id_carrera === (int) $validated['id_carrera']) {
                throw ValidationException::withMessages([
                    'id_carrera' => 'La carrera seleccionada ya es la carrera actual del estudiante.',
                ]);
            }

            $gestionId = (int) $validated['id_gestionacademica'];
            $carreraId = (int) $validated['id_carrera'];
            $personaId = (int) $estudiante->id_persona;

            if (DB::table('inscrito')
                ->where('id_persona', $personaId)
                ->where('id_carrera', $carreraId)
                ->where('id_gestionacademica', $gestionId)
                ->exists()) {
                throw ValidationException::withMessages([
                    'id_carrera' => 'El estudiante ya está inscrito en esta carrera durante la gestión seleccionada.',
                ]);
            }

            $deposito = trim($validated['deposito']);
            $depositoNormalizado = mb_strtolower($deposito);
            $depositoRegistrado = DB::table('inscrito')
                ->where('id_gestionacademica', $gestionId)
                ->whereRaw('LOWER(deposito) = ?', [$depositoNormalizado])
                ->exists()
                || DB::table('comprobante')
                    ->where('id_gestionacademica', $gestionId)
                    ->whereRaw('LOWER(deposito) = ?', [$depositoNormalizado])
                    ->exists();

            if ($depositoRegistrado) {
                throw ValidationException::withMessages([
                    'deposito' => 'Este número de depósito ya está registrado en la gestión seleccionada.',
                ]);
            }

            $ahora = now();
            $becado = (int) $validated['becado'];
            $traspaso = (int) $validated['traspaso_convalidacion'];
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

            $monto = $this->montoInscripcion($carreraId);
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
                ->update([
                    'id_carrera' => $carreraId,
                    'id_gestionacademica' => $gestionId,
                    'becado' => $becado,
                    'deposito' => $deposito,
                    'traspaso_convalidacion' => $traspaso,
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
