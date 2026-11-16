<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class HomeDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_dashboard_shows_enrollment_counts_by_career_parallel_and_shift(): void
    {
        $fixture = $this->crearDatos();

        $response = $this->actingAs($fixture['admin'])->get(route('reportes.dashboard'));

        $response->assertOk()
            ->assertSee('Reporte estadístico académico por carrera')
            ->assertSee('Sistemas Demo · R.M. 210/2026')
            ->assertSee('Exportar Excel')
            ->assertSee('Aprobados')
            ->assertSee('Reprobados')
            ->assertSee('Abandono')
            ->assertSee('Efectivos')
            ->assertSee('Todas las carreras')
            ->assertDontSee('Estudiante Activo');

        $this->assertSame(4, $response->viewData('estadisticas')['inscripciones']);
        $this->assertSame(2, $response->viewData('estadisticas')['carreras']);
        $this->assertSame(4, $response->viewData('estadisticas')['combinaciones']);

        $detalles = $response->viewData('filasTabla')->where('tipo_fila', 'detalle');
        $nombreSistemas = 'Sistemas Demo · R.M. 210/2026';
        $turnoNoche = $detalles->first(fn (object $fila): bool => $fila->carrera === $nombreSistemas && $fila->turno === 'Noche');
        $this->assertSame('A', $turnoNoche->paralelo);
        $this->assertSame(1, $turnoNoche->inscritos_v);
        $this->assertSame(0, $turnoNoche->inscritos_m);
        $this->assertSame(1, $turnoNoche->inscritos_t);

        $turnoManana = $detalles->first(fn (object $fila): bool => $fila->carrera === $nombreSistemas && $fila->turno === 'Mañana');
        $this->assertSame('A', $turnoManana->paralelo);
        $this->assertSame(0, $turnoManana->inscritos_v);
        $this->assertSame(1, $turnoManana->inscritos_m);
        $this->assertSame(1, $turnoManana->inscritos_t);

        $turnoTarde = $detalles->first(fn (object $fila): bool => $fila->carrera === $nombreSistemas && $fila->turno === 'Tarde');
        $this->assertSame('B', $turnoTarde->paralelo);
        $this->assertSame(1, $turnoTarde->inscritos_v);
        $this->assertSame(1, $turnoTarde->inscritos_t);

        $filtered = $this->actingAs($fixture['admin'])->get(route('reportes.dashboard', [
            'id_gestionacademica' => $fixture['gestionActual']->id_gestionacademica,
            'id_carrera' => $fixture['sistemas']->id_carrera,
        ]));
        $filtered->assertOk()->assertSee($nombreSistemas);
        $this->assertSame(
            3,
            $filtered->viewData('filasTabla')->where('tipo_fila', 'detalle')->count()
        );
    }

    public function test_excel_export_includes_a_career_table_for_each_management(): void
    {
        $fixture = $this->crearDatos();

        $response = $this->actingAs($fixture['admin'])->get(route('reportes.dashboard', [
            'formato' => 'excel',
            'id_carrera' => 99999,
        ]));

        $response->assertOk()->assertDownload('reporte-estadistico-academico.xlsx');
        $content = $response->streamedContent();
        $this->assertStringStartsWith('PK', $content);

        $tempPath = tempnam(sys_get_temp_dir(), 'home-dashboard-');
        $this->assertNotFalse($tempPath);
        file_put_contents($tempPath, $content);

        try {
            $workbook = IOFactory::load($tempPath);
            $this->assertSame(['G2025-P1', 'G2026-P1'], $workbook->getSheetNames());
            $sheet = $workbook->getSheetByName('G2026-P1');
            $this->assertSame('CARRERA', $sheet->getCell('A4')->getValue());
            $this->assertSame('INSCRITOS', $sheet->getCell('E4')->getValue());
            $this->assertSame('APROBADOS', $sheet->getCell('H4')->getValue());
            $this->assertSame('V', $sheet->getCell('E5')->getValue());
            $this->assertSame('M', $sheet->getCell('F5')->getValue());
            $this->assertSame('T', $sheet->getCell('G5')->getValue());

            $filaNoche = null;
            for ($row = 6; $row <= $sheet->getHighestRow(); $row++) {
                if ($sheet->getCell('C'.$row)->getValue() === 'A' && $sheet->getCell('D'.$row)->getValue() === 'Noche') {
                    $filaNoche = $row;
                    break;
                }
            }

            $this->assertNotNull($filaNoche);
            $this->assertSame(1, $sheet->getCell('E'.$filaNoche)->getValue());
            $this->assertSame(0, $sheet->getCell('F'.$filaNoche)->getValue());
            $this->assertSame(1, $sheet->getCell('G'.$filaNoche)->getValue());
            $this->assertNull($sheet->getCell('H'.$filaNoche)->getValue());
            $this->assertSame('TOTAL INSCRITOS', $sheet->getCell('A'.$sheet->getHighestRow())->getValue());
            $workbook->disconnectWorksheets();
        } finally {
            unlink($tempPath);
        }
    }

    public function test_student_home_does_not_expose_the_administrative_dashboard(): void
    {
        $this->crearPersona('DASH-CI-ADMIN', 'Admin', 'Dashboard', 'admin');
        $student = $this->crearPersona('DASH-CI-003', 'Estudiante Portal', 'Prueba', 'estudiante');
        User::create([
            'id_persona' => $student->id_persona,
            'usuario' => 'dashboard-student',
            'password' => 'password',
        ]);
        DB::table('estudiante')->insert([
            'id_persona' => $student->id_persona,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail($student->id_persona))
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Panel de gestión académica del instituto.')
            ->assertDontSee('Resumen del proceso de inscripción');

        $this->get(route('reportes.dashboard'))->assertForbidden();
    }

    public function test_main_dashboard_uses_simple_adminlte_components_without_report_tables(): void
    {
        $fixture = $this->crearDatos();

        $this->actingAs($fixture['admin'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Inicio')
            ->assertSee('fa-home', false)
            ->assertSee('Inscripciones por carrera')
            ->assertSee('Inscripciones activas')
            ->assertSee('Postulantes')
            ->assertDontSee('Reporte estadístico académico por carrera')
            ->assertDontSee('Aprobados')
            ->assertDontSee('Exportar Excel')
            ->assertDontSee('Estudiante Activo');
    }

    private function crearDatos(): array
    {
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto Dashboard']);
        $sistemas = Carrera::create([
            'codigo' => 'SIS-DASH',
            'nombre' => 'Sistemas Demo',
            'resolucionministerial1' => 'R.M. N° 210/2026',
            'activo' => true,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'id_instituto' => $instituto->id_instituto,
        ]);
        $administracion = Carrera::create([
            'codigo' => 'ADM-DASH',
            'nombre' => 'Administración Demo',
            'activo' => true,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'id_instituto' => $instituto->id_instituto,
        ]);
        $gestionAnterior = $this->crearGestion(2025, false);
        $gestionActual = $this->crearGestion(2026, true);

        $adminPersona = $this->crearPersona('DASH-CI-ADMIN', 'Admin', 'Dashboard', 'admin');
        $admin = User::create([
            'id_persona' => $adminPersona->id_persona,
            'usuario' => 'dashboard-admin',
            'password' => 'password',
        ]);
        $estudianteActual = $this->crearPersona('DASH-CI-001', 'Estudiante Activo', 'Prueba', 'estudiante', 'M');
        $estudianteFemenina = $this->crearPersona('DASH-CI-004', 'Estudiante Femenina', 'Prueba', 'estudiante', 'F');
        $estudianteParaleloB = $this->crearPersona('DASH-CI-005', 'Estudiante Paralelo B', 'Prueba', 'estudiante', 'M');
        $estudianteHistorico = $this->crearPersona('DASH-CI-002', 'Estudiante Histórico', 'Prueba', 'estudiante', 'F');
        $inscritoActual = $this->crearInscripcion($estudianteActual, $sistemas, $gestionActual, true);
        $this->crearInscripcion($estudianteActual, $administracion, $gestionActual, true);
        $inscritoFemenina = $this->crearInscripcion($estudianteFemenina, $sistemas, $gestionActual, true);
        $inscritoParaleloB = $this->crearInscripcion($estudianteParaleloB, $sistemas, $gestionActual, true);
        $this->crearInscripcion($estudianteHistorico, $administracion, $gestionAnterior, true);

        DB::table('estudiante')->insert([
            [
                'id_persona' => $estudianteActual->id_persona,
                'path_bth' => 'demo/bth.jpg',
                'traspaso_convalidacion' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_persona' => $estudianteHistorico->id_persona,
                'path_bth' => null,
                'traspaso_convalidacion' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $materiaNoche = $this->crearMateriaGrupo($sistemas, $gestionActual, 'NOCHE', 3);
        $materiaManana = $this->crearMateriaGrupo($sistemas, $gestionActual, 'MANANA', 1);
        $materiaTarde = $this->crearMateriaGrupo($sistemas, $gestionActual, 'TARDE', 2, 'B');
        DB::table('inscrito_materia')->insert([
            [
                'id_inscrito' => $inscritoActual,
                'id_materia' => $materiaNoche['materia'],
                'id_grupo' => $materiaNoche['grupo'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_inscrito' => $inscritoFemenina,
                'id_materia' => $materiaManana['materia'],
                'id_grupo' => $materiaManana['grupo'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_inscrito' => $inscritoParaleloB,
                'id_materia' => $materiaTarde['materia'],
                'id_grupo' => $materiaTarde['grupo'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->crearPostulante($sistemas, $gestionActual, 'POST-DASH-001', 'DASH-POS-001');
        $this->crearPostulante($administracion, $gestionAnterior, 'POST-DASH-002', 'DASH-POS-002');

        return compact('admin', 'sistemas', 'gestionActual');
    }

    private function crearGestion(int $year, bool $vigente): GestionAcademica
    {
        return GestionAcademica::create([
            'gestion' => $year,
            'periodo' => 1,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'inicio' => $year.'-01-01',
            'fin' => $year.'-12-31',
            'vigente' => $vigente,
            'activo' => true,
            'modalidadevaluacion' => 1,
        ]);
    }

    private function crearPersona(
        string $ci,
        string $nombre,
        string $apellido,
        string $tipo = 'estudiante',
        string $sexo = 'M'
    ): Persona {
        return Persona::create([
            'nombre' => $nombre,
            'primerapellido' => $apellido,
            'segundoapellido' => 'Dashboard',
            'ci' => $ci,
            'tipo' => $tipo,
            'sexo' => $sexo,
        ]);
    }

    private function crearInscripcion(Persona $persona, Carrera $carrera, GestionAcademica $gestion, bool $activo): int
    {
        $idInscrito = DB::table('inscrito')->insertGetId([
            'codigo' => $gestion->gestion * 10000 + $persona->id_persona + $carrera->id_carrera,
            'fecha' => $gestion->gestion.'-03-10',
            'numero' => 1,
            'tipo' => $gestion->gestion === 2026 ? 2 : 1,
            'becado' => false,
            'activo' => $activo,
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $persona->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_inscrito');

        DB::table('cursa')->insert([
            'id_persona' => $persona->id_persona,
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'nivelinicio' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $idInscrito;
    }

    private function crearMateriaGrupo(
        Carrera $carrera,
        GestionAcademica $gestion,
        string $suffix,
        int $turno,
        string $paralelo = 'A'
    ): array {
        $materia = DB::table('materia')->insertGetId([
            'codigo' => 'SIS-DASH-'.$suffix,
            'nombre' => 'Materia '.$suffix,
            'nivel' => 2,
            'activo' => true,
            'id_carrera' => $carrera->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $grupo = DB::table('grupo')->insertGetId([
            'codigo' => 'SIS-DASH-'.$suffix.'-'.$paralelo,
            'turno' => $turno,
            'capacidad' => 30,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materia,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return compact('materia', 'grupo');
    }

    private function crearPostulante(Carrera $carrera, GestionAcademica $gestion, string $codigo, string $ci): void
    {
        DB::table('postulante')->insert([
            'codigo' => $codigo,
            'ci' => $ci,
            'primerapellido' => 'Postulante',
            'segundoapellido' => 'Dashboard',
            'nombre' => 'Pendiente Demo',
            'celular' => '70000001',
            'email' => strtolower($codigo).'@example.test',
            'direccion' => 'Dirección de prueba',
            'fechanacimiento' => '2005-01-01',
            'lugarnacimiento' => 'Sacaba',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'nombrecolegio' => 'Unidad Educativa Demo',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'nombrecontacto' => 'Contacto Demo',
            'parentescocontacto' => 'Madre',
            'celularcontacto' => '70000002',
            'deposito' => 'DEPOSITO-'.$codigo,
            'path_ci_frontal' => 'ci-frontal.jpg',
            'path_ci_reverso' => 'ci-reverso.jpg',
            'path_nacimiento' => 'nacimiento.jpg',
            'path_diploma' => 'diploma.jpg',
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
