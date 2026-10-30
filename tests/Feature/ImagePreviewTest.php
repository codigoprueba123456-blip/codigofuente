<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\EmpleadoDocumento;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImagePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_upload_forms_render_previews_for_new_and_existing_images(): void
    {
        $adminPersona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Sistema',
            'dni' => '11111111',
            'tipo' => 'admin',
        ]);
        $admin = User::create([
            'id_persona' => $adminPersona->id_persona,
            'usuario' => 'admin_test',
            'password' => Hash::make('password123'),
        ]);

        Instituto::create([
            'nombreinstituto' => 'Instituto de Prueba',
            'logo' => 'instituto/logo-prueba.png',
        ]);

        $empleadoPersona = Persona::create([
            'nombre' => 'Docente',
            'primerapellido' => 'Prueba',
            'dni' => '22222222',
            'tipo' => 'empleado',
            'foto' => 'personas/foto-prueba.png',
        ]);
        $empleado = Empleado::create([
            'id_persona' => $empleadoPersona->id_persona,
            'codigo' => 'EMP-PRUEBA',
        ]);
        EmpleadoDocumento::create([
            'id_persona' => $empleado->id_persona,
            'slot' => 1,
            'ruta' => 'empleados/documentos/documento-prueba.png',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.instituto.index'))
            ->assertOk()
            ->assertSee('data-image-preview="logo-preview"', false)
            ->assertSee("document.addEventListener('change'", false)
            ->assertSee('/storage/instituto/logo-prueba.png', false)
            ->assertSee('logo-prueba.png')
            ->assertSee('El instituto no tiene un logo guardado.')
            ->assertDontSee('$configuracion')
            ->assertDontSee('onchange="mostrarImagen');

        $this->get(route('admin.empleados.create'))
            ->assertOk()
            ->assertSee('data-image-preview="foto-preview"', false)
            ->assertSee('data-image-preview="documento-preview-1"', false)
            ->assertSee('data-image-preview="documento-preview-4"', false);

        $this->get(route('admin.empleados.edit', $empleado->id_persona))
            ->assertOk()
            ->assertSee('/storage/personas/foto-prueba.png', false)
            ->assertSee('foto-prueba.png')
            ->assertSee('/storage/empleados/documentos/documento-prueba.png', false);
    }

    public function test_employee_delete_confirmation_shows_images_and_removes_them_with_the_record(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('personas/foto-eliminar.png', 'photo');
        Storage::disk('public')->put('empleados/documentos/ci-eliminar.png', 'document');

        $adminPersona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Sistema',
            'dni' => '33333333',
            'tipo' => 'admin',
        ]);
        $admin = User::create([
            'id_persona' => $adminPersona->id_persona,
            'usuario' => 'admin_delete_test',
            'password' => Hash::make('password123'),
        ]);
        $employeePersona = Persona::create([
            'nombre' => 'Eva',
            'primerapellido' => 'Prueba',
            'dni' => '44444444',
            'tipo' => 'empleado',
            'foto' => 'personas/foto-eliminar.png',
        ]);
        $employee = Empleado::create(['id_persona' => $employeePersona->id_persona]);
        EmpleadoDocumento::create([
            'id_persona' => $employee->id_persona,
            'slot' => 1,
            'ruta' => 'empleados/documentos/ci-eliminar.png',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.empleados.index'))
            ->assertOk()
            ->assertSee('data-target="#deleteEmployeeModal"', false)
            ->assertSee('foto-eliminar.png')
            ->assertSee('ci-eliminar.png');

        $this->delete(route('admin.empleados.destroy', $employee->id_persona))
            ->assertRedirect(route('admin.empleados.index'));

        $this->assertDatabaseMissing('empleados', ['id_persona' => $employee->id_persona]);
        Storage::disk('public')->assertMissing('personas/foto-eliminar.png');
        Storage::disk('public')->assertMissing('empleados/documentos/ci-eliminar.png');
    }

    public function test_institute_logo_can_be_removed_and_its_placeholder_stays_visible(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('instituto/logo-actual.png', 'logo');

        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Sistema',
            'dni' => '55555555',
            'tipo' => 'admin',
        ]);
        $admin = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_logo_test',
            'password' => Hash::make('password123'),
        ]);
        Instituto::create([
            'nombreinstituto' => 'Instituto de Prueba',
            'nombreregulador' => 'Ministerio de Educación',
            'ubicacion' => 'La Paz',
            'caracter' => 'FISCAL',
            'preciocredito' => 100,
            'resolucionministerial' => 'RM-001',
            'abreviatura' => 'IP',
            'logo' => 'instituto/logo-actual.png',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.instituto.update'), [
                'nombreinstituto' => 'Instituto de Prueba',
                'nombreregulador' => 'Ministerio de Educación',
                'ubicacion' => 'La Paz',
                'caracter' => 'FISCAL',
                'preciocredito' => 100,
                'resolucionministerial' => 'RM-001',
                'abreviatura' => 'IP',
                'eliminar_logo' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('instituto', [
            'nombreinstituto' => 'Instituto de Prueba',
            'logo' => null,
        ]);
        Storage::disk('public')->assertMissing('instituto/logo-actual.png');

        $this->get(route('admin.instituto.index'))
            ->assertOk()
            ->assertSee('El instituto no tiene un logo guardado.');
    }
}
