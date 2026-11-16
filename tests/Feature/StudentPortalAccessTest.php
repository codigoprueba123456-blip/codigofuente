<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\GestionAcademica;
use App\Models\Inscrito;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\StudentPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_student_enrollment_history_shows_its_career_resolution_only_for_its_enrolled_career(): void
    {
        $this->seed(StudentPortalSeeder::class);
        $student = User::where('usuario', '20260003')->firstOrFail();
        $gestion = GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'activo' => true,
            'vigente' => true,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto del Portal']);
        $carrera = Carrera::create([
            'codigo' => 'PORTAL-SIS',
            'nombre' => 'Sistemas del Portal',
            'resolucionministerial1' => 'R.M. N° 210/2027',
            'activo' => true,
            'id_instituto' => $instituto->id_instituto,
        ]);
        Carrera::create([
            'codigo' => 'PORTAL-OTRA',
            'nombre' => 'Carrera no vinculada',
            'activo' => true,
            'id_instituto' => $instituto->id_instituto,
        ]);
        Inscrito::create([
            'fecha' => '2027-01-10',
            'becado' => false,
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $student->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'activo' => true,
        ]);
        DB::table('cursa')->insert([
            'id_persona' => $student->id_persona,
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'nivelinicio' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('estudiantes.estado_inscripcion'))
            ->assertOk()
            ->assertSee('Sistemas del Portal · R.M. 210/2027')
            ->assertDontSee('Carrera no vinculada');
    }

    public function test_administrators_can_open_student_portal_but_teachers_cannot(): void
    {
        $admin = $this->createUser('admin', 'Administrador', true);
        $teacher = $this->createUser('docente', 'Docente', true);

        $this->actingAs($admin)
            ->get(route('estudiantes.estado_inscripcion'))
            ->assertOk();

        $this->get('/home')
            ->assertOk()
            ->assertSee('Administración')
            ->assertSee('Estado Inscripcion');

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

    public function test_an_account_with_student_and_non_admin_roles_is_denied(): void
    {
        $student = $this->createUser('multiple_roles', 'Estudiante', true);
        $otherRole = Rol::create(['nombre' => 'Docente']);

        Cuenta::create([
            'id_persona' => $student->id_persona,
            'id_rol' => $otherRole->id_rol,
            'usuario' => 'multiple_roles_docente',
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
