<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use App\Models\GestionAcademica;
use App\Models\Postulante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EstudianteNuevoPostulacionController extends Controller
{
    public function index()
    {
        $gestionActual = GestionAcademica::where('vigente', true)
            ->orderByDesc('id_gestionacademica')
            ->first() ?? GestionAcademica::orderByDesc('id_gestionacademica')->first();

        $gestionSeleccionadaId = $gestionActual?->id_gestionacademica;

        $postulantes = Postulante::with(['carrera', 'gestionAcademica'])
            ->when($gestionSeleccionadaId, fn ($query) => $query->where('id_gestionacademica', $gestionSeleccionadaId))
            ->whereHas('carrera', fn ($query) => $query->where('activo', true))
            ->whereNotIn('id_postulante', DB::table('inscrito')
                ->select('id_postulante_origen')
                ->whereNotNull('id_postulante_origen'))
            ->orderBy('nombre', 'asc')
            ->orderBy('primerapellido', 'asc')
            ->get();

        $postulantesData = $postulantes->map(fn (Postulante $postulante): array => [
            'id' => (string) $postulante->id_postulante,
            'nombre' => $postulante->nombre,
            'primerapellido' => $postulante->primerapellido,
            'segundoapellido' => $postulante->segundoapellido ?? '',
            'ci' => $postulante->ci,
            'idcarrera' => (string) $postulante->id_carrera,
            'carreraNombre' => $postulante->carrera?->nombre ?? 'Carrera sin nombre',
            'idgestion' => (string) $postulante->id_gestionacademica,
            'lugarexpedicion' => $postulante->lugarexpedicion ?? '',
            'fechanacimiento' => $postulante->fechanacimiento?->format('Y-m-d') ?? '',
            'lugarnacimiento' => $postulante->lugarnacimiento ?? '',
            'nacionalidad' => $postulante->nacionalidad ?? '',
            'sexo' => $postulante->sexo ?? '',
            'direccion' => $postulante->direccion ?? '',
            'telefono' => $postulante->telefono ?? '',
            'celular' => $postulante->celular ?? '',
            'email' => $postulante->email ?? '',
            'nombrecontacto' => $postulante->nombrecontacto ?? '',
            'celularcontacto' => $postulante->celularcontacto ?? '',
            'parentescocontacto' => $postulante->parentescocontacto ?? '',
            'nombrecolegio' => $postulante->nombrecolegio ?? '',
            'caractercolegio' => $postulante->caractercolegio ?? '',
            'egresocolegio' => (string) ($postulante->egresocolegio ?? ''),
            'diplomabachiller' => (string) ($postulante->diplomabachiller ?? '0'),
            'pathci' => $postulante->path_ci_frontal ?? '',
            'pathciReverso' => $postulante->path_ci_reverso ?? '',
            'pathnac' => $postulante->path_nacimiento ?? '',
            'pathdip' => $postulante->path_diploma ?? '',
            'deposito' => $postulante->deposito ?? '',
        ]);

        return view('Inscripciones.estudiante_nuevo_postulacion.index', compact(
            'postulantes',
            'postulantesData',
            'gestionActual',
            'gestionSeleccionadaId'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'postulaciones' => 'required|array|min:1',
            'postulaciones.*.id_postulante' => 'required|integer|distinct|exists:postulante,id_postulante',
            'postulaciones.*.deposito' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'primerapellido' => 'required|string|max:255',
            'segundoapellido' => 'nullable|string|max:255',
            'ci' => 'required|string|max:30',
            'lugarexpedicion' => 'nullable|string|max:100',
            'fechanacimiento' => 'required|date',
            'lugarnacimiento' => 'required|string|max:255',
            'nacionalidad' => 'required|string|max:255',
            'sexo' => 'required|in:M,F',
            'direccion' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'nombrecontacto' => 'required|string|max:255',
            'celularcontacto' => 'required|string|max:20',
            'parentescocontacto' => 'required|string|max:255',
            'nombrecolegio' => 'required|string|max:255',
            'caractercolegio' => 'required|string|max:100',
            'egresocolegio' => 'required|numeric',
            'fecha' => 'required|date',
            'diplomabachiller' => 'required|in:0,1',
            'becado' => 'nullable|boolean',
            'id_gestionacademica' => 'required|exists:gestionacademica,id_gestionacademica',
            'traspaso_convalidacion' => 'nullable|boolean',
        ]);

        $gestionActualId = GestionAcademica::where('vigente', true)
            ->orderByDesc('id_gestionacademica')
            ->value('id_gestionacademica')
            ?? GestionAcademica::orderByDesc('id_gestionacademica')->value('id_gestionacademica');

        if ((int) $validated['id_gestionacademica'] !== (int) $gestionActualId) {
            throw ValidationException::withMessages([
                'id_gestionacademica' => 'Solo se pueden inscribir postulaciones de la gestión vigente.',
            ]);
        }

        try {
            DB::transaction(function () use ($validated): void {
                $gestionId = (int) $validated['id_gestionacademica'];
                $enviadas = collect($validated['postulaciones'])
                    ->keyBy(fn (array $postulacion): int => (int) $postulacion['id_postulante']);
                $ids = $enviadas->keys()->all();
                $postulaciones = Postulante::where('ci', $validated['ci'])
                    ->where('id_gestionacademica', $gestionId)
                    ->whereHas('carrera', fn ($query) => $query->where('activo', true))
                    ->whereNotIn('id_postulante', DB::table('inscrito')
                        ->select('id_postulante_origen')
                        ->whereNotNull('id_postulante_origen'))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id_postulante');
                $idsPendientes = $postulaciones->keys()->map(fn ($id): int => (int) $id)->sort()->values();
                $idsEnviados = collect($ids)->map(fn ($id): int => (int) $id)->sort()->values();

                if ($idsPendientes->all() !== $idsEnviados->all()) {
                    throw ValidationException::withMessages([
                        'postulaciones' => 'La lista de carreras pendientes cambió. Vuelve a buscar a la persona antes de guardar.',
                    ]);
                }

                $voucherPorId = [];
                $indicePorId = [];
                $vouchersNormalizados = [];
                foreach (array_values($validated['postulaciones']) as $indice => $postulacion) {
                    $idPostulante = (int) $postulacion['id_postulante'];
                    $voucher = trim($postulacion['deposito']);
                    $normalizado = mb_strtolower($voucher);
                    if (in_array($normalizado, $vouchersNormalizados, true)) {
                        throw ValidationException::withMessages([
                            "postulaciones.$indice.deposito" => 'Cada carrera debe tener un voucher distinto.',
                        ]);
                    }
                    $vouchersNormalizados[] = $normalizado;
                    $voucherPorId[$idPostulante] = $voucher;
                    $indicePorId[$idPostulante] = $indice;
                }

                foreach ($postulaciones as $id => $postulante) {
                    if ($postulante->ci !== $validated['ci']) {
                        throw ValidationException::withMessages([
                            'postulaciones' => 'Las postulaciones no corresponden a la persona indicada.',
                        ]);
                    }

                    $voucher = $voucherPorId[(int) $id];
                    $duplicadoPendiente = Postulante::where('deposito', $voucher)
                        ->where('id_gestionacademica', $gestionId)
                        ->whereNotIn('id_postulante', $ids)
                        ->exists();
                    $duplicadoInscrito = DB::table('inscrito')
                        ->where('deposito', $voucher)
                        ->where('id_gestionacademica', $gestionId)
                        ->exists();
                    $duplicadoFichaAnterior = DB::table('estudiante')
                        ->where('deposito', $voucher)
                        ->where('id_gestionacademica', $gestionId)
                        ->exists();
                    $duplicadoComprobante = DB::table('comprobante')
                        ->where('deposito', $voucher)
                        ->where('id_gestionacademica', $gestionId)
                        ->where(function ($query) use ($ids): void {
                            $query->whereNull('id_postulante')
                                ->orWhereNotIn('id_postulante', $ids);
                        })
                        ->exists();

                    if ($duplicadoPendiente || $duplicadoInscrito || $duplicadoFichaAnterior || $duplicadoComprobante) {
                        $indice = $indicePorId[(int) $id];
                        throw ValidationException::withMessages([
                            "postulaciones.$indice.deposito" => 'El voucher ya está registrado en esta gestión.',
                        ]);
                    }
                }

                $idPersona = DB::table('personas')
                    ->where('ci', $validated['ci'])
                    ->lockForUpdate()
                    ->value('id_persona');
                $estudianteExistente = $idPersona
                    ? DB::table('estudiante')->where('id_persona', $idPersona)->exists()
                    : false;
                if ($idPersona && DB::table('inscrito')
                    ->where('id_persona', $idPersona)
                    ->where('id_gestionacademica', $gestionId)
                    ->whereIn('id_carrera', $postulaciones->pluck('id_carrera'))
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'postulaciones' => 'La persona ya tiene una inscripción en una o más carreras de esta gestión.',
                    ]);
                }

                $primeraPostulacion = $postulaciones->first();
                $ahora = now();
                if (! $idPersona) {
                    $idPersona = DB::table('personas')->insertGetId([
                        'nombre' => $validated['nombre'],
                        'primerapellido' => $validated['primerapellido'],
                        'segundoapellido' => $validated['segundoapellido'] ?? null,
                        'ci' => $validated['ci'],
                        'lugarexpedicion' => $validated['lugarexpedicion'] ?? null,
                        'fechanacimiento' => $validated['fechanacimiento'],
                        'lugarnacimiento' => $validated['lugarnacimiento'],
                        'nacionalidad' => $validated['nacionalidad'],
                        'sexo' => $validated['sexo'],
                        'direccion' => $validated['direccion'],
                        'telefono' => $validated['telefono'] ?? null,
                        'celular' => $validated['celular'] ?? null,
                        'email' => $validated['email'],
                        'tipo' => 'Estudiante',
                        'foto' => $primeraPostulacion->foto,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ]);
                }

                if (! DB::table('estudiante')->where('id_persona', $idPersona)->exists()) {
                    DB::table('estudiante')->insert([
                        'id_persona' => $idPersona,
                        'fecha' => $validated['fecha'],
                        'matricula' => random_int(1000, 9999),
                        'diplomabachiller' => (int) $validated['diplomabachiller'],
                        'becado' => (int) ($validated['becado'] ?? 0),
                        'nombrecontacto' => $validated['nombrecontacto'],
                        'celularcontacto' => $validated['celularcontacto'],
                        'parentescocontacto' => $validated['parentescocontacto'],
                        'nombrecolegio' => $validated['nombrecolegio'],
                        'caractercolegio' => $validated['caractercolegio'],
                        'egresocolegio' => $validated['egresocolegio'],
                        'traspaso_convalidacion' => (int) ($validated['traspaso_convalidacion'] ?? 0),
                        'path_ci' => $primeraPostulacion->path_ci_frontal,
                        'path_ci_reverso' => $primeraPostulacion->path_ci_reverso,
                        'path_nacimiento' => $primeraPostulacion->path_nacimiento,
                        'path_diploma' => $primeraPostulacion->path_diploma,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ]);
                }

                foreach ($postulaciones as $id => $postulante) {
                    $inscripcionesPrevias = DB::table('inscrito')
                        ->where('id_persona', $idPersona)
                        ->where('id_carrera', $postulante->id_carrera)
                        ->count();
                    DB::table('cursa')->updateOrInsert(
                        [
                            'id_persona' => $idPersona,
                            'id_carrera' => $postulante->id_carrera,
                            'id_gestionacademica' => $gestionId,
                        ],
                        [
                            'nivelinicio' => 1,
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ]
                    );
                    $idInscrito = DB::table('inscrito')->insertGetId([
                        'codigo' => date('Y').$idPersona,
                        'fecha' => $validated['fecha'],
                        'numero' => $inscripcionesPrevias + 1,
                        'tipo' => $estudianteExistente ? 2 : 1,
                        'becado' => (int) ($validated['becado'] ?? 0),
                        'deposito' => $voucherPorId[(int) $id],
                        'id_postulante_origen' => $postulante->id_postulante,
                        'id_carrera' => $postulante->id_carrera,
                        'id_persona' => $idPersona,
                        'id_gestionacademica' => $gestionId,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ]);

                    $pagoExistente = DB::table('pago')
                        ->where('id_postulante', $postulante->id_postulante)
                        ->first();
                    if ($pagoExistente) {
                        DB::table('pago')
                            ->where('id_pago', $pagoExistente->id_pago)
                            ->update([
                                'id_postulante' => null,
                                'id_inscrito' => $idInscrito,
                                'updated_at' => $ahora,
                            ]);
                    } else {
                        $monto = $this->montoInscripcion((int) $postulante->id_carrera);
                        $idPago = DB::table('pago')->insertGetId([
                            'concepto' => null,
                            'monto' => $monto,
                            'pagado' => false,
                            'estado' => true,
                            'id_inscrito' => $idInscrito,
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
                    }
                    DB::table('comprobante')
                        ->where('id_postulante', $postulante->id_postulante)
                        ->update([
                            'deposito' => $voucherPorId[(int) $id],
                            'fecha' => $validated['fecha'],
                            'id_postulante' => null,
                            'id_inscrito' => $idInscrito,
                            'id_estudiante' => $idPersona,
                            'id_usuario' => auth()->id(),
                            'id_carrera' => $postulante->id_carrera,
                            'id_gestionacademica' => $gestionId,
                            'updated_at' => $ahora,
                        ]);
                }
            });

            return redirect()->route('estudiante_nuevo_postulacion.index', [
                'id_gestionacademica' => $validated['id_gestionacademica'],
            ])->with('success', 'Inscripciones registradas correctamente.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            return redirect()->back()->withInput()->withErrors([
                'error' => 'No se pudo completar el lote. Las postulaciones siguen disponibles para corregir o reintentar.',
            ]);
        }
    }
}
