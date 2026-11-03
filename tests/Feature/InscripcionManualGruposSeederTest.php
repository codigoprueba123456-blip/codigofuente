<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\User;
use Database\Seeders\Modulos\InscripcionManualGruposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InscripcionManualGruposSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_open_a_and_b_groups_for_all_subjects_without_overwriting_existing_groups(): void
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

        $this->assertDatabaseCount('grupo', 6);
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
        foreach (['SIS-101-A', 'SIS-101-B'] as $codigo) {
            $this->assertDatabaseHas('grupo', [
                'codigo' => $codigo,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'abierto' => true,
            ]);
        }
    }

    public function test_seeded_groups_are_offered_and_saved_in_manual_enrollment(): void
    {
        $adminPersona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'ADMIN-MANUAL-SEED',
            'tipo' => 'admin',
        ]);
        $this->actingAs(User::create([
            'id_persona' => $adminPersona->id_persona,
            'usuario' => 'admin_manual_seed',
            'password' => 'password',
        ]));

        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'SIS-2023',
            'nombre' => 'SISTEMAS INFORMATICOS',
            'activo' => true,
            'id_instituto' => $instituto->id_instituto,
        ]);
        $gestion = GestionAcademica::create([
            'gestion' => now()->year,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $materia = Materia::create([
            'codigo' => 'SIS-201',
            'nombre' => 'Materia de segundo año',
            'nivel' => 2,
            'numero' => 1,
            'id_carrera' => $carrera->id_carrera,
        ]);
        $estudiantePersona = Persona::create([
            'nombre' => 'Andrea',
            'primerapellido' => 'Quispe',
            'ci' => 'CI-MANUAL-SEED',
            'tipo' => 'estudiante',
        ]);
        DB::table('estudiante')->insert(['id_persona' => $estudiantePersona->id_persona]);
        $inscritoId = DB::table('inscrito')->insertGetId([
            'fecha' => now()->toDateString(),
            'activo' => true,
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $estudiantePersona->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('cursa')->insert([
            'id_persona' => $estudiantePersona->id_persona,
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'nivelinicio' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(InscripcionManualGruposSeeder::class);

        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $inscritoId,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'activo' => true,
        ]);
        $this->assertDatabaseHas('estudiante', ['id_persona' => $estudiantePersona->id_persona]);
        $this->assertDatabaseHas('cursa', [
            'id_persona' => $estudiantePersona->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'nivelinicio' => 2,
        ]);
        $this->assertSame(1, DB::table('inscrito as i')
            ->join('estudiante as e', 'e.id_persona', '=', 'i.id_persona')
            ->join('personas as p', 'p.id_persona', '=', 'i.id_persona')
            ->join('carreras as c', 'c.id_carrera', '=', 'i.id_carrera')
            ->where('i.id_inscrito', $inscritoId)
            ->where('i.id_gestionacademica', $gestion->id_gestionacademica)
            ->where('i.activo', true)
            ->whereNull('i.id_inscrito_reemplazo')
            ->count());
        $this->get(route('inscripcion_manual.buscar', ['q' => 'CI-MANUAL-SEED']))
            ->assertOk()
            ->assertJsonPath('0.inscripciones.0.id_inscrito', $inscritoId)
            ->assertJsonPath('0.inscripciones.0.nivel', 2);

        $grupoB = DB::table('grupo')
            ->where('codigo', 'SIS-201-B')
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->firstOrFail();
        $this->get(route('inscripcion_manual.oferta', [
            'inscrito' => $inscritoId,
            'paralelo' => 'B',
        ]))
            ->assertOk()
            ->assertSee('Materia de segundo año')
            ->assertSee('value="'.$grupoB->id_grupo.'"', false)
            ->assertSee('B [3');

        $this->put(route('inscripcion_manual.guardar', $inscritoId), [
            'grupos' => [$materia->id_materia => $grupoB->id_grupo],
        ])->assertRedirect();

        $this->assertDatabaseHas('inscrito_materia', [
            'id_inscrito' => $inscritoId,
            'id_materia' => $materia->id_materia,
            'id_grupo' => $grupoB->id_grupo,
        ]);
        $this->get(route('inscripcion_manual.index', ['id_inscrito' => $inscritoId]))
            ->assertOk()
            ->assertSee('B [3');
    }
}
