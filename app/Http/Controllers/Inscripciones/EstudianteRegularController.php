<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EstudianteRegularController extends Controller
{
    public function index()
    {
        $gestionActual = $this->gestionActual();

        return view('Inscripciones.estudiante_regular.index', compact('gestionActual'));
    }

    public function buscar(Request $request)
    {
        $gestionActual = $this->gestionActual();
        if (! $gestionActual) {
            return response()->json([]);
        }

        $termino = trim((string) $request->query('q', ''));
        $personaId = $request->query('id_persona');
        if (! $personaId && mb_strlen($termino) < 2) {
            return response()->json([]);
        }

        $personasQuery = DB::table('personas as p')
            ->join('estudiante as e', 'e.id_persona', '=', 'p.id_persona')
            ->select('p.id_persona', 'p.nombre', 'p.primerapellido', 'p.segundoapellido', 'p.ci as dni', 'e.id_carrera', 'e.traspaso_convalidacion')
            ->orderBy('p.primerapellido')
            ->orderBy('p.nombre');

        if ($personaId) {
            $personasQuery->where('p.id_persona', $personaId);
        } else {
            $personasQuery->where(function ($query) use ($termino): void {
                foreach (['p.ci', 'p.nombre', 'p.primerapellido', 'p.segundoapellido'] as $columna) {
                    $query->orWhere($columna, 'like', '%'.$termino.'%');
                }
            })->limit(15);
        }

        $personas = $personasQuery->get();
        if ($personas->isEmpty()) {
            return response()->json([]);
        }

        $idsPersona = $personas->pluck('id_persona');
        $historial = DB::table('inscrito as i')
            ->join('gestionacademica as g', 'g.id_gestionacademica', '=', 'i.id_gestionacademica')
            ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
            ->leftJoin('cursa as cu', function ($join): void {
                $join->on('cu.id_persona', '=', 'i.id_persona')
                    ->on('cu.id_carrera', '=', 'i.id_carrera')
                    ->on('cu.id_gestionacademica', '=', 'i.id_gestionacademica');
            })
            ->whereIn('i.id_persona', $idsPersona)
            ->orderByDesc('g.gestion')
            ->orderByDesc('g.periodo')
            ->orderByDesc('i.id_gestionacademica')
            ->get([
                'i.id_persona',
                'i.id_carrera',
                'c.nombre as carrera',
                'i.id_gestionacademica',
                'cu.nivelinicio',
                'g.gestion',
                'g.periodo',
            ])
            ->groupBy('id_persona');

        $carreras = Carrera::activas()->orderBy('nombre')->get(['id_carrera', 'nombre']);

        return response()->json($personas->map(function ($persona) use ($historial, $carreras, $gestionActual): array {
            $nombre = trim(implode(' ', array_filter([
                $persona->nombre,
                $persona->primerapellido,
                $persona->segundoapellido,
            ])));
            $historialPersona = $historial->get($persona->id_persona, collect());

            return [
                'id' => $persona->id_persona,
                'nombre' => $nombre,
                'dni' => $persona->dni,
                'traspaso_convalidacion' => (bool) $persona->traspaso_convalidacion,
                'carreras' => $carreras->map(function ($carrera) use ($historialPersona, $gestionActual): array {
                    $registrosCarrera = $historialPersona
                        ->where('id_carrera', $carrera->id_carrera)
                        ->values();
                    $registroReciente = $registrosCarrera->first();

                    return [
                        'id' => (int) $carrera->id_carrera,
                        'nombre' => $carrera->nombre,
                        'ya_inscrito' => $registrosCarrera->contains(
                            fn ($registro): bool => (int) $registro->id_gestionacademica === (int) $gestionActual->id_gestionacademica
                        ),
                        'nivel_sugerido' => max(1, (int) ($registroReciente->nivelinicio ?? 1)),
                        'historial' => $registrosCarrera->map(fn ($registro): array => [
                            'gestion' => $registro->gestion,
                            'periodo' => $registro->periodo,
                            'nivel' => $registro->nivelinicio,
                        ])->values(),
                    ];
                })->values(),
            ];
        })->values());
    }

    public function store(Request $request)
    {
        $gestionActual = $this->gestionActual();
        if (! $gestionActual) {
            throw ValidationException::withMessages([
                'id_gestionacademica' => 'No hay una gestión académica configurada para inscribir.',
            ]);
        }

        $validated = $request->validate([
            'id_persona' => 'required|integer|exists:estudiante,id_persona',
            'fecha' => 'required|date',
            'becado' => 'nullable|boolean',
            'traspaso_convalidacion' => 'required|boolean',
            'inscripciones' => 'required|array|min:1',
            'inscripciones.*.id_carrera' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('carreras', 'id_carrera')->where('activo', true),
            ],
            'inscripciones.*.nivelinicio' => 'required|integer|between:1,10',
            'inscripciones.*.deposito' => 'required|string|max:255',
        ]);

        $vouchers = [];
        foreach ($validated['inscripciones'] as $indice => $inscripcion) {
            $voucher = trim($inscripcion['deposito']);
            $normalizado = mb_strtolower($voucher);
            if (in_array($normalizado, $vouchers, true)) {
                throw ValidationException::withMessages([
                    "inscripciones.$indice.deposito" => 'Cada carrera debe tener un voucher distinto.',
                ]);
            }
            $vouchers[] = $normalizado;
            $validated['inscripciones'][$indice]['deposito'] = $voucher;
        }

        DB::transaction(function () use ($validated, $gestionActual): void {
            DB::table('estudiante')
                ->where('id_persona', $validated['id_persona'])
                ->lockForUpdate()
                ->first();
            DB::table('estudiante')
                ->where('id_persona', $validated['id_persona'])
                ->update([
                    'traspaso_convalidacion' => (int) $validated['traspaso_convalidacion'],
                    'updated_at' => now(),
                ]);

            foreach ($validated['inscripciones'] as $indice => $inscripcion) {
                $idCarrera = (int) $inscripcion['id_carrera'];
                $nivelActual = (int) $inscripcion['nivelinicio'];
                $voucher = $inscripcion['deposito'];

                if (DB::table('inscrito')
                    ->where('id_persona', $validated['id_persona'])
                    ->where('id_carrera', $idCarrera)
                    ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        "inscripciones.$indice.id_carrera" => 'El estudiante ya está inscrito en esta carrera durante la gestión actual.',
                    ]);
                }

                if (DB::table('inscrito')
                    ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
                    ->whereRaw('LOWER(deposito) = ?', [mb_strtolower($voucher)])
                    ->exists()) {
                    throw ValidationException::withMessages([
                        "inscripciones.$indice.deposito" => 'Este voucher ya está registrado en la gestión actual.',
                    ]);
                }
                if (DB::table('comprobante')
                    ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
                    ->whereRaw('LOWER(deposito) = ?', [mb_strtolower($voucher)])
                    ->exists()) {
                    throw ValidationException::withMessages([
                        "inscripciones.$indice.deposito" => 'Este voucher ya está registrado en la gestión actual.',
                    ]);
                }

                $inscripcionesPrevias = DB::table('inscrito')
                    ->where('id_persona', $validated['id_persona'])
                    ->where('id_carrera', $idCarrera)
                    ->count();

                $ahora = now();
                $idInscrito = DB::table('inscrito')->insertGetId([
                    'codigo' => date('Y').$validated['id_persona'],
                    'fecha' => $validated['fecha'],
                    'numero' => $inscripcionesPrevias + 1,
                    'tipo' => 2,
                    'becado' => (int) ($validated['becado'] ?? 0),
                    'deposito' => $voucher,
                    'id_carrera' => $idCarrera,
                    'id_persona' => $validated['id_persona'],
                    'id_gestionacademica' => $gestionActual->id_gestionacademica,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);

                $monto = $this->montoInscripcion($idCarrera);
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
                    'deposito' => $voucher,
                    'fecha' => $validated['fecha'],
                    'valido' => false,
                    'id_inscrito' => $idInscrito,
                    'id_usuario' => auth()->id(),
                    'id_estudiante' => $validated['id_persona'],
                    'id_carrera' => $idCarrera,
                    'id_gestionacademica' => $gestionActual->id_gestionacademica,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
                DB::table('detalle')->insert([
                    'nombre' => 'Inscripción',
                    'monto' => $monto,
                    'id_pago' => $idPago,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);

                DB::table('cursa')->updateOrInsert(
                    [
                        'id_persona' => $validated['id_persona'],
                        'id_carrera' => $idCarrera,
                        'id_gestionacademica' => $gestionActual->id_gestionacademica,
                    ],
                    ['nivelinicio' => $nivelActual, 'created_at' => $ahora, 'updated_at' => $ahora]
                );
            }
        });

        return redirect()->route('estudiante_regular.index')->with('success', 'Inscripción regular registrada correctamente.');
    }

    private function gestionActual(): ?GestionAcademica
    {
        return GestionAcademica::where('vigente', true)
            ->orderByDesc('id_gestionacademica')
            ->first() ?? GestionAcademica::orderByDesc('id_gestionacademica')->first();
    }

}
