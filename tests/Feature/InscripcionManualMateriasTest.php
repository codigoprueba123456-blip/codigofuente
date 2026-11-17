<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureFunctionalityAccess;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InscripcionManualMateriasTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_subjects_are_limited_to_the_selected_career_and_enrollment_period(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestion = GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $otraGestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 2,
            'regimen' => 1,
            'vigente' => false,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = $this->createCarrera($instituto, 'TEST-MANUAL-A', 'Carrera seleccionada');
        $carrera->update(['resolucionministerial1' => 'R.M. N° 123/2027']);
        $otraCarrera = $this->createCarrera($instituto, 'TEST-MANUAL-B', 'Otra carrera');
        $persona = Persona::create([
            'nombre' => 'Diego',
            'primerapellido' => 'Flores',
            'ci' => 'CI-MANUAL-001',
            'tipo' => 'Estudiante',
        ]);
        DB::table('estudiante')->insert(['id_persona' => $persona->id_persona]);
        $inscritoId = DB::table('inscrito')->insertGetId([
            'fecha' => '2027-01-10',
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $persona->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $materiaInscrita = $this->createMateria($carrera, 'MAN-101', 'Materia inscrita');
        $materiaNoAsignada = $this->createMateria($carrera, 'MAN-102', 'Materia no asignada');
        $materiaOtraCarrera = $this->createMateria($otraCarrera, 'OTR-101', 'Materia de otra carrera');
        $grupoActual = $this->createGrupo($materiaInscrita, $gestion, 'GRUPO-2027');
        $this->createGrupo($materiaInscrita, $otraGestion, 'GRUPO-2026');
        DB::table('inscrito_materia')->insert([
            'id_inscrito' => $inscritoId,
            'id_materia' => $materiaInscrita,
            'id_grupo' => $grupoActual,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('inscripcion_manual.materias', $inscritoId))
            ->assertOk()
            ->assertSee('Materia inscrita')
            ->assertSee('Materia no asignada')
            ->assertSee('GRUPO-2027')
            ->assertSee('Carrera seleccionada · R.M. 123/2027')
            ->assertSee('Gestión 2027 / Periodo 1')
            ->assertDontSee('Materia de otra carrera')
            ->assertDontSee('GRUPO-2026');
    }

    public function test_a_single_career_must_still_be_selected_explicitly(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestion = GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = $this->createCarrera($instituto, 'TEST-ONLY-ONE', 'Una sola carrera');
        $carrera->update(['resolucionministerial1' => 'R.M. N° 456/2027']);
        $persona = Persona::create([
            'nombre' => 'Diego',
            'primerapellido' => 'Flores',
            'ci' => 'CI-MANUAL-002',
            'tipo' => 'Estudiante',
        ]);
        DB::table('estudiante')->insert(['id_persona' => $persona->id_persona]);
        $inscritoId = DB::table('inscrito')->insertGetId([
            'fecha' => '2027-01-10',
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $persona->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'turno' => 3,
            'paralelo' => 'A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('inscripcion_manual.buscar', ['q' => 'CI-MANUAL-002']))
            ->assertOk()
            ->assertJsonPath('0.inscripciones.0.id_inscrito', $inscritoId)
            ->assertJsonPath('0.inscripciones.0.carrera', 'Una sola carrera · R.M. 456/2027')
            ->assertJsonPath('0.inscripciones.0.turno', 3)
            ->assertJsonPath('0.inscripciones.0.paralelo', 'A');
        $this->get(route('inscripcion_manual.index'))
            ->assertOk()
            ->assertSee('Seleccione una carrera para cargar sus materias.')
            ->assertSee('Seleccione la carrera que desea gestionar.')
            ->assertSee('2027 · Periodo 1');
    }

    public function test_offer_uses_the_registered_level_and_turn_and_turn_change_preserves_historical_groups(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());
        $gestionActual = GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $gestionAnterior = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => false,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = $this->createCarrera($instituto, 'TEST-MANUAL-TURN', 'Carrera de turnos');
        $persona = Persona::create([
            'nombre' => 'Diego',
            'primerapellido' => 'Flores',
            'ci' => 'CI-MANUAL-TURN-001',
            'tipo' => 'Estudiante',
        ]);
        DB::table('estudiante')->insert(['id_persona' => $persona->id_persona]);
        $inscritoActual = DB::table('inscrito')->insertGetId([
            'fecha' => '2027-01-10',
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $persona->id_persona,
            'id_gestionacademica' => $gestionActual->id_gestionacademica,
            'turno' => 3,
            'paralelo' => 'A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $inscritoAnterior = DB::table('inscrito')->insertGetId([
            'fecha' => '2026-01-10',
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $persona->id_persona,
            'id_gestionacademica' => $gestionAnterior->id_gestionacademica,
            'turno' => 2,
            'paralelo' => 'C',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('cursa')->insert([
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestionActual->id_gestionacademica,
            'nivelinicio' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $materiaPrimerNivel = $this->createMateria($carrera, 'TURN-101', 'Materia de primer año', 1);
        $materiaSegundoNivel = $this->createMateria($carrera, 'TURN-201', 'Materia de segundo año', 2);
        $grupoActual = $this->createGrupo($materiaSegundoNivel, $gestionActual, 'TURN-201-A', 3);
        $grupoDestino = $this->createGrupo($materiaSegundoNivel, $gestionActual, 'TURN-201-B', 1, null);
        $grupoAnterior = $this->createGrupo($materiaSegundoNivel, $gestionAnterior, 'TURN-201-C', 2);
        DB::table('inscrito_materia')->insert([
            [
                'id_inscrito' => $inscritoActual,
                'id_materia' => $materiaSegundoNivel,
                'id_grupo' => $grupoActual,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_inscrito' => $inscritoAnterior,
                'id_materia' => $materiaSegundoNivel,
                'id_grupo' => $grupoAnterior,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get(route('inscripcion_manual.index', ['id_inscrito' => $inscritoActual]))
            ->assertOk()
            ->assertSee('Turno Noche')
            ->assertSee('Paralelo A');

        $this->get(route('inscripcion_manual.oferta', $inscritoActual))
            ->assertOk()
            ->assertSee('Turno Noche')
            ->assertSee('Paralelo A')
            ->assertSee('Materia de segundo año')
            ->assertDontSee('Materia de primer año')
            ->assertSee('Cambiar turno/paralelo');

        $this->put(route('inscripcion_manual.cambiar-turno', $inscritoActual), [
            'turno' => 1,
            'paralelo' => 'B',
        ])->assertRedirect(route('inscripcion_manual.oferta', $inscritoActual));

        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $inscritoActual,
            'turno' => 1,
            'paralelo' => 'B',
        ]);
        $this->assertDatabaseHas('inscrito_materia', [
            'id_inscrito' => $inscritoActual,
            'id_materia' => $materiaSegundoNivel,
            'id_grupo' => null,
        ]);
        $this->assertDatabaseHas('inscrito_materia', [
            'id_inscrito' => $inscritoAnterior,
            'id_materia' => $materiaSegundoNivel,
            'id_grupo' => $grupoAnterior,
        ]);

        $this->get(route('inscripcion_manual.oferta', $inscritoActual))
            ->assertOk()
            ->assertSee('Turno Mañana')
            ->assertSee('Paralelo B')
            ->assertSee('value="'.$grupoDestino.'"', false);

        $this->put(route('inscripcion_manual.guardar', $inscritoActual), [
            'grupos' => [$materiaSegundoNivel => $grupoDestino],
            'turno' => 1,
            'paralelo' => 'B',
        ])->assertRedirect();
        $this->assertDatabaseHas('inscrito_materia', [
            'id_inscrito' => $inscritoActual,
            'id_materia' => $materiaSegundoNivel,
            'id_grupo' => $grupoDestino,
        ]);

        $this->put(route('inscripcion_manual.cambiar-turno', $inscritoActual), [
            'turno' => 2,
            'paralelo' => 'C',
        ])->assertSessionHasErrors('turno');
        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $inscritoActual,
            'turno' => 1,
            'paralelo' => 'B',
        ]);
    }

    private function createCarrera(Instituto $instituto, string $code, string $name): Carrera
    {
        return Carrera::create([
            'codigo' => $code,
            'nombre' => $name,
            'id_instituto' => $instituto->id_instituto,
        ]);
    }

    private function createMateria(Carrera $carrera, string $code, string $name, int $nivel = 1): int
    {
        return DB::table('materia')->insertGetId([
            'codigo' => $code,
            'nombre' => $name,
            'nivel' => $nivel,
            'numero' => 1,
            'id_carrera' => $carrera->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createGrupo(
        int $materiaId,
        GestionAcademica $gestion,
        string $code,
        int $turno = 1,
        ?int $capacidad = 30
    ): int
    {
        return DB::table('grupo')->insertGetId([
            'codigo' => $code,
            'capacidad' => $capacidad,
            'turno' => $turno,
            'abierto' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materiaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'ADMIN-MANUAL-001',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_manual',
            'password' => Hash::make('password123'),
        ]);
    }
}
