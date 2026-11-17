<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\Postulante;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\OpensPublicPostulationWindow;
use Tests\TestCase;

class PostulacionTurnosTest extends TestCase
{
    use OpensPublicPostulationWindow;
    use RefreshDatabase;

    public function test_a_person_cannot_submit_two_careers_in_the_same_shift_but_can_choose_another_shift(): void
    {
        $gestion = GestionAcademica::create([
            'gestion' => now()->year,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $this->openPublicPostulationWindow($gestion);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de turnos']);
        $carreraInicial = $this->createCarrera($instituto, 'TURNO-INICIAL');
        $carreraDestino = $this->createCarrera($instituto, 'TURNO-DESTINO');
        $this->createGrupo($carreraInicial, $gestion, 3, 'A', 10);
        $this->createGrupo($carreraDestino, $gestion, 3, 'A', 10);
        $this->createGrupo($carreraDestino, $gestion, 2, 'B', 10);
        $this->createPostulante($carreraInicial, $gestion);

        $formulario = $this->get(route('postulantes.registrar', ['ci_consulta' => 'POST-TURNO-001']))
            ->assertOk()
            ->getContent();
        preg_match('/<select\b[^>]*id="id_carrera"[^>]*>(.*?)<\/select>/s', $formulario, $opcionesCarrera);
        $this->assertCount(2, $opcionesCarrera);
        $this->assertDoesNotMatchRegularExpression(
            '/<option\b[^>]*value="'.preg_quote((string) $carreraInicial->id_carrera, '/').'"/',
            $opcionesCarrera[1]
        );
        $this->assertMatchesRegularExpression(
            '/<option\b[^>]*value="'.preg_quote((string) $carreraDestino->id_carrera, '/').'"/',
            $opcionesCarrera[1]
        );

        $this->post(route('postulantes.store'), [
            'ci' => 'POST-TURNO-001',
            'id_carrera' => $carreraInicial->id_carrera,
        ])->assertSessionHasErrors('id_carrera');

        $datos = [
            'nombre' => 'Valeria',
            'primerapellido' => 'Prueba',
            'ci' => 'POST-TURNO-001',
            'lugarexpedicion' => 'CB',
            'fechanacimiento' => '2005-01-01',
            'lugarnacimiento' => 'Cochabamba',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'direccion' => 'Zona Central',
            'celular' => '70000000',
            'email' => 'post-turno@example.test',
            'nombrecontacto' => 'Contacto',
            'celularcontacto' => '70000001',
            'parentescocontacto' => 'Madre',
            'nombrecolegio' => 'Colegio de prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'fecha' => now()->toDateString(),
            'diplomabachiller' => 1,
            'id_carrera' => $carreraDestino->id_carrera,
        ];

        $this->post(route('postulantes.store'), $datos + [
            'turno' => 3,
            'paralelo' => 'A',
            'deposito' => 'POST-TURNO-VOUCHER-RECHAZADO',
        ])->assertSessionHasErrors('turno');

        $this->post(route('postulantes.store'), $datos + [
            'turno' => 2,
            'paralelo' => 'B',
            'deposito' => 'POST-TURNO-VOUCHER-ACEPTADO',
        ])->assertRedirect();

        $this->assertDatabaseHas('postulante', [
            'ci' => 'POST-TURNO-001',
            'id_carrera' => $carreraDestino->id_carrera,
            'turno' => 2,
            'paralelo' => 'B',
        ]);
    }

    public function test_edit_cannot_switch_to_a_career_already_in_the_applicants_student_history(): void
    {
        $gestion = GestionAcademica::create([
            'gestion' => now()->year,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $this->openPublicPostulationWindow($gestion);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de turnos']);
        $carreraPostulada = $this->createCarrera($instituto, 'EDIT-POSTULADA');
        $carreraRegistrada = $this->createCarrera($instituto, 'EDIT-REGISTRADA');
        $this->createGrupo($carreraPostulada, $gestion, 3, 'A', 10);
        $this->createGrupo($carreraRegistrada, $gestion, 1, 'A', 10);
        $persona = Persona::create([
            'nombre' => 'Valeria',
            'primerapellido' => 'Prueba',
            'ci' => 'POST-TURNO-EDIT',
            'tipo' => 'Estudiante',
        ]);
        DB::table('cursa')->insert([
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carreraRegistrada->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'nivelinicio' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $postulante = $this->createPostulante($carreraPostulada, $gestion, 'POST-TURNO-EDIT');

        $formularioNuevo = $this->get(route('postulantes.registrar', ['ci_consulta' => 'POST-TURNO-EDIT']))
            ->assertOk()
            ->getContent();
        preg_match('/<select\b[^>]*id="id_carrera"[^>]*>(.*?)<\/select>/s', $formularioNuevo, $opcionesNuevas);
        $this->assertCount(2, $opcionesNuevas);
        $this->assertDoesNotMatchRegularExpression(
            '/<option\b[^>]*value="'.preg_quote((string) $carreraRegistrada->id_carrera, '/').'"/',
            $opcionesNuevas[1]
        );

        $formularioEdicion = $this->get(route('postulantes.edit', $postulante->id_postulante))
            ->assertOk()
            ->getContent();
        preg_match('/<select\b[^>]*id="id_carrera"[^>]*>(.*?)<\/select>/s', $formularioEdicion, $opcionesEdicion);
        $this->assertCount(2, $opcionesEdicion);
        $this->assertDoesNotMatchRegularExpression(
            '/<option\b[^>]*value="'.preg_quote((string) $carreraRegistrada->id_carrera, '/').'"/',
            $opcionesEdicion[1]
        );
        $this->assertMatchesRegularExpression(
            '/<option\b[^>]*value="'.preg_quote((string) $carreraPostulada->id_carrera, '/').'"/',
            $opcionesEdicion[1]
        );

        $this->put(route('postulantes.update', $postulante->id_postulante), [
            'ci' => 'POST-TURNO-EDIT',
            'id_carrera' => $carreraRegistrada->id_carrera,
        ])->assertSessionHasErrors('id_carrera');
    }

    private function createCarrera(Instituto $instituto, string $codigo): Carrera
    {
        return Carrera::create([
            'codigo' => $codigo,
            'nombre' => $codigo,
            'id_instituto' => $instituto->id_instituto,
        ]);
    }

    private function createGrupo(
        Carrera $carrera,
        GestionAcademica $gestion,
        int $turno,
        string $paralelo,
        int $capacidad
    ): void {
        $idMateria = DB::table('materia')->insertGetId([
            'codigo' => $carrera->codigo.'-MAT',
            'nombre' => 'Materia de '.$carrera->codigo,
            'id_carrera' => $carrera->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('grupo')->insert([
            'codigo' => $carrera->codigo.'-MAT-'.$paralelo,
            'capacidad' => $capacidad,
            'turno' => $turno,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $idMateria,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPostulante(
        Carrera $carrera,
        GestionAcademica $gestion,
        string $ci = 'POST-TURNO-001'
    ): Postulante {
        return Postulante::create([
            'ci' => $ci,
            'primerapellido' => 'Prueba',
            'nombre' => 'Valeria',
            'celular' => '70000000',
            'email' => 'post-turno@example.test',
            'direccion' => 'Zona Central',
            'fechanacimiento' => '2005-01-01',
            'lugarnacimiento' => 'Cochabamba',
            'lugarexpedicion' => 'CB',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'fecha' => now(),
            'nombrecolegio' => 'Colegio de prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'diplomabachiller' => true,
            'nombrecontacto' => 'Contacto',
            'parentescocontacto' => 'Madre',
            'celularcontacto' => '70000001',
            'deposito' => 'POST-TURNO-VOUCHER-INICIAL',
            'foto' => 'postulantes/historico/foto.jpg',
            'path_ci_frontal' => 'postulantes/historico/ci-frontal.jpg',
            'path_ci_reverso' => 'postulantes/historico/ci-reverso.jpg',
            'path_nacimiento' => 'postulantes/historico/nacimiento.jpg',
            'path_diploma' => 'postulantes/historico/diploma.jpg',
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'turno' => 3,
            'paralelo' => 'A',
        ]);
    }
}
