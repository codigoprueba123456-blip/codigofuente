<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Instituto;
use App\Models\Materia;
use Database\Seeders\Modulos\InicioInscripcionDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InicioInscripcionDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_requested_groups_for_the_existing_2026_catalog_and_is_idempotent(): void
    {
        [$gestion, $carreras] = $this->createCatalog();

        $this->seed(InicioInscripcionDemoSeeder::class);
        $this->seed(InicioInscripcionDemoSeeder::class);

        $this->assertDatabaseCount('grupo', 4);
        $this->assertDatabaseHas('grupo', [
            'codigo' => 'SIS-101-A',
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'capacidad' => 30,
            'turno' => 3,
            'activo' => true,
            'abierto' => true,
        ]);

        foreach ([
            ['codigo' => 'GAS-101-A', 'turno' => 1],
            ['codigo' => 'GAS-101-B', 'turno' => 1],
            ['codigo' => 'SEC-101-A', 'turno' => 3],
        ] as $grupoEsperado) {
            $this->assertDatabaseHas('grupo', [
                'codigo' => $grupoEsperado['codigo'],
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'capacidad' => 30,
                'turno' => $grupoEsperado['turno'],
                'activo' => true,
                'abierto' => true,
            ]);
        }

        $this->assertDatabaseMissing('grupo', [
            'codigo' => 'SIS-102-A',
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);
        $this->assertSame('R.M. N° 0210/2023', $carreras['sistemas']->fresh()->resolucionministerial1);
        $this->assertSame('R.M. 145/2025', $carreras['gastronomia']->fresh()->resolucionministerial1);
        $this->assertSame('R.M. 22/2024', $carreras['secretariado']->fresh()->resolucionministerial1);
    }

    public function test_it_does_not_overwrite_conflicting_groups_or_partially_seed_other_careers(): void
    {
        [$gestion, $carreras, $materias] = $this->createCatalog();
        Grupo::create([
            'codigo' => 'SIS-101-A',
            'capacidad' => 25,
            'turno' => 3,
            'activo' => true,
            'abierto' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materias['sistemas']->id_materia,
        ]);

        try {
            $this->seed(InicioInscripcionDemoSeeder::class);
            $this->fail('Se esperaba que el seeder informara del conflicto de capacidad.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('otro cupo o estado', $exception->getMessage());
        }

        $this->assertDatabaseCount('grupo', 1);
        $this->assertDatabaseHas('grupo', [
            'codigo' => 'SIS-101-A',
            'capacidad' => 25,
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);
    }

    private function createCatalog(): array
    {
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
            'activo' => true,
        ]);
        $carreras = [
            'sistemas' => Carrera::create([
                'codigo' => 'DEMO-SIS',
                'nombre' => 'Sistemas Informáticos',
                'activo' => true,
                'regimen' => Carrera::REGIMEN_ANUAL,
                'resolucionministerial1' => 'R.M. N° 0210/2023',
                'id_instituto' => $instituto->id_instituto,
            ]),
            'gastronomia' => Carrera::create([
                'codigo' => 'DEMO-GAS',
                'nombre' => 'Gastronomía',
                'activo' => true,
                'regimen' => Carrera::REGIMEN_ANUAL,
                'resolucionministerial1' => 'R.M. 145/2025',
                'id_instituto' => $instituto->id_instituto,
            ]),
            'secretariado' => Carrera::create([
                'codigo' => 'DEMO-SEC',
                'nombre' => 'Secretariado Ejecutivo',
                'activo' => true,
                'regimen' => Carrera::REGIMEN_ANUAL,
                'resolucionministerial1' => 'R.M. 22/2024',
                'id_instituto' => $instituto->id_instituto,
            ]),
        ];
        $materias = [
            'sistemas' => Materia::create([
                'codigo' => 'SIS-101',
                'nombre' => 'Materia de Sistemas',
                'nivel' => 1,
                'id_carrera' => $carreras['sistemas']->id_carrera,
            ]),
            'sistemasInactiva' => Materia::create([
                'codigo' => 'SIS-102',
                'nombre' => 'Materia inactiva',
                'nivel' => 2,
                'activo' => false,
                'id_carrera' => $carreras['sistemas']->id_carrera,
            ]),
            'gastronomia' => Materia::create([
                'codigo' => 'GAS-101',
                'nombre' => 'Materia de Gastronomía',
                'nivel' => 1,
                'id_carrera' => $carreras['gastronomia']->id_carrera,
            ]),
            'secretariado' => Materia::create([
                'codigo' => 'SEC-101',
                'nombre' => 'Materia de Secretariado',
                'nivel' => 1,
                'id_carrera' => $carreras['secretariado']->id_carrera,
            ]),
        ];

        return [$gestion, $carreras, $materias];
    }
}
