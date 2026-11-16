<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Persona;
use App\Models\Materia;
use App\Models\Instituto;
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

    public function test_academic_module_seeds_only_the_listed_careers_and_is_idempotent(): void
    {
        $this->seed(AcademicoSeeder::class);

        $this->assertDatabaseHas('users', ['usuario' => '20260003']);
        $this->assertDatabaseMissing('carreras', ['codigo' => 'DEMO-SIS']);
        $this->assertDatabaseMissing('carreras', ['codigo' => 'DEMO-MTZ']);
        $this->assertDatabaseHas('carreras', [
            'codigo' => 'COC-2023',
            'nivelacademico' => Carrera::NIVEL_TECNICO_SUPERIOR,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'activo' => true,
            'resolucionministerial1' => '0210/2023',
        ]);
        $this->assertDatabaseHas('carreras', [
            'codigo' => 'COC-2019',
            'nivelacademico' => Carrera::NIVEL_TECNICO_SUPERIOR,
            'regimen' => Carrera::REGIMEN_SEMESTRAL,
            'activo' => false,
        ]);
        $this->assertSame(9, Carrera::activas()->count());
        $this->assertEqualsCanonicalizing(
            ['ADM-2023', 'COC-2023', 'CNG-2023', 'GAS-2023', 'INF-2023', 'MTZ-2023', 'SEJ-2023', 'SIS-2023', 'TOP-2023'],
            Carrera::activas()->orderBy('codigo')->pluck('codigo')->all()
        );
        $this->assertSame(9, Carrera::activas()->where('resolucionministerial1', '0210/2023')->count());
        $this->assertDatabaseCount('carreras', 18);
        $this->assertDatabaseMissing('materia', ['codigo' => 'DEMO-SIS-201']);
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
        $this->assertDatabaseHas('grupo', ['codigo' => 'ADM-2023-M01-01-A', 'turno' => 3]);
        $this->assertDatabaseHas('grupo', ['codigo' => 'DEMO-ADM-2023-M01-01-A', 'turno' => 1]);
        $this->assertDatabaseHas('grupo', ['codigo' => 'GAS-2023-M03-07-B']);
        $this->assertDatabaseHas('grupo', ['codigo' => 'SEJ-2023-M03-10-A', 'turno' => 3]);
        $this->assertDatabaseMissing('grupo', ['codigo' => 'SEJ-2023-M03-10-B']);
        $this->assertSame(100, Grupo::count());
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
        $this->seed(AcademicoSeeder::class);

        $this->assertDatabaseCount('carreras', 18);
        $this->assertSame(1, GestionAcademica::where('gestion', 2025)->where('periodo', 1)->count());
        $this->assertDatabaseCount('materia', 72);
        $this->assertDatabaseCount('grupo', 100);
    }

    public function test_academic_module_removes_unlisted_careers_and_their_subjects_and_groups(): void
    {
        $this->seed(AcademicoSeeder::class);
        $this->seed(InscripcionesSeeder::class);

        $carrera = Carrera::create([
            'codigo' => 'OLD-TEST',
            'nombre' => 'Carrera fuera del catálogo',
            'activo' => true,
            'id_instituto' => Instituto::firstOrFail()->id_instituto,
        ]);
        $materia = Materia::create([
            'codigo' => 'OLD-TEST-M01',
            'nombre' => 'Materia de prueba',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $gestion = GestionAcademica::where('vigente', true)->firstOrFail();
        $grupo = Grupo::create([
            'codigo' => 'OLD-TEST-M01-A',
            'capacidad' => 30,
            'turno' => 1,
            'abierto' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materia->id_materia,
        ]);
        $postulante = Postulante::firstOrFail();
        $postulante->update(['id_carrera' => $carrera->id_carrera]);
        $persona = Persona::create([
            'nombre' => 'Estudiante',
            'primerapellido' => 'Prueba',
            'ci' => 'STUDENT-OUT-OF-CATALOG',
            'tipo' => 'estudiante',
        ]);
        DB::table('estudiante')->insert([
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carrera->id_carrera,
        ]);

        $this->seed(AcademicoSeeder::class);

        $this->assertDatabaseMissing('carreras', ['id_carrera' => $carrera->id_carrera]);
        $this->assertDatabaseMissing('materia', ['id_materia' => $materia->id_materia]);
        $this->assertDatabaseMissing('grupo', ['id_grupo' => $grupo->id_grupo]);
        $this->assertDatabaseMissing('postulante', ['id_postulante' => $postulante->id_postulante]);
        $this->assertDatabaseMissing('estudiante', ['id_persona' => $persona->id_persona]);
        $this->assertDatabaseMissing('personas', ['id_persona' => $persona->id_persona]);
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
