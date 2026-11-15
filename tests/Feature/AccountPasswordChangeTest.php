<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_choose_a_new_password_for_their_account(): void
    {
        [$user, $cuenta] = $this->createAccount();

        $this->actingAs($user)
            ->get(route('account.password.edit'))
            ->assertOk()
            ->assertSee('Cambiar contraseña')
            ->assertSee(route('account.password.edit'), false)
            ->assertSee('Contraseña actual');

        $this->put(route('account.password.update'), [
            'current_password' => 'Generated123',
            'password' => 'MyChosenPass123',
            'password_confirmation' => 'MyChosenPass123',
        ])->assertRedirect(route('account.password.edit'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('MyChosenPass123', $user->fresh()->password));
        $this->assertTrue(Hash::check('MyChosenPass123', $cuenta->fresh()->password));
    }

    public function test_password_change_requires_the_current_password(): void
    {
        [$user, $cuenta] = $this->createAccount();

        $this->actingAs($user)
            ->from(route('account.password.edit'))
            ->put(route('account.password.update'), [
                'current_password' => 'Incorrect123',
                'password' => 'MyChosenPass123',
                'password_confirmation' => 'MyChosenPass123',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Generated123', $user->fresh()->password));
        $this->assertTrue(Hash::check('Generated123', $cuenta->fresh()->password));
    }

    /**
     * @return array{0: User, 1: Cuenta}
     */
    private function createAccount(): array
    {
        $persona = Persona::create([
            'nombre' => 'Usuario',
            'primerapellido' => 'Prueba',
            'ci' => '98765432',
            'tipo' => 'empleado',
        ]);
        $passwordHash = Hash::make('Generated123');
        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'usuario_' . $persona->id_persona,
            'password' => $passwordHash,
        ]);
        $rol = Rol::create(['nombre' => 'Usuario']);
        $cuenta = Cuenta::create([
            'id_persona' => $persona->id_persona,
            'id_rol' => $rol->id_rol,
            'usuario' => $user->usuario,
            'password' => $passwordHash,
        ]);

        return [$user, $cuenta];
    }
}
