<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Estudiante;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\StudentPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_uses_the_existing_logo_asset(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(asset('images/logo.png'))
            ->assertSee(asset('images/image.png'));

        $this->assertFileExists(public_path('images/image.png'));
    }

    public function test_seeded_student_can_open_student_portal_pages(): void
    {
        $this->seed(StudentPortalSeeder::class);
        $this->seed(StudentPortalSeeder::class);
        $student = User::where('usuario', '20260003')->firstOrFail();

        $this->assertSame(1, Persona::where('ci', '87654322')->count());
        $this->assertSame(1, Estudiante::where('id_persona', $student->id_persona)->count());
        $this->assertTrue(Hash::check('estudiante123', $student->password));
        $this->assertTrue($student->canAccess('estudiantes/estado_inscripcion'));
        $this->assertFalse($student->canAccess('admin/empleados'));
        $this->assertFalse($student->canAccess('inscripciones/estudiante_regular'));

        $this->post(route('login'), [
            'usuario' => '20260003',
            'password' => 'estudiante123',
        ])->assertRedirect('/home');

        $this->assertAuthenticatedAs($student);

        $this->get('/home')
            ->assertOk()
            ->assertSee('Estado Inscripcion')
            ->assertDontSee('Administración')
            ->assertDontSee('Inscripciones')
            ->assertSee(asset('css/custom.css'));

        $this->get(route('estudiantes.estado_inscripcion'))->assertOk();

        $this->get(route('estudiantes.historial_economico'))
            ->assertOk();

        $this->get(route('estudiantes.file_estudiante'))
            ->assertOk();
    }

    public function test_administrators_and_teachers_cannot_open_student_portal(): void
    {
        $admin = $this->createUser('admin', 'Administrador', true);
        $teacher = $this->createUser('docente', 'Docente', true);

        $this->actingAs($admin)
            ->get(route('estudiantes.estado_inscripcion'))
            ->assertForbidden();

        $this->get('/home')
            ->assertOk()
            ->assertSee('Administración')
            ->assertDontSee('Estado Inscripcion');

        $this->actingAs($teacher)
            ->get(route('estudiantes.estado_inscripcion'))
            ->assertForbidden();
    }

    public function test_a_student_role_without_a_student_record_is_denied(): void
    {
        $student = $this->createUser('sin_ficha', 'Estudiante');

        $this->actingAs($student)
            ->get(route('estudiantes.estado_inscripcion'))
            ->assertForbidden();
    }

    public function test_an_account_with_student_and_non_student_roles_is_denied(): void
    {
        $student = $this->createUser('multiple_roles', 'Estudiante', true);
        $adminRole = Rol::create(['nombre' => 'Administrador']);

        Cuenta::create([
            'id_persona' => $student->id_persona,
            'id_rol' => $adminRole->id_rol,
            'usuario' => 'multiple_roles_admin',
            'password' => $student->password,
        ]);

        $this->actingAs($student)
            ->get(route('estudiantes.estado_inscripcion'))
            ->assertForbidden();
    }

    private function createUser(string $suffix, string $roleName, bool $withStudentRecord = false): User
    {
        $persona = Persona::create([
            'nombre' => ucfirst($suffix),
            'primerapellido' => 'Prueba',
            'ci' => (string) random_int(10000000, 99999999),
            'tipo' => mb_strtolower($roleName),
        ]);

        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => $suffix . '_' . $persona->id_persona,
            'password' => Hash::make('password123'),
        ]);

        $role = Rol::firstOrCreate(['nombre' => $roleName]);
        Cuenta::create([
            'id_persona' => $persona->id_persona,
            'id_rol' => $role->id_rol,
            'usuario' => $user->usuario,
            'password' => $user->password,
        ]);

        if ($withStudentRecord) {
            Estudiante::create(['id_persona' => $persona->id_persona]);
        }

        return $user;
    }
}
