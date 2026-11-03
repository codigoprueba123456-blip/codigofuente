<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Cuenta;
use App\Models\Estudiante;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class EstudianteRegularWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_search_only_returns_active_careers_associated_with_student_and_their_resolutions(): void
    {
        $this->withoutMiddleware();
        $fixture = $this->createFixture();

        $response = $this->getJson(route('estudiante_regular.buscar', [
            'id_persona' => $fixture['persona']->id_persona,
        ]))->assertOk();

        $this->assertSame(
            [
                [$fixture['carreraUno']->id_carrera, 'Sistemas Informáticos', ['0001/2020']],
                [$fixture['carreraDos']->id_carrera, 'Mecánica Automotriz', ['0210/2023']],
            ],
            collect($response->json()[0]['carreras'])
                ->map(fn (array $carrera): array => [$carrera['id'], $carrera['nombre'], $carrera['resoluciones']])
                ->sortBy(0)
                ->values()
                ->all()
        );
        $this->assertSame(
            [$fixture['carreraNoAsociada']->id_carrera],
            collect($response->json()[0]['carreras_disponibles'])
                ->pluck('id')
                ->all()
        );
    }

    public function test_registration_forms_display_existing_career_resolutions(): void
    {
        $this->withoutMiddleware();
        View::share('errors', new ViewErrorBag);
        $this->createFixture();

        $this->get(route('estudiante_nuevo.index'))
            ->assertOk()
            ->assertSee('Sistemas Informáticos')
            ->assertSee('R.M. 0001/2020')
            ->assertSee('Tercer año')
            ->assertDontSee('Cuarto año');

        $this->get(route('cambio_carrera.index'))
            ->assertOk()
            ->assertSee('Mecánica Automotriz')
            ->assertSee('R.M. 0210/2023');

        $this->get(route('estudiante_regular.index'))
            ->assertOk()
            ->assertSee('Año que cursará')
            ->assertSee('Añadir otra carrera');

        $this->get(route('postulantes.registrar'))
            ->assertOk()
            ->assertSee('Sistemas Informáticos')
            ->assertSee('R.M. 0001/2020');
    }

    public function test_regular_enrollment_can_register_one_associated_career_and_skip_another(): void
    {
        $this->withoutMiddleware();
        $fixture = $this->createFixture();

        $this->post(route('estudiante_regular.store'), [
            'id_persona' => $fixture['persona']->id_persona,
            'fecha' => now()->toDateString(),
            'becado' => 0,
            'traspaso_convalidacion' => 0,
            'inscripciones' => [
                [
                    'registrar' => 1,
                    'id_carrera' => $fixture['carreraUno']->id_carrera,
                    'nivelinicio' => 2,
                    'deposito' => 'REGULAR-VOUCHER-001',
                ],
                [
                    'registrar' => 0,
                    'id_carrera' => $fixture['carreraDos']->id_carrera,
                ],
            ],
        ])->assertRedirect(route('estudiante_regular.index'));

        $this->assertDatabaseCount('inscrito', 3);
        $this->assertDatabaseHas('inscrito', [
            'id_persona' => $fixture['persona']->id_persona,
            'id_carrera' => $fixture['carreraUno']->id_carrera,
            'id_gestionacademica' => $fixture['gestionActual']->id_gestionacademica,
        ]);
        $this->assertDatabaseMissing('inscrito', [
            'id_persona' => $fixture['persona']->id_persona,
            'id_carrera' => $fixture['carreraDos']->id_carrera,
            'id_gestionacademica' => $fixture['gestionActual']->id_gestionacademica,
        ]);
        $this->assertDatabaseHas('cursa', [
            'id_persona' => $fixture['persona']->id_persona,
            'id_carrera' => $fixture['carreraDos']->id_carrera,
            'id_gestionacademica' => $fixture['gestionAnterior']->id_gestionacademica,
        ]);
    }

    public function test_regular_enrollment_rejects_unassociated_careers_and_registering_none(): void
    {
        $this->withoutMiddleware();
        $fixture = $this->createFixture();
        $payload = [
            'id_persona' => $fixture['persona']->id_persona,
            'fecha' => now()->toDateString(),
            'becado' => 0,
            'traspaso_convalidacion' => 0,
            'inscripciones' => [[
                'registrar' => 1,
                'id_carrera' => $fixture['carreraNoAsociada']->id_carrera,
                'nivelinicio' => 2,
                'deposito' => 'REGULAR-VOUCHER-002',
            ]],
        ];

        $this->post(route('estudiante_regular.store'), $payload)
            ->assertSessionHasErrors('inscripciones.0.id_carrera');

        $payload['inscripciones'][0]['registrar'] = 0;
        $this->post(route('estudiante_regular.store'), $payload)
            ->assertSessionHasErrors('inscripciones');

        $this->assertDatabaseCount('inscrito', 2);
    }

    public function test_regular_student_can_add_another_career_without_reentering_personal_data(): void
    {
        $this->withoutMiddleware();
        $fixture = $this->createFixture();

        $this->post(route('estudiante_regular.store'), [
            'id_persona' => $fixture['persona']->id_persona,
            'fecha' => now()->toDateString(),
            'becado' => 0,
            'traspaso_convalidacion' => 0,
            'inscripciones' => [
                [
                    'registrar' => 1,
                    'nueva' => 0,
                    'id_carrera' => $fixture['carreraUno']->id_carrera,
                    'nivelinicio' => 3,
                    'deposito' => 'REGULAR-SISTEMAS-VOUCHER-001',
                ],
                [
                    'registrar' => 1,
                    'nueva' => 1,
                    'id_carrera' => $fixture['carreraNoAsociada']->id_carrera,
                    'nivelinicio' => 1,
                    'deposito' => 'NUEVA-CARRERA-VOUCHER-001',
                ],
            ],
        ])->assertRedirect(route('estudiante_regular.index'));

        $inscripcionesActuales = DB::table('inscrito')
            ->where('id_persona', $fixture['persona']->id_persona)
            ->where('id_gestionacademica', $fixture['gestionActual']->id_gestionacademica)
            ->get();
        $this->assertCount(2, $inscripcionesActuales);
        $this->assertSame(2, DB::table('pago')
            ->whereIn('id_inscrito', $inscripcionesActuales->pluck('id_inscrito'))
            ->count());
        $this->assertSame(2, DB::table('comprobante')
            ->whereIn('id_inscrito', $inscripcionesActuales->pluck('id_inscrito'))
            ->count());
        $this->assertSame(2, DB::table('detalle')
            ->whereIn('id_pago', DB::table('pago')
                ->whereIn('id_inscrito', $inscripcionesActuales->pluck('id_inscrito'))
                ->select('id_pago'))
            ->count());
        $this->assertDatabaseHas('inscrito', [
            'id_persona' => $fixture['persona']->id_persona,
            'id_carrera' => $fixture['carreraUno']->id_carrera,
            'id_gestionacademica' => $fixture['gestionActual']->id_gestionacademica,
        ]);
        $this->assertDatabaseHas('inscrito', [
            'id_persona' => $fixture['persona']->id_persona,
            'id_carrera' => $fixture['carreraNoAsociada']->id_carrera,
            'id_gestionacademica' => $fixture['gestionActual']->id_gestionacademica,
            'tipo' => 2,
        ]);
        $this->assertDatabaseHas('cursa', [
            'id_persona' => $fixture['persona']->id_persona,
            'id_carrera' => $fixture['carreraNoAsociada']->id_carrera,
            'id_gestionacademica' => $fixture['gestionActual']->id_gestionacademica,
            'nivelinicio' => 1,
        ]);
        $inscritoNuevaCarrera = DB::table('inscrito')
            ->where('id_persona', $fixture['persona']->id_persona)
            ->where('id_carrera', $fixture['carreraNoAsociada']->id_carrera)
            ->where('id_gestionacademica', $fixture['gestionActual']->id_gestionacademica)
            ->first();
        $inscritoSistemas = DB::table('inscrito')
            ->where('id_persona', $fixture['persona']->id_persona)
            ->where('id_carrera', $fixture['carreraUno']->id_carrera)
            ->where('id_gestionacademica', $fixture['gestionActual']->id_gestionacademica)
            ->first();
        $pagoNuevaCarrera = DB::table('pago')->where('id_inscrito', $inscritoNuevaCarrera->id_inscrito)->first();
        $pagoSistemas = DB::table('pago')->where('id_inscrito', $inscritoSistemas->id_inscrito)->first();
        $this->assertNotNull($pagoNuevaCarrera);
        $this->assertNotNull($pagoSistemas);
        $this->assertSame(0, (int) $pagoNuevaCarrera->pagado);
        $this->assertSame(0, (int) $pagoSistemas->pagado);
        $this->assertSame(500.0, (float) $pagoNuevaCarrera->monto);
        $this->assertSame(500.0, (float) $pagoSistemas->monto);
        $this->assertDatabaseHas('comprobante', [
            'id_inscrito' => $inscritoNuevaCarrera->id_inscrito,
            'id_estudiante' => $fixture['persona']->id_persona,
            'id_carrera' => $fixture['carreraNoAsociada']->id_carrera,
            'deposito' => 'NUEVA-CARRERA-VOUCHER-001',
            'valido' => false,
        ]);
        $this->assertDatabaseHas('detalle', [
            'id_pago' => $pagoNuevaCarrera->id_pago,
            'nombre' => 'Inscripción',
            'monto' => 500,
        ]);
        $this->assertDatabaseHas('comprobante', [
            'id_inscrito' => $inscritoSistemas->id_inscrito,
            'id_carrera' => $fixture['carreraUno']->id_carrera,
            'deposito' => 'REGULAR-SISTEMAS-VOUCHER-001',
            'valido' => false,
        ]);

        $usuario = User::create([
            'id_persona' => $fixture['persona']->id_persona,
            'usuario' => 'estudiante_regular_prueba',
            'password' => Hash::make('password123'),
        ]);
        $rol = Rol::firstOrCreate(['nombre' => 'Estudiante']);
        Cuenta::create([
            'id_persona' => $usuario->id_persona,
            'id_rol' => $rol->id_rol,
            'usuario' => $usuario->usuario,
            'password' => $usuario->password,
        ]);

        $this->withMiddleware()
            ->post(route('login'), [
                'usuario' => 'estudiante_regular_prueba',
                'password' => 'password123',
            ])->assertRedirect('/home');

        $this->assertAuthenticatedAs($usuario);
        $this->get(route('estudiantes.estado_inscripcion', [
            'id_carrera' => $fixture['carreraNoAsociada']->id_carrera,
            'id_gestionacademica' => $fixture['gestionActual']->id_gestionacademica,
        ]))
            ->assertOk()
            ->assertSee('Administración de Empresas')
            ->assertSee('Primer año')
            ->assertSee('Sistemas Informáticos')
            ->assertSee('Tercer año');

        $historialEconomico = $this->get(route('estudiantes.historial_economico', [
            'id_carrera' => $fixture['carreraNoAsociada']->id_carrera,
        ]))->assertOk()
            ->assertSee('NUEVA-CARRERA-VOUCHER-001')
            ->assertSee('500.00')
            ->assertSee('No');

        $pagoHistorico = $historialEconomico->viewData('pagos')->first();
        $this->assertSame(0, (int) $pagoHistorico->pagado);

        $historialSistemas = $this->get(route('estudiantes.historial_economico', [
            'id_carrera' => $fixture['carreraUno']->id_carrera,
        ]))->assertOk()
            ->assertSee('REGULAR-SISTEMAS-VOUCHER-001')
            ->assertSee('500.00')
            ->assertSee('No');
        $this->assertSame(0, (int) $historialSistemas->viewData('pagos')->first()->pagado);
    }

    public function test_regular_enrollment_cannot_exceed_three_years(): void
    {
        $this->withoutMiddleware();
        $fixture = $this->createFixture();

        $this->post(route('estudiante_regular.store'), [
            'id_persona' => $fixture['persona']->id_persona,
            'fecha' => now()->toDateString(),
            'becado' => 0,
            'traspaso_convalidacion' => 0,
            'inscripciones' => [[
                'registrar' => 1,
                'nueva' => 0,
                'id_carrera' => $fixture['carreraUno']->id_carrera,
                'nivelinicio' => 4,
                'deposito' => 'REGULAR-VOUCHER-003',
            ]],
        ])->assertSessionHasErrors('inscripciones.0.nivelinicio');

        $this->assertDatabaseCount('inscrito', 2);
    }

    public function test_new_student_enrollment_rejects_years_above_three(): void
    {
        $this->withoutMiddleware();
        $fixture = $this->createFixture();

        $this->post(route('estudiante_nuevo.store'), [
            'fecha' => now()->toDateString(),
            'deposito' => 'NUEVO-VOUCHER-001',
            'id_gestionacademica' => $fixture['gestionActual']->id_gestionacademica,
            'id_carrera' => $fixture['carreraUno']->id_carrera,
            'nivelinicio' => 4,
        ])->assertSessionHasErrors('nivelinicio');
    }

    private function createFixture(): array
    {
        $gestionAnterior = GestionAcademica::create([
            'gestion' => now()->year - 1,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => false,
        ]);
        $gestionActual = GestionAcademica::create([
            'gestion' => now()->year,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $instituto = Instituto::create([
            'nombreinstituto' => 'Instituto de Prueba',
            'preciocredito' => 25,
        ]);
        $carreraUno = $this->createCarrera($instituto, 'TEST-SIS', 'Sistemas Informáticos', 'R.M. 0001/2020');
        $carreraDos = $this->createCarrera($instituto, 'TEST-MTZ', 'Mecánica Automotriz', 'R.M. 0210/2023');
        $carreraNoAsociada = $this->createCarrera($instituto, 'TEST-ADM', 'Administración de Empresas', null);
        $persona = Persona::create([
            'tipo' => 'estudiante',
            'ci' => 'CI-REGULAR-001',
            'nombre' => 'Estudiante',
            'primerapellido' => 'Regular',
            'segundoapellido' => 'Prueba',
        ]);

        Estudiante::create([
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carreraUno->id_carrera,
            'traspaso_convalidacion' => false,
        ]);

        foreach ([$carreraUno, $carreraDos] as $index => $carrera) {
            $now = now();
            DB::table('inscrito')->insert([
                'codigo' => 1000 + $index,
                'fecha' => $now->toDateString(),
                'numero' => 1,
                'tipo' => 1,
                'becado' => false,
                'deposito' => 'HISTORIAL-VOUCHER-'.$index,
                'id_carrera' => $carrera->id_carrera,
                'id_persona' => $persona->id_persona,
                'id_gestionacademica' => $gestionAnterior->id_gestionacademica,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('cursa')->insert([
                'id_persona' => $persona->id_persona,
                'id_carrera' => $carrera->id_carrera,
                'id_gestionacademica' => $gestionAnterior->id_gestionacademica,
                'nivelinicio' => $index === 0 ? 2 : 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return compact('persona', 'gestionAnterior', 'gestionActual', 'carreraUno', 'carreraDos', 'carreraNoAsociada');
    }

    private function createCarrera(Instituto $instituto, string $codigo, string $nombre, ?string $resolucion): Carrera
    {
        return Carrera::create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'creditajematricula' => 20,
            'resolucionministerial1' => $resolucion,
            'id_instituto' => $instituto->id_instituto,
        ]);
    }
}
