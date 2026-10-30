<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\Modulos\AdministracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmptyModuleSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_administration_seeder_creates_demo_accounts_idempotently(): void
    {
        $this->seed(AdministracionSeeder::class);

        $this->assertDatabaseCount('personas', 4);
        $this->assertDatabaseCount('empleados', 3);
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('cuenta', 4);
        $this->assertDatabaseCount('rol', 4);
        $this->assertDatabaseCount('estudiante', 1);
        $this->assertDatabaseHas('personas', [
            'ci' => '99000001',
            'nombre' => 'Alex',
            'primerapellido' => 'Administrador',
            'email' => 'admin_prueba@example.test',
            'fechanacimiento' => '1985-01-15',
            'tipo' => 'admin',
        ]);
        $this->assertDatabaseHas('personas', [
            'ci' => '99000004',
            'nombre' => 'Esteban',
            'primerapellido' => 'Estudiante',
            'email' => 'estudiante_prueba@example.test',
            'tipo' => 'estudiante',
        ]);

        foreach ([
            'Administrador' => ['admin_prueba', 'Admin2026!'],
            'Secretaria' => ['secretaria_prueba', 'Secretaria2026!'],
            'Docente' => ['docente_prueba', 'Docente2026!'],
            'Estudiante' => ['estudiante_prueba', 'Estudiante2026!'],
        ] as $roleName => [$username, $password]) {
            $role = Rol::where('nombre', $roleName)->firstOrFail();
            $account = Cuenta::where('id_rol', $role->id_rol)->firstOrFail();
            $user = User::where('usuario', $username)->firstOrFail();

            $this->assertSame($username, $account->usuario);
            $this->assertTrue(Hash::check($password, $account->password));
            $this->assertTrue(Hash::check($password, $user->password));
        }

        $studentAccount = User::where('usuario', 'estudiante_prueba')->firstOrFail();
        $this->assertDatabaseHas('estudiante', ['id_persona' => $studentAccount->id_persona]);

        $this->seed(AdministracionSeeder::class);
        $this->assertDatabaseCount('personas', 4);
        $this->assertDatabaseCount('empleados', 3);
        $this->assertDatabaseCount('users', 4);
    }
}
