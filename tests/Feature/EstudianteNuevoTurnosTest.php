<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\TarifaMatricula;
use App\Models\User;
use App\Services\Inscripciones\DisponibilidadTurnos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EstudianteNuevoTurnosTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_uses_only_the_latest_vigente_management_and_lists_turns_by_career_level(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->createAdmin());
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $gestionAnterior = $this->createGestion(2025);
        $gestionVigente = $this->createGestion(2026);
        $carrera = $this->createCarrera($instituto, 'NEW-STUDENT-SIS');

        $this->createGrupo($carrera, $gestionAnterior, 1, 2, 'C');
        $this->createGrupo($carrera, $gestionVigente, 1, 1, 'A');
        $this->createGrupo($carrera, $gestionVigente, 2, 3, 'B');

        $this->withViewErrors([])
            ->get(route('estudiante_nuevo.index'))
            ->assertOk()
            ->assertSee('2026')
            ->assertDontSee('2025')
            ->assertSee('data-turnos-config=', false)
            ->assertViewHas('gestiones', fn ($gestiones): bool =>
                $gestiones->pluck('id_gestionacademica')->all() === [$gestionVigente->id_gestionacademica]
            )
            ->assertViewHas('opcionesTurnosCarrera', fn (array $opciones): bool =>
                ($opciones[$carrera->id_carrera][1][0]['turno'] ?? null) === 1
                && ($opciones[$carrera->id_carrera][1][0]['paralelo'] ?? null) === 'A'
                && ($opciones[$carrera->id_carrera][2][0]['turno'] ?? null) === 3
                && ($opciones[$carrera->id_carrera][2][0]['paralelo'] ?? null) === 'B'
            );
    }

    public function test_existing_student_can_enroll_in_another_career_but_not_repeat_a_career_or_shift(): void
    {
        $this->withoutMiddleware();
        $this->actingAs($this->createAdmin());
        $gestion = $this->createGestion(2026);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carreraAnterior = $this->createCarrera($instituto, 'NEW-STUDENT-HISTORY');
        $carreraDestino = $this->createCarrera($instituto, 'NEW-STUDENT-DESTINATION');
        $carreraAlternativa = $this->createCarrera($instituto, 'NEW-STUDENT-ALTERNATIVE');
        TarifaMatricula::create([
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carreraDestino->id_carrera,
            'nivel' => 1,
            'monto' => 765.50,
        ]);
        $this->createGrupo($carreraDestino, $gestion, 1, 1, 'A');
        $this->createGrupo($carreraDestino, $gestion, 2, 3, 'B');
        $this->createGrupo($carreraAlternativa, $gestion, 1, 1, 'A');
        $this->createGrupo($carreraAlternativa, $gestion, 1, 3, 'B');

        $persona = Persona::create([
            'nombre' => 'Estudiante',
            'primerapellido' => 'Prueba',
            'ci' => 'NEW-STUDENT-001',
            'tipo' => 'Estudiante',
        ]);
        DB::table('estudiante')->insert([
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carreraAnterior->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $datos = [
            'ci' => $persona->ci,
            'fecha' => now()->toDateString(),
            'deposito' => 'NEW-STUDENT-VOUCHER-1',
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carreraDestino->id_carrera,
            'nivelinicio' => 1,
        ];

        $this->post(route('estudiante_nuevo.store'), $datos + [
            'turno' => 3,
            'paralelo' => 'B',
        ])->assertSessionHasErrors('turno');

        $this->post(route('estudiante_nuevo.store'), $datos + [
            'turno' => 1,
            'paralelo' => 'A',
        ])->assertRedirect(route('estudiante_nuevo.index'));

        $this->assertDatabaseHas('inscrito', [
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carreraDestino->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'turno' => 1,
            'paralelo' => 'A',
        ]);
        $inscrito = DB::table('inscrito')
            ->where('id_persona', $persona->id_persona)
            ->where('id_carrera', $carreraDestino->id_carrera)
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->first();
        $this->assertDatabaseHas('pago', [
            'id_inscrito' => $inscrito->id_inscrito,
            'monto' => '765.50',
        ]);
        $this->assertSame(
            29,
            app(DisponibilidadTurnos::class)
                ->opciones($carreraDestino->id_carrera, $gestion->id_gestionacademica, null, 1)[0]['disponibles']
        );

        $this->post(route('estudiante_nuevo.store'), array_merge($datos, [
            'deposito' => 'NEW-STUDENT-VOUCHER-2',
            'turno' => 1,
            'paralelo' => 'A',
            'id_carrera' => $carreraAlternativa->id_carrera,
        ]))->assertSessionHasErrors('turno');

        $this->post(route('estudiante_nuevo.store'), array_merge($datos, [
            'deposito' => 'NEW-STUDENT-VOUCHER-3',
            'turno' => 3,
            'paralelo' => 'B',
            'id_carrera' => $carreraAlternativa->id_carrera,
        ]))->assertRedirect(route('estudiante_nuevo.index'));

        $this->post(route('estudiante_nuevo.store'), array_merge($datos, [
            'deposito' => 'NEW-STUDENT-VOUCHER-4',
            'turno' => 1,
            'paralelo' => 'A',
        ]))->assertSessionHasErrors('id_carrera');

        $this->assertSame(2, DB::table('inscrito')
            ->where('id_persona', $persona->id_persona)
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->where('activo', true)
            ->count());
    }

    private function createGestion(int $year): GestionAcademica
    {
        return GestionAcademica::create([
            'gestion' => $year,
            'periodo' => 1,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'vigente' => true,
            'activo' => true,
        ]);
    }

    private function createCarrera(Instituto $instituto, string $codigo): Carrera
    {
        return Carrera::create([
            'codigo' => $codigo,
            'nombre' => $codigo,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'activo' => true,
            'id_instituto' => $instituto->id_instituto,
        ]);
    }

    private function createGrupo(
        Carrera $carrera,
        GestionAcademica $gestion,
        int $nivel,
        int $turno,
        string $paralelo
    ): void {
        $codigoMateria = "{$carrera->codigo}-N{$nivel}-T{$turno}-{$paralelo}";
        $idMateria = DB::table('materia')->insertGetId([
            'codigo' => $codigoMateria,
            'nombre' => "Materia {$codigoMateria}",
            'nivel' => $nivel,
            'numero' => 1,
            'activo' => true,
            'curricular' => true,
            'id_carrera' => $carrera->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('grupo')->insert([
            'codigo' => "{$codigoMateria}-{$paralelo}",
            'capacidad' => 30,
            'turno' => $turno,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $idMateria,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'ADMIN-NEW-STUDENT-TURNS',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_new_student_turns',
            'password' => Hash::make('password123'),
        ]);
    }
}
