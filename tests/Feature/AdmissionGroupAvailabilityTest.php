<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Postulante;
use App\Services\Inscripciones\DisponibilidadTurnos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdmissionGroupAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_options_reflect_pending_application_seats_and_reject_a_full_group(): void
    {
        [$gestion, $carrera] = $this->createCatalogo(2);
        $disponibilidad = app(DisponibilidadTurnos::class);

        $this->assertSame(2, $disponibilidad->opciones($carrera->id_carrera, $gestion->id_gestionacademica)[0]['disponibles']);

        $postulante = $this->createPostulante($gestion, $carrera, 'TURNOS-001');
        $postulante->update(['turno' => 3, 'paralelo' => 'A']);
        $this->assertSame(1, $disponibilidad->opciones($carrera->id_carrera, $gestion->id_gestionacademica)[0]['disponibles']);

        $segundaPostulacion = $this->createPostulante($gestion, $carrera, 'TURNOS-002');
        $segundaPostulacion->update(['turno' => 3, 'paralelo' => 'A']);
        $this->assertSame([], $disponibilidad->opciones($carrera->id_carrera, $gestion->id_gestionacademica));

        try {
            $disponibilidad->validarDisponible(
                $carrera->id_carrera,
                $gestion->id_gestionacademica,
                3,
                'A'
            );
            $this->fail('Se esperaba rechazar el grupo lleno.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('turno', $exception->errors());
        }
    }

    private function createCatalogo(int $capacidad): array
    {
        $gestion = GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de turnos']);
        $carrera = Carrera::create([
            'codigo' => 'TURNOS-CARRERA',
            'nombre' => 'Carrera de turnos',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $materiaId = DB::table('materia')->insertGetId([
            'codigo' => 'TURNOS-MAT',
            'nombre' => 'Materia de turnos',
            'id_carrera' => $carrera->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('grupo')->insert([
            'codigo' => 'TURNOS-MAT-A',
            'capacidad' => $capacidad,
            'turno' => 3,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materiaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$gestion, $carrera];
    }

    private function createPostulante(GestionAcademica $gestion, Carrera $carrera, string $ci): Postulante
    {
        return Postulante::create([
            'ci' => $ci,
            'primerapellido' => 'Prueba',
            'nombre' => 'Postulante',
            'celular' => '70000000',
            'email' => $ci.'@example.test',
            'direccion' => 'Zona de prueba',
            'fechanacimiento' => '2005-01-01',
            'lugarnacimiento' => 'Cochabamba',
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
            'deposito' => $ci.'-VOUCHER',
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);
    }
}
