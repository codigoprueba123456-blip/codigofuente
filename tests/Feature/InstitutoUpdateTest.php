<?php

namespace Tests\Feature;

use App\Models\Instituto;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\EnablesFunctionality;
use Tests\TestCase;

class InstitutoUpdateTest extends TestCase
{
    use RefreshDatabase;
    use EnablesFunctionality;

    public function test_institute_form_saves_and_displays_all_configured_data(): void
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Sistema',
            'dni' => '11111111',
            'tipo' => 'admin',
        ]);
        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_test',
            'password' => Hash::make('password123'),
        ]);
        $this->enableFunctionality('admin/instituto');

        $datosInstituto = [
            'nombreinstituto' => 'Instituto de Prueba',
            'nombreregulador' => 'Ministerio de Educación',
            'ubicacion' => 'La Paz',
            'caracter' => 'FISCAL',
            'nombreimpuestos' => 'Instituto de Prueba',
            'nit' => '123456789',
            'actividadeconomica' => 'Educación superior',
            'preciocredito' => 150,
            'resolucionministerial' => 'RM-123',
            'abreviatura' => 'IP',
            'token' => 'token-prueba',
        ];

        $this->actingAs($user)
            ->get(route('admin.instituto.index'))
            ->assertOk()
            ->assertSee('name="nombreinstituto"', false);

        $this->put(route('admin.instituto.update'), $datosInstituto)
            ->assertRedirect(route('admin.instituto.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('instituto', $datosInstituto);

        $this->get(route('admin.instituto.index'))
            ->assertOk()
            ->assertSee('value="Instituto de Prueba"', false)
            ->assertSee('value="La Paz"', false)
            ->assertSee('value="123456789"', false)
            ->assertSee('value="IP"', false);

        $this->assertSame(1, Instituto::count());
    }

    public function test_institute_form_reports_missing_required_values_instead_of_silently_failing(): void
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Sistema',
            'dni' => '11111111',
            'tipo' => 'admin',
        ]);
        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_test',
            'password' => Hash::make('password123'),
        ]);
        $this->enableFunctionality('admin/instituto');

        $this->actingAs($user)
            ->put(route('admin.instituto.update'), [])
            ->assertSessionHasErrors([
                'nombreinstituto',
                'nombreregulador',
                'ubicacion',
                'caracter',
                'preciocredito',
                'resolucionministerial',
                'abreviatura',
            ]);
    }
}
