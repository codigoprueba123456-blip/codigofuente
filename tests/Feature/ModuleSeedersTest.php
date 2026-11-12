<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Postulante;
use App\Models\User;
use Database\Seeders\Modulos\AcademicoSeeder;
use Database\Seeders\Modulos\InscripcionesSeeder;
use Database\Seeders\Modulos\InstitucionalSeeder;
use Database\Seeders\Modulos\PagosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModuleSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_institutional_module_seeds_without_other_modules(): void
    {
        $this->seed(InstitucionalSeeder::class);

        $this->assertDatabaseHas('instituto', [
            'nombreinstituto' => 'Instituto de Prueba',
            'abreviatura' => 'ITSA',
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
        $this->assertDatabaseHas('materia', [
            'codigo' => 'ADM-2023-M01-01',
            'nombre' => 'Fundamentos de la Administración',
            'nivel' => 1,
            'numero' => 1,
        ]);
        $this->assertDatabaseHas('materia', [
            'codigo' => 'GAS-2023-M03-07',
            'nombre' => 'Taller de Modalidades de Graduación',
            'nivel' => 3,
            'numero' => 7,
        ]);
        $this->assertDatabaseHas('materia', [
            'codigo' => 'SEJ-2023-M03-10',
            'nombre' => 'Electivas II',
            'nivel' => 3,
            'numero' => 10,
        ]);
        foreach ([
            'ADM-2023' => 22,
            'GAS-2023' => 21,
            'SEJ-2023' => 29,
        ] as $careerCode => $subjectCount) {
            $career = Carrera::where('codigo', $careerCode)->firstOrFail();
            $this->assertSame(
                $subjectCount,
                Materia::where('id_carrera', $career->id_carrera)
                    ->where('codigo', 'like', $careerCode.'-M%')
                    ->count()
            );
        }
        $curriculumCareerIds = Carrera::whereIn('codigo', ['ADM-2023', 'GAS-2023', 'SEJ-2023'])
            ->pluck('id_carrera');
        $this->assertSame(72, Materia::whereIn('id_carrera', $curriculumCareerIds)->count());
        $this->assertSame(11, DB::table('prerequisito')
            ->whereIn('id_materia', Materia::whereIn('id_carrera', $curriculumCareerIds)->select('id_materia'))
            ->count());
        foreach ([
            'ADM-2023' => 22,
            'GAS-2023' => 21,
            'SEJ-2023' => 29,
        ] as $careerCode => $subjectCount) {
            $career = Carrera::where('codigo', $careerCode)->firstOrFail();
            $this->assertSame(50, (int) $career->creditajeadmision);
            $this->assertSame(50, (int) $career->creditajematricula);
            $this->assertSame(
                50,
                (int) Materia::where('id_carrera', $career->id_carrera)
                    ->where('curricular', true)
                    ->sum('creditajemateria')
            );
        }
        $this->assertDatabaseHas('prerequisito', [
            'id_materia' => Materia::where('codigo', 'ADM-2023-M03-03')->value('id_materia'),
            'id_prerequisito' => Materia::where('codigo', 'ADM-2023-M02-05')->value('id_materia'),
        ]);
        $this->assertDatabaseHas('prerequisito', [
            'id_materia' => Materia::where('codigo', 'SEJ-2023-M02-06')->value('id_materia'),
            'id_prerequisito' => Materia::where('codigo', 'SEJ-2023-M01-03')->value('id_materia'),
        ]);
        $this->assertDatabaseHas('grupo', ['codigo' => 'DEMO-MTZ-201-A']);
        $this->assertDatabaseHas('grupo', ['codigo' => 'ADM-2023-M01-01-A']);
        $this->assertDatabaseHas('grupo', ['codigo' => 'GAS-2023-M03-07-B']);
        $this->assertDatabaseHas('grupo', ['codigo' => 'SEJ-2023-M03-10-A']);
        $this->assertSame(
            144,
            Grupo::whereIn(
                'id_materia',
                Materia::whereIn('id_carrera', $curriculumCareerIds)->select('id_materia')
            )->count()
        );
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
        $this->assertDatabaseCount('materia', 76);
        $this->assertDatabaseCount('grupo', 146);
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
