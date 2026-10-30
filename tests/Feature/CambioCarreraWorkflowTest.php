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
        $persona = $this->createEstudiante($carrera);

        $this->get(route('cambio_carrera.buscar', ['q' => 'Cruz Ana']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $persona->id_persona,
                'ci' => 'CI-CRUZ-001',
                'nombre' => 'Ana Cruz Mamani',
                'carrera_actual' => 'Carrera anterior',
            ]);

        $this->get(route('cambio_carrera.index'))
            ->assertOk()
            ->assertSee('2027 - Periodo 1')
            ->assertSee('2026 - Periodo 2');
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
        $carreraNueva = $this->createCarrera($instituto, 'TEST-NEW', 'Carrera nueva', 20);
        $persona = $this->createEstudiante($carreraAnterior);

        $this->post(route('cambio_carrera.store'), [
            'id_persona' => $persona->id_persona,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'traspaso_convalidaciones' => 'No',
            'id_carrera' => $carreraNueva->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ])->assertSessionHasErrors('deposito');

        $this->assertDatabaseCount('inscrito', 0);

        $this->post(route('cambio_carrera.store'), [
            'id_persona' => $persona->id_persona,
            'fecha' => '2027-01-15',
            'becado' => 'No',
            'traspaso_convalidaciones' => 'No',
            'deposito' => 'CAMBIO-VOUCHER-001',
            'id_carrera' => $carreraNueva->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ])->assertRedirect(route('cambio_carrera.index'));

        $inscrito = DB::table('inscrito')->first();
        $this->assertNotNull($inscrito);
        $this->assertSame((int) $carreraNueva->id_carrera, (int) $inscrito->id_carrera);
        $this->assertDatabaseHas('pago', [
            'id_inscrito' => $inscrito->id_inscrito,
            'id_postulante' => null,
            'pagado' => 0,
            'monto' => 2000,
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
            'monto' => 2000,
        ]);
        $this->assertDatabaseHas('estudiante', [
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carreraNueva->id_carrera,
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
