<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Carrera;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\Postulante;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_consistent_demo_data_and_can_be_run_repeatedly(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('personas', 7);
        $this->assertDatabaseCount('users', 7);
        $this->assertDatabaseCount('rol', 4);
        $this->assertDatabaseCount('empleados', 4);
        $this->assertDatabaseCount('instituto', 1);
        $this->assertDatabaseCount('carreras', 20);
        $this->assertDatabaseCount('gestionacademica', 1);
        $this->assertDatabaseCount('materia', 76);
        $this->assertDatabaseCount('grupo', 146);
        $this->assertDatabaseCount('actividad', 4);
        $this->assertDatabaseCount('postulante', 3);
        $this->assertDatabaseCount('pago', 3);
        $this->assertDatabaseCount('comprobante', 3);
        $this->assertDatabaseCount('detalle', 3);
        $this->assertDatabaseCount('cursa', 1);

        $adminPersonaId = Persona::where('ci', '99000001')->value('id_persona');
        $adminUser = User::where('id_persona', $adminPersonaId)->firstOrFail();
        $this->assertMatchesRegularExpression('/^'.date('Y').'\d{4}$/', $adminUser->usuario);
        $this->assertDatabaseHas('users', ['usuario' => '20260003']);
        $this->assertDatabaseHas('carreras', ['codigo' => 'SIS-2023', 'regimen' => 1]);
        $this->assertDatabaseHas('postulante', [
            'codigo' => 'DEMO-POST-001',
            'lugarexpedicion' => 'LP',
            'sexo' => 'F',
            'caractercolegio' => 'Fiscal',
            'path_nacimiento' => 'demo/valeria_quispe_certificado_nacimiento.jpg',
            'path_diploma' => 'demo/valeria_quispe_diploma_bachiller.jpg',
        ]);
        $this->assertDatabaseHas('pago', ['id_postulante' => Postulante::where('codigo', 'DEMO-POST-001')->value('id_postulante')]);

        $this->assertDatabaseHas('carreras', [
            'codigo' => 'DEMO-SIS',
            'id_jefecarrera' => Persona::where('ci', '87654321')->value('id_persona'),
        ]);
        $this->assertDatabaseHas('prerequisito', [
            'id_materia' => Materia::where('codigo', 'DEMO-SIS-201')->value('id_materia'),
            'id_prerequisito' => Materia::where('codigo', 'DEMO-SIS-101')->value('id_materia'),
        ]);
        $this->assertDatabaseHas('carreras', [
            'codigo' => 'DEMO-MTZ',
            'nombre' => 'Mecánica Automotriz (Prueba)',
            'resolucionministerial1' => 'R.M. 001/2026',
        ]);
        $this->assertDatabaseHas('prerequisito', [
            'id_materia' => Materia::where('codigo', 'DEMO-MTZ-201')->value('id_materia'),
            'id_prerequisito' => Materia::where('codigo', 'DEMO-MTZ-101')->value('id_materia'),
        ]);
        $this->assertSame(1, Postulante::where('ci', 'VALERIA-001')->count());
        $this->assertSame(2, Postulante::where('ci', 'DIEGO-002')->count());
        $this->assertSame(
            2,
            Postulante::where('ci', 'DIEGO-002')->distinct('id_carrera')->count('id_carrera')
        );
        $this->assertDatabaseHas('actividad_carrera', [
            'id_actividad' => Actividad::where('nombre', 'Actividad de prueba por carrera')->value('id_actividad'),
            'id_carrera' => Carrera::where('codigo', 'DEMO-SIS')->value('id_carrera'),
        ]);
        $this->assertDatabaseHas('actividad_usuario', [
            'id_actividad' => Actividad::where('nombre', 'Actividad de prueba por usuario')->value('id_actividad'),
            'id_persona' => User::where('usuario', '20260001')->value('id_persona'),
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('personas', 7);
        $this->assertDatabaseCount('users', 7);
        $this->assertDatabaseCount('rol', 4);
        $this->assertDatabaseCount('empleados', 4);
        $this->assertDatabaseCount('instituto', 1);
        $this->assertDatabaseCount('carreras', 20);
        $this->assertDatabaseCount('gestionacademica', 1);
        $this->assertDatabaseCount('materia', 76);
        $this->assertDatabaseCount('grupo', 146);
        $this->assertDatabaseCount('actividad', 4);
        $this->assertDatabaseCount('postulante', 3);
        $this->assertDatabaseCount('pago', 3);
        $this->assertDatabaseCount('comprobante', 3);
        $this->assertDatabaseCount('detalle', 3);
        $this->assertDatabaseCount('cursa', 1);
    }

    public function test_database_seeder_keeps_employee_usernames_unique_and_in_registration_format(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['99000001', '99000002', '99000003'] as $ci) {
            $persona = Persona::where('ci', $ci)->firstOrFail();
            $user = User::where('id_persona', $persona->id_persona)->firstOrFail();

            $this->assertMatchesRegularExpression('/^'.date('Y').'\d{4}$/', $user->usuario);
        }

        $this->assertSame(User::count(), User::query()->distinct('usuario')->count('usuario'));
    }
}
