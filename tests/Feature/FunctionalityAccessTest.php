<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Carrera;
use App\Models\Cuenta;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\Recurso;
use App\Models\Rol;
use App\Models\User;
use App\Support\MenuResourceSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ])->assertRedirect(route('planes.carreras.index'));

        $career = Carrera::where('codigo', 'INF-001')->firstOrFail();
        $this->get(route('planes.carreras.edit', $career->id_carrera))->assertOk();
        $this->put(route('planes.carreras.update', $career->id_carrera), [
            'codigo' => 'INF-001',
            'nombre' => 'Informática actualizada',
        ])->assertRedirect(route('planes.carreras.index'));

        $this->get(route('planes.materias.index'))->assertOk();
        $this->get(route('planes.materias.create'))
            ->assertOk()
            ->assertDontSee('name="creditajemateria"', false);
        $this->post(route('planes.materias.store'), [
            'nombre' => 'Programación',
            'id_carrera' => $career->id_carrera,
        ])->assertRedirect(route('planes.materias.index'));

        $subject = Materia::where('nombre', 'Programación')->firstOrFail();
        $this->get(route('planes.materias.edit', $subject->id_materia))->assertOk();
        $this->put(route('planes.materias.update', $subject->id_materia), [
            'nombre' => 'Programación actualizada',
            'id_carrera' => $career->id_carrera,
        ])->assertRedirect(route('planes.materias.index'));
        $this->delete(route('planes.materias.destroy', $subject->id_materia))
            ->assertRedirect(route('planes.materias.index'));

        $this->delete(route('planes.carreras.destroy', $career->id_carrera))
            ->assertRedirect(route('planes.carreras.index'));
        $this->assertDatabaseMissing('carreras', ['id_carrera' => $career->id_carrera]);
        $this->assertDatabaseMissing('materia', ['id_materia' => $subject->id_materia]);
    }

    public function test_module_pages_require_authentication_even_when_read_only(): void
    {
        $this->get(route('planes.carreras.index'))
            ->assertRedirect(route('login'));
    }

    public function test_administration_can_be_edited_and_deleted_without_enabled_functionality(): void
    {
        $user = $this->createUser();
        MenuResourceSync::sync();
        $role = Rol::create(['nombre' => 'Rol de prueba']);

        $this->actingAs($user)
            ->get(route('admin.roles.edit', $role->id_rol))
            ->assertOk();

        $this->put(route('admin.roles.update', $role->id_rol), [
            'nombre' => 'Rol actualizado',
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('rol', [
            'id_rol' => $role->id_rol,
            'nombre' => 'Rol actualizado',
        ]);

        $this->delete(route('admin.roles.destroy', $role->id_rol))
            ->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseMissing('rol', ['id_rol' => $role->id_rol]);
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
