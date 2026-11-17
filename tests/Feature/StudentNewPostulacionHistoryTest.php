<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureFunctionalityAccess;
use App\Models\Carrera;
use App\Models\Cuenta;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\OpensPublicPostulationWindow;
use Tests\TestCase;

class StudentNewPostulacionHistoryTest extends TestCase
{
    use OpensPublicPostulationWindow;
    use RefreshDatabase;

    public function test_enrollment_keeps_postulant_history_and_assigns_first_level_automatically(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestion = GestionAcademica::create([
            'gestion' => now()->year - 1,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $this->openPublicPostulationWindow($gestion);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'TEST-SIS',
            'nombre' => 'Sistemas de Prueba',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $carreraGastronomia = Carrera::create([
            'codigo' => 'TEST-GAS',
            'nombre' => 'Gastronomía de Prueba',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $postulante = Postulante::create([
            'codigo' => 'TEST-POST-001',
            'ci' => 'POSTULANTE-001',
            'primerapellido' => 'Quispe',
            'segundoapellido' => 'Mamani',
            'nombre' => 'Valeria',
            'celular' => '70000001',
            'telefono' => '2000001',
            'email' => 'valeria@example.test',
            'direccion' => 'El Alto, La Paz',
            'fechanacimiento' => '2005-04-15',
            'lugarnacimiento' => 'El Alto',
            'lugarexpedicion' => 'La Paz',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'FEMENINO',
            'foto' => 'postulantes/foto.jpg',
            'fecha' => now(),
            'nombrecolegio' => 'U.E. de Prueba',
            'caractercolegio' => 'FISCAL',
            'egresocolegio' => 2024,
            'diplomabachiller' => true,
            'nombrecontacto' => 'Rosa Mamani',
            'parentescocontacto' => 'Madre',
            'celularcontacto' => '70000002',
            'deposito' => 'POST-VOUCHER-001',
            'path_ci_frontal' => 'postulantes/ci-frontal.jpg',
            'path_ci_reverso' => 'postulantes/ci-reverso.jpg',
            'path_nacimiento' => 'postulantes/nacimiento.jpg',
            'path_diploma' => 'postulantes/diploma.jpg',
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'turno' => 1,
            'paralelo' => 'A',
        ]);
        $postulanteGastronomia = $postulante->replicate();
        $postulanteGastronomia->codigo = 'TEST-POST-GAS-001';
        $postulanteGastronomia->deposito = 'POST-VOUCHER-GAS-001';
        $postulanteGastronomia->id_carrera = $carreraGastronomia->id_carrera;
        $postulanteGastronomia->turno = 3;
        $postulanteGastronomia->paralelo = 'B';
        $postulanteGastronomia->save();

        $indexResponse = $this->get(route('estudiante_nuevo_postulacion.index'))
            ->assertOk()
            ->assertSee('sweetalert2@11', false)
            ->assertSee('sweetalert-notifications.js', false)
            ->assertDontSee('POSTULANTE-001')
            ->assertSee('id="btnSearchPostulante"', false)
            ->assertSee('id="avisoEstudianteExistente"', false)
            ->assertSee('Se reutilizará esa ficha y solo se agregará la carrera que se inscriba.', false)
            ->assertDontSee('Nivel autorizado')
            ->assertDontSee('nivelinicio')
            ->assertSee('postulaciones.forEach((postulacion, index) => {', false)
            ->assertSee('postulaciones[${index}][id_postulante]', false)
            ->assertSee('postulaciones[${index}][deposito]', false)
            ->assertSee('postulaciones[${index}][decision]', false);
        $busquedaInicial = $this->get(route('estudiante_nuevo_postulacion.buscar', [
            'q' => 'POSTULANTE-001',
        ]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonCount(2, 'postulantes')
            ->json('postulantes');
        $datosPrecargados = collect($busquedaInicial)->first();
        $this->assertSame(1, $datosPrecargados['turno']);
        $this->assertSame('A', $datosPrecargados['paralelo']);
        $datosGastronomia = collect($busquedaInicial)
            ->firstWhere('id', (string) $postulanteGastronomia->id_postulante);
        $this->assertSame(3, $datosGastronomia['turno']);
        $this->assertSame('B', $datosGastronomia['paralelo']);
        $this->assertSame('LP', $datosPrecargados['lugarexpedicion']);
        $this->assertSame('F', $datosPrecargados['sexo']);
        $this->assertSame('Fiscal', $datosPrecargados['caractercolegio']);
        $this->assertSame('postulantes/ci-frontal.jpg', $datosPrecargados['pathci']);
        $this->assertSame('postulantes/ci-reverso.jpg', $datosPrecargados['pathciReverso']);
        $this->assertSame('postulantes/nacimiento.jpg', $datosPrecargados['pathnac']);
        $this->assertSame('postulantes/diploma.jpg', $datosPrecargados['pathdip']);
        $this->assertSame('postulantes/foto.jpg', $datosPrecargados['foto']);

        $carreraActualizada = Carrera::create([
            'codigo' => 'TEST-ACT',
            'nombre' => 'Carrera Actualizada de Prueba',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $postulante->update(['id_carrera' => $carreraActualizada->id_carrera]);
        $busquedaActualizada = $this->get(route('estudiante_nuevo_postulacion.buscar', [
            'q' => 'POSTULANTE-001',
        ]))
            ->assertOk()
            ->json('postulantes');
        $postulacionActualizada = collect($busquedaActualizada)
            ->firstWhere('id', (string) $postulante->id_postulante);
        $this->assertSame($carreraActualizada->nombre, $postulacionActualizada['carreraNombre']);
        $postulante->update(['id_carrera' => $carrera->id_carrera]);

        $datosRegistro = [
            'nombre' => 'Valeria',
            'primerapellido' => 'Quispe',
            'segundoapellido' => 'Mamani',
            'ci' => 'POSTULANTE-001',
            'lugarexpedicion' => 'LP',
            'fechanacimiento' => '2005-04-15',
            'lugarnacimiento' => 'El Alto',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'direccion' => 'El Alto, La Paz',
            'telefono' => '2000001',
            'celular' => '70000001',
            'email' => 'valeria@example.test',
            'nombrecontacto' => 'Rosa Mamani',
            'celularcontacto' => '70000002',
            'parentescocontacto' => 'Madre',
            'nombrecolegio' => 'U.E. de Prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'fecha' => now()->toDateString(),
            'diplomabachiller' => 1,
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ];
        $this->post(route('estudiante_nuevo_postulacion.store'), $datosRegistro + [
            'postulaciones' => [
                [
                    'id_postulante' => $postulante->id_postulante,
                    'decision' => 'no',
                ],
                [
                    'id_postulante' => $postulanteGastronomia->id_postulante,
                    'decision' => 'no',
                ],
            ],
        ])->assertSessionHasErrors('postulaciones');

        $response = $this->post(route('estudiante_nuevo_postulacion.store'), $datosRegistro + [
            'postulaciones' => [
                [
                    'id_postulante' => $postulante->id_postulante,
                    'decision' => 'si',
                    'deposito' => 'POST-VOUCHER-001',
                ],
                [
                    'id_postulante' => $postulanteGastronomia->id_postulante,
                    'decision' => 'no',
                ],
            ],
        ]);
        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $this->assertDatabaseHas('postulante', [
            'id_postulante' => $postulante->id_postulante,
            'ci' => 'POSTULANTE-001',
        ]);
        $this->assertDatabaseHas('inscrito', [
            'id_postulante_origen' => $postulante->id_postulante,
            'id_persona' => Persona::where('ci', 'POSTULANTE-001')->value('id_persona'),
            'turno' => 1,
            'paralelo' => 'A',
        ]);
        $this->assertDatabaseHas('cursa', [
            'id_persona' => Persona::where('ci', 'POSTULANTE-001')->value('id_persona'),
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'nivelinicio' => 1,
        ]);
        $idPersona = Persona::where('ci', 'POSTULANTE-001')->value('id_persona');
        $carrerasEstudianteRegular = $this->getJson(route('estudiante_regular.buscar', [
            'id_persona' => $idPersona,
        ]))->assertOk()->json('0.carreras');
        $this->assertTrue(collect($carrerasEstudianteRegular)
            ->firstWhere('id', $carrera->id_carrera)['ya_inscrito']);
        $this->assertDatabaseHas('estudiante', [
            'id_persona' => $idPersona,
            'nombrecolegio' => 'U.E. de Prueba',
            'caractercolegio' => 'Fiscal',
            'path_ci' => 'postulantes/ci-frontal.jpg',
            'path_ci_reverso' => 'postulantes/ci-reverso.jpg',
            'path_nacimiento' => 'postulantes/nacimiento.jpg',
            'path_diploma' => 'postulantes/diploma.jpg',
        ]);
        $this->assertDatabaseHas('personas', [
            'id_persona' => $idPersona,
            'foto' => 'postulantes/foto.jpg',
        ]);
        $usuario = User::where('id_persona', $idPersona)->firstOrFail();
        $cuenta = Cuenta::where('id_persona', $idPersona)->firstOrFail();
        $this->assertMatchesRegularExpression('/^'.now()->format('Y').'\d{4}$/', $usuario->usuario);
        $this->assertSame($usuario->usuario, $cuenta->usuario);
        $this->assertSame($usuario->password, $cuenta->password);
        $this->assertSame('Estudiante', $cuenta->rol->nombre);

        $this->post(route('estudiante_regular.store'), [
            'id_persona' => $idPersona,
            'fecha' => now()->toDateString(),
            'becado' => 0,
            'traspaso_convalidacion' => 0,
            'inscripciones' => [[
                'registrar' => 1,
                'nueva' => 0,
                'id_carrera' => $carrera->id_carrera,
                'nivelinicio' => 1,
                'turno' => 1,
                'paralelo' => 'A',
                'deposito' => 'REGULAR-DUPLICATE-VOUCHER',
            ]],
        ])->assertSessionHasErrors('inscripciones.0.id_carrera');
        $this->assertSame(1, DB::table('inscrito')
            ->where('id_persona', $idPersona)
            ->where('id_carrera', $carrera->id_carrera)
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->where('activo', true)
            ->count());
        $this->assertSame(1, DB::table('estudiante')->where('id_persona', $idPersona)->count());

        $postulacionPendiente = $this->get(route('estudiante_nuevo_postulacion.buscar', [
            'q' => 'POSTULANTE-001',
        ]))->assertOk();
        $postulacionesPendientes = collect($postulacionPendiente->json('postulantes'));
        $this->assertCount(1, $postulacionesPendientes);
        $this->assertSame((string) $postulanteGastronomia->id_postulante, $postulacionesPendientes->first()['id']);
        $this->assertTrue($postulacionesPendientes->first()['usuarioExistente']);
        $this->assertTrue($postulacionesPendientes->first()['estudianteExistente']);
        $this->assertSame($usuario->usuario, $postulacionesPendientes->first()['usuario']);
        $contrasenaAnterior = $usuario->password;

        $this->post(route('estudiante_nuevo_postulacion.store'), $datosRegistro + [
            'postulaciones' => [[
                'id_postulante' => $postulanteGastronomia->id_postulante,
                'decision' => 'si',
                'deposito' => 'POST-VOUCHER-GAS-001',
            ]],
        ])->assertSessionHasErrors('accion_credenciales');

        $this->post(route('estudiante_nuevo_postulacion.store'), $datosRegistro + [
            'postulaciones' => [[
                'id_postulante' => $postulanteGastronomia->id_postulante,
                'decision' => 'si',
                'deposito' => 'POST-VOUCHER-GAS-001',
            ]],
            'accion_credenciales' => 'mantener',
        ])->assertRedirect();

        $this->assertDatabaseHas('inscrito', [
            'id_postulante_origen' => $postulanteGastronomia->id_postulante,
            'id_persona' => $idPersona,
            'turno' => 3,
            'paralelo' => 'B',
        ]);
        $this->assertDatabaseHas('cursa', [
            'id_persona' => $idPersona,
            'id_carrera' => $carreraGastronomia->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'nivelinicio' => 1,
        ]);
        $this->assertSame($contrasenaAnterior, User::where('id_persona', $idPersona)->value('password'));
        $this->assertSame(1, DB::table('estudiante')->where('id_persona', $idPersona)->count());
        $this->assertSame(1, User::where('id_persona', $idPersona)->count());
        $this->assertSame(1, Cuenta::where('id_persona', $idPersona)->count());

        $this->get(route('estudiante_nuevo_postulacion.buscar', ['q' => 'POSTULANTE-001']))
            ->assertOk()
            ->assertJsonCount(0, 'postulantes');

        $carreraTercera = Carrera::create([
            'codigo' => 'TEST-TER',
            'nombre' => 'Tercera Carrera de Prueba',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $postulanteTercera = $postulante->replicate();
        $postulanteTercera->codigo = 'TEST-POST-TER-001';
        $postulanteTercera->deposito = 'POST-VOUCHER-TER-001';
        $postulanteTercera->id_carrera = $carreraTercera->id_carrera;
        $postulanteTercera->save();
        $this->get(route('estudiante_nuevo_postulacion.index'))
            ->assertOk()
            ->assertSee('POSTULANTE-001');
        $this->post(route('estudiante_nuevo_postulacion.store'), $datosRegistro + [
            'postulaciones' => [[
                'id_postulante' => $postulanteTercera->id_postulante,
                'decision' => 'si',
                'deposito' => 'POST-VOUCHER-TER-001',
            ]],
            'accion_credenciales' => 'regenerar',
        ])->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $usuarioActualizado = User::where('id_persona', $idPersona)->firstOrFail();
        $cuentaActualizada = Cuenta::where('id_persona', $idPersona)->firstOrFail();
        $this->assertSame($usuario->usuario, $usuarioActualizado->usuario);
        $this->assertNotSame($contrasenaAnterior, $usuarioActualizado->password);
        $this->assertSame($usuarioActualizado->usuario, $cuentaActualizada->usuario);
        $this->assertSame($usuarioActualizado->password, $cuentaActualizada->password);
        $this->assertSame(1, User::where('id_persona', $idPersona)->count());
        $this->assertSame(1, Cuenta::where('id_persona', $idPersona)->count());
        $this->assertSame(1, DB::table('estudiante')->where('id_persona', $idPersona)->count());

        $this->get(route('admisiones.postulantes.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]))
            ->assertOk()
            ->assertSee('POSTULANTE-001')
            ->assertSee((string) (now()->year - 1))
            ->assertSee((string) $gestion->periodo)
            ->assertSee('Inscrito como estudiante');
        $this->get(route('admisiones.postulantes.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'estado' => 'inscrito',
        ]))
            ->assertOk()
            ->assertSee('POSTULANTE-001');
        $this->get(route('admisiones.postulantes.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'estado' => 'pendiente',
        ]))
            ->assertOk()
            ->assertDontSee('POSTULANTE-001');

        $this->delete(route('postulantes.destroy', $postulante->id_postulante))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('postulante', [
            'id_postulante' => $postulante->id_postulante,
        ]);
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'ADMIN-POST-001',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_postulaciones',
            'password' => Hash::make('password123'),
        ]);
    }
}
