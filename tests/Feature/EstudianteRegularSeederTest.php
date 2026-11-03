<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\Modulos\EstudianteRegularSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EstudianteRegularSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_searchable_regular_student_with_2025_history(): void
    {
        $this->seed(EstudianteRegularSeeder::class);
        $this->seed(EstudianteRegularSeeder::class);

        $persona = DB::table('personas')->where('ci', '9843201')->first();
        $gestion = DB::table('gestionacademica')
            ->where('gestion', 2025)
            ->where('periodo', 1)
            ->first();
        $carrera = DB::table('carreras')->where('codigo', 'SIS-2023')->first();

        $this->assertNotNull($persona);
        $this->assertNotNull($gestion);
        $this->assertNotNull($carrera);
        $usuario = User::where('id_persona', $persona->id_persona)->firstOrFail();
        $this->assertSame('andrea_quispe_9843201', $usuario->usuario);
        $this->assertTrue(Hash::check('Andrea2025!', $usuario->password));
        $this->assertDatabaseHas('cuenta', [
            'id_persona' => $persona->id_persona,
            'usuario' => 'andrea_quispe_9843201',
        ]);
        $this->assertDatabaseCount('inscrito', 1);
        $this->assertDatabaseHas('estudiante', [
            'id_persona' => $persona->id_persona,
            'matricula' => 20250001,
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);
        $this->assertDatabaseHas('inscrito', [
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'tipo' => 1,
        ]);

        $response = $this->withoutMiddleware()->getJson(route('estudiante_regular.buscar', [
            'q' => '9843201',
        ]))->assertOk();

        $this->assertSame('SISTEMAS INFORMATICOS', $response->json('0.carreras.0.nombre'));
        $this->assertSame(3, $response->json('0.carreras.0.nivel_sugerido'));
        $this->assertSame(2025, $response->json('0.carreras.0.historial.0.gestion'));
        $this->assertNotEmpty($response->json('0.carreras_disponibles'));

        $this->withMiddleware()->post(route('login'), [
            'usuario' => 'andrea_quispe_9843201',
            'password' => 'Andrea2025!',
        ])->assertRedirect('/home');

        $this->assertAuthenticatedAs($usuario);
        $this->get(route('estudiantes.estado_inscripcion'))
            ->assertOk()
            ->assertSee('SISTEMAS INFORMATICOS')
            ->assertSee('Segundo año');
        $this->get(route('estudiantes.historial_economico'))
            ->assertOk();
    }
}
