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
            ->select('p.id_persona', 'p.nombre', 'p.primerapellido', 'p.segundoapellido', 'p.ci', 'e.id_carrera', 'e.traspaso_convalidacion')
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
        $inscripcionesActivas = DB::table('inscrito')
            ->whereIn('id_persona', $idsPersona)
            ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
            ->where('activo', true)
            ->whereNull('id_inscrito_reemplazo')
            ->get(['id_persona', 'id_carrera'])
            ->groupBy('id_persona')
            ->map(fn ($inscripciones) => $inscripciones->pluck('id_carrera')->map(fn ($id): int => (int) $id));
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

        $historialCursa = DB::table('cursa as cu')
            ->leftJoin('gestionacademica as g', 'g.id_gestionacademica', '=', 'cu.id_gestionacademica')
            ->join('carreras as c', 'c.id_carrera', '=', 'cu.id_carrera')
            ->whereIn('cu.id_persona', $idsPersona)
            ->get([
                'cu.id_persona',
                'cu.id_carrera',
                'c.nombre as carrera',
                'cu.id_gestionacademica',
                'cu.nivelinicio',
                'g.gestion',
                'g.periodo',
            ])
            ->groupBy('id_persona');

        $carreras = Carrera::activas()
            ->orderBy('nombre')
            ->get(['id_carrera', 'nombre', 'resolucionministerial1', 'resolucionministerial2', 'resolucionministerial3']);

        return response()->json($personas->map(function ($persona) use ($historial, $historialCursa, $inscripcionesActivas, $carreras, $gestionActual): array {
            $nombre = trim(implode(' ', array_filter([
                $persona->nombre,
                $persona->primerapellido,
                $persona->segundoapellido,
            ])));
            $historialPersona = $historial->get($persona->id_persona, collect())
                ->concat($historialCursa->get($persona->id_persona, collect()))
                ->sortByDesc(fn ($registro) => [
                    (int) ($registro->gestion ?? 0),
                    (int) ($registro->periodo ?? 0),
                    (int) ($registro->id_gestionacademica ?? 0),
                ])
                ->unique(fn ($registro) => $registro->id_carrera.'-'.($registro->id_gestionacademica ?? 'sin-gestion'))
                ->values();
            $idsCarrerasPersona = $historialPersona->pluck('id_carrera')
                ->push($persona->id_carrera)
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->unique();
            return [
                'id' => $persona->id_persona,
                'nombre' => $nombre,
                'ci' => $persona->ci,
                'traspaso_convalidacion' => (bool) $persona->traspaso_convalidacion,
                'carreras_disponibles' => $carreras->whereNotIn('id_carrera', $idsCarrerasPersona)
                    ->map(fn ($carrera): array => [
                        'id' => (int) $carrera->id_carrera,
                        'nombre' => $carrera->nombre,
                        'resoluciones' => $carrera->resolucionesMinisteriales(),
                    ])->values(),
                'carreras' => $carreras->whereIn('id_carrera', $idsCarrerasPersona)->map(function ($carrera) use ($historialPersona, $inscripcionesActivas, $persona): array {
                    $registrosCarrera = $historialPersona
                        ->where('id_carrera', (int) $carrera->id_carrera)
                        ->values();
                    $registroReciente = $registrosCarrera->first();

                    return [
                        'id' => (int) $carrera->id_carrera,
                        'nombre' => $carrera->nombre,
                        'resoluciones' => $carrera->resolucionesMinisteriales(),
                        'ya_inscrito' => $inscripcionesActivas
                            ->get($persona->id_persona, collect())
                            ->contains((int) $carrera->id_carrera),
                        'nivel_sugerido' => min(3, max(1, (int) ($registroReciente->nivelinicio ?? 0) + 1)),
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
            'inscripciones.*.registrar' => 'required|boolean',
            'inscripciones.*.id_carrera' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('carreras', 'id_carrera')->where('activo', true),
            ],
            'inscripciones.*.nueva' => 'nullable|boolean',
            'inscripciones.*.nivelinicio' => 'nullable|integer|between:1,3',
            'inscripciones.*.deposito' => 'nullable|string|max:255',
        ]);

        $inscripcionesSolicitadas = collect($validated['inscripciones'])
            ->filter(fn (array $inscripcion): bool => (bool) (int) $inscripcion['registrar'])
            ->values();
        if ($inscripcionesSolicitadas->isEmpty()) {
            throw ValidationException::withMessages([
                'inscripciones' => 'Selecciona Registrar: Sí en al menos una carrera.',
            ]);
        }

        foreach ($inscripcionesSolicitadas as $indice => $inscripcion) {
            $voucher = trim((string) ($inscripcion['deposito'] ?? ''));
            if (empty($inscripcion['nivelinicio']) || $voucher === '') {
                $errors = [];
                if (empty($inscripcion['nivelinicio'])) {
                    $errors["inscripciones.$indice.nivelinicio"] = 'Selecciona el año que cursará en cada carrera que se va a registrar.';
                }
                if ($voucher === '') {
                    $errors["inscripciones.$indice.deposito"] = 'Ingresa el voucher para cada carrera que se va a registrar.';
                }

                throw ValidationException::withMessages($errors);
            }
        }
        $validated['inscripciones'] = $inscripcionesSolicitadas->all();

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
            $estudiante = DB::table('estudiante')
                ->where('id_persona', $validated['id_persona'])
                ->lockForUpdate()
                ->first();

            $carrerasAsociadas = DB::table('inscrito')
                ->where('id_persona', $validated['id_persona'])
                ->pluck('id_carrera')
                ->merge(DB::table('cursa')
                    ->where('id_persona', $validated['id_persona'])
                    ->pluck('id_carrera'))
                ->push($estudiante->id_carrera ?? null)
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->unique();

            foreach ($validated['inscripciones'] as $indice => $inscripcion) {
                $carreraAsociada = $carrerasAsociadas->contains((int) $inscripcion['id_carrera']);
                $esNuevaCarrera = (bool) ($inscripcion['nueva'] ?? false);

                if ($esNuevaCarrera && $carreraAsociada) {
                    throw ValidationException::withMessages([
                        "inscripciones.$indice.id_carrera" => 'Esta carrera ya forma parte del historial. Regístrala desde su fila existente.',
                    ]);
                }

                if (! $carreraAsociada && ! $esNuevaCarrera) {
                    throw ValidationException::withMessages([
                        "inscripciones.$indice.id_carrera" => 'Para agregar otra carrera, selecciónala con el botón Añadir otra carrera.',
                    ]);
                }
            }

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
                    ->where('activo', true)
                    ->whereNull('id_inscrito_reemplazo')
                    ->exists()) {
                    throw ValidationException::withMessages([
                        "inscripciones.$indice.id_carrera" => 'El estudiante ya está inscrito en esta carrera durante la gestión actual.',
                    ]);
                }

                if (DB::table('inscrito')
                    ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
                    ->where('activo', true)
                    ->whereRaw('LOWER(deposito) = ?', [mb_strtolower($voucher)])
                    ->exists()) {
                    throw ValidationException::withMessages([
                        "inscripciones.$indice.deposito" => 'Este voucher ya está registrado en la gestión actual.',
                    ]);
                }
                if (DB::table('comprobante')
                    ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
                    ->where('activo', true)
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
