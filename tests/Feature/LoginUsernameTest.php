<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginUsernameTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_username_and_password(): void
    {
        $persona = Persona::create([
            'nombre' => 'Maria',
            'primerapellido' => 'Flores',
            'segundoapellido' => 'Lopez',
            'dni' => '12345678',
            'tipo' => 'empleado',
        ]);

        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => '20260001',
            'password' => Hash::make('Secret123'),
        ]);

        $response = $this->post(route('login'), [
            'usuario' => '20260001',
            'password' => 'Secret123',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Maria Flores', auth()->user()->name);
    }

    public function test_invalid_login_credentials_are_reported_in_spanish(): void
    {
        $response = $this->from('/login')->post(route('login'), [
            'usuario' => 'no-existe',
            'password' => 'incorrecta',
        ]);

        $response->assertRedirect('/login')
            ->assertSessionHasErrors([
                'usuario' => 'El usuario o la contraseña son incorrectos.',
            ]);
    }

    public function test_login_validation_errors_are_reported_in_spanish(): void
    {
        $response = $this->from('/login')->post(route('login'), []);

        $response->assertRedirect('/login')
            ->assertSessionHasErrors([
                'usuario' => 'El campo usuario es obligatorio cuando correo electrónico no esté presente.',
                'contrasena' => 'El campo contraseña es obligatorio cuando contraseña no esté presente.',
            ]);

        $errors = implode(' ', session('errors')->all());
        $this->assertStringNotContainsString('The ', $errors);
    }
}
