<?php

namespace Tests\Feature;

use App\Models\Instituto;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\EnablesFunctionality;
use Tests\TestCase;

class CarreraInstituteTest extends TestCase
{
    use RefreshDatabase;
    use EnablesFunctionality;

    public function test_career_uses_configured_institute_when_created_and_updated(): void
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Sistema',
            'ci' => '11111111',
            'tipo' => 'admin',
        ]);
        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_test',
            'password' => Hash::make('password123'),
        ]);

        $institutoConfigurado = Instituto::create([
            'nombreinstituto' => 'Instituto Configurado',
            'preciocredito' => 5,
        ]);
        $otroInstituto = Instituto::create(['nombreinstituto' => 'Otro Instituto']);
        $this->enableFunctionality('planes/carreras');

        $this->actingAs($user)
            ->get(route('planes.carreras.create'))
            ->assertOk()
            ->assertSee('Instituto Configurado')
            ->assertSee('Técnico Superior')
            ->assertSee('Técnico Medio')
            ->assertSee('Anual')
            ->assertSee('Semestral')
            ->assertDontSee('name="creditajeadmision"', false)
            ->assertDontSee('name="creditajematricula"', false);

        $datosCarrera = [
            'codigo' => 'INF-001',
            'nombre' => 'Informática',
            'id_instituto' => $otroInstituto->id_instituto,
            'creditajeadmision' => 10,
            'creditajematricula' => 20,
            'nivelacademico' => 1,
            'regimen' => 1,
            'version' => 1,
        ];

        $this->post(route('planes.carreras.store'), $datosCarrera)
            ->assertRedirect(route('planes.carreras.index'));

        $this->assertDatabaseHas('carreras', [
            'codigo' => 'INF-001',
            'id_instituto' => $institutoConfigurado->id_instituto,
            'activo' => true,
            'creditajeadmision' => 50,
            'creditajematricula' => 50,
        ]);

        $carrera = \App\Models\Carrera::where('codigo', 'INF-001')->firstOrFail();
        $this->put(route('planes.carreras.update', $carrera->id_carrera), [
            ...$datosCarrera,
            'codigo' => 'INF-001',
            'id_instituto' => $otroInstituto->id_instituto,
            'resolucionministerial1' => 'RM-123',
        ])->assertRedirect(route('planes.carreras.index'));

        $this->assertDatabaseHas('carreras', [
            'id_carrera' => $carrera->id_carrera,
            'id_instituto' => $institutoConfigurado->id_instituto,
            'resolucionministerial1' => 'RM-123',
            'creditajeadmision' => 50,
            'creditajematricula' => 50,
        ]);

        $this->get(route('planes.carreras.index'))
            ->assertOk()
            ->assertSee('50')
            ->assertSee('Anual')
            ->assertSee('Técnico Superior')
            ->assertSee('RM-123');
    }

    public function test_subject_credits_are_automatically_distributed_and_recalculated(): void
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Sistema',
            'ci' => '33333333',
            'tipo' => 'admin',
        ]);
        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_creditage_test',
            'password' => Hash::make('password123'),
        ]);
        $instituto = Instituto::create([
            'nombreinstituto' => 'Instituto de Prueba',
            'preciocredito' => 5,
        ]);
        $carrera = \App\Models\Carrera::create([
            'codigo' => 'INF-003',
            'nombre' => 'Informática',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $this->enableFunctionality('planes/materias');

        $this->actingAs($user);
        $materias = [];
        foreach (['Materia 1', 'Materia 2', 'Materia 3'] as $nombre) {
            $this->post(route('planes.materias.store'), [
                'nombre' => $nombre,
                'id_carrera' => $carrera->id_carrera,
                'curricular' => 1,
            ])->assertRedirect(route('planes.materias.index'));

            $materias[] = \App\Models\Materia::where('nombre', $nombre)->firstOrFail();
        }

        $this->assertSame([17, 17, 16], array_map(
            static fn ($materia): int => (int) $materia->fresh()->creditajemateria,
            $materias
        ));
        $this->assertSame(50, (int) $carrera->fresh()->creditajematricula);
        $this->assertSame(50, (int) $carrera->fresh()->creditajeadmision);

        $this->put(route('planes.materias.update', $materias[2]->id_materia), [
            'nombre' => 'Materia 3',
            'id_carrera' => $carrera->id_carrera,
        ])->assertRedirect(route('planes.materias.index'));

        $this->assertSame(25, (int) $materias[0]->fresh()->creditajemateria);
        $this->assertSame(25, (int) $materias[1]->fresh()->creditajemateria);
        $this->assertNull($materias[2]->fresh()->creditajemateria);
    }

    public function test_career_can_be_enabled_and_disabled_from_its_listing(): void
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Sistema',
            'ci' => '22222222',
            'tipo' => 'admin',
        ]);
        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_estado_test',
            'password' => Hash::make('password123'),
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = \App\Models\Carrera::create([
            'codigo' => 'INF-002',
            'nombre' => 'Informática',
            'id_instituto' => $instituto->id_instituto,
        ]);

        $this->actingAs($user)
            ->get(route('planes.carreras.index'))
            ->assertOk()
            ->assertSee('Habilitada');

        $this->patch(route('planes.carreras.estado', $carrera->id_carrera), [
            'activo' => 0,
        ])->assertRedirect(route('planes.carreras.index'));

        $this->assertDatabaseHas('carreras', [
            'id_carrera' => $carrera->id_carrera,
            'activo' => false,
        ]);

        $this->get(route('planes.carreras.index'))
            ->assertOk()
            ->assertSee('Deshabilitada');

        $this->patch(route('planes.carreras.estado', $carrera->id_carrera), [
            'activo' => 1,
        ])->assertRedirect(route('planes.carreras.index'));

        $this->assertDatabaseHas('carreras', [
            'id_carrera' => $carrera->id_carrera,
            'activo' => true,
        ]);
    }
}
