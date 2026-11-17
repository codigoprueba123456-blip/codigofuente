<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\OpensPublicPostulationWindow;
use Tests\TestCase;

class CarreraAvailabilityTest extends TestCase
{
    use OpensPublicPostulationWindow;
    use RefreshDatabase;

    public function test_disabled_career_is_hidden_from_new_admission_and_enrollment_forms(): void
    {
        $this->actingAs($this->createAdmin());
        $this->createCareers();
        $gestion = GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
            'activo' => true,
        ]);
        $this->openPublicPostulationWindow($gestion);
        $this->createGroup(Carrera::where('codigo', 'TEST-ON')->firstOrFail(), $gestion);

        $this->get(route('postulantes.registrar'))
            ->assertOk()
            ->assertSee('Carrera habilitada')
            ->assertDontSee('Carrera deshabilitada');

        $this->get(route('estudiante_nuevo.index'))
            ->assertOk()
            ->assertSee('Carrera habilitada')
            ->assertDontSee('Carrera deshabilitada');
    }

    public function test_postulant_registration_rejects_a_disabled_career(): void
    {
        $this->createCareers();
        $gestion = GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $this->openPublicPostulationWindow($gestion);
        $carreraDeshabilitada = Carrera::where('codigo', 'TEST-OFF')->firstOrFail();

        $this->post(route('postulantes.store'), [
            'ci' => '12345678',
            'id_carrera' => $carreraDeshabilitada->id_carrera,
        ])->assertSessionHasErrors('id_carrera');
    }

    private function createCareers(): void
    {
        $institute = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);

        Carrera::create([
            'codigo' => 'TEST-ON',
            'nombre' => 'Carrera habilitada',
            'regimen' => Carrera::REGIMEN_ANUAL,
            'activo' => true,
            'id_instituto' => $institute->id_instituto,
        ]);
        Carrera::create([
            'codigo' => 'TEST-OFF',
            'nombre' => 'Carrera deshabilitada',
            'regimen' => Carrera::REGIMEN_ANUAL,
            'activo' => false,
            'id_instituto' => $institute->id_instituto,
        ]);
    }

    private function createGroup(Carrera $carrera, GestionAcademica $gestion): void
    {
        $idMateria = DB::table('materia')->insertGetId([
            'codigo' => 'TEST-ON-MAT',
            'nombre' => 'Materia habilitada',
            'nivel' => 1,
            'numero' => 1,
            'activo' => true,
            'id_carrera' => $carrera->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('grupo')->insert([
            'codigo' => 'TEST-ON-MAT-A',
            'capacidad' => 30,
            'turno' => 1,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $idMateria,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => '12345678',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_carreras',
            'password' => Hash::make('password123'),
        ]);
    }
}
