<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureFunctionalityAccess;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CambioCarreraWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_search_shows_identity_and_lists_all_academic_periods(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestionActual = $this->createGestion(2027, 1, true);
        $gestionAnterior = $this->createGestion(2026, 2, false);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = $this->createCarrera($instituto, 'TEST-OLD', 'Carrera anterior');
        $otraCarrera = $this->createCarrera($instituto, 'TEST-OTHER', 'Otra carrera');
        $persona = $this->createEstudiante($carrera);
        $this->createInscripcion($persona, $carrera, $gestionActual, 'SEARCH-CURRENT-001');
        $this->createInscripcion($persona, $otraCarrera, $gestionAnterior, 'SEARCH-OLD-001');

        $this->get(route('cambio_carrera.buscar', ['q' => 'Cruz Ana']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $persona->id_persona,
                'ci' => 'CI-CRUZ-001',
                'nombre' => 'Ana Cruz Mamani',
                'carrera' => 'Carrera anterior',
                'gestion' => 2027,
            ])
            ->assertJsonFragment([
                'carrera' => 'Otra carrera',
                'gestion' => 2026,
            ]);

        $this->get(route('cambio_carrera.index'))
            ->assertOk()
            ->assertSee('Carrera que se cambiará')
            ->assertSee('Nueva carrera')
            ->assertSee('Monto del cambio (Bs.)');
    }

    public function test_career_change_requires_receipt_and_links_payment_history_to_student(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestion = $this->createGestion(2027, 1, false);
        $instituto = Instituto::create([
            'nombreinstituto' => 'Instituto de Prueba',
            'preciocredito' => 100,
        ]);
        $carreraAnterior = $this->createCarrera($instituto, 'TEST-OLD', 'Carrera anterior');
        $carreraAdicional = $this->createCarrera($instituto, 'TEST-OTHER', 'Otra carrera');
        $carreraNueva = $this->createCarrera($instituto, 'TEST-NEW', 'Carrera nueva', 20);
        $persona = $this->createEstudiante($carreraAnterior);
        $inscripcionOrigen = $this->createInscripcion($persona, $carreraAnterior, $gestion, 'CAMBIO-ORIGEN-001');
        $inscripcionConservar = $this->createInscripcion($persona, $carreraAdicional, $gestion, 'CAMBIO-OTRA-001');

        $this->post(route('cambio_carrera.store'), [
            'id_persona' => $persona->id_persona,
            'id_inscrito_origen' => $inscripcionOrigen,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'id_carrera' => $carreraNueva->id_carrera,
        ])->assertSessionHasErrors('deposito');

        $this->assertDatabaseCount('inscrito', 2);

        $this->post(route('cambio_carrera.store'), [
            'id_persona' => $persona->id_persona,
            'id_inscrito_origen' => $inscripcionOrigen,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'deposito' => 'CAMBIO-VOUCHER-001',
            'monto' => '350.50',
            'id_carrera' => $carreraNueva->id_carrera,
        ])->assertRedirect(route('cambio_carrera.index'));

        $inscrito = DB::table('inscrito')->where('id_carrera', $carreraNueva->id_carrera)->first();
        $this->assertNotNull($inscrito);
        $this->assertSame((int) $carreraNueva->id_carrera, (int) $inscrito->id_carrera);
        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $inscripcionOrigen,
            'id_inscrito_reemplazo' => $inscrito->id_inscrito,
        ]);
        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $inscripcionConservar,
            'id_inscrito_reemplazo' => null,
        ]);
        $this->assertDatabaseHas('pago', [
            'id_inscrito' => $inscrito->id_inscrito,
            'id_postulante' => null,
            'pagado' => 0,
            'monto' => 350.50,
        ]);
        $this->assertDatabaseHas('comprobante', [
            'deposito' => 'CAMBIO-VOUCHER-001',
            'id_inscrito' => $inscrito->id_inscrito,
            'id_estudiante' => $persona->id_persona,
            'id_carrera' => $carreraNueva->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'valido' => 0,
        ]);
        $this->assertDatabaseHas('detalle', [
            'nombre' => 'Cambio de carrera',
            'monto' => 350.50,
        ]);
        $this->assertDatabaseHas('estudiante', [
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carreraNueva->id_carrera,
        ]);
    }

    public function test_change_rejects_an_active_destination_and_a_reused_voucher(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestion = $this->createGestion(2027, 1, true);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carreraOrigen = $this->createCarrera($instituto, 'TEST-FROM', 'Carrera origen');
        $carreraDestinoExistente = $this->createCarrera($instituto, 'TEST-TAKEN', 'Carrera destino existente');
        $carreraDestinoNueva = $this->createCarrera($instituto, 'TEST-NEW', 'Carrera destino nueva');
        $persona = $this->createEstudiante($carreraOrigen);
        $idOrigen = $this->createInscripcion($persona, $carreraOrigen, $gestion, 'VOUCHER-ORIGEN-001');
        $this->createInscripcion($persona, $carreraDestinoExistente, $gestion, 'VOUCHER-DESTINO-001');

        $payload = [
            'id_persona' => $persona->id_persona,
            'id_inscrito_origen' => $idOrigen,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'deposito' => 'CAMBIO-VOUCHER-002',
            'monto' => '125',
            'id_carrera' => $carreraDestinoExistente->id_carrera,
        ];

        $this->post(route('cambio_carrera.store'), $payload)
            ->assertSessionHasErrors('id_carrera');

        $payload['id_carrera'] = $carreraDestinoNueva->id_carrera;
        $payload['deposito'] = 'voucher-origen-001';
        $this->post(route('cambio_carrera.store'), $payload)
            ->assertSessionHasErrors('deposito');

        $this->assertDatabaseCount('pago', 0);
        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $idOrigen,
            'id_inscrito_reemplazo' => null,
        ]);
    }

    public function test_changing_a_secondary_career_keeps_the_students_primary_career_unchanged(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestion = $this->createGestion(2027, 1, true);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carreraPrincipal = $this->createCarrera($instituto, 'TEST-PRIMARY', 'Carrera principal');
        $carreraCambiar = $this->createCarrera($instituto, 'TEST-SECONDARY', 'Carrera secundaria');
        $carreraNueva = $this->createCarrera($instituto, 'TEST-SECONDARY-NEW', 'Carrera nueva');
        $persona = $this->createEstudiante($carreraPrincipal);
        $this->createInscripcion($persona, $carreraPrincipal, $gestion, 'PRIMARY-VOUCHER-001');
        $idOrigen = $this->createInscripcion($persona, $carreraCambiar, $gestion, 'SECONDARY-VOUCHER-001');

        $this->post(route('cambio_carrera.store'), [
            'id_persona' => $persona->id_persona,
            'id_inscrito_origen' => $idOrigen,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'deposito' => 'SECONDARY-CHANGE-001',
            'monto' => '200',
            'id_carrera' => $carreraNueva->id_carrera,
        ])->assertRedirect(route('cambio_carrera.index'));

        $this->assertDatabaseHas('estudiante', [
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carreraPrincipal->id_carrera,
        ]);
        $this->assertDatabaseHas('inscrito', [
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carreraPrincipal->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_inscrito_reemplazo' => null,
        ]);
        $this->assertDatabaseHas('inscrito', [
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carreraCambiar->id_carrera,
            'id_inscrito_reemplazo' => DB::table('inscrito')
                ->where('id_persona', $persona->id_persona)
                ->where('id_carrera', $carreraNueva->id_carrera)
                ->value('id_inscrito'),
        ]);
    }

    public function test_change_payment_concept_and_amount_appear_in_student_and_admin_histories(): void
    {
        $this->withoutMiddleware();
        $admin = $this->createAdmin();
        $this->actingAs($admin);
        $gestion = $this->createGestion(2027, 1, true);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carreraOrigen = $this->createCarrera($instituto, 'TEST-HIST-OLD', 'Carrera origen');
        $carreraDestino = $this->createCarrera($instituto, 'TEST-HIST-NEW', 'Carrera nueva');
        $persona = $this->createEstudiante($carreraOrigen);
        $idOrigen = $this->createInscripcion($persona, $carreraOrigen, $gestion, 'HIST-ORIGEN-001');
        $this->post(route('cambio_carrera.store'), [
            'id_persona' => $persona->id_persona,
            'id_inscrito_origen' => $idOrigen,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'deposito' => 'HIST-CAMBIO-001',
            'monto' => '275.25',
            'id_carrera' => $carreraDestino->id_carrera,
        ])->assertRedirect(route('cambio_carrera.index'));

        $persona->refresh();
        $studentUser = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'ana_estudiante',
            'password' => Hash::make('password123'),
        ]);
        $this->actingAs($studentUser)
            ->get(route('estudiantes.estado_inscripcion'))
            ->assertOk()
            ->assertSee('Reemplazada por Carrera nueva')
            ->assertSee('Carrera nueva');

        $this->actingAs($studentUser)
            ->get(route('estudiantes.historial_economico', ['id_carrera' => $carreraDestino->id_carrera]))
            ->assertOk()
            ->assertSee('Cambio de carrera')
            ->assertSee('275.25');

        $this->actingAs($admin)
            ->get(route('pagos.index'))
            ->assertOk()
            ->assertSee('Cambio de carrera')
            ->assertSee('275.25');
    }

    private function createGestion(int $year, int $period, bool $current): GestionAcademica
    {
        return GestionAcademica::create([
            'gestion' => $year,
            'periodo' => $period,
            'regimen' => 1,
            'vigente' => $current,
        ]);
    }

    private function createCarrera(Instituto $instituto, string $code, string $name, int $credits = 1): Carrera
    {
        return Carrera::create([
            'codigo' => $code,
            'nombre' => $name,
            'creditajematricula' => $credits,
            'id_instituto' => $instituto->id_instituto,
        ]);
    }

    private function createEstudiante(Carrera $carrera): Persona
    {
        $persona = Persona::create([
            'nombre' => 'Ana',
            'primerapellido' => 'Cruz',
            'segundoapellido' => 'Mamani',
            'ci' => 'CI-CRUZ-001',
            'tipo' => 'Estudiante',
            'email' => 'ana@example.test',
            'celular' => 70000001,
        ]);
        DB::table('estudiante')->insert([
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carrera->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $persona;
    }

    private function createInscripcion(Persona $persona, Carrera $carrera, GestionAcademica $gestion, string $voucher): int
    {
        return DB::table('inscrito')->insertGetId([
            'codigo' => 2027 . $persona->id_persona,
            'fecha' => '2027-01-10',
            'numero' => 1,
            'tipo' => 2,
            'becado' => 0,
            'deposito' => $voucher,
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $persona->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'ADMIN-CAMBIO-001',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_cambio',
            'password' => Hash::make('password123'),
        ]);
    }
}
