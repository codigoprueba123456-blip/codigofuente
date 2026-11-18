<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Carrera;
use App\Models\Cuenta;
use App\Models\Empleado;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Instituto;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\Recurso;
use App\Models\Rol;
use App\Models\User;
use App\Support\MenuResourceSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FunctionalityAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_careers_and_subjects_can_be_managed_without_enabled_functionality(): void
    {
        $user = $this->createUser();
        Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);

        $this->actingAs($user)
            ->get(route('planes.carreras.index'))
            ->assertOk();

        $this->get(route('planes.carreras.create'))
            ->assertOk();

        $this->post(route('planes.carreras.store'), [
            'codigo' => 'INF-001',
            'nombre' => 'Informática',
        ])->assertRedirect(route('planes.carreras.index'))
            ->assertSessionHas('success', 'Carrera creada correctamente.');

        $career = Carrera::where('codigo', 'INF-001')->firstOrFail();
        $this->get(route('planes.carreras.edit', $career->id_carrera))->assertOk();
        $this->put(route('planes.carreras.update', $career->id_carrera), [
            'codigo' => 'INF-001',
            'nombre' => 'Informática actualizada',
        ])->assertRedirect(route('planes.carreras.index'))
            ->assertSessionHas('success', 'Carrera actualizada correctamente.');

        $this->get(route('planes.materias.index'))->assertOk();
        $this->get(route('planes.materias.create'))
            ->assertOk()
            ->assertDontSee('name="creditajemateria"', false);
        $this->post(route('planes.materias.store'), [
            'nombre' => 'Programación',
            'id_carrera' => $career->id_carrera,
        ])->assertRedirect(route('planes.materias.index'))
            ->assertSessionHas('success', 'Materia creada correctamente.');

        $subject = Materia::where('nombre', 'Programación')->firstOrFail();
        $this->get(route('planes.materias.edit', $subject->id_materia))->assertOk();
        $this->put(route('planes.materias.update', $subject->id_materia), [
            'nombre' => 'Programación actualizada',
            'id_carrera' => $career->id_carrera,
        ])->assertRedirect(route('planes.materias.index'))
            ->assertSessionHas('success', 'Materia actualizada correctamente.');
        $this->patch(route('planes.materias.estado', $subject->id_materia), ['activo' => false])
            ->assertRedirect(route('planes.materias.index'));
        $this->patch(route('planes.carreras.estado', $career->id_carrera), ['activo' => false])
            ->assertRedirect(route('planes.carreras.index'));
        $this->assertDatabaseHas('carreras', ['id_carrera' => $career->id_carrera, 'activo' => false]);
        $this->assertDatabaseHas('materia', ['id_materia' => $subject->id_materia, 'activo' => false]);

        $this->patch(route('planes.materias.estado', $subject->id_materia), ['activo' => true]);
        $this->patch(route('planes.carreras.estado', $career->id_carrera), ['activo' => true]);
        $this->assertDatabaseHas('carreras', ['id_carrera' => $career->id_carrera, 'activo' => true]);
        $this->assertDatabaseHas('materia', ['id_materia' => $subject->id_materia, 'activo' => true]);
    }

    public function test_subject_index_lists_careers_and_filters_subjects_by_career(): void
    {
        $user = $this->createUser();
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $primeraCarrera = Carrera::create([
            'codigo' => 'INF-001',
            'nombre' => 'Informática',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $segundaCarrera = Carrera::create([
            'codigo' => 'ADM-001',
            'nombre' => 'Administración',
            'resolucionministerial1' => 'R.M. N° 123/2024',
            'id_instituto' => $instituto->id_instituto,
        ]);
        Materia::create([
            'nombre' => 'Programación',
            'id_carrera' => $primeraCarrera->id_carrera,
        ]);
        Materia::create([
            'nombre' => 'Contabilidad',
            'id_carrera' => $segundaCarrera->id_carrera,
        ]);

        $this->actingAs($user)
            ->get(route('planes.materias.index', ['id_carrera' => $primeraCarrera->id_carrera]))
            ->assertOk()
            ->assertSee('Informática')
            ->assertSee('Administración · R.M. 123/2024')
            ->assertSee('Programación')
            ->assertDontSee('Contabilidad')
            ->assertSee('value="' . $primeraCarrera->id_carrera . '" selected', false)
            ->assertSee('onchange="this.form.submit()"', false)
            ->assertDontSee('>Filtrar</button>', false);
    }

    public function test_subject_levels_follow_the_selected_career_regime(): void
    {
        $user = $this->createUser();
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'SEM-001',
            'nombre' => 'Carrera Semestral',
            'regimen' => Carrera::REGIMEN_SEMESTRAL,
            'id_instituto' => $instituto->id_instituto,
        ]);

        $this->actingAs($user)
            ->get(route('planes.materias.create'))
            ->assertOk()
            ->assertSee('Sexto semestre');

        $this->post(route('planes.materias.store'), [
            'nombre' => 'Materia de sexto semestre',
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 6,
        ])->assertRedirect(route('planes.materias.index'));

        $this->assertDatabaseHas('materia', [
            'nombre' => 'Materia de sexto semestre',
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 6,
        ]);

        $this->post(route('planes.materias.store'), [
            'nombre' => 'Materia fuera de rango',
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 7,
        ])->assertSessionHasErrors('nivel');
    }

    public function test_group_programming_accepts_semester_levels_for_semestral_careers(): void
    {
        $user = $this->createUser();
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'vigente' => true,
        ]);
        $carrera = Carrera::create([
            'codigo' => 'SEM-GRP-001',
            'nombre' => 'Carrera Semestral',
            'regimen' => Carrera::REGIMEN_SEMESTRAL,
            'id_instituto' => $instituto->id_instituto,
        ]);
        Materia::create([
            'codigo' => 'SEM-601',
            'nombre' => 'Materia de sexto semestre',
            'nivel' => 6,
            'id_carrera' => $carrera->id_carrera,
        ]);

        $this->actingAs($user)
            ->get(route('admin.grupos.programar'))
            ->assertOk()
            ->assertSee('Sexto semestre');

        $this->post(route('admin.grupos.store'), [
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 6,
        ])->assertRedirect(route('admin.grupos.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carrera->id_carrera,
        ]));

        $this->assertDatabaseHas('grupo', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => Materia::where('codigo', 'SEM-601')->value('id_materia'),
        ]);

        $this->post(route('admin.grupos.store'), [
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 7,
        ])->assertSessionHasErrors('nivel');
    }

    public function test_administration_and_study_plan_searches_filter_results(): void
    {
        $user = $this->createUser('search-target');
        $otherUser = $this->createUser('search-other');
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $career = Carrera::create([
            'codigo' => 'SEARCH-CAREER',
            'nombre' => 'Carrera de Búsqueda',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $otherCareer = Carrera::create([
            'codigo' => 'OTHER-CAREER',
            'nombre' => 'Otra Carrera',
            'id_instituto' => $instituto->id_instituto,
        ]);
        Materia::create([
            'codigo' => 'SEARCH-SUBJECT',
            'nombre' => 'Materia de Búsqueda',
            'id_carrera' => $career->id_carrera,
        ]);
        Materia::create([
            'codigo' => 'OTHER-SUBJECT',
            'nombre' => 'Otra Materia',
            'id_carrera' => $otherCareer->id_carrera,
        ]);

        $employeePersona = Persona::create([
            'nombre' => 'Empleado Buscable',
            'primerapellido' => 'Prueba',
            'ci' => 'SEARCH-CI',
            'tipo' => 'empleado',
        ]);
        Empleado::create(['id_persona' => $employeePersona->id_persona, 'codigo' => 'SEARCH-EMP']);
        $otherEmployeePersona = Persona::create([
            'nombre' => 'Otro Empleado',
            'primerapellido' => 'Prueba',
            'ci' => 'OTHER-CI',
            'tipo' => 'empleado',
        ]);
        Empleado::create(['id_persona' => $otherEmployeePersona->id_persona, 'codigo' => 'OTHER-EMP']);

        Rol::create(['nombre' => 'Rol de Búsqueda']);
        Rol::create(['nombre' => 'Otro Rol']);

        $this->actingAs($user)
            ->get(route('admin.usuarios.index', ['search' => $user->usuario]))
            ->assertOk()
            ->assertSee($user->usuario)
            ->assertDontSee($otherUser->usuario);

        $this->get(route('admin.empleados.index', ['q' => 'SEARCH-CI']))
            ->assertOk()
            ->assertSee('Empleado Buscable')
            ->assertDontSee('Otro Empleado');

        $this->get(route('admin.roles.index', ['q' => 'Rol de Búsqueda']))
            ->assertOk()
            ->assertSee('Rol de Búsqueda')
            ->assertDontSee('Otro Rol');

        $this->get(route('planes.carreras.index', ['q' => 'SEARCH-CAREER']))
            ->assertOk()
            ->assertSee('Carrera de Búsqueda')
            ->assertDontSee('Otra Carrera');

        $this->get(route('planes.materias.index', [
            'id_carrera' => $career->id_carrera,
            'q' => 'SEARCH-SUBJECT',
        ]))
            ->assertOk()
            ->assertSee('Materia de Búsqueda')
            ->assertDontSee('Otra Materia')
            ->assertSee('name="id_carrera"', false)
            ->assertSee('data-auto-search-input', false);
    }

    public function test_index_gestion_filters_include_all_while_entry_forms_offer_only_current_gestiones(): void
    {
        $user = $this->createUser();
        GestionAcademica::create([
            'gestion' => 2025,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => false,
        ]);
        GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.grupos.index'))
            ->assertOk()
            ->assertSee('I2025A')
            ->assertSee('I2026A');

        $this->get(route('admin.actividad.index'))
            ->assertOk()
            ->assertSee('I2025A')
            ->assertSee('I2026A');

        $this->get(route('admin.actividad.create'))
            ->assertOk()
            ->assertSee('I2026A')
            ->assertDontSee('I2025A');

        $this->get(route('estudiante_nuevo.index'))
            ->assertOk()
            ->assertSee('I2026A')
            ->assertDontSee('I2025A');
    }

    public function test_group_search_matches_group_and_subject_fields(): void
    {
        $user = $this->createUser();
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'INF-001',
            'nombre' => 'Informática',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $materia = Materia::create([
            'codigo' => 'INF-101',
            'nombre' => 'Programación Web',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $otraMateria = Materia::create([
            'codigo' => 'INF-102',
            'nombre' => 'Base de Datos',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $grupo = Grupo::create([
            'codigo' => 'GRUPO-PW-A',
            'capacidad' => 60,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materia->id_materia,
            'id_persona' => $user->id_persona,
        ]);
        Grupo::create([
            'codigo' => 'GRUPO-BD-A',
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $otraMateria->id_materia,
        ]);
        $inscritoId = DB::table('inscrito')->insertGetId([
            'fecha' => now()->toDateString(),
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $user->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inscrito_materia')->insert([
            'id_inscrito' => $inscritoId,
            'id_materia' => $materia->id_materia,
            'id_grupo' => $grupo->id_grupo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.grupos.index'))
            ->assertOk()
            ->assertSee('class="thead-light"', false)
            ->assertDontSee('alert-info')
            ->assertDontSee('GRUPO-PW-A')
            ->assertDontSee('Programación Web');

        $this->get(route('admin.grupos.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]))
            ->assertOk()
            ->assertDontSee('GRUPO-PW-A')
            ->assertDontSee('Programación Web');

        $this->get(route('admin.grupos.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carrera->id_carrera,
            'buscar' => 'Programación Web',
        ]))
            ->assertOk()
            ->assertSee('>a</span>', false)
            ->assertDontSee('GRUPO-PW-A')
            ->assertDontSee('GRUPO-BD-A')
            ->assertSee('Inscritos')
            ->assertSee('Nivel')
            ->assertSeeInOrder(['Programación Web', '60', '1', 'Sí']);

        $this->get(route('admin.grupos.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carrera->id_carrera,
            'buscar' => 'INF-102',
        ]))
            ->assertOk()
            ->assertSee('>a</span>', false)
            ->assertDontSee('Programación Web');

        $this->get(route('admin.grupos.index'))
            ->assertOk()
            ->assertSee('setTimeout')
            ->assertSee('400')
            ->assertSee('requestSubmit');

        $this->get(route('admin.gestionacademica.index'))
            ->assertOk()
            ->assertSee('data-auto-search-input', false);

        $this->get(route('admin.actividad.index'))
            ->assertOk()
            ->assertSee('data-auto-search-input', false);

        $this->get(route('admin.asignacion-docente.index', ['buscar' => 'Admin']))
            ->assertOk()
            ->assertSee('GRUPO-PW-A')
            ->assertDontSee('GRUPO-BD-A')
            ->assertSee('data-auto-search-input', false);
    }

    public function test_subject_creation_limits_prerequisites_to_the_selected_career(): void
    {
        $user = $this->createUser();
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'INF-001',
            'nombre' => 'Informática',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $otraCarrera = Carrera::create([
            'codigo' => 'ADM-001',
            'nombre' => 'Administración',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $materiaDeCarrera = Materia::create([
            'codigo' => 'INF-101',
            'nombre' => 'Programación I',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $materiaDeOtraCarrera = Materia::create([
            'codigo' => 'ADM-101',
            'nombre' => 'Administración I',
            'id_carrera' => $otraCarrera->id_carrera,
        ]);

        $this->actingAs($user)
            ->get(route('planes.materias.create'))
            ->assertOk()
            ->assertSee('data-carrera-id="' . $carrera->id_carrera . '"', false)
            ->assertSee('data-carrera-id="' . $otraCarrera->id_carrera . '"', false)
            ->assertSee('data-prerequisitos-select', false)
            ->assertSee('multiple', false)
            ->assertSee('filtrarPrerequisitos(conservarSeleccion)');

        $this->post(route('planes.materias.store'), [
            'nombre' => 'Programación II',
            'id_carrera' => $carrera->id_carrera,
            'prerequisitos' => [$materiaDeOtraCarrera->id_materia],
        ])->assertSessionHasErrors('prerequisitos.0');

        $this->assertDatabaseMissing('materia', [
            'nombre' => 'Programación II',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $this->assertDatabaseHas('materia', [
            'id_materia' => $materiaDeCarrera->id_materia,
        ]);
    }

    public function test_success_messages_are_available_globally_in_the_navigation(): void
    {
        $this->actingAs($this->createUser())
            ->withSession(['success' => 'Carrera creada correctamente.'])
            ->get(route('planes.carreras.index'))
            ->assertOk()
            ->assertSee('data-kind="success"', false)
            ->assertSee('data-message="Carrera creada correctamente."', false);
    }

    public function test_homepage_career_selector_shows_active_career_resolutions(): void
    {
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'INF-001',
            'nombre' => 'Informática',
            'resolucionministerial1' => 'R.M. N° 123/2024',
            'id_instituto' => $instituto->id_instituto,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('value="' . $carrera->id_carrera . '"', false)
            ->assertSee('Informática · R.M. 123/2024');
    }

    public function test_curriculum_report_page_and_exports_load_the_existing_views(): void
    {
        $user = $this->createUser();
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'INF-001',
            'nombre' => 'Informática',
            'resolucionministerial1' => 'R.M. N° 123/2024',
            'id_instituto' => $instituto->id_instituto,
        ]);
        Materia::create([
            'codigo' => 'INF-101',
            'nombre' => 'Programación',
            'nivel' => 1,
            'id_carrera' => $carrera->id_carrera,
        ]);

        $this->actingAs($user)
            ->get(route('reportes.academicos.malla-curricular'))
            ->assertOk()
            ->assertSee('Reporte Malla Curricular')
            ->assertSee('Informática · R.M. 123/2024');

        $this->get(route('reportes.academicos.malla-curricular', [
            'generar' => 1,
            'id_carrera' => $carrera->id_carrera,
        ]))
            ->assertOk()
            ->assertSee('Programación');

        $this->get(route('reportes.academicos.malla-curricular', [
            'formato' => 'pdf',
            'id_carrera' => $carrera->id_carrera,
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('reportes.academicos.malla-curricular', [
            'formato' => 'excel',
            'id_carrera' => $carrera->id_carrera,
        ]))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
    }

    public function test_module_pages_require_authentication_even_when_read_only(): void
    {
        $this->get(route('planes.carreras.index'))
            ->assertRedirect(route('login'));
    }

    public function test_administration_records_can_be_edited_and_disabled_without_enabled_functionality(): void
    {
        $user = $this->createUser();
        MenuResourceSync::sync();
        $role = Rol::create(['nombre' => 'Rol de prueba']);

        $this->actingAs($user)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Deshabilitar');

        $this->get(route('admin.roles.edit', $role->id_rol))
            ->assertOk();

        $this->put(route('admin.roles.update', $role->id_rol), [
            'nombre' => 'Rol actualizado',
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('rol', [
            'id_rol' => $role->id_rol,
            'nombre' => 'Rol actualizado',
        ]);

        $this->patch(route('admin.roles.estado', $role->id_rol), ['activo' => false])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('rol', ['id_rol' => $role->id_rol, 'activo' => false]);
        $this->patch(route('admin.roles.estado', $role->id_rol), ['activo' => true]);
        $this->assertDatabaseHas('rol', ['id_rol' => $role->id_rol, 'activo' => true]);
    }

    public function test_role_creation_form_selects_resources_with_checkboxes(): void
    {
        $user = $this->createUser();
        MenuResourceSync::sync();
        $resource = Recurso::where('urlpattern', 'planes/materias')->firstOrFail();
        $standaloneResource = Recurso::where('urlpattern', 'planes/gestiones/inicio-inscripcion')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.roles.create'))
            ->assertOk()
            ->assertSee('type="checkbox"', false)
            ->assertSee('name="recursos[]"', false)
            ->assertSee('value="'.$resource->id_recurso.'"', false)
            ->assertSee('value="'.$standaloneResource->id_recurso.'"', false)
            ->assertSee('Planes de Estudio')
            ->assertDontSee('<select', false);

        $this->post(route('admin.roles.store'), [
            'nombre' => 'Rol creado con checkbox',
            'recursos' => [$resource->id_recurso],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Rol::where('nombre', 'Rol creado con checkbox')->firstOrFail();
        $this->assertSame(
            [$resource->id_recurso],
            $role->recursos()->pluck('recurso.id_recurso')->all()
        );
    }

    public function test_roles_list_does_not_show_resources_for_roles_without_assignments(): void
    {
        $admin = $this->createUser();
        $role = Rol::create(['nombre' => 'Rol sin recursos']);

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Rol sin recursos')
            ->assertSee('Sin recursos asignados')
            ->assertDontSee('Administrador &gt; Empleados')
            ->assertDontSee('Administrador &gt; Usuarios');
    }

    public function test_groups_activities_and_gestiones_can_be_disabled_without_deleting_them(): void
    {
        $user = $this->createUser();
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'EST-001',
            'nombre' => 'Estudios',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $materia = Materia::create([
            'codigo' => 'EST-101',
            'nombre' => 'Introducción',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $grupo = Grupo::create([
            'codigo' => 'EST-101-a',
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materia->id_materia,
        ]);
        $actividad = Actividad::create([
            'nombre' => 'Inscripción',
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);

        $this->actingAs($user)
            ->get(route('admin.grupos.index', [
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'id_carrera' => $carrera->id_carrera,
            ]))
            ->assertOk()
            ->assertSee('Deshabilitar');
        $this->get(route('admin.actividad.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]))
            ->assertOk()
            ->assertSee('Deshabilitar');
        $this->get(route('admin.gestionacademica.index'))
            ->assertOk()
            ->assertSee('Deshabilitar');
        $this->patch(route('admin.grupos.estado', $grupo->id_grupo), ['activo' => false])
            ->assertRedirect();
        $this->patch(route('admin.actividad.estado', $actividad->id_actividad), ['activo' => false])
            ->assertRedirect();
        $this->patch(route('admin.gestionacademica.estado', $gestion->id_gestionacademica), ['activo' => false])
            ->assertRedirect();

        $this->assertDatabaseHas('grupo', ['id_grupo' => $grupo->id_grupo, 'activo' => false]);
        $this->assertDatabaseHas('actividad', ['id_actividad' => $actividad->id_actividad, 'activo' => false]);
        $this->assertDatabaseHas('gestionacademica', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'activo' => false,
            'vigente' => false,
        ]);
    }

    public function test_inactive_roles_do_not_grant_role_middleware_access_from_legacy_person_type(): void
    {
        $user = $this->createUser();
        $role = Rol::create(['nombre' => 'Administrador', 'activo' => false]);
        Cuenta::create([
            'id_persona' => $user->id_persona,
            'id_rol' => $role->id_rol,
            'usuario' => $user->usuario,
            'password' => $user->password,
        ]);
        Route::middleware(['web', 'auth', 'resource.access:admin'])
            ->get('/test/disabled-role-access', fn () => response('access granted'));

        $this->actingAs($user)
            ->get('/test/disabled-role-access')
            ->assertForbidden();
    }

    public function test_administration_sections_are_not_offered_as_functionality_toggles(): void
    {
        $user = $this->createUser();
        MenuResourceSync::sync();
        $careerResource = Recurso::where('urlpattern', 'planes/carreras')->firstOrFail();
        $employeeResource = Recurso::where('urlpattern', 'admin/empleados')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.actividad.create'))
            ->assertOk()
            ->assertSee('Sección ' . $careerResource->id_recurso . ' - Carreras')
            ->assertDontSee('Sección ' . $employeeResource->id_recurso . ' - Empleados');
    }

    public function test_calendar_specific_user_list_can_be_searched_by_name_or_username(): void
    {
        $admin = $this->createUser();
        $usuario = $this->createUser('usuario-buscable');

        $this->actingAs($admin)
            ->get(route('admin.actividad.create'))
            ->assertOk()
            ->assertSee('data-user-search-input', false)
            ->assertSee('placeholder="Buscar por nombre o username..."', false)
            ->assertSee('data-user-search-options', false)
            ->assertSee('name="usuarios[]"', false)
            ->assertSee('type="checkbox"', false)
            ->assertSee(e($usuario->name . ' (' . $usuario->usuario . ')'), false);
    }

    public function test_reports_dashboard_and_student_sections_are_not_calendar_functionalities(): void
    {
        $user = $this->createUser();
        $dashboard = Recurso::create([
            'nombre' => 'Dashboard',
            'urlpattern' => 'reportes/dashboard',
        ]);
        $report = Recurso::create([
            'nombre' => 'Reporte de prueba',
            'urlpattern' => 'reportes/inscripciones/prueba',
        ]);
        $studentSection = Recurso::create([
            'nombre' => 'Estado de inscripción',
            'urlpattern' => 'estudiantes/estado_inscripcion',
        ]);
        $allowedSection = Recurso::create([
            'nombre' => 'Sección permitida',
            'urlpattern' => 'planes/seccion-permitida',
        ]);
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.actividad.create'))
            ->assertOk()
            ->assertSee('Sección ' . $allowedSection->id_recurso . ' - Sección permitida')
            ->assertDontSee('Sección ' . $dashboard->id_recurso . ' - Dashboard')
            ->assertDontSee('Sección ' . $report->id_recurso . ' - Reporte de prueba')
            ->assertDontSee('Sección ' . $studentSection->id_recurso . ' - Estado de inscripción');

        foreach ([$dashboard, $report, $studentSection] as $recurso) {
            $this->post(route('admin.actividad.store'), [
                'nombre' => 'Actividad de prueba',
                'id_gestionacademica' => $gestion->id_gestionacademica,
                'audiencia' => 'general',
                'funcionalidades' => [$recurso->id_recurso],
            ])->assertSessionHasErrors('funcionalidades.0');
        }
    }

    public function test_new_menu_modules_receive_functionality_numbers_automatically(): void
    {
        $menu = config('functionality.menu', []);
        $menu[] = ['text' => 'Inscripciones', 'url' => 'planes/inscripciones'];
        config(['functionality.menu' => $menu]);

        MenuResourceSync::sync();

        $resource = Recurso::where('urlpattern', 'planes/inscripciones')->firstOrFail();
        $this->assertGreaterThan(0, $resource->id_recurso);
    }

    public function test_future_menu_module_is_write_protected_without_module_specific_middleware(): void
    {
        $this->createUser();
        $user = $this->createUser('teacher');
        $menu = config('functionality.menu', []);
        $menu[] = ['text' => 'Inscripciones', 'url' => 'planes/inscripciones'];
        config(['functionality.menu' => $menu]);

        Route::middleware('web')
            ->post('/planes/inscripciones', fn () => response('write reached'));

        $this->actingAs($user)
            ->post('/planes/inscripciones')
            ->assertForbidden()
            ->assertSee('solo lectura');
    }

    public function test_audience_and_dates_are_checked_before_enabling_module_writes(): void
    {
        $this->createUser();
        $user = $this->createUser('owner');
        $otherUser = $this->createUser('other');
        $menu = config('functionality.menu', []);
        $menu[] = ['text' => 'Inscripciones', 'url' => 'planes/inscripciones'];
        config(['functionality.menu' => $menu]);
        Route::middleware('web')
            ->post('/planes/inscripciones', fn () => response('write reached'))
            ->name('planes.inscripciones.store');

        $this->actingAs($user);
        MenuResourceSync::sync();
        $resource = Recurso::where('urlpattern', 'planes/inscripciones')->firstOrFail();
        $this->createActivity($resource->id_recurso, $otherUser);

        $this->post('/planes/inscripciones')
            ->assertForbidden();

        Actividad::query()->delete();
        $this->createActivity($resource->id_recurso, $user, now()->subDays(2), now()->subDay());
        $this->post('/planes/inscripciones')
            ->assertForbidden();
    }

    public function test_future_calendar_activity_does_not_enable_a_section_before_its_start_date(): void
    {
        $admin = $this->createUser();
        $user = $this->createUser('future');
        $menu = config('functionality.menu', []);
        $menu[] = ['text' => 'Módulo futuro', 'url' => 'planes/modulo-futuro'];
        config(['functionality.menu' => $menu]);
        Route::middleware('web')
            ->post('/planes/modulo-futuro', fn () => response('módulo habilitado'));
        MenuResourceSync::sync();

        $resource = Recurso::where('urlpattern', 'planes/modulo-futuro')->firstOrFail();
        $this->createActivity($resource->id_recurso, $user, now()->addHour(), now()->addHours(2));

        $this->actingAs($user)
            ->post('/planes/modulo-futuro')
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.actividad.index'))
            ->assertOk()
            ->assertSee('Programada')
            ->assertDontSee('Habilitada');
    }

    public function test_one_calendar_activity_can_enable_multiple_sections_until_its_end_date(): void
    {
        $admin = $this->createUser();
        $user = $this->createUser('owner');
        $menu = config('functionality.menu', []);
        $menu[] = ['text' => 'Módulo uno', 'url' => 'planes/modulo-uno'];
        $menu[] = ['text' => 'Módulo dos', 'url' => 'planes/modulo-dos'];
        config(['functionality.menu' => $menu]);
        Route::middleware('web')->post('/planes/modulo-uno', fn () => response('módulo uno habilitado'));
        Route::middleware('web')->post('/planes/modulo-dos', fn () => response('módulo dos habilitado'));
        MenuResourceSync::sync();

        $recursoUno = Recurso::where('urlpattern', 'planes/modulo-uno')->firstOrFail();
        $recursoDos = Recurso::where('urlpattern', 'planes/modulo-dos')->firstOrFail();
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $actividad = Actividad::create([
            'nombre' => 'Habilitación de dos módulos',
            'inicio' => now()->subMinute(),
            'fin' => now()->addHour(),
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);
        $actividad->funcionalidades()->sync([$recursoUno->id_recurso, $recursoDos->id_recurso]);

        $this->actingAs($admin)
            ->get(route('admin.actividad.edit', $actividad->id_actividad))
            ->assertOk()
            ->assertSee('name="funcionalidades[]"', false)
            ->assertSee('type="checkbox"', false)
            ->assertSee('id="funcionalidad_' . $recursoUno->id_recurso . '"', false)
            ->assertSee('id="funcionalidad_' . $recursoDos->id_recurso . '"', false)
            ->assertSee('checked', false);

        $this->put(route('admin.actividad.update', $actividad->id_actividad), [
            'nombre' => 'Habilitación de dos módulos',
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'audiencia' => 'general',
            'inicio' => now()->subMinute()->format('Y-m-d\TH:i'),
            'fin' => now()->addHour()->format('Y-m-d\TH:i'),
            'funcionalidades' => [$recursoUno->id_recurso, $recursoDos->id_recurso],
        ])->assertRedirect(route('admin.actividad.index', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]));
        $this->assertSame(
            [$recursoUno->id_recurso, $recursoDos->id_recurso],
            $actividad->fresh()->funcionalidades()->orderBy('recurso.id_recurso')->pluck('recurso.id_recurso')->all()
        );
        $this->assertNull($actividad->fresh()->funcionalidad);

        $this->actingAs($user)
            ->post('/planes/modulo-uno')
            ->assertOk()
            ->assertSee('módulo uno habilitado');

        $this->post('/planes/modulo-dos')
            ->assertOk()
            ->assertSee('módulo dos habilitado');

        $actividad->update(['fin' => now()->subMinute()]);
        $this->post('/planes/modulo-uno')->assertForbidden();
        $this->post('/planes/modulo-dos')->assertForbidden();
    }

    public function test_calendar_rejects_an_end_datetime_before_the_start_datetime(): void
    {
        $admin = $this->createUser();
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $mensaje = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
        $datos = [
            'nombre' => 'Periodo de inscripción',
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'audiencia' => 'general',
            'inicio' => '2026-11-05T12:00',
            'fin' => '2026-11-05T11:59',
        ];

        $this->actingAs($admin)
            ->get(route('admin.actividad.create'))
            ->assertOk()
            ->assertSee('fin-date-error', false)
            ->assertSee('setCustomValidity', false);

        $this->from(route('admin.actividad.create'))
            ->post(route('admin.actividad.store'), $datos)
            ->assertSessionHasErrors(['fin' => $mensaje])
            ->assertRedirect(route('admin.actividad.create'));
        $this->assertDatabaseMissing('actividad', ['nombre' => 'Periodo de inscripción']);

        $actividad = Actividad::create([
            'nombre' => 'Actividad existente',
            'inicio' => '2026-11-01 09:00:00',
            'fin' => '2026-11-06 09:00:00',
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);

        $this->get(route('admin.actividad.edit', $actividad->id_actividad))
            ->assertOk()
            ->assertSee('fin-date-error', false);

        $this->from(route('admin.actividad.edit', $actividad->id_actividad))
            ->put(route('admin.actividad.update', $actividad->id_actividad), $datos)
            ->assertSessionHasErrors(['fin' => $mensaje])
            ->assertRedirect(route('admin.actividad.edit', $actividad->id_actividad));

        $this->assertSame('2026-11-06 09:00:00', $actividad->fresh()->fin->format('Y-m-d H:i:s'));
    }

    public function test_administrators_bypass_calendar_and_role_restrictions(): void
    {
        $admin = $this->createUser();
        $menu = config('functionality.menu', []);
        $menu[] = ['text' => 'Inscripciones', 'url' => 'planes/inscripciones'];
        config(['functionality.menu' => $menu]);

        Route::middleware('web')
            ->post('/planes/inscripciones', fn () => response('write reached'));
        Route::middleware(['web', 'role:estudiante'])
            ->get('/estudiantes/restriccion-admin', fn () => response('student route reached'));

        MenuResourceSync::sync();
        $resource = Recurso::where('urlpattern', 'planes/inscripciones')->firstOrFail();
        $this->createActivity($resource->id_recurso, $this->createUser('other'), now()->addDay(), now()->addDays(2));

        $this->actingAs($admin)
            ->post('/planes/inscripciones')
            ->assertOk()
            ->assertSee('write reached');

        $this->get('/estudiantes/restriccion-admin')
            ->assertOk()
            ->assertSee('student route reached');

        $this->assertTrue($admin->canAccess('estudiantes/restriccion-admin'));
    }

    public function test_management_routes_require_the_matching_resource_permission(): void
    {
        $admin = $this->createUser();
        $teacher = $this->createUser('teacher');
        MenuResourceSync::sync();
        $teacherRole = Rol::create(['nombre' => 'Docente']);
        Cuenta::create([
            'id_persona' => $teacher->id_persona,
            'id_rol' => $teacherRole->id_rol,
            'usuario' => $teacher->usuario,
            'password' => $teacher->password,
        ]);
        $rolesResource = Recurso::where('urlpattern', 'admin/roles')->firstOrFail();
        $careersResource = Recurso::where('urlpattern', 'planes/carreras')->firstOrFail();

        $this->actingAs($teacher)
            ->get(route('admin.roles.index'))
            ->assertForbidden();

        $this->get(route('planes.carreras.index'))
            ->assertForbidden();

        $teacherRole->recursos()->sync([$rolesResource->id_recurso]);
        $this->get(route('admin.roles.index'))->assertOk();
        $this->get(route('planes.carreras.index'))->assertForbidden();

        $teacherRole->recursos()->sync([$rolesResource->id_recurso, $careersResource->id_recurso]);
        $this->get(route('planes.carreras.index'))->assertOk();
        $this->get(route('planes.carreras.create'))->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk();
    }

    public function test_teacher_assignment_section_is_named_consistently(): void
    {
        $admin = $this->createUser();

        $this->actingAs($admin)
            ->get(route('admin.asignacion-docente.index'))
            ->assertOk()
            ->assertSee('Asignación Docente')
            ->assertDontSee('<title>Horarios</title>', false);

        $this->assertSame(
            'planes/asignacion-docente',
            Route::getRoutes()->getByName('admin.asignacion-docente.index')->uri()
        );

        $menu = config('adminlte.menu');
        $this->assertSame(
            'Asignación docente',
            collect($menu)
                ->flatMap(fn (array $item) => $item['submenu'] ?? [$item])
                ->firstWhere('url', 'planes/asignacion-docente')['text']
        );
    }

    private function createUser(string $suffix = 'admin'): User
    {
        $persona = Persona::create([
            'nombre' => ucfirst($suffix),
            'primerapellido' => 'Prueba',
            'ci' => $suffix === 'admin' ? '11111111' : (string) random_int(20000000, 99999999),
            'tipo' => $suffix === 'admin' ? 'admin' : 'empleado',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => $suffix . '_' . $persona->id_persona,
            'password' => Hash::make('password123'),
        ]);
    }

    private function createActivity(
        int $functionality,
        User $audienceUser,
        $startsAt = null,
        $endsAt = null
    ): Actividad {
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);

        $activity = Actividad::create([
            'nombre' => 'Habilitación de módulo',
            'inicio' => $startsAt ?? now()->subHour(),
            'fin' => $endsAt ?? now()->addHour(),
            'funcionalidad' => $functionality,
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);
        $activity->usuarios()->sync([$audienceUser->id_persona]);

        return $activity;
    }
}
