<?php

namespace Database\Seeders\Modulos;

use App\Models\Carrera;
use App\Models\Cuenta;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\Postulante;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class EstudiantesCasosPruebaSeeder extends Seeder
{
    private const PASSWORD = 'DemoEstudiante2026!';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Este seeder contiene datos de demostración y no debe ejecutarse en producción.');
        }

        $gestionActual = GestionAcademica::habilitadas()
            ->where('gestion', 2026)
            ->orderByDesc('periodo')
            ->first();

        if (! $gestionActual) {
            throw new RuntimeException('Debe existir una gestión 2026 vigente y habilitada antes de ejecutar este seeder.');
        }

        $carreras = [
            'SIS' => $this->carreraRequerida('SIS-2023'),
            'ADM' => $this->carreraRequerida('ADM-2023'),
            'SEJ' => $this->carreraRequerida('SEJ-2023'),
        ];
        $paralelosDemo = $this->paralelosDemo();
        $estudiantesDemo = $this->estudiantesDemo();
        $this->validarMateriasDemo($carreras, $estudiantesDemo);
        $documentos = $this->prepararDocumentos();
        $rolEstudiante = Rol::firstOrCreate(['nombre' => 'Estudiante']);

        DB::transaction(function () use ($gestionActual, $carreras, $documentos, $rolEstudiante, $paralelosDemo, $estudiantesDemo): void {
            $gestionHistorica = $this->gestionHistorica();
            $this->asegurarGruposDemo($gestionActual, $carreras, $paralelosDemo);

            foreach ($this->postulantesDemo() as $postulante) {
                $gestion = $postulante['historico'] ? $gestionHistorica : $gestionActual;

                foreach ($postulante['carreras'] as $codigoCarrera) {
                    $this->crearPostulacion(
                        $postulante,
                        $carreras[$codigoCarrera],
                        $gestion,
                        $documentos
                    );
                }
            }

            foreach ($estudiantesDemo as $indice => $estudiante) {
                $this->crearEstudiante(
                    $estudiante,
                    $indice + 1,
                    $gestionActual,
                    $gestionHistorica,
                    $carreras,
                    $documentos,
                    $rolEstudiante,
                    $paralelosDemo
                );
            }
        });

        if ($this->command) {
            $this->command->info('Casos de prueba creados. Contraseña de las cuentas demo: '.self::PASSWORD);
        }
    }

    private function paralelosDemo(): array
    {
        return [
            'SIS' => [
                ['paralelo' => 'A', 'turno' => 3, 'capacidad' => 30],
            ],
            'ADM' => [
                ['paralelo' => 'A', 'turno' => 3, 'capacidad' => 30],
                ['paralelo' => 'A', 'turno' => 1, 'capacidad' => 30],
            ],
            'SEJ' => [
                ['paralelo' => 'A', 'turno' => 3, 'capacidad' => 30],
            ],
        ];
    }

    private function validarMateriasDemo(array $carreras, array $estudiantes): void
    {
        foreach ($carreras as $carrera) {
            if (! Materia::activas()->where('id_carrera', $carrera->id_carrera)->exists()) {
                $sugerencia = $carrera->codigo === 'SIS-2023'
                    ? ' Ejecuta primero el seeder Database\\Seeders\\Modulos\\SistemasInformaticasMateriasDemoSeeder.'
                    : '';

                throw new RuntimeException(
                    "La carrera {$carrera->codigo} no tiene materias activas para crear sus grupos demo.{$sugerencia}"
                );
            }
        }

        foreach ($estudiantes as $estudiante) {
            foreach ($estudiante['carreras'] as $carreraDatos) {
                $nivel = $carreraDatos['nivel_actual'];
                if ($nivel === null) {
                    continue;
                }

                $carrera = $carreras[$carreraDatos['codigo']];
                $tieneMateria = Materia::activas()
                    ->where('id_carrera', $carrera->id_carrera)
                    ->where('nivel', $nivel)
                    ->exists();

                if (! $tieneMateria) {
                    $sugerencia = $carrera->codigo === 'SIS-2023'
                        ? ' Ejecuta primero el seeder Database\\Seeders\\Modulos\\SistemasInformaticasMateriasDemoSeeder para los niveles demo de Sistemas.'
                        : '';

                    throw new RuntimeException(
                        "La carrera {$carrera->codigo} no tiene materias activas en el nivel {$nivel}.{$sugerencia}"
                    );
                }
            }
        }
    }

    private function asegurarGruposDemo(GestionAcademica $gestion, array $carreras, array $paralelosDemo): void
    {
        foreach ($paralelosDemo as $codigoCarrera => $paralelos) {
            $carrera = $carreras[$codigoCarrera];
            $materias = Materia::activas()
                ->where('id_carrera', $carrera->id_carrera)
                ->orderBy('nivel')
                ->orderBy('numero')
                ->orderBy('id_materia')
                ->get();

            if ($materias->isEmpty()) {
                throw new RuntimeException("La carrera {$carrera->codigo} no tiene materias activas para crear sus grupos demo.");
            }

            foreach ($materias as $materia) {
                foreach ($paralelos as $paralelo) {
                    $codigoGrupo = $this->codigoGrupoDemo($gestion, $materia, $paralelo);
                    $existentes = Grupo::query()
                        ->where('id_gestionacademica', $gestion->id_gestionacademica)
                        ->where('codigo', $codigoGrupo)
                        ->get();
                    $existente = $existentes->first(
                        fn (Grupo $grupo): bool => (int) $grupo->id_materia === (int) $materia->id_materia
                            && (int) $grupo->turno === $paralelo['turno']
                    );

                    if ($existentes->isNotEmpty() && (! $existente || $existentes->count() !== 1)) {
                        throw new RuntimeException("El código {$codigoGrupo} ya existe con otra materia o turno en la gestión actual.");
                    }

                    if ($existente) {
                        if ((int) $existente->capacidad !== $paralelo['capacidad']
                            || ! $existente->activo
                            || ! $existente->abierto) {
                            throw new RuntimeException("El grupo {$codigoGrupo} existe con otro cupo o estado; no se modificó.");
                        }

                        continue;
                    }

                    Grupo::create([
                        'codigo' => $codigoGrupo,
                        'capacidad' => $paralelo['capacidad'],
                        'turno' => $paralelo['turno'],
                        'abierto' => true,
                        'activo' => true,
                        'id_persona' => null,
                        'id_gestionacademica' => $gestion->id_gestionacademica,
                        'id_materia' => $materia->id_materia,
                    ]);
                }
            }
        }
    }

    private function carreraRequerida(string $codigo): Carrera
    {
        $carrera = Carrera::where('codigo', $codigo)->where('activo', true)->first();

        if (! $carrera) {
            throw new RuntimeException("No se encontró la carrera activa {$codigo}. No se crearán carreras desde este seeder.");
        }

        return $carrera;
    }

    private function gestionHistorica(): GestionAcademica
    {
        $gestion = GestionAcademica::where('gestion', 2024)->where('periodo', 1)->first();

        if ($gestion) {
            return $gestion;
        }

        return GestionAcademica::create([
            'gestion' => 2024,
            'periodo' => 1,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'inicio' => '2024-01-01',
            'fin' => '2024-12-31',
            'vigente' => false,
            'activo' => false,
            'modalidadevaluacion' => 1,
        ]);
    }

    private function prepararDocumentos(): array
    {
        $origenes = [
            'carnet1.jpeg' => 'database/seeders/img_documentos/carnet1.jpeg',
            'carnet2.jpeg' => 'database/seeders/img_documentos/carnet2.jpeg',
            'certificado_de_nacimiento.webp' => 'database/seeders/img_documentos/certificado_de_nacimiento.webp',
            'diploma.webp' => 'database/seeders/img_documentos/diploma.webp',
        ];
        $rutas = [];
        $disco = Storage::disk('public');

        foreach ($origenes as $nombre => $origenRelativo) {
            $origen = base_path($origenRelativo);
            if (! is_file($origen) || ! is_readable($origen)) {
                throw new RuntimeException("No se encontró o no se puede leer el archivo demo {$origenRelativo}.");
            }

            $ruta = 'demo/inscripciones/documentos/'.$nombre;
            if (! $disco->put($ruta, file_get_contents($origen)) || ! $disco->exists($ruta)) {
                throw new RuntimeException("No se pudo copiar el documento demo a storage/app/public/{$ruta}.");
            }

            $rutas[$nombre] = $ruta;
        }

        return $rutas;
    }

    private function crearPostulacion(array $datos, Carrera $carrera, GestionAcademica $gestion, array $documentos): int
    {
        $deposito = 'DEMO-PAG-'.$gestion->gestion.'-'.$datos['codigo'].'-'.$carrera->codigo;
        $codigoPostulante = 'DEMO-'.$gestion->gestion.'-'.$datos['codigo'].'-'.$carrera->codigo;
        $existente = Postulante::where('ci', $datos['ci'])
            ->where('id_carrera', $carrera->id_carrera)
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->first();

        if ($existente) {
            $idPostulante = (int) $existente->id_postulante;
        } else {
            $idPostulante = (int) DB::table('postulante')->insertGetId(
                array_merge(
                    $this->datosPostulante($datos, $documentos),
                    [
                        'codigo' => $codigoPostulante,
                        'deposito' => $deposito,
                        'id_carrera' => $carrera->id_carrera,
                        'id_gestionacademica' => $gestion->id_gestionacademica,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ),
                'id_postulante'
            );
        }

        $convertido = DB::table('inscrito')->where('id_postulante_origen', $idPostulante)->exists();
        if (! $convertido) {
            $this->crearFinanzasPostulante($idPostulante, $datos, $carrera, $gestion, $deposito);
        }

        return $idPostulante;
    }

    private function datosPostulante(array $datos, array $documentos): array
    {
        $persona = $this->datosPersona($datos, 'postulante');

        return [
            'ci' => $datos['ci'],
            'primerapellido' => $datos['primerapellido'],
            'segundoapellido' => $datos['segundoapellido'],
            'nombre' => $datos['nombre'],
            'celular' => $persona['celular'],
            'telefono' => $persona['telefono'],
            'email' => $persona['email'],
            'direccion' => $persona['direccion'],
            'fechanacimiento' => $datos['fechanacimiento'],
            'lugarnacimiento' => 'Cochabamba, Bolivia',
            'lugarexpedicion' => 'CB',
            'nacionalidad' => 'Boliviana',
            'sexo' => $datos['sexo'],
            'foto' => $documentos['carnet1.jpeg'],
            'fecha' => $datos['historico'] ? '2024-02-15 09:00:00' : now(),
            'nombrecolegio' => $datos['colegio'],
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => $datos['egreso'],
            'diplomabachiller' => true,
            'nombrecontacto' => $datos['contacto'],
            'parentescocontacto' => $datos['parentesco'],
            'celularcontacto' => $this->celularContacto((int) $datos['numero']),
            'path_ci_frontal' => $documentos['carnet1.jpeg'],
            'path_ci_reverso' => $documentos['carnet2.jpeg'],
            'path_nacimiento' => $documentos['certificado_de_nacimiento.webp'],
            'path_diploma' => $documentos['diploma.webp'],
        ];
    }

    private function crearFinanzasPostulante(
        int $idPostulante,
        array $datos,
        Carrera $carrera,
        GestionAcademica $gestion,
        string $deposito
    ): void {
        if (DB::table('pago')->where('id_postulante', $idPostulante)->exists()) {
            return;
        }

        $ahora = now();
        $idPago = DB::table('pago')->insertGetId([
            'concepto' => 1,
            'monto' => 0,
            'pagado' => false,
            'estado' => true,
            'id_postulante' => $idPostulante,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], 'id_pago');

        $idComprobante = DB::table('comprobante')->where('id_postulante', $idPostulante)->value('id_comprobante');
        if (! $idComprobante) {
            $idComprobante = DB::table('comprobante')->insertGetId([
                'codigo' => null,
                'deposito' => $deposito,
                'fecha' => $datos['historico'] ? '2024-02-15 09:00:00' : $ahora,
                'valido' => false,
                'activo' => true,
                'id_postulante' => $idPostulante,
                'id_carrera' => $carrera->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ], 'id_comprobante');
        }

        DB::table('detalle')->insert([
            'concepto' => 1,
            'codigo' => 'DEMO-INSCRIPCION',
            'nombre' => 'Inscripción de demostración',
            'monto' => 0,
            'id_comprobante' => $idComprobante,
            'id_pago' => $idPago,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);
    }

    private function crearEstudiante(
        array $datos,
        int $numero,
        GestionAcademica $gestionActual,
        GestionAcademica $gestionHistorica,
        array $carreras,
        array $documentos,
        Rol $rolEstudiante,
        array $paralelosDemo
    ): void {
        $persona = Persona::firstOrCreate(
            ['ci' => $datos['ci']],
            $this->datosPersona($datos, 'estudiante', $documentos['carnet1.jpeg'])
        );
        $historialPostulantes = [];

        foreach ($datos['carreras'] as $carreraDatos) {
            $carrera = $carreras[$carreraDatos['codigo']];
            $historialPostulantes[$carreraDatos['codigo']] = $this->crearPostulacion(
                array_merge($datos, ['historico' => true, 'codigo' => sprintf('EST-%02d', $numero)]),
                $carrera,
                $gestionHistorica,
                $documentos
            );
        }

        $carreraPrincipal = $carreras[$datos['carreras'][0]['codigo']];
        $idPostulantePrincipal = $historialPostulantes[$datos['carreras'][0]['codigo']];
        $tieneInscripcionActual = collect($datos['carreras'])->contains(fn (array $carrera): bool => $carrera['nivel_actual'] !== null);
        $gestionPerfil = $tieneInscripcionActual ? $gestionActual : $gestionHistorica;
        $fechaPerfil = $tieneInscripcionActual ? now()->toDateString() : '2024-01-15';
        $depositoPerfil = sprintf('DEMO-EST-%04d', $numero);
        $pathBth = $datos['bth'] ? $documentos['diploma.webp'] : null;

        DB::table('estudiante')->updateOrInsert(
            ['id_persona' => $persona->id_persona],
            [
                'fecha' => $fechaPerfil,
                'matricula' => ((int) $gestionPerfil->gestion * 10000) + $numero,
                'diplomabachiller' => true,
                'nombrecontacto' => $datos['contacto'],
                'celularcontacto' => (int) $this->celularContacto($numero),
                'parentescocontacto' => $datos['parentesco'],
                'nombrecolegio' => $datos['colegio'],
                'caractercolegio' => 'Fiscal',
                'egresocolegio' => $datos['egreso'],
                'path_ci' => $documentos['carnet1.jpeg'],
                'path_ci_reverso' => $documentos['carnet2.jpeg'],
                'path_nacimiento' => $documentos['certificado_de_nacimiento.webp'],
                'path_diploma' => $documentos['diploma.webp'],
                'path_bth' => $pathBth,
                'becado' => false,
                'deposito' => $depositoPerfil,
                'traspaso_convalidacion' => $datos['traspaso_convalidacion'],
                'id_carrera' => $carreraPrincipal->id_carrera,
                'id_gestionacademica' => $gestionPerfil->id_gestionacademica,
                'id_postulante' => $idPostulantePrincipal,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $usuario = User::updateOrCreate(
            ['id_persona' => $persona->id_persona],
            [
                'usuario' => sprintf('demo-estudiante-%02d', $numero),
                'password' => Hash::make(self::PASSWORD),
            ]
        );
        Cuenta::updateOrCreate(
            [
                'id_persona' => $persona->id_persona,
                'id_rol' => $rolEstudiante->id_rol,
            ],
            [
                'usuario' => $usuario->usuario,
                'password' => $usuario->password,
            ]
        );

        foreach ($datos['carreras'] as $carreraDatos) {
            $carrera = $carreras[$carreraDatos['codigo']];
            $idPostulante = $historialPostulantes[$carreraDatos['codigo']];

            $idInscritoHistorico = $this->crearInscripcion(
                $persona->id_persona,
                $carrera,
                $gestionHistorica,
                $carreraDatos['nivel_historico'],
                $idPostulante,
                $numero,
                false,
                $datos['completado'] && $carreraDatos['codigo'] === 'SIS'
            );
            $this->vincularFinanzasPostulante($idPostulante, $idInscritoHistorico, $persona->id_persona);

            if ($carreraDatos['nivel_actual'] !== null) {
                $idInscritoActual = $this->crearInscripcion(
                    $persona->id_persona,
                    $carrera,
                    $gestionActual,
                    $carreraDatos['nivel_actual'],
                    null,
                    $numero,
                    true,
                    false
                );
                $this->asignarMateriasAParalelo(
                    $idInscritoActual,
                    $carrera,
                    $gestionActual,
                    $carreraDatos['nivel_actual'],
                    $carreraDatos['paralelo_actual'],
                    $paralelosDemo[$carreraDatos['codigo']]
                );
            }
        }
    }

    private function asignarMateriasAParalelo(
        int $idInscrito,
        Carrera $carrera,
        GestionAcademica $gestion,
        int $nivel,
        string $paralelo,
        array $paralelosDisponibles
    ): void {
        $paraleloElegido = collect($paralelosDisponibles)->firstWhere('paralelo', $paralelo);
        if (! $paraleloElegido) {
            throw new RuntimeException("No está configurado el paralelo {$paralelo} para {$carrera->codigo}.");
        }

        $materias = Materia::activas()
            ->where('id_carrera', $carrera->id_carrera)
            ->where('nivel', $nivel)
            ->orderBy('numero')
            ->orderBy('id_materia')
            ->get();

        if ($materias->isEmpty()) {
            throw new RuntimeException("No hay materias activas de nivel {$nivel} para {$carrera->codigo}.");
        }

        foreach ($materias as $materia) {
            $codigoGrupo = $this->codigoGrupoDemo($gestion, $materia, $paraleloElegido);
            $grupo = Grupo::query()
                ->where('id_gestionacademica', $gestion->id_gestionacademica)
                ->where('id_materia', $materia->id_materia)
                ->where('codigo', $codigoGrupo)
                ->where('turno', $paraleloElegido['turno'])
                ->first();

            if (! $grupo || ! $grupo->activo || ! $grupo->abierto) {
                throw new RuntimeException("No está disponible el grupo {$codigoGrupo} para {$gestion->gestion}.");
            }

            $asignacion = DB::table('inscrito_materia')
                ->where('id_inscrito', $idInscrito)
                ->where('id_materia', $materia->id_materia)
                ->first();

            if ($asignacion && $asignacion->id_grupo !== null && (int) $asignacion->id_grupo !== (int) $grupo->id_grupo) {
                throw new RuntimeException("El inscrito {$idInscrito} ya tiene {$materia->codigo} asignada a otro grupo.");
            }

            if (! $asignacion || $asignacion->id_grupo === null) {
                $ocupacion = DB::table('inscrito_materia')
                    ->where('id_grupo', $grupo->id_grupo)
                    ->where('id_inscrito', '<>', $idInscrito)
                    ->count();

                if ($ocupacion >= (int) $grupo->capacidad) {
                    throw new RuntimeException("El grupo {$codigoGrupo} alcanzó su capacidad de {$grupo->capacidad} estudiantes.");
                }

                DB::table('inscrito_materia')->updateOrInsert(
                    [
                        'id_inscrito' => $idInscrito,
                        'id_materia' => $materia->id_materia,
                    ],
                    [
                        'id_grupo' => $grupo->id_grupo,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    private function codigoGrupoDemo(GestionAcademica $gestion, Materia $materia, array $paralelo): string
    {
        $codigoBase = trim((string) $materia->codigo);
        if ($codigoBase === '') {
            $codigoBase = 'MAT-'.$materia->id_materia;
        }

        $codigoGrupo = $codigoBase.'-'.$paralelo['paralelo'];
        $ocupadoPorOtroTurnoOMateria = Grupo::query()
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->where('codigo', $codigoGrupo)
            ->where(function ($query) use ($materia, $paralelo): void {
                $query->where('id_materia', '<>', $materia->id_materia)
                    ->orWhere('turno', '<>', $paralelo['turno']);
            })
            ->exists();

        return $ocupadoPorOtroTurnoOMateria ? 'DEMO-'.$codigoGrupo : $codigoGrupo;
    }

    private function crearInscripcion(
        int $idPersona,
        Carrera $carrera,
        GestionAcademica $gestion,
        int $nivel,
        ?int $idPostulante,
        int $numeroEstudiante,
        bool $actual,
        bool $completado
    ): int {
        $inscrito = DB::table('inscrito')
            ->where('id_persona', $idPersona)
            ->where('id_carrera', $carrera->id_carrera)
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->first();
        $deposito = $idPostulante
            ? Postulante::where('id_postulante', $idPostulante)->value('deposito')
            : sprintf('DEMO-INS-%d-%02d-%s', $gestion->gestion, $numeroEstudiante, $carrera->codigo);
        $fecha = $actual ? now()->toDateString() : '2024-01-15';

        if ($inscrito) {
            $idInscrito = (int) $inscrito->id_inscrito;
        } else {
            $idInscrito = (int) DB::table('inscrito')->insertGetId([
                'codigo' => ((int) $gestion->gestion * 10000) + $numeroEstudiante,
                'fecha' => $fecha,
                'numero' => DB::table('inscrito')->where('id_persona', $idPersona)
                    ->where('id_gestionacademica', $gestion->id_gestionacademica)->count() + 1,
                'tipo' => $actual ? 2 : 1,
                'becado' => false,
                'activo' => ! $completado,
                'deposito' => $deposito,
                'id_postulante_origen' => $idPostulante,
                'id_carrera' => $carrera->id_carrera,
                'id_persona' => $idPersona,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id_inscrito');
        }

        DB::table('cursa')->updateOrInsert(
            [
                'id_persona' => $idPersona,
                'id_carrera' => $carrera->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
            ],
            [
                'nivelinicio' => $nivel,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if ($actual) {
            $this->crearFinanzasInscripcion($idInscrito, $idPersona, $carrera, $gestion, $deposito);
        }

        return $idInscrito;
    }

    private function vincularFinanzasPostulante(int $idPostulante, int $idInscrito, int $idPersona): void
    {
        $ahora = now();
        DB::table('pago')->where('id_postulante', $idPostulante)->update([
            'id_inscrito' => $idInscrito,
            'id_postulante' => null,
            'pagado' => true,
            'updated_at' => $ahora,
        ]);
        DB::table('comprobante')->where('id_postulante', $idPostulante)->update([
            'id_inscrito' => $idInscrito,
            'id_postulante' => null,
            'id_estudiante' => $idPersona,
            'valido' => true,
            'updated_at' => $ahora,
        ]);
    }

    private function crearFinanzasInscripcion(
        int $idInscrito,
        int $idPersona,
        Carrera $carrera,
        GestionAcademica $gestion,
        string $deposito
    ): void {
        if (DB::table('pago')->where('id_inscrito', $idInscrito)->exists()) {
            return;
        }

        $ahora = now();
        $idPago = DB::table('pago')->insertGetId([
            'concepto' => 1,
            'monto' => 0,
            'pagado' => true,
            'estado' => true,
            'id_inscrito' => $idInscrito,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], 'id_pago');
        $idComprobante = DB::table('comprobante')->where('id_inscrito', $idInscrito)->value('id_comprobante');

        if (! $idComprobante) {
            $idComprobante = DB::table('comprobante')->insertGetId([
                'codigo' => null,
                'deposito' => $deposito,
                'fecha' => $ahora,
                'valido' => true,
                'activo' => true,
                'id_inscrito' => $idInscrito,
                'id_usuario' => $idPersona,
                'id_estudiante' => $idPersona,
                'id_carrera' => $carrera->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ], 'id_comprobante');
        }

        DB::table('detalle')->insert([
            'concepto' => 1,
            'codigo' => 'DEMO-INSCRIPCION',
            'nombre' => 'Inscripción de demostración',
            'monto' => 0,
            'id_comprobante' => $idComprobante,
            'id_pago' => $idPago,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);
    }

    private function datosPersona(array $datos, string $tipo, ?string $foto = null): array
    {
        $numero = (int) $datos['numero'];
        $celular = $this->celular($numero);

        return [
            'tipo' => $tipo,
            'nombre' => $datos['nombre'],
            'primerapellido' => $datos['primerapellido'],
            'segundoapellido' => $datos['segundoapellido'],
            'ci' => $datos['ci'],
            'lugarexpedicion' => 'CB',
            'fechanacimiento' => $datos['fechanacimiento'],
            'lugarnacimiento' => 'Cochabamba, Bolivia',
            'nacionalidad' => 'Boliviana',
            'sexo' => $datos['sexo'],
            'direccion' => 'Zona Central, Cochabamba (domicilio de demostración)',
            'telefono' => 4400000 + $numero,
            'celular' => (int) $celular,
            'email' => strtolower(str_replace(' ', '.', $datos['codigo'])).'@example.test',
            'foto' => $foto,
        ];
    }

    private function celular(int $numero): string
    {
        return (string) (70000000 + $numero);
    }

    private function celularContacto(int $numero): string
    {
        return (string) (71000000 + $numero);
    }

    private function postulantesDemo(): array
    {
        return [
            ['numero' => 1, 'codigo' => 'POST-01', 'ci' => 'DEMO-POST-001', 'nombre' => 'Ana Lucía', 'primerapellido' => 'Rojas', 'segundoapellido' => 'Huanca', 'sexo' => 'F', 'fechanacimiento' => '2005-04-12', 'colegio' => 'Unidad Educativa Demo Andina', 'egreso' => 2023, 'contacto' => 'Rosa Huanca', 'parentesco' => 'Madre', 'carreras' => ['SIS', 'ADM'], 'historico' => false],
            ['numero' => 2, 'codigo' => 'POST-02', 'ci' => 'DEMO-POST-002', 'nombre' => 'Marco Antonio', 'primerapellido' => 'Flores', 'segundoapellido' => 'Quispe', 'sexo' => 'M', 'fechanacimiento' => '2004-09-22', 'colegio' => 'Unidad Educativa Demo del Valle', 'egreso' => 2022, 'contacto' => 'Elena Quispe', 'parentesco' => 'Madre', 'carreras' => ['SIS', 'SEJ'], 'historico' => false],
            ['numero' => 3, 'codigo' => 'POST-03', 'ci' => 'DEMO-POST-003', 'nombre' => 'Daniela Paola', 'primerapellido' => 'Mamani', 'segundoapellido' => 'Vargas', 'sexo' => 'F', 'fechanacimiento' => '2005-02-07', 'colegio' => 'Unidad Educativa Demo Central', 'egreso' => 2023, 'contacto' => 'Julia Vargas', 'parentesco' => 'Madre', 'carreras' => ['ADM', 'SEJ'], 'historico' => false],
            ['numero' => 4, 'codigo' => 'POST-04', 'ci' => 'DEMO-POST-004', 'nombre' => 'Luis Fernando', 'primerapellido' => 'Choque', 'segundoapellido' => 'Rojas', 'sexo' => 'M', 'fechanacimiento' => '2003-11-18', 'colegio' => 'Unidad Educativa Demo Norte', 'egreso' => 2021, 'contacto' => 'Marta Rojas', 'parentesco' => 'Madre', 'carreras' => ['SIS', 'ADM'], 'historico' => false],
            ['numero' => 5, 'codigo' => 'POST-05', 'ci' => 'DEMO-POST-005', 'nombre' => 'Camila Andrea', 'primerapellido' => 'Condori', 'segundoapellido' => 'Flores', 'sexo' => 'F', 'fechanacimiento' => '2005-06-21', 'colegio' => 'Unidad Educativa Demo Sur', 'egreso' => 2023, 'contacto' => 'Patricia Flores', 'parentesco' => 'Madre', 'carreras' => ['ADM', 'SIS'], 'historico' => false],
            ['numero' => 6, 'codigo' => 'POST-06', 'ci' => 'DEMO-POST-006', 'nombre' => 'Gabriel Esteban', 'primerapellido' => 'Vargas', 'segundoapellido' => 'Limachi', 'sexo' => 'M', 'fechanacimiento' => '2004-01-12', 'colegio' => 'Unidad Educativa Demo Andina', 'egreso' => 2022, 'contacto' => 'Teresa Limachi', 'parentesco' => 'Madre', 'carreras' => ['SIS'], 'historico' => false],
            ['numero' => 7, 'codigo' => 'POST-07', 'ci' => 'DEMO-POST-007', 'nombre' => 'Valentina Sofía', 'primerapellido' => 'Montaño', 'segundoapellido' => 'Pérez', 'sexo' => 'F', 'fechanacimiento' => '2005-08-03', 'colegio' => 'Unidad Educativa Demo del Valle', 'egreso' => 2023, 'contacto' => 'Carmen Pérez', 'parentesco' => 'Madre', 'carreras' => ['ADM'], 'historico' => false],
            ['numero' => 8, 'codigo' => 'POST-08', 'ci' => 'DEMO-POST-008', 'nombre' => 'Mateo Javier', 'primerapellido' => 'Ayllón', 'segundoapellido' => 'Cruz', 'sexo' => 'M', 'fechanacimiento' => '2004-05-30', 'colegio' => 'Unidad Educativa Demo Central', 'egreso' => 2022, 'contacto' => 'Nora Cruz', 'parentesco' => 'Madre', 'carreras' => ['SEJ'], 'historico' => false],
            ['numero' => 9, 'codigo' => 'POST-09', 'ci' => 'DEMO-POST-009', 'nombre' => 'Natalia Fernanda', 'primerapellido' => 'Quiroga', 'segundoapellido' => 'Mercado', 'sexo' => 'F', 'fechanacimiento' => '2002-11-11', 'colegio' => 'Unidad Educativa Demo Norte', 'egreso' => 2020, 'contacto' => 'Silvia Mercado', 'parentesco' => 'Madre', 'carreras' => ['SIS'], 'historico' => true],
            ['numero' => 10, 'codigo' => 'POST-10', 'ci' => 'DEMO-POST-010', 'nombre' => 'Diego Alejandro', 'primerapellido' => 'Paredes', 'segundoapellido' => 'Arce', 'sexo' => 'M', 'fechanacimiento' => '2003-01-09', 'colegio' => 'Unidad Educativa Demo Sur', 'egreso' => 2021, 'contacto' => 'Laura Arce', 'parentesco' => 'Madre', 'carreras' => ['SIS'], 'historico' => true],
        ];
    }

    private function estudiantesDemo(): array
    {
        return [
            ['numero' => 1, 'codigo' => 'EST-01', 'ci' => 'DEMO-EST-001', 'nombre' => 'Andrea Camila', 'primerapellido' => 'Quispe', 'segundoapellido' => 'Mamani', 'sexo' => 'F', 'fechanacimiento' => '2004-07-18', 'colegio' => 'Unidad Educativa Demo Andina', 'egreso' => 2022, 'contacto' => 'Rosa Mamani', 'parentesco' => 'Madre', 'bth' => false, 'traspaso_convalidacion' => false, 'completado' => false, 'carreras' => [['codigo' => 'SIS', 'nivel_historico' => 2, 'nivel_actual' => null]]],
            ['numero' => 2, 'codigo' => 'EST-02', 'ci' => 'DEMO-EST-002', 'nombre' => 'Jorge Daniel', 'primerapellido' => 'Rojas', 'segundoapellido' => 'Choque', 'sexo' => 'M', 'fechanacimiento' => '2002-03-26', 'colegio' => 'Unidad Educativa Demo del Valle', 'egreso' => 2020, 'contacto' => 'Elena Choque', 'parentesco' => 'Madre', 'bth' => false, 'traspaso_convalidacion' => false, 'completado' => true, 'carreras' => [['codigo' => 'SIS', 'nivel_historico' => 3, 'nivel_actual' => null]]],
            ['numero' => 3, 'codigo' => 'EST-03', 'ci' => 'DEMO-EST-003', 'nombre' => 'María Fernanda', 'primerapellido' => 'Aguilar', 'segundoapellido' => 'Flores', 'sexo' => 'F', 'fechanacimiento' => '2003-08-14', 'colegio' => 'Unidad Educativa Demo Central', 'egreso' => 2021, 'contacto' => 'Julia Flores', 'parentesco' => 'Madre', 'bth' => true, 'traspaso_convalidacion' => false, 'completado' => false, 'carreras' => [['codigo' => 'ADM', 'nivel_historico' => 1, 'nivel_actual' => 2, 'paralelo_actual' => 'A']]],
            ['numero' => 4, 'codigo' => 'EST-04', 'ci' => 'DEMO-EST-004', 'nombre' => 'José Miguel', 'primerapellido' => 'Soria', 'segundoapellido' => 'Mamani', 'sexo' => 'M', 'fechanacimiento' => '2004-02-10', 'colegio' => 'Unidad Educativa Demo Norte', 'egreso' => 2022, 'contacto' => 'Marta Mamani', 'parentesco' => 'Madre', 'bth' => true, 'traspaso_convalidacion' => false, 'completado' => false, 'carreras' => [['codigo' => 'SEJ', 'nivel_historico' => 1, 'nivel_actual' => 2, 'paralelo_actual' => 'A']]],
            ['numero' => 5, 'codigo' => 'EST-05', 'ci' => 'DEMO-EST-005', 'nombre' => 'Sofía Belén', 'primerapellido' => 'Rocha', 'segundoapellido' => 'Quispe', 'sexo' => 'F', 'fechanacimiento' => '2002-12-02', 'colegio' => 'Unidad Educativa Demo Sur', 'egreso' => 2020, 'contacto' => 'Patricia Quispe', 'parentesco' => 'Madre', 'bth' => false, 'traspaso_convalidacion' => true, 'completado' => false, 'carreras' => [['codigo' => 'SIS', 'nivel_historico' => 2, 'nivel_actual' => 3, 'paralelo_actual' => 'A']]],
            ['numero' => 6, 'codigo' => 'EST-06', 'ci' => 'DEMO-EST-006', 'nombre' => 'Diego Andrés', 'primerapellido' => 'Lima', 'segundoapellido' => 'Vargas', 'sexo' => 'M', 'fechanacimiento' => '2003-05-23', 'colegio' => 'Unidad Educativa Demo Andina', 'egreso' => 2021, 'contacto' => 'Teresa Vargas', 'parentesco' => 'Madre', 'bth' => false, 'traspaso_convalidacion' => true, 'completado' => false, 'carreras' => [['codigo' => 'ADM', 'nivel_historico' => 1, 'nivel_actual' => 2, 'paralelo_actual' => 'A']]],
            ['numero' => 7, 'codigo' => 'EST-07', 'ci' => 'DEMO-EST-007', 'nombre' => 'Paola Andrea', 'primerapellido' => 'Ortega', 'segundoapellido' => 'Condori', 'sexo' => 'F', 'fechanacimiento' => '2004-09-05', 'colegio' => 'Unidad Educativa Demo del Valle', 'egreso' => 2022, 'contacto' => 'Carmen Condori', 'parentesco' => 'Madre', 'bth' => false, 'traspaso_convalidacion' => true, 'completado' => false, 'carreras' => [['codigo' => 'SEJ', 'nivel_historico' => 1, 'nivel_actual' => 2, 'paralelo_actual' => 'A']]],
            ['numero' => 8, 'codigo' => 'EST-08', 'ci' => 'DEMO-EST-008', 'nombre' => 'Carlos Eduardo', 'primerapellido' => 'Alanes', 'segundoapellido' => 'Choque', 'sexo' => 'M', 'fechanacimiento' => '2002-06-16', 'colegio' => 'Unidad Educativa Demo Central', 'egreso' => 2020, 'contacto' => 'Nora Choque', 'parentesco' => 'Madre', 'bth' => false, 'traspaso_convalidacion' => true, 'completado' => false, 'carreras' => [['codigo' => 'SIS', 'nivel_historico' => 2, 'nivel_actual' => 3, 'paralelo_actual' => 'A']]],
            ['numero' => 9, 'codigo' => 'EST-09', 'ci' => 'DEMO-EST-009', 'nombre' => 'Daniela Marisol', 'primerapellido' => 'Fernández', 'segundoapellido' => 'Rojas', 'sexo' => 'F', 'fechanacimiento' => '2003-10-28', 'colegio' => 'Unidad Educativa Demo Norte', 'egreso' => 2021, 'contacto' => 'Silvia Rojas', 'parentesco' => 'Madre', 'bth' => false, 'traspaso_convalidacion' => false, 'completado' => false, 'carreras' => [['codigo' => 'SIS', 'nivel_historico' => 1, 'nivel_actual' => 2, 'paralelo_actual' => 'A'], ['codigo' => 'ADM', 'nivel_historico' => 1, 'nivel_actual' => 2, 'paralelo_actual' => 'A']]],
            ['numero' => 10, 'codigo' => 'EST-10', 'ci' => 'DEMO-EST-010', 'nombre' => 'Pablo Andrés', 'primerapellido' => 'Rojas', 'segundoapellido' => 'Montaño', 'sexo' => 'M', 'fechanacimiento' => '2002-01-19', 'colegio' => 'Unidad Educativa Demo Sur', 'egreso' => 2020, 'contacto' => 'Laura Montaño', 'parentesco' => 'Madre', 'bth' => false, 'traspaso_convalidacion' => false, 'completado' => false, 'carreras' => [['codigo' => 'ADM', 'nivel_historico' => 2, 'nivel_actual' => 3, 'paralelo_actual' => 'A'], ['codigo' => 'SEJ', 'nivel_historico' => 1, 'nivel_actual' => 2, 'paralelo_actual' => 'A']]],
            ['numero' => 11, 'codigo' => 'EST-11', 'ci' => 'DEMO-EST-011', 'nombre' => 'Lucía Belén', 'primerapellido' => 'Calderón', 'segundoapellido' => 'Siles', 'sexo' => 'F', 'fechanacimiento' => '2004-10-04', 'colegio' => 'Unidad Educativa Demo Andina', 'egreso' => 2022, 'contacto' => 'María Siles', 'parentesco' => 'Madre', 'bth' => false, 'traspaso_convalidacion' => false, 'completado' => false, 'carreras' => [['codigo' => 'SIS', 'nivel_historico' => 1, 'nivel_actual' => 2, 'paralelo_actual' => 'A']]],
            ['numero' => 12, 'codigo' => 'EST-12', 'ci' => 'DEMO-EST-012', 'nombre' => 'Omar Felipe', 'primerapellido' => 'Salvatierra', 'segundoapellido' => 'Paz', 'sexo' => 'M', 'fechanacimiento' => '2003-12-17', 'colegio' => 'Unidad Educativa Demo Central', 'egreso' => 2021, 'contacto' => 'Claudia Paz', 'parentesco' => 'Madre', 'bth' => false, 'traspaso_convalidacion' => false, 'completado' => false, 'carreras' => [['codigo' => 'ADM', 'nivel_historico' => 1, 'nivel_actual' => 2, 'paralelo_actual' => 'A']]],
        ];
    }
}
