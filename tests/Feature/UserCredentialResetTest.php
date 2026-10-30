<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\EnablesFunctionality;
use Tests\TestCase;

class UserCredentialResetTest extends TestCase
{
    use RefreshDatabase;
    use EnablesFunctionality;

    public function test_reset_password_action_regenerates_credentials_and_returns_pdf(): void
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

        $targetPersona = Persona::create([
            'nombre' => 'Ana',
            'primerapellido' => 'Pérez',
            'dni' => '22222222',
            'email' => 'ana@example.com',
            'tipo' => 'empleado',
        ]);
        $role = Rol::create(['nombre' => 'Empleado']);
        $target = User::create([
            'id_persona' => $targetPersona->id_persona,
            'usuario' => '20260001',
            'password' => Hash::make('OldPassword123'),
        ]);
        Cuenta::create([
            'id_persona' => $targetPersona->id_persona,
            'id_rol' => $role->id_rol,
            'usuario' => '20260001',
            'password' => Hash::make('OldPassword123'),
        ]);
        $this->enableFunctionality('admin/usuarios');

        $response = $this->actingAs($admin)
            ->post(route('admin.usuarios.regenerar-credenciales', $target->id_persona));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $target->refresh();
        $cuenta = Cuenta::findOrFail($targetPersona->id_persona);
        $this->assertFalse(Hash::check('OldPassword123', $target->password));
        $this->assertFalse(Hash::check('OldPassword123', $cuenta->password));
        $this->assertSame($target->password, $cuenta->password);
    }

    public function test_reset_password_button_posts_to_the_credential_reset_route(): void
    {
        $persona = Persona::create([
            'nombre' => 'Usuario',
            'primerapellido' => 'Prueba',
            'dni' => '33333333',
            'tipo' => 'empleado',
        ]);
        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => '20260002',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee(route('admin.usuarios.regenerar-credenciales', $user->id_persona), false)
            ->assertSee('Restablecer contraseña');
    }
}
