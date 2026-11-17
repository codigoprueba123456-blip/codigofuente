<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureFunctionalityAccess;
use App\Models\Carrera;
use App\Models\Cuenta;
use App\Models\Estudiante;
use App\Models\GestionAcademica;
use App\Models\Inscrito;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InscritosManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_state_migration_can_resume_after_a_partial_application(): void
    {
        $migration = require database_path('migrations/2026_10_27_000001_add_active_state_to_enrollments.php');
        $migration->down();

        Schema::table('inscrito', function ($table): void {
            $table->boolean('activo')->default(true)->after('becado');
        });
        Schema::table('inscrito', function ($table): void {
            $table->dropUnique('inscrito_voucher_gestion_unique');
        });

        $migration->up();

        $this->assertTrue(Schema::hasColumn('inscrito', 'activo'));
        $this->assertTrue(Schema::hasColumn('comprobante', 'activo'));
        $this->assertTrue(Schema::hasIndex('inscrito', 'inscrito_persona_carrera_gestion_index'));
        $this->assertTrue(Schema::hasIndex('inscrito', 'inscrito_voucher_gestion_index'));
        $this->assertFalse(Schema::hasIndex('inscrito', 'inscrito_persona_carrera_gestion_unique'));
        $this->assertFalse(Schema::hasIndex('inscrito', 'inscrito_voucher_gestion_unique'));
    }

    public function test_admin_can_edit_student_data_and_the_selected_enrollment_without_changing_its_payment(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $admin = $this->createAdmin();
        $this->actingAs($admin);
        $record = $this->createEnrollment();
        $record['carrera']->update(['regimen' => Carrera::REGIMEN_SEMESTRAL]);
        DB::table('materia')->where('id_carrera', $record['carrera']->id_carrera)->update(['nivel' => 6]);
        DB::table('inscrito')->where('id_inscrito', $record['inscrito']->id_inscrito)->update([
            'turno' => 1,
            'paralelo' => 'A',
        ]);

        $this->get(route('inscriptos.edit', $record['inscrito']->id_inscrito))
            ->assertOk()
            ->assertSee('Editar ficha de inscripción')
            ->assertSee('Sexto semestre')
            ->assertSee('name="turno"', false)
            ->assertSee('name="paralelo"', false)
            ->assertSee('window.turnoInscripcionInicial = 1;', false)
            ->assertSee('window.paraleloInscripcionInicial = "A";', false)
            ->assertSee('Andrea')
            ->assertDontSee('path_ci_frontal');

        $this->put(route('inscriptos.update', $record['inscrito']->id_inscrito), [
            'nombre' => 'Andrea Camila',
            'primerapellido' => 'Quispe',
            'segundoapellido' => 'Mamani',
            'ci' => 'CI-EDITADO',
            'lugarexpedicion' => 'CB',
            'fechanacimiento' => '2005-04-03',
            'lugarnacimiento' => 'Sacaba',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'direccion' => 'Calle de prueba',
            'telefono' => '4455667',
            'celular' => '70707070',
            'email' => 'andrea@example.test',
            'diplomabachiller' => '1',
            'nombrecontacto' => 'Contacto de prueba',
            'celularcontacto' => '70000000',
            'parentescocontacto' => 'Madre',
            'nombrecolegio' => 'Colegio de prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => '2023',
            'traspaso_convalidacion' => '0',
            'fecha' => '2027-01-15',
            'becado' => '0',
            'deposito' => 'VOUCHER-EDITADO',
            'id_carrera' => $record['carrera']->id_carrera,
            'id_gestionacademica' => $record['gestion']->id_gestionacademica,
            'nivelinicio' => '6',
            'turno' => '1',
            'paralelo' => 'A',
        ])->assertRedirect(route('inscriptos.index'));

        $this->assertDatabaseHas('personas', [
            'id_persona' => $record['persona']->id_persona,
            'nombre' => 'Andrea Camila',
            'ci' => 'CI-EDITADO',
        ]);
        $this->assertDatabaseHas('estudiante', [
            'id_persona' => $record['persona']->id_persona,
            'nombrecontacto' => 'Contacto de prueba',
            'egresocolegio' => 2023,
        ]);
        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $record['inscrito']->id_inscrito,
            'deposito' => 'VOUCHER-EDITADO',
            'fecha' => '2027-01-15',
            'turno' => 1,
            'paralelo' => 'A',
        ]);
        $this->assertDatabaseHas('comprobante', [
            'id_comprobante' => $record['comprobante'],
            'deposito' => 'VOUCHER-EDITADO',
            'activo' => true,
        ]);
        $this->assertDatabaseHas('cursa', [
            'id_persona' => $record['persona']->id_persona,
            'id_carrera' => $record['carrera']->id_carrera,
            'id_gestionacademica' => $record['gestion']->id_gestionacademica,
            'nivelinicio' => 6,
        ]);
        $this->assertDatabaseHas('pago', [
            'id_pago' => $record['pago'],
            'monto' => 150,
            'estado' => true,
        ]);
    }

    public function test_edit_changes_course_turn_and_parallel_using_groups_for_that_course(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $admin = $this->createAdmin();
        $record = $this->createEnrollment();
        $record['carrera']->update(['regimen' => Carrera::REGIMEN_ANUAL]);
        DB::table('inscrito')->where('id_inscrito', $record['inscrito']->id_inscrito)->update([
            'turno' => 1,
            'paralelo' => 'A',
        ]);

        DB::table('materia')->insert([
            'codigo' => 'TEST-MANAGEMENT-MAT-2A',
            'nombre' => 'Materia de segundo año mañana',
            'nivel' => 2,
            'numero' => 1,
            'activo' => true,
            'id_carrera' => $record['carrera']->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $materiaSegundoManana = DB::table('materia')->where('codigo', 'TEST-MANAGEMENT-MAT-2A')->value('id_materia');
        DB::table('grupo')->insert([
            'codigo' => 'TEST-MANAGEMENT-MAT-2A-A',
            'capacidad' => 1,
            'turno' => 1,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $record['gestion']->id_gestionacademica,
            'id_materia' => $materiaSegundoManana,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('materia')->insert([
            'codigo' => 'TEST-MANAGEMENT-MAT-2B',
            'nombre' => 'Materia de segundo año noche',
            'nivel' => 2,
            'numero' => 2,
            'activo' => true,
            'id_carrera' => $record['carrera']->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $materiaSegundoNoche = DB::table('materia')->where('codigo', 'TEST-MANAGEMENT-MAT-2B')->value('id_materia');
        DB::table('grupo')->insert([
            'codigo' => 'TEST-MANAGEMENT-MAT-2B-B',
            'capacidad' => 30,
            'turno' => 3,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $record['gestion']->id_gestionacademica,
            'id_materia' => $materiaSegundoNoche,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $editView = $this->actingAs($admin)->get(route('inscriptos.edit', $record['inscrito']->id_inscrito));
        $editView->assertOk();
        $opcionesSegundoAno = $editView->viewData('opcionesTurnosCarrera')
            [$record['gestion']->id_gestionacademica]
            [$record['carrera']->id_carrera][2];
        $this->assertSame(1, $editView->viewData('turnoInicial'));
        $this->assertSame('A', $editView->viewData('paraleloInicial'));
        $this->assertSame(1, $opcionesSegundoAno[0]['turno']);
        $this->assertSame('A', $opcionesSegundoAno[0]['paralelo']);
        $this->assertSame(1, $opcionesSegundoAno[0]['disponibles']);

        $this->actingAs($admin)
            ->put(route('inscriptos.update', $record['inscrito']->id_inscrito), [
                'nombre' => 'Andrea',
                'primerapellido' => 'Quispe',
                'ci' => 'CI-PRUEBA',
                'diplomabachiller' => '1',
                'traspaso_convalidacion' => '0',
                'fecha' => '2027-01-10',
                'becado' => '0',
                'deposito' => 'VOUCHER-PRUEBA',
                'id_carrera' => $record['carrera']->id_carrera,
                'id_gestionacademica' => $record['gestion']->id_gestionacademica,
                'nivelinicio' => '2',
                'turno' => '1',
                'paralelo' => 'A',
            ])
            ->assertRedirect(route('inscriptos.index'));

        $this->assertDatabaseHas('cursa', [
            'id_persona' => $record['persona']->id_persona,
            'id_carrera' => $record['carrera']->id_carrera,
            'id_gestionacademica' => $record['gestion']->id_gestionacademica,
            'nivelinicio' => 2,
        ]);

        $this->put(route('inscriptos.update', $record['inscrito']->id_inscrito), [
            'nombre' => 'Andrea',
            'primerapellido' => 'Quispe',
            'ci' => 'CI-PRUEBA',
            'diplomabachiller' => '1',
            'traspaso_convalidacion' => '0',
            'fecha' => '2027-01-10',
            'becado' => '0',
            'deposito' => 'VOUCHER-PRUEBA',
            'id_carrera' => $record['carrera']->id_carrera,
            'id_gestionacademica' => $record['gestion']->id_gestionacademica,
            'nivelinicio' => '2',
            'turno' => '3',
            'paralelo' => 'B',
        ])->assertRedirect(route('inscriptos.index'));

        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $record['inscrito']->id_inscrito,
            'turno' => 3,
            'paralelo' => 'B',
        ]);
    }

    public function test_index_displays_enrollment_shift_and_parallel_when_no_groups_are_linked(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $admin = $this->createAdmin();
        $record = $this->createEnrollment();
        DB::table('inscrito')->where('id_inscrito', $record['inscrito']->id_inscrito)->update([
            'turno' => 2,
            'paralelo' => 'B',
        ]);

        $this->actingAs($admin)
            ->get(route('inscriptos.index'))
            ->assertOk()
            ->assertSee('Paralelo B')
            ->assertSee('Tarde')
            ->assertDontSee('Sin paralelo asignado')
            ->assertDontSee('Sin turno asignado');
    }

    public function test_index_displays_saved_enrollment_turn_and_parallel_when_groups_are_linked(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $admin = $this->createAdmin();
        $record = $this->createEnrollment();
        $materia = DB::table('materia')->where('id_carrera', $record['carrera']->id_carrera)->value('id_materia');
        $grupo = DB::table('grupo')->where('id_materia', $materia)->first();
        DB::table('inscrito_materia')->insert([
            'id_inscrito' => $record['inscrito']->id_inscrito,
            'id_materia' => $materia,
            'id_grupo' => $grupo->id_grupo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inscrito')->where('id_inscrito', $record['inscrito']->id_inscrito)->update([
            'turno' => 3,
            'paralelo' => 'B',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('inscriptos.index'))
            ->assertOk()
            ->assertSee('Paralelo B')
            ->assertSee('Noche');

        $inscrito = $response->viewData('inscritos')->getCollection()->first();
        $grupoMostrado = $inscrito->grupos_asignados->first();
        $this->assertSame('B', $grupoMostrado->paralelo);
        $this->assertSame('Noche', $grupoMostrado->turno_etiqueta);
    }

    public function test_disabling_preserves_history_and_allows_reusing_the_voucher_for_a_new_enrollment(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $admin = $this->createAdmin();
        $this->actingAs($admin);
        $record = $this->createEnrollment();
        $student = $this->createStudentUser($record['persona']);

        $this->post(route('inscriptos.deshabilitar', $record['inscrito']->id_inscrito))
            ->assertRedirect(route('inscriptos.index'));

        $this->assertDatabaseHas('inscrito', [
            'id_inscrito' => $record['inscrito']->id_inscrito,
            'activo' => false,
        ]);
        $this->assertDatabaseHas('comprobante', [
            'id_comprobante' => $record['comprobante'],
            'activo' => false,
            'valido' => true,
        ]);
        $this->assertDatabaseHas('pago', [
            'id_pago' => $record['pago'],
            'monto' => 150,
            'pagado' => true,
            'estado' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('pagos.index'))
            ->assertOk()
            ->assertSee('VOUCHER-PRUEBA')
            ->assertSee('Deshabilitado');

        $this->actingAs($student)
            ->get(route('estudiantes.estado_inscripcion'))
            ->assertOk()
            ->assertDontSee('Sistemas Informáticos');
        $this->get(route('estudiantes.historial_economico'))
            ->assertOk()
            ->assertDontSee('VOUCHER-PRUEBA');

        $this->actingAs($admin);
        $this->post(route('inscripcion.store'), [
            'ci' => $record['persona']->ci,
            'fecha' => '2027-02-01',
            'becado' => '0',
            'deposito' => 'VOUCHER-PRUEBA',
            'id_gestionacademica' => $record['gestion']->id_gestionacademica,
            'traspaso_convalidacion' => '0',
            'id_carrera' => $record['carrera']->id_carrera,
            'nivelinicio' => '1',
            'turno' => '1',
            'paralelo' => 'A',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, DB::table('inscrito')
            ->where('id_persona', $record['persona']->id_persona)
            ->where('id_carrera', $record['carrera']->id_carrera)
            ->where('id_gestionacademica', $record['gestion']->id_gestionacademica)
            ->count());
        $this->assertSame(1, DB::table('inscrito')
            ->where('id_persona', $record['persona']->id_persona)
            ->where('id_carrera', $record['carrera']->id_carrera)
            ->where('id_gestionacademica', $record['gestion']->id_gestionacademica)
            ->where('activo', true)
            ->count());

        $this->get(route('inscriptos.index', ['estado' => 'deshabilitados']))
            ->assertOk()
            ->assertSee('Deshabilitada');
    }

    private function createEnrollment(): array
    {
        $gestion = GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'TEST-MANAGEMENT',
            'nombre' => 'Sistemas Informáticos',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $idMateria = DB::table('materia')->insertGetId([
            'codigo' => 'TEST-MANAGEMENT-MAT-1',
            'nombre' => 'Materia de primer año',
            'nivel' => 1,
            'numero' => 1,
            'activo' => true,
            'id_carrera' => $carrera->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('grupo')->insert([
            'codigo' => 'TEST-MANAGEMENT-MAT-1-A',
            'capacidad' => 30,
            'turno' => 1,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $idMateria,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $persona = Persona::create([
            'nombre' => 'Andrea',
            'primerapellido' => 'Quispe',
            'segundoapellido' => 'Mamani',
            'ci' => 'CI-PRUEBA',
            'lugarexpedicion' => 'CB',
            'fechanacimiento' => '2005-04-03',
            'lugarnacimiento' => 'Sacaba',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'direccion' => 'Calle inicial',
            'telefono' => 4455667,
            'celular' => 70707070,
            'email' => 'andrea-inicial@example.test',
            'tipo' => 'Estudiante',
        ]);
        Estudiante::create([
            'id_persona' => $persona->id_persona,
            'diplomabachiller' => true,
            'nombrecontacto' => 'Contacto inicial',
            'celularcontacto' => 70000000,
            'parentescocontacto' => 'Madre',
            'nombrecolegio' => 'Colegio inicial',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2023,
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);
        $inscrito = Inscrito::create([
            'fecha' => '2027-01-10',
            'becado' => false,
            'deposito' => 'VOUCHER-PRUEBA',
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $persona->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);
        DB::table('cursa')->insert([
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'nivelinicio' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $pago = DB::table('pago')->insertGetId([
            'monto' => 150,
            'pagado' => true,
            'estado' => true,
            'id_inscrito' => $inscrito->id_inscrito,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $comprobante = DB::table('comprobante')->insertGetId([
            'deposito' => 'VOUCHER-PRUEBA',
            'fecha' => '2027-01-10',
            'valido' => true,
            'id_inscrito' => $inscrito->id_inscrito,
            'id_estudiante' => $persona->id_persona,
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return compact('gestion', 'carrera', 'persona', 'inscrito', 'pago', 'comprobante');
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'ADMIN-MANAGEMENT-001',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_management',
            'password' => Hash::make('password123'),
        ]);
    }

    private function createStudentUser(Persona $persona): User
    {
        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'student_management',
            'password' => Hash::make('password123'),
        ]);
        $role = Rol::firstOrCreate(['nombre' => 'Estudiante']);
        Cuenta::create([
            'id_persona' => $persona->id_persona,
            'id_rol' => $role->id_rol,
            'usuario' => $user->usuario,
            'password' => $user->password,
        ]);

        return $user;
    }
}
