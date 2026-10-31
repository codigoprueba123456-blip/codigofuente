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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentNewPostulacionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_keeps_postulant_history_and_assigns_first_level_automatically(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestion = GestionAcademica::create([
            'gestion' => now()->year,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'TEST-SIS',
            'nombre' => 'Sistemas de Prueba',
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
            'lugarexpedicion' => 'LP',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'fecha' => now(),
            'nombrecolegio' => 'U.E. de Prueba',
            'caractercolegio' => 'Fiscal',
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
        ]);

        $this->get(route('estudiante_nuevo_postulacion.index'))
            ->assertOk()
            ->assertSee('POSTULANTE-001')
            ->assertDontSee('Nivel autorizado')
            ->assertDontSee('nivelinicio')
            ->assertSee('postulaciones.forEach((postulacion, index) => {', false)
            ->assertSee('postulaciones[${index}][id_postulante]', false)
            ->assertSee('postulaciones[${index}][deposito]', false);

        $response = $this->post(route('estudiante_nuevo_postulacion.store'), [
            'postulaciones' => [[
                'id_postulante' => $postulante->id_postulante,
                'deposito' => 'POST-VOUCHER-001',
            ]],
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
        ]);
        $this->assertDatabaseHas('cursa', [
            'id_persona' => Persona::where('ci', 'POSTULANTE-001')->value('id_persona'),
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'nivelinicio' => 1,
        ]);
        $idPersona = Persona::where('ci', 'POSTULANTE-001')->value('id_persona');
        $usuario = User::where('id_persona', $idPersona)->firstOrFail();
        $cuenta = Cuenta::where('id_persona', $idPersona)->firstOrFail();
        $this->assertMatchesRegularExpression('/^'.now()->format('Y').'\d{4}$/', $usuario->usuario);
        $this->assertSame($usuario->usuario, $cuenta->usuario);
        $this->assertSame($usuario->password, $cuenta->password);
        $this->assertSame('Estudiante', $cuenta->rol->nombre);

        $this->get(route('estudiante_nuevo_postulacion.index'))
            ->assertOk()
            ->assertDontSee('POSTULANTE-001');
        $this->get(route('admisiones.postulantes.index'))
            ->assertOk()
            ->assertSee('POSTULANTE-001');
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
