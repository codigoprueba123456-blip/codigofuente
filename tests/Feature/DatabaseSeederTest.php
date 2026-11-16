<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Carrera;
use App\Models\Persona;
use App\Models\Postulante;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_consistent_demo_data_and_can_be_run_repeatedly(): void
    {
        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);

        $countsAfterFirstRun = $this->seededDataCounts();

        $adminPersonaId = Persona::where('ci', '99000001')->value('id_persona');
        $adminUser = User::where('id_persona', $adminPersonaId)->firstOrFail();
        $this->assertMatchesRegularExpression('/^'.date('Y').'\d{4}$/', $adminUser->usuario);
        $this->assertDatabaseHas('users', ['usuario' => '20260003']);
        $this->assertDatabaseHas('carreras', ['codigo' => 'SIS-2023', 'regimen' => 1]);
        $this->assertDatabaseMissing('carreras', ['codigo' => 'DEMO-SIS']);
        $this->assertDatabaseMissing('carreras', ['codigo' => 'DEMO-MTZ']);
        $this->assertDatabaseHas('postulante', [
            'codigo' => 'DEMO-POST-001',
            'lugarexpedicion' => 'LP',
            'sexo' => 'F',
            'caractercolegio' => 'Fiscal',
            'path_nacimiento' => 'demo/inscripciones/documentos/certificado_de_nacimiento.webp',
            'path_diploma' => 'demo/inscripciones/documentos/diploma.webp',
        ]);
        $this->assertDatabaseHas('materia', ['codigo' => 'SIS-DEMO-2026-N2-01']);
        $this->assertDatabaseHas('postulante', ['codigo' => 'DEMO-2026-POST-01-SIS-2023']);
        $this->assertDatabaseHas('actividad', ['nombre' => 'Ventana de postulación pública']);
        Storage::disk('public')->assertExists('demo/inscripciones/documentos/carnet1.jpeg');
        $this->assertDatabaseHas('pago', ['id_postulante' => Postulante::where('codigo', 'DEMO-POST-001')->value('id_postulante')]);

        $this->assertSame(1, Postulante::where('ci', 'VALERIA-001')->count());
        $this->assertSame(2, Postulante::where('ci', 'DIEGO-002')->count());
        $this->assertSame(
            2,
            Postulante::where('ci', 'DIEGO-002')->distinct('id_carrera')->count('id_carrera')
        );
        $this->assertDatabaseHas('actividad_carrera', [
            'id_actividad' => Actividad::where('nombre', 'Actividad de prueba por carrera')->value('id_actividad'),
            'id_carrera' => Carrera::where('codigo', 'SIS-2023')->value('id_carrera'),
        ]);
        $this->assertDatabaseHas('actividad_usuario', [
            'id_actividad' => Actividad::where('nombre', 'Actividad de prueba por usuario')->value('id_actividad'),
            'id_persona' => User::where('usuario', '20260001')->value('id_persona'),
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($countsAfterFirstRun, $this->seededDataCounts());
    }

    public function test_database_seeder_keeps_employee_usernames_unique_and_in_registration_format(): void
    {
        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);

        foreach (['99000001', '99000002', '99000003'] as $ci) {
            $persona = Persona::where('ci', $ci)->firstOrFail();
            $user = User::where('id_persona', $persona->id_persona)->firstOrFail();

            $this->assertMatchesRegularExpression('/^'.date('Y').'\d{4}$/', $user->usuario);
        }

        $this->assertSame(User::count(), User::query()->distinct('usuario')->count('usuario'));
    }

    private function seededDataCounts(): array
    {
        return collect([
            'personas',
            'users',
            'rol',
            'empleados',
            'instituto',
            'carreras',
            'gestionacademica',
            'materia',
            'grupo',
            'actividad',
            'postulante',
            'estudiante',
            'inscrito',
            'pago',
            'comprobante',
            'detalle',
            'cursa',
        ])->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])
            ->all();
    }
}
