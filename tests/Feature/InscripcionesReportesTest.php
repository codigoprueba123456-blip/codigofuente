<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InscripcionesReportesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->withViewErrors([]);
    }

    public function test_all_seven_inscription_report_pages_are_available(): void
    {
        foreach ([
            'lista-postulantes',
            'lista-inscritos-carrera',
            'lista-inscritos-paralelo',
            'lista-inscritos-grupo',
            'libro-inscripciones',
            'libro-inscripciones-carrera',
            'lista-inscritos-multicarrera',
        ] as $reporte) {
            $this->get(route('reportes.inscripciones.index', ['reporte' => $reporte]))
                ->assertOk()
                ->assertSee('Generar reporte');
        }
    }

    public function test_applicant_report_filters_records_and_exports_pdf_and_excel(): void
    {
        $fixture = $this->createReportFixture();
        $parameters = [
            'generar' => 1,
            'id_gestionacademica' => $fixture['gestion']->id_gestionacademica,
            'id_carrera' => $fixture['carrera']->id_carrera,
        ];

        $result = $this->get(route('reportes.inscripciones.index', ['reporte' => 'lista-postulantes'] + $parameters));

        $result->assertOk()
            ->assertSee('Postulante de Prueba')
            ->assertSee('Inscrito como estudiante')
            ->assertSee('Exportar Excel')
            ->assertSee('Exportar PDF');
        $this->assertCount(1, $result->viewData('filas'));
        $this->assertSame('Inscrito como estudiante', $result->viewData('filas')->first()->estado);
        $this->assertSame(2026, $result->viewData('filas')->first()->gestion);

        $excel = $this->get(route('reportes.inscripciones.index', [
            'reporte' => 'lista-postulantes',
            ...$parameters,
            'formato' => 'excel',
        ]));
        $excel->assertOk()->assertDownload();

        $pdf = $this->get(route('reportes.inscripciones.index', [
            'reporte' => 'lista-postulantes',
            ...$parameters,
            'formato' => 'pdf',
        ]));
        $pdf->assertOk()->assertDownload();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
    }

    public function test_enrollment_reports_apply_career_group_period_and_multicareer_filters(): void
    {
        $fixture = $this->createReportFixture();

        $filters = [
            'id_gestionacademica' => $fixture['gestion']->id_gestionacademica,
            'id_carrera' => $fixture['carrera']->id_carrera,
        ];

        $careerReport = $this->get(route('reportes.inscripciones.index', [
            'reporte' => 'lista-inscritos-carrera',
            'generar' => 1,
            ...$filters,
        ]));
        $careerReport->assertOk()->assertSee('Ana')->assertSee('Estudiante');
        $this->assertCount(1, $careerReport->viewData('filas'));

        $parallelReport = $this->get(route('reportes.inscripciones.index', [
            'reporte' => 'lista-inscritos-paralelo',
            'generar' => 1,
            ...$filters,
            'nivel' => 1,
            'turno' => 1,
            'paralelo' => 'A',
        ]));
        $parallelReport->assertOk()->assertSee('Materia de Prueba');
        $this->assertCount(1, $parallelReport->viewData('filas'));

        $groupReport = $this->get(route('reportes.inscripciones.index', [
            'reporte' => 'lista-inscritos-grupo',
            'generar' => 1,
            ...$filters,
            'nivel' => 1,
            'id_grupo' => $fixture['grupo'],
        ]));
        $groupReport->assertOk()->assertSee('Materia de Prueba');
        $this->assertCount(1, $groupReport->viewData('filas'));

        $bookReport = $this->get(route('reportes.inscripciones.index', [
            'reporte' => 'libro-inscripciones',
            'generar' => 1,
            'gestion' => $fixture['gestion']->gestion,
            'periodo' => $fixture['gestion']->periodo,
        ]));
        $bookReport->assertOk()->assertSee('Ana')->assertSee('Estudiante');
        $this->assertCount(2, $bookReport->viewData('filas'));

        $multicarreraReport = $this->get(route('reportes.inscripciones.index', [
            'reporte' => 'lista-inscritos-multicarrera',
            'generar' => 1,
            'id_gestionacademica' => $fixture['gestion']->id_gestionacademica,
        ]));
        $multicarreraReport->assertOk()->assertSee('Ana')->assertSee('Estudiante');
        $this->assertCount(2, $multicarreraReport->viewData('filas'));
    }

    public function test_all_seven_reports_export_excel_and_pdf(): void
    {
        $fixture = $this->createReportFixture();
        $gestionId = $fixture['gestion']->id_gestionacademica;
        $carreraId = $fixture['carrera']->id_carrera;
        $filtrosPorReporte = [
            'lista-postulantes' => ['id_gestionacademica' => $gestionId, 'id_carrera' => $carreraId],
            'lista-inscritos-carrera' => ['id_gestionacademica' => $gestionId, 'id_carrera' => $carreraId],
            'lista-inscritos-paralelo' => [
                'id_gestionacademica' => $gestionId,
                'id_carrera' => $carreraId,
                'nivel' => 1,
                'turno' => 1,
                'paralelo' => 'A',
            ],
            'lista-inscritos-grupo' => [
                'id_gestionacademica' => $gestionId,
                'id_carrera' => $carreraId,
                'nivel' => 1,
                'id_grupo' => $fixture['grupo'],
            ],
            'libro-inscripciones' => ['gestion' => 2026, 'periodo' => 1],
            'libro-inscripciones-carrera' => ['id_gestionacademica' => $gestionId, 'id_carrera' => $carreraId],
            'lista-inscritos-multicarrera' => ['id_gestionacademica' => $gestionId],
        ];

        foreach ($filtrosPorReporte as $reporte => $filtros) {
            $excel = $this->get(route('reportes.inscripciones.index', [
                'reporte' => $reporte,
                'generar' => 1,
                ...$filtros,
                'formato' => 'excel',
            ]));
            $excel->assertOk()->assertDownload();
            $this->assertStringStartsWith('PK', $excel->streamedContent(), 'XLSX inválido para '.$reporte);

            $pdf = $this->get(route('reportes.inscripciones.index', [
                'reporte' => $reporte,
                'generar' => 1,
                ...$filtros,
                'formato' => 'pdf',
            ]));
            $pdf->assertOk()->assertDownload();
            $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
            $this->assertStringStartsWith('%PDF-', $pdf->getContent(), 'PDF inválido para '.$reporte);
        }
    }

    private function createReportFixture(): array
    {
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'SIS-REPORTES',
            'nombre' => 'Sistemas de Prueba',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $otraCarrera = Carrera::create([
            'codigo' => 'ADM-REPORTES',
            'nombre' => 'Administración de Prueba',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $persona = Persona::create([
            'nombre' => 'Ana',
            'primerapellido' => 'Estudiante',
            'segundoapellido' => 'Prueba',
            'ci' => 'CI-REPORTES-1',
            'tipo' => 'estudiante',
        ]);
        $inscrito = DB::table('inscrito')->insertGetId([
            'codigo' => 20260001,
            'fecha' => '2026-03-10',
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $persona->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inscrito')->insert([
            'codigo' => 20260002,
            'fecha' => '2026-03-11',
            'id_carrera' => $otraCarrera->id_carrera,
            'id_persona' => $persona->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $materia = DB::table('materia')->insertGetId([
            'codigo' => 'MAT-REPORTES',
            'nombre' => 'Materia de Prueba',
            'nivel' => 1,
            'id_carrera' => $carrera->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $grupo = DB::table('grupo')->insertGetId([
            'codigo' => 'A',
            'turno' => 1,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materia,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inscrito_materia')->insert([
            'id_inscrito' => $inscrito,
            'id_materia' => $materia,
            'id_grupo' => $grupo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $postulanteId = DB::table('postulante')->insertGetId([
            'codigo' => 'POST-REPORTES-1',
            'ci' => 'CI-POSTULANTE-1',
            'primerapellido' => 'Prueba',
            'segundoapellido' => 'Postulante',
            'nombre' => 'Postulante de Prueba',
            'celular' => '70000000',
            'email' => 'postulante@example.test',
            'direccion' => 'Dirección de prueba',
            'fechanacimiento' => '2005-01-01',
            'lugarnacimiento' => 'Sacaba',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'nombrecolegio' => 'Colegio de Prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'nombrecontacto' => 'Contacto de Prueba',
            'parentescocontacto' => 'Madre',
            'celularcontacto' => '70000001',
            'deposito' => 'DEP-REPORTES-1',
            'path_ci_frontal' => 'ci-frontal.jpg',
            'path_ci_reverso' => 'ci-reverso.jpg',
            'path_nacimiento' => 'nacimiento.jpg',
            'path_diploma' => 'diploma.jpg',
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inscrito')
            ->where('id_inscrito', $inscrito)
            ->update(['id_postulante_origen' => $postulanteId]);

        return compact('carrera', 'gestion', 'grupo');
    }
}
