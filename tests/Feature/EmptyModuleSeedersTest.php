<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Recurso;
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
        $this->assertDatabaseCount('rol', 7);
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

        $usernames = [];
        foreach ([
            'Administrador' => 'Admin2026!',
            'Secretaria' => 'Secretaria2026!',
            'Docente' => 'Docente2026!',
            'Estudiante' => 'Estudiante2026!',
        ] as $roleName => $password) {
            $role = Rol::where('nombre', $roleName)->firstOrFail();
            $account = Cuenta::where('id_rol', $role->id_rol)->firstOrFail();
            $user = User::where('id_persona', $account->id_persona)->firstOrFail();

            if ($roleName === 'Estudiante') {
                $this->assertMatchesRegularExpression('/^' . date('Y') . '\d{4,}$/', $user->usuario);
            } else {
                $this->assertMatchesRegularExpression('/^' . date('Y') . '\d{4,}$/', $user->usuario);
            }
            $this->assertSame($user->usuario, $account->usuario);
            $this->assertTrue(Hash::check($password, $account->password));
            $this->assertTrue(Hash::check($password, $user->password));
            $usernames[$roleName] = $user->usuario;
        }

        $this->seed(AdministracionSeeder::class);
        $this->assertDatabaseCount('personas', 4);
        $this->assertDatabaseCount('empleados', 3);
        $this->assertDatabaseCount('users', 4);

        foreach ($usernames as $roleName => $username) {
            $role = Rol::where('nombre', $roleName)->firstOrFail();
            $account = Cuenta::where('id_rol', $role->id_rol)->firstOrFail();
            $this->assertSame($username, $account->usuario);
        }

        $studentAccount = User::whereHas('persona', fn ($query) => $query->where('ci', '99000004'))
            ->firstOrFail();
        $this->assertDatabaseHas('estudiante', ['id_persona' => $studentAccount->id_persona]);

        $this->assertRoleResources('Docente', ['planes/materias']);
        $this->assertRoleResources('Secretaria', [
            'admisiones/postulantes',
            'inscripciones/estudiante_nuevo',
            'inscripciones/estudiante_nuevo_postulacion',
            'inscripciones/estudiante_regular',
            'inscripciones/cambio_carrera',
            'inscripciones/inscripcion_manual',
            'inscripciones/inscriptos',
            'pagos/pago',
        ]);
        $this->assertRoleResources('Estudiante', [
            'estudiantes/estado_inscripcion',
            'estudiantes/historial_economico',
            'estudiantes/file_estudiante',
        ]);
        $this->assertRoleResources('Director Administrativo', [
            'admin/instituto',
            'admin/empleados',
            'admin/usuarios',
            'pagos/pago',
            'reportes/malla-curricular',
            'reportes/dashboard',
            'reportes/inscripciones/lista-postulantes',
            'reportes/inscripciones/lista-inscritos-carrera',
            'reportes/inscripciones/lista-inscritos-paralelo',
            'reportes/inscripciones/lista-inscritos-grupo',
            'reportes/inscripciones/libro-inscripciones',
            'reportes/inscripciones/libro-inscripciones-carrera',
            'reportes/inscripciones/lista-inscritos-multicarrera',
        ]);

        $allMenuResources = Recurso::pluck('urlpattern')->all();
        $this->assertRoleResources('Administrador', $allMenuResources);
        $this->assertRoleResources('Rector', $allMenuResources);
        $this->assertRoleResources('Director Académico', $allMenuResources);
    }

    private function assertRoleResources(string $roleName, array $expectedUrls): void
    {
        $role = Rol::where('nombre', $roleName)->firstOrFail();
        $this->assertEqualsCanonicalizing(
            $expectedUrls,
            $role->recursos()->pluck('urlpattern')->all(),
            "Los recursos asignados al rol {$roleName} no coinciden."
        );
    }
}
