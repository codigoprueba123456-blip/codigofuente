<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Instituto;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\User;
use Database\Seeders\Modulos\AcademicoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InicioInscripcionModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_existing_gestiones_and_links_to_existing_forms_without_writing(): void
    {
        $this->actingAs($this->createAdmin());
        foreach ([2024, 2025, 2026] as $year) {
            GestionAcademica::create([
                'gestion' => $year,
                'periodo' => 1,
                'regimen' => 1,
                'inicio' => "{$year}-02-01",
                'fin' => "{$year}-12-15",
                'vigente' => $year === 2026,
                'activo' => $year === 2026,
            ]);
        }

        $counts = [
            'gestiones' => GestionAcademica::count(),
            'grupos' => Grupo::count(),
            'actividades' => Actividad::count(),
        ];

        $this->get(route('inicio_inscripcion.index'))
            ->assertOk()
            ->assertSee('Inicio de Inscripción')
            ->assertSee('2024')
            ->assertSee('2025')
            ->assertSee('2026')
            ->assertSee(route('admin.gestionacademica.create'))
            ->assertSee(route('admin.gestionacademica.index'));

        $this->assertSame($counts['gestiones'], GestionAcademica::count());
        $this->assertSame($counts['grupos'], Grupo::count());
        $this->assertSame($counts['actividades'], Actividad::count());
    }

    public function test_gestion_lists_every_active_seeded_career_even_without_subjects_or_groups(): void
    {
        $this->actingAs($this->createAdmin());
        $this->seed(AcademicoSeeder::class);

        $gestion = GestionAcademica::where('vigente', true)->firstOrFail();
        $response = $this->get(route('inicio_inscripcion.gestion', $gestion->id_gestionacademica))
            ->assertOk()
            ->assertSee('Sin materias ni grupos en esta gestión.');

        foreach ([
            'ADMINISTRACION DE EMPRESAS',
            'CONSTRUCCIÓN CIVIL',
            'CONTADURIA GENERAL',
            'GASTRONOMIA',
            'INFORMATICA INDUSTRIAL',
            'MECANICA AUTOMOTRIZ',
            'SECRETARIADO EJECUTIVO',
            'SISTEMAS INFORMATICOS',
            'TOPOGRAFIA Y GEODESIA',
        ] as $nombreCarrera) {
            $response->assertSee($nombreCarrera);
        }
    }

    public function test_navigation_opens_career_parallel_and_subject_views_for_the_selected_year(): void
    {
        $this->actingAs($this->createAdmin());
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $gestionActual = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
            'activo' => true,
        ]);
        $gestionAnterior = GestionAcademica::create([
            'gestion' => 2025,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => false,
            'activo' => false,
        ]);
        $carrera = Carrera::create([
            'codigo' => 'INI-SIS',
            'nombre' => 'Sistemas Informáticos',
            'regimen' => Carrera::REGIMEN_ANUAL,
            'resolucionministerial1' => 'R.M. N° 210/2026',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $materia = Materia::create([
            'codigo' => 'INI-101',
            'nombre' => 'Programación Inicial',
            'nivel' => 1,
            'id_carrera' => $carrera->id_carrera,
        ]);
        Grupo::create([
            'codigo' => 'INI-101-A',
            'capacidad' => 35,
            'turno' => 1,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $gestionActual->id_gestionacademica,
            'id_materia' => $materia->id_materia,
        ]);
        Grupo::create([
            'codigo' => 'INI-101-A',
            'capacidad' => 30,
            'turno' => 3,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $gestionActual->id_gestionacademica,
            'id_materia' => $materia->id_materia,
        ]);
        $materiaSegundoAnio = Materia::create([
            'codigo' => 'INI-201',
            'nombre' => 'Programación Avanzada',
            'nivel' => 2,
            'id_carrera' => $carrera->id_carrera,
        ]);
        Grupo::create([
            'codigo' => 'INI-201-A',
            'capacidad' => 30,
            'turno' => 3,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $gestionActual->id_gestionacademica,
            'id_materia' => $materiaSegundoAnio->id_materia,
        ]);
        Grupo::create([
            'codigo' => 'INI-101-B',
            'capacidad' => 28,
            'turno' => 3,
            'abierto' => false,
            'activo' => true,
            'id_gestionacademica' => $gestionAnterior->id_gestionacademica,
            'id_materia' => $materia->id_materia,
        ]);
        Actividad::create([
            'nombre' => 'Inscripción de prueba',
            'descripcion' => 'Actividad de prueba',
            'inicio' => '2026-02-01 09:00:00',
            'fin' => '2026-02-15 17:00:00',
            'activo' => true,
            'id_gestionacademica' => $gestionActual->id_gestionacademica,
        ]);

        $this->get(route('inicio_inscripcion.gestion', $gestionActual->id_gestionacademica))
            ->assertOk()
            ->assertSee('Sistemas Informáticos')
            ->assertDontSee('Programación Inicial')
            ->assertSee('R.M. 210/2026')
            ->assertSee('Inscripción de prueba')
            ->assertSee(route('inicio_inscripcion.carrera', [
                $gestionActual->id_gestionacademica,
                $carrera->id_carrera,
            ]));

        $this->get(route('inicio_inscripcion.carrera', [
            $gestionActual->id_gestionacademica,
            $carrera->id_carrera,
        ]))
            ->assertOk()
            ->assertSee('Mañana')
            ->assertSee('Noche')
            ->assertSee('Paralelo A')
            ->assertDontSee('Programación Inicial')
            ->assertDontSee('INI-101-B')
            ->assertDontSee('28')
            ->assertSee(route('inicio_inscripcion.paralelo', [
                $gestionActual->id_gestionacademica,
                $carrera->id_carrera,
                'A',
                3,
            ]));

        $this->get(route('inicio_inscripcion.paralelo', [
            $gestionActual->id_gestionacademica,
            $carrera->id_carrera,
            'A',
            3,
        ]))
            ->assertOk()
            ->assertSee('Programación Inicial')
            ->assertSee('Programación Avanzada')
            ->assertSee('Segundo año')
            ->assertSee(route('inicio_inscripcion.materia', [
                $gestionActual->id_gestionacademica,
                $carrera->id_carrera,
                'A',
                3,
                $materiaSegundoAnio->id_materia,
            ]));

        $this->get(route('inicio_inscripcion.materia', [
            $gestionActual->id_gestionacademica,
            $carrera->id_carrera,
            'A',
            3,
            $materiaSegundoAnio->id_materia,
        ]))
            ->assertOk()
            ->assertSee('Programación Avanzada')
            ->assertSee('INI-201-A')
            ->assertSee('Cupo')
            ->assertSee('30')
            ->assertSee('Disponibles');
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'ADMIN-INICIO-001',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_inicio_inscripcion',
            'password' => Hash::make('password123'),
        ]);
    }
}
