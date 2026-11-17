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

    public function test_it_creates_only_the_configured_night_a_group_without_overwriting_group_capacity_or_state(): void
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

        $this->assertDatabaseCount('grupo', 3);
        $this->assertDatabaseHas('grupo', [
            'codigo' => 'SIS-201-A',
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'capacidad' => 18,
            'turno' => 3,
            'abierto' => false,
        ]);
        foreach (['SIS-202-A', 'SIS-101-A'] as $codigo) {
            $this->assertDatabaseHas('grupo', [
                'codigo' => $codigo,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'turno' => 3,
                'capacidad' => 30,
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

        DB::table('grupo')->insert([
            'codigo' => 'SIS-201-B',
            'capacidad' => 30,
            'turno' => 3,
            'abierto' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materia->id_materia,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $grupoB = DB::table('grupo')
            ->where('codigo', 'SIS-201-B')
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->firstOrFail();
        $grupoBMananaId = DB::table('grupo')->insertGetId([
            'codigo' => 'SIS-201-B',
            'capacidad' => 30,
            'turno' => 1,
            'abierto' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materia->id_materia,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->get(route('inscripcion_manual.oferta', ['inscrito' => $inscritoId]))
            ->assertOk()
            ->assertSee('Seleccione un turno antes de ver o asignar materias.')
            ->assertDontSee('Materia de segundo año');

        $ofertaInicial = $this->get(route('inscripcion_manual.oferta', [
            'inscrito' => $inscritoId,
            'paralelo' => 'B',
            'turno' => 3,
        ]));
        $ofertaInicial
            ->assertOk()
            ->assertSee('Materia de segundo año')
            ->assertSee('value="'.$grupoB->id_grupo.'"', false)
            ->assertSee('B [Noche]')
            ->assertDontSee('B [Mañana]');
        preg_match('/<input[^>]*id="seleccionarTodo"[^>]*>/', $ofertaInicial->getContent(), $casillaSeleccionarTodo);
        $this->assertNotEmpty($casillaSeleccionarTodo);
        $this->assertStringNotContainsString('disabled', $casillaSeleccionarTodo[0]);

        $this->get(route('inscripcion_manual.oferta', [
            'inscrito' => $inscritoId,
            'paralelo' => 'B',
            'turno' => 1,
        ]))
            ->assertOk()
            ->assertSee('value="'.$grupoBMananaId.'"', false)
            ->assertSee('B [Mañana]')
            ->assertDontSee('B [Noche]');

        $this->put(route('inscripcion_manual.guardar', $inscritoId), [
            'grupos' => [$materia->id_materia => $grupoBMananaId],
            'turno' => 3,
            'paralelo' => 'B',
        ])->assertSessionHasErrors('grupos');

        $this->put(route('inscripcion_manual.guardar', $inscritoId), [
            'grupos' => [$materia->id_materia => $grupoB->id_grupo],
            'turno' => 3,
            'paralelo' => 'A',
        ])->assertSessionHasErrors('grupos');

        $this->put(route('inscripcion_manual.guardar', $inscritoId), [
            'grupos' => [$materia->id_materia => $grupoB->id_grupo],
            'paralelo' => 'B',
        ])->assertSessionHasErrors('turno');

        $this->put(route('inscripcion_manual.guardar', $inscritoId), [
            'grupos' => [$materia->id_materia => $grupoB->id_grupo],
            'turno' => 3,
            'paralelo' => 'B',
        ])->assertRedirect();

        $this->assertDatabaseHas('inscrito_materia', [
            'id_inscrito' => $inscritoId,
            'id_materia' => $materia->id_materia,
            'id_grupo' => $grupoB->id_grupo,
        ]);
        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $inscritoId,
            'turno' => 3,
            'paralelo' => 'B',
        ]);

        $materiaNueva = Materia::create([
            'codigo' => 'SIS-202',
            'nombre' => 'Otra materia de segundo año',
            'nivel' => 2,
            'numero' => 2,
            'id_carrera' => $carrera->id_carrera,
        ]);
        $grupoMananaNuevoId = DB::table('grupo')->insertGetId([
            'codigo' => 'SIS-202-B',
            'capacidad' => 30,
            'turno' => 1,
            'abierto' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materiaNueva->id_materia,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $grupoNocheParaleloCId = DB::table('grupo')->insertGetId([
            'codigo' => 'SIS-202-C',
            'capacidad' => 30,
            'turno' => 3,
            'abierto' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materiaNueva->id_materia,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $grupoNocheParaleloBId = DB::table('grupo')->insertGetId([
            'codigo' => 'SIS-202-B',
            'capacidad' => 30,
            'turno' => 3,
            'abierto' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materiaNueva->id_materia,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ofertaBloqueada = $this->get(route('inscripcion_manual.oferta', [
            'inscrito' => $inscritoId,
            'paralelo' => 'B',
            'turno' => 1,
        ]));
        $ofertaBloqueada
            ->assertOk()
            ->assertSee('Esta inscripción está registrada en turno Noche y paralelo B.')
            ->assertSee('B [Noche]')
            ->assertDontSee('B [Mañana]');
        preg_match('/<input[^>]*id="seleccionarTodo"[^>]*>/', $ofertaBloqueada->getContent(), $casillaBloqueada);
        $this->assertNotEmpty($casillaBloqueada);
        $this->assertStringContainsString('disabled', $casillaBloqueada[0]);

        $this->put(route('inscripcion_manual.guardar', $inscritoId), [
            'grupos' => [$materiaNueva->id_materia => $grupoMananaNuevoId],
            'turno' => 1,
            'paralelo' => 'B',
        ])->assertSessionHasErrors('grupos');

        $this->put(route('inscripcion_manual.guardar', $inscritoId), [
            'grupos' => [$materiaNueva->id_materia => $grupoNocheParaleloCId],
            'turno' => 3,
            'paralelo' => 'C',
        ])->assertSessionHasErrors('grupos');

        $this->put(route('inscripcion_manual.guardar', $inscritoId), [
            'grupos' => [$materiaNueva->id_materia => $grupoNocheParaleloBId],
            'turno' => 3,
            'paralelo' => 'B',
        ])->assertRedirect();
        $this->assertDatabaseHas('inscrito_materia', [
            'id_inscrito' => $inscritoId,
            'id_materia' => $materiaNueva->id_materia,
            'id_grupo' => $grupoNocheParaleloBId,
        ]);

        $this->get(route('inscripcion_manual.index', ['id_inscrito' => $inscritoId]))
            ->assertOk()
            ->assertSee('B [3');
    }
}
