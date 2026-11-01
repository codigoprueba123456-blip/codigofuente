<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Materia;
use App\Models\Postulante;
use App\Models\User;
use Database\Seeders\Modulos\AcademicoSeeder;
use Database\Seeders\Modulos\InscripcionesSeeder;
use Database\Seeders\Modulos\InstitucionalSeeder;
use Database\Seeders\Modulos\PagosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_institutional_module_seeds_without_other_modules(): void
    {
        $this->seed(InstitucionalSeeder::class);

        $this->assertDatabaseHas('instituto', [
            'nombreinstituto' => 'Instituto de Prueba',
            'abreviatura' => 'ITSA-DEMO',
        ]);
    }

    public function test_academic_module_seeds_its_dependencies_and_demo_catalog_idempotently(): void
    {
        $this->seed(AcademicoSeeder::class);

        $this->assertDatabaseHas('users', ['usuario' => '20260003']);
        $this->assertDatabaseHas('carreras', ['codigo' => 'DEMO-SIS']);
        $this->assertDatabaseHas('carreras', ['codigo' => 'DEMO-MTZ']);
        $this->assertDatabaseHas('carreras', [
            'codigo' => 'COC-2023',
            'nivelacademico' => Carrera::NIVEL_TECNICO_SUPERIOR,
            'regimen' => Carrera::REGIMEN_ANUAL,
        ]);
        $this->assertDatabaseHas('carreras', [
            'codigo' => 'COC-2019',
            'nivelacademico' => Carrera::NIVEL_TECNICO_SUPERIOR,
            'regimen' => Carrera::REGIMEN_SEMESTRAL,
        ]);
        $this->assertDatabaseHas('materia', ['codigo' => 'DEMO-SIS-201']);
        $this->assertDatabaseHas('grupo', ['codigo' => 'DEMO-MTZ-201-A']);
        $this->assertDatabaseHas('gestionacademica', [
            'gestion' => 2025,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => false,
        ]);
        $this->assertDatabaseHas('gestionacademica', [
            'gestion' => now()->year,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $this->assertDatabaseHas('cursa', [
            'id_persona' => User::where('usuario', '20260003')->value('id_persona'),
            'id_carrera' => Carrera::where('codigo', 'SIS-2023')->value('id_carrera'),
        ]);
        $this->assertDatabaseHas('prerequisito', [
            'id_materia' => Materia::where('codigo', 'DEMO-SIS-201')->value('id_materia'),
            'id_prerequisito' => Materia::where('codigo', 'DEMO-SIS-101')->value('id_materia'),
        ]);

        $this->seed(AcademicoSeeder::class);

        $this->assertDatabaseCount('carreras', 20);
        $this->assertSame(1, GestionAcademica::where('gestion', 2025)->where('periodo', 1)->count());
        $this->assertDatabaseCount('materia', 6);
        $this->assertDatabaseCount('grupo', 3);
    }

    public function test_inscriptions_module_seeds_academic_prerequisites_and_postulants(): void
    {
        $this->seed(InscripcionesSeeder::class);

        $this->assertDatabaseHas('postulante', ['codigo' => 'DEMO-POST-001']);
        $this->assertDatabaseHas('postulante', ['codigo' => 'DEMO-POST-002-SIS']);
        $this->assertDatabaseHas('postulante', ['codigo' => 'DEMO-POST-002-MTZ']);

        $this->seed(InscripcionesSeeder::class);

        $this->assertSame(3, Postulante::count());
    }

    public function test_payments_module_seeds_its_academic_and_inscription_prerequisites(): void
    {
        $this->seed(PagosSeeder::class);

        $this->assertDatabaseCount('postulante', 3);
        $this->assertDatabaseCount('pago', 3);
        $this->assertDatabaseCount('comprobante', 3);
        $this->assertDatabaseCount('detalle', 3);

        $this->seed(PagosSeeder::class);

        $this->assertDatabaseCount('pago', 3);
        $this->assertDatabaseCount('comprobante', 3);
        $this->assertDatabaseCount('detalle', 3);
    }
}
