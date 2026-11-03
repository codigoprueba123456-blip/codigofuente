<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Materia;
use Database\Seeders\Modulos\InscripcionManualGruposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InscripcionManualGruposSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_open_a_and_b_groups_for_second_year_subjects_without_overwriting_existing_groups(): void
    {
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'SIS-2023',
            'nombre' => 'SISTEMAS INFORMATICOS',
            'activo' => true,
            'id_instituto' => $instituto->id_instituto,
        ]);
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $materias = collect([
            Materia::create([
                'codigo' => 'SIS-201',
                'nombre' => 'Materia de segundo año',
                'nivel' => 2,
                'numero' => 1,
                'id_carrera' => $carrera->id_carrera,
            ]),
            Materia::create([
                'codigo' => 'SIS-202',
                'nombre' => 'Otra materia de segundo año',
                'nivel' => 2,
                'numero' => 2,
                'id_carrera' => $carrera->id_carrera,
            ]),
            Materia::create([
                'codigo' => 'SIS-101',
                'nombre' => 'Materia de primer año',
                'nivel' => 1,
                'numero' => 1,
                'id_carrera' => $carrera->id_carrera,
            ]),
        ]);

        DB::table('grupo')->insert([
            'codigo' => 'SIS-201-A',
            'capacidad' => 18,
            'turno' => 2,
            'abierto' => false,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materias[0]->id_materia,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(InscripcionManualGruposSeeder::class);
        $this->seed(InscripcionManualGruposSeeder::class);

        $this->assertDatabaseCount('grupo', 4);
        $this->assertDatabaseHas('grupo', [
            'codigo' => 'SIS-201-A',
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'capacidad' => 18,
            'turno' => 2,
            'abierto' => false,
        ]);
        foreach (['SIS-201-B', 'SIS-202-A', 'SIS-202-B'] as $codigo) {
            $this->assertDatabaseHas('grupo', [
                'codigo' => $codigo,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'capacidad' => 30,
                'abierto' => true,
            ]);
        }
        $this->assertDatabaseMissing('grupo', [
            'codigo' => 'SIS-101-A',
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);
    }
}
