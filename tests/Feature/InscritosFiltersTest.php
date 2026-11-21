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

class InscritosFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_bth_and_transfer_filters_can_be_applied_independently_and_together(): void
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
        $carrera = Carrera::create([
            'codigo' => 'TEST-INSCRITOS',
            'nombre' => 'Carrera de Prueba',
            'resolucionministerial1' => 'R.M. N° 789/2027',
            'id_instituto' => $instituto->id_instituto,
        ]);

        $bth = $this->createInscrito($carrera, $gestion, 'BTH', true, false);
        $traspaso = $this->createInscrito($carrera, $gestion, 'TRASPASO', false, true);
        $ambos = $this->createInscrito($carrera, $gestion, 'AMBOS', true, true);

        $bthResponse = $this->get(route('inscriptos.index', ['bth' => '1']));
        $bthResponse->assertOk()
            ->assertSee('Certificado BTH')
            ->assertSee('Carrera de Prueba · R.M. 789/2027')
            ->assertSee('Traspaso / convalidación');
        $this->assertEqualsCanonicalizing(
            [$bth, $ambos],
            $bthResponse->viewData('inscritos')->getCollection()->pluck('id_persona')->all()
        );

        $traspasoResponse = $this->get(route('inscriptos.index', ['traspaso_convalidacion' => '1']));
        $traspasoResponse->assertOk();
        $this->assertEqualsCanonicalizing(
            [$traspaso, $ambos],
            $traspasoResponse->viewData('inscritos')->getCollection()->pluck('id_persona')->all()
        );

        $combinadoResponse = $this->get(route('inscriptos.index', [
            'bth' => '1',
            'traspaso_convalidacion' => '1',
        ]));
        $combinadoResponse->assertOk();
        $this->assertEquals(
            [$ambos],
            $combinadoResponse->viewData('inscritos')->getCollection()->pluck('id_persona')->all()
        );
    }

    public function test_inscriptions_show_course_group_and_turn_and_sort_by_turn_then_parallel(): void
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
        $carrera = Carrera::create([
            'codigo' => 'TEST-GRUPOS-INSCRITOS',
            'nombre' => 'Sistemas Informáticos',
            'id_instituto' => $instituto->id_instituto,
        ]);

        $inscripciones = [];
        foreach ([
            ['NOCHE', 3, 3, 'TEST-A'],
            ['MANANA-B', 1, 2, 'TEST-B'],
            ['MANANA-A', 1, 1, 'TEST-A'],
            ['TARDE', 2, 2, 'TEST-A'],
        ] as [$suffix, $turno, $nivel, $codigoParalelo]) {
            $idPersona = $this->createInscrito($carrera, $gestion, $suffix, false, false);
            $idInscrito = DB::table('inscrito')->where('id_persona', $idPersona)->value('id_inscrito');
            DB::table('cursa')->insert([
                'id_persona' => $idPersona,
                'id_carrera' => $carrera->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'nivelinicio' => $nivel,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $idMateria = DB::table('materia')->insertGetId([
                'codigo' => 'MATERIA-'.$suffix,
                'nombre' => 'Materia '.$suffix,
                'nivel' => $nivel,
                'id_carrera' => $carrera->id_carrera,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id_materia');
            $idGrupo = DB::table('grupo')->insertGetId([
                'codigo' => $codigoParalelo,
                'turno' => $turno,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'id_materia' => $idMateria,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id_grupo');
            DB::table('inscrito_materia')->insert([
                'id_inscrito' => $idInscrito,
                'id_materia' => $idMateria,
                'id_grupo' => $idGrupo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $inscripciones[$suffix] = $idPersona;
        }

        $response = $this->get(route('inscriptos.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carrera->id_carrera,
        ]));
        $response->assertOk()
            ->assertDontSee('Aplicar filtros')
            ->assertSee('form.submit()', false)
            ->assertSee('Primer año')
            ->assertSee('Segundo año')
            ->assertSee('Tercer año')
            ->assertSee('Paralelo A')
            ->assertSee('Paralelo B')
            ->assertSee('Mañana')
            ->assertSee('Tarde')
            ->assertSee('Noche');
        $this->assertSame(
            [
                $inscripciones['MANANA-A'],
                $inscripciones['MANANA-B'],
                $inscripciones['TARDE'],
                $inscripciones['NOCHE'],
            ],
            $response->viewData('inscritos')->getCollection()->pluck('id_persona')->all()
        );
        $this->assertSame(
            [1, 2, 3],
            $response->viewData('niveles')->pluck('valor')->map(fn ($nivel): int => (int) $nivel)->all()
        );
        $this->assertTrue($response->viewData('turnos')->isEmpty());

        $response = $this->get(route('inscriptos.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 2,
        ]));
        $this->assertEqualsCanonicalizing(
            ['Mañana', 'Tarde'],
            $response->viewData('turnos')->pluck('etiqueta')->all()
        );
        $this->assertSame(
            ['Primer año', 'Segundo año', 'Tercer año'],
            $response->viewData('niveles')->pluck('etiqueta')->all()
        );
        $this->assertTrue($response->viewData('paralelos')->isEmpty());

        $response = $this->get(route('inscriptos.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 2,
            'turno' => '1',
        ]));
        $this->assertSame(['B'], $response->viewData('paralelos')->all());

        $response = $this->get(route('inscriptos.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 1,
            'turno' => '1',
            'paralelo' => 'A',
        ]));
        $response->assertOk()
            ->assertSee('Primer año')
            ->assertSee('Paralelo A')
            ->assertSee('Mañana');
        $this->assertSame(
            [$inscripciones['MANANA-A']],
            $response->viewData('inscritos')->getCollection()->pluck('id_persona')->all()
        );
    }

    public function test_levels_are_limited_to_configured_groups_allowed_by_the_career_regime(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());

        $gestion = GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'vigente' => true,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'TEST-NIVELES-REALES',
            'nombre' => 'Sistemas Informáticos',
            'regimen' => Carrera::REGIMEN_ANUAL,
            'id_instituto' => $instituto->id_instituto,
        ]);

        foreach ([1, 6] as $nivel) {
            $personaId = $this->createInscrito($carrera, $gestion, 'NIVEL-'.$nivel, false, false);
            DB::table('cursa')->insert([
                'id_persona' => $personaId,
                'id_carrera' => $carrera->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'nivelinicio' => $nivel,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $materiaId = DB::table('materia')->insertGetId([
                'codigo' => 'MATERIA-NIVEL-'.$nivel,
                'nombre' => 'Materia nivel '.$nivel,
                'nivel' => $nivel,
                'id_carrera' => $carrera->id_carrera,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('grupo')->insert([
                'codigo' => 'GRUPO-NIVEL-'.$nivel.'-A',
                'turno' => 1,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'id_materia' => $materiaId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $filters = [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carrera->id_carrera,
        ];
        $response = $this->get(route('inscriptos.index', $filters));
        $this->assertSame(
            [[1, 'Primer año']],
            $response->viewData('niveles')
                ->map(fn (object $nivel): array => [$nivel->valor, $nivel->etiqueta])
                ->all()
        );

        $this->get(route('inscriptos.index', [...$filters, 'nivel' => 6]))
            ->assertSessionHasErrors('nivel');
    }

    public function test_configured_careers_levels_shifts_and_parallels_are_available_without_enrolled_students(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());

        $gestion = GestionAcademica::create([
            'gestion' => 2028,
            'periodo' => 1,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'vigente' => true,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'TEST-SIN-INSCRITOS',
            'nombre' => 'Construcción Civil',
            'regimen' => Carrera::REGIMEN_ANUAL,
            'id_instituto' => $instituto->id_instituto,
        ]);

        foreach ([[1, 1, 'A'], [1, 2, 'B']] as [$nivel, $turno, $paralelo]) {
            $materiaId = DB::table('materia')->insertGetId([
                'codigo' => 'MATERIA-CONSTRUCCION-'.$turno,
                'nombre' => 'Materia de Construcción '.$turno,
                'nivel' => $nivel,
                'id_carrera' => $carrera->id_carrera,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('grupo')->insert([
                'codigo' => 'CONSTRUCCION-'.$paralelo,
                'turno' => $turno,
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'id_materia' => $materiaId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $otraGestion = GestionAcademica::create([
            'gestion' => 2029,
            'periodo' => 1,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'vigente' => false,
        ]);
        $otraCarrera = Carrera::create([
            'codigo' => 'TEST-OTRA-GESTION',
            'nombre' => 'Carrera de otra gestión',
            'regimen' => Carrera::REGIMEN_ANUAL,
            'id_instituto' => $instituto->id_instituto,
        ]);
        $otraMateria = DB::table('materia')->insertGetId([
            'codigo' => 'MATERIA-OTRA-GESTION',
            'nombre' => 'Materia de otra gestión',
            'nivel' => 1,
            'id_carrera' => $otraCarrera->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('grupo')->insert([
            'codigo' => 'OTRA-GESTION-A',
            'turno' => 1,
            'id_gestionacademica' => $otraGestion->id_gestionacademica,
            'id_materia' => $otraMateria,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $baseFilters = ['id_gestionacademica' => $gestion->id_gestionacademica];
        $managementPage = $this->get(route('inscriptos.index', $baseFilters));
        $this->assertTrue(
            $managementPage->viewData('carreras')->contains(
                fn (Carrera $opcion): bool => $opcion->id_carrera === $carrera->id_carrera
            )
        );
        $this->assertFalse(
            $managementPage->viewData('carreras')->contains(
                fn (Carrera $opcion): bool => $opcion->id_carrera === $otraCarrera->id_carrera
            )
        );

        $careerFilters = [
            ...$baseFilters,
            'id_carrera' => $carrera->id_carrera,
        ];
        $levelPage = $this->get(route('inscriptos.index', $careerFilters));
        $this->assertSame(
            ['Primer año'],
            $levelPage->viewData('niveles')->pluck('etiqueta')->all()
        );

        $yearFilters = [...$careerFilters, 'nivel' => 1];
        $turnPage = $this->get(route('inscriptos.index', $yearFilters));
        $this->assertEqualsCanonicalizing(
            ['Mañana', 'Tarde'],
            $turnPage->viewData('turnos')->pluck('etiqueta')->all()
        );

        $parallelFilters = [...$yearFilters, 'turno' => 2];
        $parallelPage = $this->get(route('inscriptos.index', $parallelFilters));
        $this->assertSame(['B'], $parallelPage->viewData('paralelos')->all());

        $emptyReport = $this->get(route('inscriptos.index', [
            ...$parallelFilters,
            'paralelo' => 'B',
        ]));
        $emptyReport->assertOk()->assertSee('No se encontraron inscripciones con esos criterios.');
        $this->assertSame(0, $emptyReport->viewData('inscritos')->total());
    }

    private function createInscrito(
        Carrera $carrera,
        GestionAcademica $gestion,
        string $suffix,
        bool $hasBth,
        bool $isTransfer
    ): int {
        $persona = Persona::create([
            'nombre' => 'Estudiante '.$suffix,
            'primerapellido' => 'Prueba',
            'ci' => 'CI-'.$suffix,
            'tipo' => 'Estudiante',
        ]);
        DB::table('estudiante')->insert([
            'id_persona' => $persona->id_persona,
            'path_bth' => $hasBth ? 'documentos/'.$suffix.'.jpg' : null,
            'traspaso_convalidacion' => (int) $isTransfer,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inscrito')->insert([
            'fecha' => '2027-01-10',
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $persona->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $persona->id_persona;
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'ADMIN-INSCRITOS-001',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_inscritos',
            'password' => Hash::make('password123'),
        ]);
    }
}
