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

    public function test_student_search_shows_only_the_most_recent_enrollment_period(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestionActual = $this->createGestion(2027, 1, true);
        $gestionAnterior = $this->createGestion(2026, 2, false);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = $this->createCarrera($instituto, 'TEST-OLD', 'Carrera anterior');
        $otraCarrera = $this->createCarrera($instituto, 'TEST-OTHER', 'Otra carrera');
        $persona = $this->createEstudiante($carrera);
        $idInscripcionActual = $this->createInscripcion($persona, $carrera, $gestionActual, 'SEARCH-CURRENT-001');
        $this->createInscripcion($persona, $otraCarrera, $gestionAnterior, 'SEARCH-OLD-001');
        DB::table('inscrito')->where('id_inscrito', $idInscripcionActual)->update(['deposito' => null]);
        DB::table('comprobante')->insert([
            'deposito' => 'SEARCH-CURRENT-001',
            'fecha' => '2027-01-10',
            'valido' => true,
            'id_inscrito' => $idInscripcionActual,
            'id_estudiante' => $persona->id_persona,
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestionActual->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $materia = $this->createMateria($carrera);
        DB::table('inscrito_materia')->insert([
            'id_inscrito' => $idInscripcionActual,
            'id_materia' => $materia,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('cambio_carrera.buscar', ['q' => 'Cruz Ana']))
            ->assertOk()
            ->assertJsonFragment([
                'ci' => 'CI-CRUZ-001',
                'nombre' => 'Ana Cruz Mamani',
            ])
            ->assertJsonFragment([
                'carreras_inscritas' => [$carrera->id_carrera, $otraCarrera->id_carrera],
            ])
            ->assertJsonFragment([
                'id' => $idInscripcionActual,
                'carrera' => 'Carrera anterior',
                'gestion' => 2027,
                'deposito' => 'SEARCH-CURRENT-001',
                'tiene_materias' => true,
            ])
            ->assertJsonMissing([
                'carrera' => 'Otra carrera',
                'gestion' => 2026,
            ]);

        $this->get(route('cambio_carrera.index'))
            ->assertOk()
            ->assertSee('Carrera que se cambiará')
            ->assertSee('Nueva carrera')
            ->assertSee('Voucher registrado (opcional)')
            ->assertSee('sweetalert2.all.min.js')
            ->assertSee('Confirmar cambio de carrera')
            ->assertDontSee('window.confirm', false)
            ->assertDontSee('Monto del cambio');
    }

    public function test_career_change_reuses_the_existing_voucher_without_creating_a_new_payment(): void
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
        DB::table('inscrito')->where('id_inscrito', $inscripcionOrigen)->update(['deposito' => null]);
        DB::table('comprobante')->insert([
            'deposito' => 'CAMBIO-ORIGEN-001',
            'fecha' => '2027-01-10',
            'valido' => true,
            'id_inscrito' => $inscripcionOrigen,
            'id_estudiante' => $persona->id_persona,
            'id_carrera' => $carreraAnterior->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $inscripcionConservar = $this->createInscripcion($persona, $carreraAdicional, $gestion, 'CAMBIO-OTRA-001');

        $this->post(route('cambio_carrera.store'), [
            'id_persona' => $persona->id_persona,
            'id_inscrito_origen' => $inscripcionOrigen,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'id_carrera' => $carreraNueva->id_carrera,
        ])->assertRedirect(route('cambio_carrera.index'));

        $inscrito = DB::table('inscrito')->where('id_carrera', $carreraNueva->id_carrera)->first();
        $this->assertNotNull($inscrito);
        $this->assertSame((int) $carreraNueva->id_carrera, (int) $inscrito->id_carrera);
        $this->assertSame('CAMBIO-ORIGEN-001', $inscrito->deposito);
        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $inscripcionOrigen,
            'id_inscrito_reemplazo' => $inscrito->id_inscrito,
        ]);
        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $inscripcionConservar,
            'id_inscrito_reemplazo' => null,
        ]);
        $this->assertDatabaseCount('pago', 0);
        $this->assertDatabaseCount('comprobante', 1);
        $this->assertDatabaseHas('comprobante', [
            'deposito' => 'CAMBIO-ORIGEN-001',
            'id_inscrito' => $inscripcionOrigen,
        ]);
        $this->assertDatabaseCount('detalle', 0);
        $this->assertDatabaseHas('estudiante', [
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carreraNueva->id_carrera,
        ]);
    }

    public function test_change_is_blocked_when_the_selected_enrollment_has_assigned_subjects(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestion = $this->createGestion(2027, 1, true);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carreraOrigen = $this->createCarrera($instituto, 'TEST-FROM', 'Carrera origen');
        $carreraNueva = $this->createCarrera($instituto, 'TEST-NEW', 'Carrera nueva');
        $persona = $this->createEstudiante($carreraOrigen);
        $idOrigen = $this->createInscripcion($persona, $carreraOrigen, $gestion, 'MATERIA-ORIGEN-001');
        $materia = $this->createMateria($carreraOrigen);
        DB::table('inscrito_materia')->insert([
            'id_inscrito' => $idOrigen,
            'id_materia' => $materia,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('cambio_carrera.store'), [
            'id_persona' => $persona->id_persona,
            'id_inscrito_origen' => $idOrigen,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'id_carrera' => $carreraNueva->id_carrera,
        ])->assertSessionHasErrors([
            'id_inscrito_origen' => 'Esta inscripción tiene materias asignadas. Retírelas antes de cambiar de carrera.',
        ]);

        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $idOrigen,
            'id_inscrito_reemplazo' => null,
        ]);
        $this->assertDatabaseCount('pago', 0);
    }

    public function test_career_change_is_allowed_without_a_voucher(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestion = $this->createGestion(2027, 1, true);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carreraOrigen = $this->createCarrera($instituto, 'TEST-FROM', 'Carrera origen');
        $carreraNueva = $this->createCarrera($instituto, 'TEST-NEW', 'Carrera nueva');
        $persona = $this->createEstudiante($carreraOrigen);
        $idOrigen = $this->createInscripcion($persona, $carreraOrigen, $gestion, 'VOUCHER-OPCIONAL-001');
        DB::table('inscrito')->where('id_inscrito', $idOrigen)->update(['deposito' => null]);

        $this->post(route('cambio_carrera.store'), [
            'id_persona' => $persona->id_persona,
            'id_inscrito_origen' => $idOrigen,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'id_carrera' => $carreraNueva->id_carrera,
        ])->assertRedirect(route('cambio_carrera.index'));

        $inscripcionNueva = DB::table('inscrito')
            ->where('id_persona', $persona->id_persona)
            ->where('id_carrera', $carreraNueva->id_carrera)
            ->first();
        $this->assertNotNull($inscripcionNueva);
        $this->assertNull($inscripcionNueva->deposito);
        $this->assertDatabaseCount('pago', 0);
    }

    public function test_change_rejects_an_active_destination(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestion = $this->createGestion(2027, 1, true);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carreraOrigen = $this->createCarrera($instituto, 'TEST-FROM', 'Carrera origen');
        $carreraDestinoExistente = $this->createCarrera($instituto, 'TEST-TAKEN', 'Carrera destino existente');
        $persona = $this->createEstudiante($carreraOrigen);
        $idOrigen = $this->createInscripcion($persona, $carreraOrigen, $gestion, 'VOUCHER-ORIGEN-001');
        $this->createInscripcion($persona, $carreraDestinoExistente, $gestion, 'VOUCHER-DESTINO-001');

        $payload = [
            'id_persona' => $persona->id_persona,
            'id_inscrito_origen' => $idOrigen,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'id_carrera' => $carreraDestinoExistente->id_carrera,
        ];

        $this->post(route('cambio_carrera.store'), $payload)
            ->assertSessionHasErrors('id_carrera');

        $this->assertDatabaseCount('pago', 0);
        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $idOrigen,
            'id_inscrito_reemplazo' => null,
        ]);
    }

    public function test_change_rejects_an_enrollment_from_an_older_period(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestionReciente = $this->createGestion(2027, 1, true);
        $gestionAnterior = $this->createGestion(2026, 1, false);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carreraReciente = $this->createCarrera($instituto, 'TEST-LATEST', 'Carrera más reciente');
        $carreraAnterior = $this->createCarrera($instituto, 'TEST-OLDER', 'Carrera anterior');
        $carreraDestino = $this->createCarrera($instituto, 'TEST-DESTINATION', 'Carrera destino');
        $persona = $this->createEstudiante($carreraReciente);
        $this->createInscripcion($persona, $carreraReciente, $gestionReciente, 'LATEST-VOUCHER-001');
        $idOrigen = $this->createInscripcion($persona, $carreraAnterior, $gestionAnterior, 'OLDER-VOUCHER-001');

        $this->post(route('cambio_carrera.store'), [
            'id_persona' => $persona->id_persona,
            'id_inscrito_origen' => $idOrigen,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'id_carrera' => $carreraDestino->id_carrera,
        ])->assertSessionHasErrors([
            'id_inscrito_origen' => 'Solo se puede cambiar una inscripción del período más reciente del estudiante.',
        ]);

        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $idOrigen,
            'id_inscrito_reemplazo' => null,
        ]);
        $this->assertDatabaseCount('inscrito', 2);
    }

    public function test_change_rejects_a_career_the_student_has_active_in_another_period(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestionActual = $this->createGestion(2027, 1, true);
        $gestionAnterior = $this->createGestion(2026, 1, false);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carreraOrigen = $this->createCarrera($instituto, 'TEST-FROM', 'Secretariado');
        $carreraYaInscrita = $this->createCarrera($instituto, 'TEST-TAKEN', 'Sistemas Informáticos');
        $persona = $this->createEstudiante($carreraOrigen);
        $idOrigen = $this->createInscripcion($persona, $carreraOrigen, $gestionActual, 'VOUCHER-ORIGEN-002');
        $this->createInscripcion($persona, $carreraYaInscrita, $gestionAnterior, 'VOUCHER-ANTERIOR-002');

        $this->post(route('cambio_carrera.store'), [
            'id_persona' => $persona->id_persona,
            'id_inscrito_origen' => $idOrigen,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'id_carrera' => $carreraYaInscrita->id_carrera,
        ])->assertSessionHasErrors([
            'id_carrera' => 'El estudiante ya tiene una inscripción activa en esta carrera.',
        ]);

        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $idOrigen,
            'id_inscrito_reemplazo' => null,
        ]);
        $this->assertDatabaseCount('inscrito', 2);
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

    public function test_career_change_preserves_existing_payment_history_without_creating_a_new_charge(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestion = $this->createGestion(2027, 1, true);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carreraOrigen = $this->createCarrera($instituto, 'TEST-HIST-OLD', 'Carrera origen');
        $carreraDestino = $this->createCarrera($instituto, 'TEST-HIST-NEW', 'Carrera nueva');
        $persona = $this->createEstudiante($carreraOrigen);
        $idOrigen = $this->createInscripcion($persona, $carreraOrigen, $gestion, 'HIST-ORIGEN-001');
        $idPago = DB::table('pago')->insertGetId([
            'concepto' => null,
            'monto' => 275.25,
            'pagado' => true,
            'estado' => true,
            'id_inscrito' => $idOrigen,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('detalle')->insert([
            'nombre' => 'Inscripción',
            'monto' => 275.25,
            'id_pago' => $idPago,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('comprobante')->insert([
            'deposito' => 'HIST-ORIGEN-001',
            'fecha' => '2027-01-10',
            'valido' => true,
            'id_inscrito' => $idOrigen,
            'id_estudiante' => $persona->id_persona,
            'id_carrera' => $carreraOrigen->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('cambio_carrera.store'), [
            'id_persona' => $persona->id_persona,
            'id_inscrito_origen' => $idOrigen,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'id_carrera' => $carreraDestino->id_carrera,
        ])->assertRedirect(route('cambio_carrera.index'));

        $this->assertDatabaseCount('pago', 1);
        $this->assertDatabaseHas('pago', [
            'id_pago' => $idPago,
            'id_inscrito' => $idOrigen,
            'pagado' => true,
            'monto' => 275.25,
        ]);
        $this->assertDatabaseCount('detalle', 1);
        $this->assertDatabaseCount('comprobante', 1);
        $this->assertDatabaseHas('comprobante', [
            'deposito' => 'HIST-ORIGEN-001',
            'id_inscrito' => $idOrigen,
            'id_carrera' => $carreraOrigen->id_carrera,
        ]);
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

    private function createMateria(Carrera $carrera): int
    {
        return DB::table('materia')->insertGetId([
            'nombre' => 'Materia de prueba',
            'id_carrera' => $carrera->id_carrera,
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
