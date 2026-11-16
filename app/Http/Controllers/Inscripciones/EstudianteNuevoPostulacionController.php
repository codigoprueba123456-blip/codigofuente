<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use App\Models\Cuenta;
use App\Models\GestionAcademica;
use App\Models\Postulante;
use App\Models\Rol;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EstudianteNuevoPostulacionController extends Controller
{
    public function index()
    {
        $gestionActual = GestionAcademica::where('vigente', true)
            ->orderByDesc('id_gestionacademica')
            ->first();

        $gestionSeleccionadaId = $gestionActual?->id_gestionacademica;

        return view('Inscripciones.estudiante_nuevo_postulacion.index', compact(
            'gestionActual',
            'gestionSeleccionadaId'
        ));
    }

    public function buscar(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|max:100',
        ]);
        $gestionActualId = GestionAcademica::where('vigente', true)
            ->orderByDesc('id_gestionacademica')
            ->value('id_gestionacademica');

        $consulta = $this->consultaPostulantes($gestionActualId ? (int) $gestionActualId : null);
        $terminos = preg_split('/\s+/', mb_strtolower(trim($validated['q'])), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($terminos as $termino) {
            $consulta->where(function (Builder $query) use ($termino): void {
                $like = '%'.$termino.'%';
                $query->whereRaw('LOWER(nombre) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(primerapellido) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(segundoapellido) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(ci) LIKE ?', [$like]);
            });
        }

        $postulantes = $consulta
            ->orderBy('nombre')
            ->orderBy('primerapellido')
            ->get();

        return response()
            ->json(['postulantes' => $this->formatearPostulantes($postulantes)])
            ->header('Cache-Control', 'no-store, private');
    }

    private function consultaPostulantes(?int $gestionId): Builder
    {
        return Postulante::with(['carrera', 'gestionAcademica'])
            ->where('id_gestionacademica', $gestionId ?? 0)
            ->whereHas('carrera', fn ($query) => $query->where('activo', true))
            ->whereNotExists(function (QueryBuilder $query): void {
                $query->selectRaw('1')
                    ->from('personas as p')
                    ->join('estudiante as e', 'e.id_persona', '=', 'p.id_persona')
                    ->whereRaw('LOWER(p.ci) = LOWER(postulante.ci)')
                    ->where(function (QueryBuilder $career): void {
                        $career->whereColumn('e.id_carrera', 'postulante.id_carrera')
                            ->orWhereExists(function (QueryBuilder $association): void {
                                $association->selectRaw('1')
                                    ->from('cursa as cu')
                                    ->whereColumn('cu.id_persona', 'e.id_persona')
                                    ->whereColumn('cu.id_carrera', 'postulante.id_carrera');
                            })
                            ->orWhereExists(function (QueryBuilder $association): void {
                                $association->selectRaw('1')
                                    ->from('inscrito as i')
                                    ->whereColumn('i.id_persona', 'e.id_persona')
                                    ->whereColumn('i.id_carrera', 'postulante.id_carrera');
                            });
                    });
            })
            ->whereNotIn('id_postulante', DB::table('inscrito')
                ->select('id_postulante_origen')
                ->where('activo', true)
                ->whereNotNull('id_postulante_origen'));
    }

    private function formatearPostulantes(Collection $postulantes): array
    {
        $usuariosPorCi = DB::table('personas as p')
            ->join('users as u', 'u.id_persona', '=', 'p.id_persona')
            ->whereIn('p.ci', $postulantes->pluck('ci')->filter()->unique()->values())
            ->get(['p.ci', 'u.usuario'])
            ->keyBy('ci');
        $estudiantesPorCi = DB::table('personas as p')
            ->join('estudiante as e', 'e.id_persona', '=', 'p.id_persona')
            ->whereIn('p.ci', $postulantes->pluck('ci')->filter()->unique()->values())
            ->pluck('p.ci')
            ->flip();

        return $postulantes->map(fn (Postulante $postulante): array => [
            'id' => (string) $postulante->id_postulante,
            'nombre' => $postulante->nombre,
            'primerapellido' => $postulante->primerapellido,
            'segundoapellido' => $postulante->segundoapellido ?? '',
            'ci' => $postulante->ci,
            'idcarrera' => (string) $postulante->id_carrera,
            'carreraNombre' => $postulante->carrera?->nombre ?? 'Carrera sin nombre',
            'carreraResoluciones' => $postulante->carrera?->resolucionesMinisteriales() ?? [],
            'idgestion' => (string) $postulante->id_gestionacademica,
            'foto' => $postulante->foto ?? '',
            'lugarexpedicion' => $this->normalizarLugarExpedicion($postulante->lugarexpedicion),
            'fechanacimiento' => $postulante->fechanacimiento?->format('Y-m-d') ?? '',
            'lugarnacimiento' => $postulante->lugarnacimiento ?? '',
            'nacionalidad' => $postulante->nacionalidad ?? '',
            'sexo' => $this->normalizarSexo($postulante->sexo),
            'direccion' => $postulante->direccion ?? '',
            'telefono' => $postulante->telefono ?? '',
            'celular' => $postulante->celular ?? '',
            'email' => $postulante->email ?? '',
            'nombrecontacto' => $postulante->nombrecontacto ?? '',
            'celularcontacto' => $postulante->celularcontacto ?? '',
            'parentescocontacto' => $postulante->parentescocontacto ?? '',
            'nombrecolegio' => $postulante->nombrecolegio ?? '',
            'caractercolegio' => $this->normalizarCaracterColegio($postulante->caractercolegio),
            'egresocolegio' => (string) ($postulante->egresocolegio ?? ''),
            'diplomabachiller' => (string) ($postulante->diplomabachiller ?? '0'),
            'pathci' => $postulante->path_ci_frontal ?? '',
            'pathciReverso' => $postulante->path_ci_reverso ?? '',
            'pathnac' => $postulante->path_nacimiento ?? '',
            'pathdip' => $postulante->path_diploma ?? '',
            'estudianteExistente' => $estudiantesPorCi->has($postulante->ci),
            'deposito' => $postulante->deposito ?? '',
            'usuarioExistente' => $usuariosPorCi->has($postulante->ci),
            'usuario' => $usuariosPorCi->get($postulante->ci)?->usuario,
        ])->all();
    }

    private function normalizarSexo(?string $sexo): string
    {
        return match (mb_strtoupper(trim((string) $sexo))) {
            'M', 'MASCULINO' => 'M',
            'F', 'FEMENINO' => 'F',
            default => '',
        };
    }

    private function normalizarCaracterColegio(?string $caracter): string
    {
        return match (mb_strtoupper(trim((string) $caracter))) {
            'FISCAL' => 'Fiscal',
            'PARTICULAR', 'PRIVADO' => 'Particular',
            'CONVENIO' => 'Convenio',
            default => '',
        };
    }

    private function normalizarLugarExpedicion(?string $lugar): string
    {
        $normalizado = mb_strtoupper(Str::ascii(trim((string) $lugar)));

        return match ($normalizado) {
            'CB', 'COCHABAMBA' => 'CB',
            'LP', 'LA PAZ' => 'LP',
            'SC', 'SANTA CRUZ' => 'SC',
            'OR', 'ORURO' => 'OR',
            'PT', 'POTOSI' => 'PT',
            'CH', 'CHUQUISACA', 'SUCRE' => 'CH',
            'TJ', 'TARIJA' => 'TJ',
            'BE', 'BENI' => 'BE',
            'PD', 'PANDO' => 'PD',
            'OTRO', 'OTROS' => 'OTRO',
            default => '',
        };
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'postulaciones' => 'required|array|min:1',
            'postulaciones.*.id_postulante' => 'required|integer|distinct|exists:postulante,id_postulante',
            'postulaciones.*.decision' => 'required|in:si,no',
            'postulaciones.*.deposito' => 'nullable|string|max:255',
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
            'accion_credenciales' => 'nullable|in:mantener,regenerar',
        ]);

        $gestionActualId = GestionAcademica::where('vigente', true)
            ->orderByDesc('id_gestionacademica')
            ->value('id_gestionacademica');

        if (! $gestionActualId || (int) $validated['id_gestionacademica'] !== (int) $gestionActualId) {
            throw ValidationException::withMessages([
                'id_gestionacademica' => 'No hay una gestión vigente o la gestión seleccionada ya no está vigente.',
            ]);
        }

        try {
            $resultado = DB::transaction(function () use ($validated): array {
                $gestionId = (int) $validated['id_gestionacademica'];
                $enviadas = collect($validated['postulaciones'])
                    ->keyBy(fn (array $postulacion): int => (int) $postulacion['id_postulante']);
                $ids = $enviadas->keys()->all();
                $postulaciones = $this->consultaPostulantes($gestionId)
                    ->where('ci', $validated['ci'])
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

                $idsSeleccionados = $enviadas
                    ->filter(fn (array $postulacion): bool => $postulacion['decision'] === 'si')
                    ->keys()
                    ->all();
                if ($idsSeleccionados === []) {
                    throw ValidationException::withMessages([
                        'postulaciones' => 'Selecciona al menos una carrera con la opción Sí.',
                    ]);
                }
                $postulaciones = $postulaciones->filter(
                    fn (Postulante $postulante): bool => in_array(
                        (int) $postulante->id_postulante,
                        array_map('intval', $idsSeleccionados),
                        true
                    )
                );

                $voucherPorId = [];
                $indicePorId = [];
                $vouchersNormalizados = [];
                foreach (array_values($validated['postulaciones']) as $indice => $postulacion) {
                    if ($postulacion['decision'] !== 'si') {
                        continue;
                    }

                    $idPostulante = (int) $postulacion['id_postulante'];
                    $voucher = trim((string) ($postulacion['deposito'] ?? ''));
                    if ($voucher === '') {
                        throw ValidationException::withMessages([
                            "postulaciones.$indice.deposito" => 'El voucher es obligatorio para las carreras seleccionadas con Sí.',
                        ]);
                    }
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

                foreach ($postulaciones as $postulante) {
                    $idPostulante = (int) $postulante->id_postulante;
                    if ($postulante->ci !== $validated['ci']) {
                        throw ValidationException::withMessages([
                            'postulaciones' => 'Las postulaciones no corresponden a la persona indicada.',
                        ]);
                    }

                    $voucher = $voucherPorId[$idPostulante];
                    $duplicadoPendiente = Postulante::where('deposito', $voucher)
                        ->where('id_gestionacademica', $gestionId)
                        ->whereNotIn('id_postulante', $ids)
                        ->exists();
                    $duplicadoInscrito = DB::table('inscrito')
                        ->where('deposito', $voucher)
                        ->where('id_gestionacademica', $gestionId)
                        ->where('activo', true)
                        ->exists();
                    $duplicadoComprobante = DB::table('comprobante')
                        ->where('deposito', $voucher)
                        ->where('id_gestionacademica', $gestionId)
                        ->where('activo', true)
                        ->where(function ($query) use ($ids): void {
                            $query->whereNull('id_postulante')
                                ->orWhereNotIn('id_postulante', $ids);
                        })
                        ->exists();

                    if ($duplicadoPendiente || $duplicadoInscrito || $duplicadoComprobante) {
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
                $carrerasYaInscritas = $idPersona
                    ? DB::table('inscrito as i')
                        ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
                        ->where('i.id_persona', $idPersona)
                        ->where('i.id_gestionacademica', $gestionId)
                        ->where('i.activo', true)
                        ->whereNull('i.id_inscrito_reemplazo')
                        ->whereIn('i.id_carrera', $postulaciones->pluck('id_carrera'))
                        ->distinct()
                        ->orderBy('c.nombre')
                        ->pluck('c.nombre')
                    : collect();
                if ($carrerasYaInscritas->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'postulaciones' => 'El estudiante ya está inscrito en '.$carrerasYaInscritas->implode(', ')
                            .' durante esta gestión. No se puede duplicar la inscripción.',
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

                $credenciales = null;
                $usuario = User::where('id_persona', $idPersona)->lockForUpdate()->first();
                $usuarioExistente = $usuario !== null;
                $rolEstudiante = Rol::firstOrCreate(['nombre' => 'Estudiante']);

                if ($usuarioExistente && ! in_array($validated['accion_credenciales'] ?? null, ['mantener', 'regenerar'], true)) {
                    throw ValidationException::withMessages([
                        'accion_credenciales' => 'Confirme si desea conservar o restablecer las credenciales de acceso existentes.',
                    ]);
                }

                if (! $usuario) {
                    $anio = now()->format('Y');
                    $ultimoUsuario = User::where('usuario', 'LIKE', $anio.'%')
                        ->orderByDesc('usuario')
                        ->lockForUpdate()
                        ->first();
                    $contador = $ultimoUsuario
                        ? ((int) substr($ultimoUsuario->usuario, 4)) + 1
                        : 1;
                    $nombreUsuario = $anio.str_pad($contador, 4, '0', STR_PAD_LEFT);
                    $contrasena = Str::password(10, letters: true, numbers: true, symbols: false, spaces: false);
                    $contrasenaHash = Hash::make($contrasena);

                    $usuario = User::create([
                        'id_persona' => $idPersona,
                        'usuario' => $nombreUsuario,
                        'password' => $contrasenaHash,
                    ]);

                    $credenciales = [
                        'usuario' => $usuario,
                        'contrasena' => $contrasena,
                        'rol' => $rolEstudiante,
                        'persona' => DB::table('personas')->where('id_persona', $idPersona)->first(),
                        'carrera' => DB::table('carreras')
                            ->where('id_carrera', $primeraPostulacion->id_carrera)
                            ->value('nombre'),
                    ];
                }

                if ($usuario && ! Cuenta::where('id_persona', $idPersona)
                    ->where('id_rol', $rolEstudiante->id_rol)
                    ->exists()) {
                    Cuenta::create([
                        'id_persona' => $idPersona,
                        'id_rol' => $rolEstudiante->id_rol,
                        'usuario' => $usuario->usuario,
                        'password' => $usuario->password,
                    ]);
                }

                if ($usuarioExistente && $validated['accion_credenciales'] === 'regenerar') {
                    $contrasena = Str::password(10, letters: true, numbers: true, symbols: false, spaces: false);
                    $usuario->password = Hash::make($contrasena);
                    $usuario->save();
                    Cuenta::where('id_persona', $idPersona)
                        ->where('id_rol', $rolEstudiante->id_rol)
                        ->update([
                            'usuario' => $usuario->usuario,
                            'password' => $usuario->password,
                        ]);
                    $credenciales = [
                        'usuario' => $usuario,
                        'contrasena' => $contrasena,
                        'rol' => $rolEstudiante,
                        'persona' => DB::table('personas')->where('id_persona', $idPersona)->first(),
                        'carrera' => DB::table('carreras')
                            ->where('id_carrera', $primeraPostulacion->id_carrera)
                            ->value('nombre'),
                    ];
                }

                foreach ($postulaciones as $postulante) {
                    $idPostulante = (int) $postulante->id_postulante;
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
                        'deposito' => $voucherPorId[$idPostulante],
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
                            'deposito' => $voucherPorId[$idPostulante],
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
                return [
                    'credenciales' => $credenciales,
                    'cuentaExistente' => $usuarioExistente,
                ];
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()->withInput()->withErrors([
                'error' => 'No se pudo completar el lote. Las postulaciones siguen disponibles para corregir o reintentar.',
            ]);
        }

        if ($resultado['credenciales']) {
            $credenciales = $resultado['credenciales'];
            $datosPdf = [
                'titulo' => 'COMPROBANTE DE CREDENCIALES DE ACCESO',
                'user' => (object) [
                    'name' => trim(implode(' ', array_filter([
                        $credenciales['persona']->nombre,
                        $credenciales['persona']->primerapellido,
                        $credenciales['persona']->segundoapellido,
                    ]))),
                    'ci' => $credenciales['persona']->ci,
                    'role' => $credenciales['rol']->nombre,
                    'carrera' => $credenciales['carrera'] ?? '-',
                    'email' => $credenciales['persona']->email,
                    'username' => $credenciales['usuario']->usuario,
                ],
                'plainPassword' => $credenciales['contrasena'],
                'fecha' => now()->format('d/m/Y H:i'),
            ];

            return Pdf::loadView('pdf.credenciales', $datosPdf)
                ->stream('credenciales_'.$credenciales['usuario']->usuario.'.pdf');
        }

        $mensaje = $resultado['cuentaExistente']
            ? 'Inscripciones registradas. Se conservó la cuenta existente y no se generaron credenciales nuevas.'
            : 'Inscripciones registradas correctamente.';

        return redirect()->route('estudiante_nuevo_postulacion.index', [
            'id_gestionacademica' => $validated['id_gestionacademica'],
        ])->with('success', $mensaje);
    }
}
