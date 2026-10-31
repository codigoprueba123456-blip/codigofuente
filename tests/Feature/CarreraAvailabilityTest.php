<?php

namespace Tests\Feature;

use App\Http\Controllers\Requests\Postulantes\PostulanteValidator;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CarreraAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_career_is_hidden_from_new_admission_and_enrollment_forms(): void
    {
        $this->actingAs($this->createAdmin());
        $this->createCareers();
        GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);

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
        $carreraDeshabilitada = Carrera::where('codigo', 'TEST-OFF')->firstOrFail();
        $request = Request::create('/postulantes/store', 'POST', [
            'nombre' => 'Postulante',
            'primerapellido' => 'Prueba',
            'ci' => '12345678',
            'lugarexpedicion' => 'CB',
            'fechanacimiento' => '2005-01-01',
            'lugarnacimiento' => 'Sacaba',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'M',
            'direccion' => 'Calle de prueba',
            'celular' => '70000000',
            'email' => 'postulante@example.test',
            'nombrecontacto' => 'Contacto',
            'celularcontacto' => '70000001',
            'parentescocontacto' => 'Familiar',
            'nombrecolegio' => 'Colegio de prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'id_carrera' => $carreraDeshabilitada->id_carrera,
            'deposito' => 'voucher-prueba',
        ], [], [
            'fotografia' => UploadedFile::fake()->image('foto.jpg'),
            'path_ci_frontal' => UploadedFile::fake()->image('ci-frontal.jpg'),
            'path_ci_reverso' => UploadedFile::fake()->image('ci-reverso.jpg'),
            'path_nacimiento' => UploadedFile::fake()->image('nacimiento.jpg'),
            'path_diploma' => UploadedFile::fake()->image('diploma.jpg'),
        ]);

        try {
            app(PostulanteValidator::class)->validarRegistro($request, $gestion, null);
            $this->fail('A disabled career must not be accepted for a new application.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('id_carrera', $exception->errors());
        }
    }

    private function createCareers(): void
    {
        $institute = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);

        Carrera::create([
            'codigo' => 'TEST-ON',
            'nombre' => 'Carrera habilitada',
            'id_instituto' => $institute->id_instituto,
        ]);
        Carrera::create([
            'codigo' => 'TEST-OFF',
            'nombre' => 'Carrera deshabilitada',
            'activo' => false,
            'id_instituto' => $institute->id_instituto,
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
