<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Instituto;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\Postulante;
use App\Models\User;
use Database\Seeders\Modulos\EstudiantesCasosPruebaSeeder;
use Database\Seeders\Modulos\SistemasInformaticasMateriasDemoSeeder;
use Database\Seeders\Modulos\VincularDocumentosIdentidadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\OpensPublicPostulationWindow;
use Tests\TestCase;

class EstudiantesCasosPruebaSeederTest extends TestCase
{
    use OpensPublicPostulationWindow;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->crearCatalogoDemo();
    }

    public function test_demo_seeder_creates_repeatable_applicants_students_and_shared_documents(): void
    {
        $this->seed(EstudiantesCasosPruebaSeeder::class);
        $this->seed(EstudiantesCasosPruebaSeeder::class);

        $gestionActual = GestionAcademica::where('gestion', 2026)->firstOrFail();
        $gestionHistorica = GestionAcademica::where('gestion', 2024)->firstOrFail();

        $this->assertDatabaseCount('estudiante', 12);
        $this->assertDatabaseCount('personas', 12);
        $this->assertDatabaseCount('users', 12);
        $this->assertDatabaseCount('cuenta', 12);
        $this->assertDatabaseCount('postulante', 29);
        $this->assertSame(13, Postulante::where('id_gestionacademica', $gestionActual->id_gestionacademica)->count());
        $this->assertSame(16, Postulante::where('id_gestionacademica', $gestionHistorica->id_gestionacademica)->count());
        $this->assertSame(12, DB::table('inscrito')->where('id_gestionacademica', $gestionActual->id_gestionacademica)->where('activo', true)->count());
        $this->assertSame(10, DB::table('inscrito')->where('id_gestionacademica', $gestionActual->id_gestionacademica)->where('activo', true)->distinct()->count('id_persona'));
        $this->assertSame(14, DB::table('inscrito')->where('id_gestionacademica', $gestionHistorica->id_gestionacademica)->count());
        $this->assertSame(12, DB::table('grupo')->where('id_gestionacademica', $gestionActual->id_gestionacademica)->count());
        $this->assertSame(12, DB::table('inscrito_materia')->count());
        $this->assertSame(2, DB::table('estudiante')->whereNotNull('path_bth')->count());
        $this->assertSame(4, DB::table('estudiante')->where('traspaso_convalidacion', true)->count());
        $this->assertFalse((bool) $gestionHistorica->activo);

        $asignacionesPorTurno = DB::table('inscrito_materia as im')
            ->join('inscrito as i', 'i.id_inscrito', '=', 'im.id_inscrito')
            ->join('grupo as g', 'g.id_grupo', '=', 'im.id_grupo')
            ->where('i.id_gestionacademica', $gestionActual->id_gestionacademica)
            ->select('g.turno', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('g.turno')
            ->pluck('cantidad', 'turno');
        $this->assertSame(12, (int) $asignacionesPorTurno[3]);

        $postulanteHistorico = Postulante::where('ci', 'DEMO-POST-009')->firstOrFail();
        $this->assertDatabaseMissing('personas', ['ci' => $postulanteHistorico->ci]);
        $this->assertDatabaseMissing('inscrito', ['id_postulante_origen' => $postulanteHistorico->id_postulante]);

        $estudianteFinalizado = Persona::where('ci', 'DEMO-EST-002')->firstOrFail();
        $this->assertDatabaseHas('cursa', [
            'id_persona' => $estudianteFinalizado->id_persona,
            'id_gestionacademica' => $gestionHistorica->id_gestionacademica,
            'nivelinicio' => 3,
        ]);
        $this->assertDatabaseHas('inscrito', [
            'id_persona' => $estudianteFinalizado->id_persona,
            'id_gestionacademica' => $gestionHistorica->id_gestionacademica,
            'activo' => false,
        ]);

        $personaDemo = Persona::where('ci', 'DEMO-EST-001')->firstOrFail();
        $cuentaDemo = User::where('id_persona', $personaDemo->id_persona)->firstOrFail();
        $this->assertMatchesRegularExpression('/^' . date('Y') . '\d{4,}$/', $cuentaDemo->usuario);
        $this->assertTrue(Hash::check('DemoEstudiante2026!', $cuentaDemo->password));
        $this->assertDatabaseHas('cuenta', [
            'id_persona' => $personaDemo->id_persona,
            'usuario' => $cuentaDemo->usuario,
        ]);

        foreach ([
            'demo/inscripciones/documentos/carnet1.jpeg',
            'demo/inscripciones/documentos/carnet2.jpeg',
            'demo/inscripciones/documentos/certificado_de_nacimiento.webp',
            'demo/inscripciones/documentos/diploma.webp',
        ] as $ruta) {
            $this->assertTrue(Storage::disk('public')->exists($ruta));
        }
    }

    public function test_temporary_document_seeder_fills_missing_documents_without_overwriting_valid_files(): void
    {
        $this->seed(EstudiantesCasosPruebaSeeder::class);

        $estudiante = DB::table('estudiante')->first();
        $postulante = Postulante::firstOrFail();
        Storage::disk('public')->put('documentos/ci-real.jpg', 'documento existente');

        DB::table('estudiante')
            ->where('id_persona', $estudiante->id_persona)
            ->update([
                'path_ci' => 'documentos/ci-real.jpg',
                'path_ci_reverso' => 'documentos/no-encontrado.jpg',
                'path_nacimiento' => 'documentos/nacimiento-no-encontrado.jpg',
                'path_diploma' => 'documentos/diploma-no-encontrado.jpg',
            ]);
        DB::table('postulante')
            ->where('id_postulante', $postulante->id_postulante)
            ->update([
                'path_ci_frontal' => 'demo/postulante-ci-inexistente.jpg',
                'path_ci_reverso' => 'demo/postulante-reverso-inexistente.jpg',
                'path_nacimiento' => 'demo/postulante-nacimiento-inexistente.jpg',
                'path_diploma' => 'demo/postulante-diploma-inexistente.jpg',
            ]);

        $this->seed(VincularDocumentosIdentidadSeeder::class);

        $this->assertDatabaseHas('estudiante', [
            'id_persona' => $estudiante->id_persona,
            'path_ci' => 'documentos/ci-real.jpg',
            'path_ci_reverso' => 'demo/inscripciones/documentos/carnet2.jpeg',
            'path_nacimiento' => 'demo/inscripciones/documentos/certificado_de_nacimiento.webp',
            'path_diploma' => 'demo/inscripciones/documentos/diploma.webp',
        ]);
        $this->assertDatabaseHas('postulante', [
            'id_postulante' => $postulante->id_postulante,
            'path_ci_frontal' => 'demo/inscripciones/documentos/carnet1.jpeg',
            'path_ci_reverso' => 'demo/inscripciones/documentos/carnet2.jpeg',
            'path_nacimiento' => 'demo/inscripciones/documentos/certificado_de_nacimiento.webp',
            'path_diploma' => 'demo/inscripciones/documentos/diploma.webp',
        ]);
        foreach ([
            'carnet1.jpeg',
            'carnet2.jpeg',
            'certificado_de_nacimiento.webp',
            'diploma.webp',
        ] as $nombre) {
            $this->assertTrue(Storage::disk('public')->exists('demo/inscripciones/documentos/'.$nombre));
        }
    }

    public function test_systems_demo_subject_seeder_fills_only_missing_levels_idempotently(): void
    {
        $carreraSistemas = Carrera::where('codigo', 'SIS-2023')->firstOrFail();
        Materia::where('id_carrera', $carreraSistemas->id_carrera)->delete();
        $materiaExistente = Materia::create([
            'codigo' => 'SIS-CURRICULO-2026-N2',
            'nombre' => 'Materia real existente',
            'activo' => true,
            'nivel' => 2,
            'numero' => 1,
            'id_carrera' => $carreraSistemas->id_carrera,
        ]);

        $this->seed(SistemasInformaticasMateriasDemoSeeder::class);
        $this->seed(SistemasInformaticasMateriasDemoSeeder::class);

        $materiasSistemas = Materia::where('id_carrera', $carreraSistemas->id_carrera)->get();
        $this->assertCount(2, $materiasSistemas);
        $this->assertSame('Materia real existente', $materiaExistente->fresh()->nombre);
        $this->assertDatabaseHas('materia', [
            'codigo' => 'SIS-DEMO-2026-N3-01',
            'nivel' => 3,
            'activo' => true,
            'id_carrera' => $carreraSistemas->id_carrera,
        ]);
        $this->assertDatabaseMissing('materia', [
            'codigo' => 'SIS-DEMO-2026-N2-01',
            'id_carrera' => $carreraSistemas->id_carrera,
        ]);
    }

    public function test_demo_subjects_allow_current_systems_students_to_be_assigned_to_groups(): void
    {
        $carreraSistemas = Carrera::where('codigo', 'SIS-2023')->firstOrFail();
        Materia::where('id_carrera', $carreraSistemas->id_carrera)->delete();

        $this->seed(SistemasInformaticasMateriasDemoSeeder::class);
        $this->seed(EstudiantesCasosPruebaSeeder::class);

        $materia = Materia::where('id_carrera', $carreraSistemas->id_carrera)
            ->where('nivel', 2)
            ->firstOrFail();
        $persona = Persona::where('ci', 'DEMO-EST-009')->firstOrFail();
        $inscrito = DB::table('inscrito')
            ->where('id_persona', $persona->id_persona)
            ->where('id_carrera', $carreraSistemas->id_carrera)
            ->where('id_gestionacademica', GestionAcademica::where('gestion', 2026)->value('id_gestionacademica'))
            ->where('activo', true)
            ->first();

        $this->assertNotNull($inscrito);
        $asignacion = DB::table('inscrito_materia')
            ->where('id_inscrito', $inscrito->id_inscrito)
            ->where('id_materia', $materia->id_materia)
            ->first();
        $this->assertNotNull($asignacion);

        $this->assertDatabaseHas('grupo', [
            'id_grupo' => $asignacion->id_grupo,
            'codigo' => $materia->codigo.'-A',
            'turno' => 3,
        ]);
    }

    public function test_demo_groups_keep_existing_catalog_groups_and_avoid_turn_conflicts(): void
    {
        $gestion = GestionAcademica::where('gestion', 2026)->firstOrFail();
        $carreraSecretariado = Carrera::where('codigo', 'SEJ-2023')->firstOrFail();
        $materia = Materia::where('id_carrera', $carreraSecretariado->id_carrera)
            ->where('nivel', 2)
            ->firstOrFail();
        $grupoCatalogo = Grupo::create([
            'codigo' => $materia->codigo.'-A',
            'capacidad' => 30,
            'turno' => 1,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_materia' => $materia->id_materia,
        ]);

        $this->seed(EstudiantesCasosPruebaSeeder::class);

        $grupoDemo = Grupo::where('codigo', 'DEMO-'.$materia->codigo.'-A')
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->firstOrFail();
        $this->assertSame(3, (int) $grupoDemo->turno);
        $this->assertSame(1, (int) $grupoCatalogo->fresh()->turno);

        $persona = Persona::where('ci', 'DEMO-EST-004')->firstOrFail();
        $inscrito = DB::table('inscrito')
            ->where('id_persona', $persona->id_persona)
            ->where('id_carrera', $carreraSecretariado->id_carrera)
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->where('activo', true)
            ->first();
        $this->assertNotNull($inscrito);
        $this->assertDatabaseHas('inscrito_materia', [
            'id_inscrito' => $inscrito->id_inscrito,
            'id_materia' => $materia->id_materia,
            'id_grupo' => $grupoDemo->id_grupo,
        ]);
    }

    public function test_missing_subjects_fail_before_creating_demo_roles_or_files(): void
    {
        $carreraSistemas = Carrera::where('codigo', 'SIS-2023')->firstOrFail();
        Materia::where('id_carrera', $carreraSistemas->id_carrera)->delete();

        try {
            $this->seed(EstudiantesCasosPruebaSeeder::class);
            $this->fail('Se esperaba un error antes de crear los datos demo.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('SistemasInformaticasMateriasDemoSeeder', $exception->getMessage());
        }

        $this->assertDatabaseMissing('rol', ['nombre' => 'Estudiante']);
        $this->assertFalse(Storage::disk('public')->exists('demo/inscripciones/documentos/carnet1.jpeg'));
    }

    public function test_student_cannot_reapply_to_a_career_already_enrolled_but_unenrolled_historical_applicant_can(): void
    {
        $this->seed(EstudiantesCasosPruebaSeeder::class);
        $gestionActual = GestionAcademica::where('gestion', 2026)->firstOrFail();
        $this->openPublicPostulationWindow($gestionActual);
        foreach (Carrera::whereIn('codigo', ['SIS-2023', 'ADM-2023', 'SEJ-2023'])->get() as $carrera) {
            $materia = Materia::where('id_carrera', $carrera->id_carrera)->firstOrFail();
            Grupo::create([
                'codigo' => $materia->codigo.'-A',
                'capacidad' => 30,
                'turno' => 3,
                'abierto' => true,
                'activo' => true,
                'id_gestionacademica' => $gestionActual->id_gestionacademica,
                'id_materia' => $materia->id_materia,
            ]);
        }

        $carreraSistemas = Carrera::where('codigo', 'SIS-2023')->firstOrFail();

        $formularioPublico = $this->get(route('postulantes.registrar', ['ci_consulta' => 'DEMO-EST-002']))
            ->assertOk()
            ->assertSee('Las carreras que ya forman parte de tu historial de estudiante no aparecen en esta lista.')
            ->getContent();
        preg_match('/<select\b[^>]*id="id_carrera"[^>]*>(.*?)<\/select>/s', $formularioPublico, $opcionesCarrera);
        $this->assertCount(2, $opcionesCarrera);
        $this->assertDoesNotMatchRegularExpression(
            '/<option\b[^>]*value="'.preg_quote((string) $carreraSistemas->id_carrera, '/').'"/',
            $opcionesCarrera[1]
        );
        $this->assertMatchesRegularExpression(
            '/<option\b[^>]*value="'.preg_quote((string) Carrera::where('codigo', 'ADM-2023')->value('id_carrera'), '/').'"/',
            $opcionesCarrera[1]
        );

        $respuestaHistorialExistente = $this->post(route('postulantes.store'), [
            'ci' => 'DEMO-EST-002',
            'id_carrera' => $carreraSistemas->id_carrera,
        ]);
        $respuestaHistorialExistente->assertSessionHasErrors('id_carrera');
        $mensajeHistorial = session('errors')->getBag('default')->first('id_carrera');
        $this->assertStringContainsString('historial de inscripción', $mensajeHistorial);

        $this->post(route('postulantes.store'), [
            'ci' => 'DEMO-EST-009',
            'id_carrera' => Carrera::where('codigo', 'ADM-2023')->value('id_carrera'),
        ])->assertSessionHasErrors('id_carrera');

        $respuestaNuevaPostulacion = $this->post(route('postulantes.store'), [
            'nombre' => 'Natalia Fernanda',
            'primerapellido' => 'Quiroga',
            'segundoapellido' => 'Mercado',
            'ci' => 'DEMO-POST-009',
            'lugarexpedicion' => 'CB',
            'fechanacimiento' => '2002-11-11',
            'lugarnacimiento' => 'Cochabamba, Bolivia',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'direccion' => 'Zona Central, Cochabamba',
            'telefono' => '4400009',
            'celular' => '70000009',
            'email' => 'post-09@example.test',
            'nombrecontacto' => 'Silvia Mercado',
            'celularcontacto' => '70000009',
            'parentescocontacto' => 'Madre',
            'nombrecolegio' => 'Unidad Educativa Demo Norte',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2020,
            'fecha' => now()->toDateString(),
            'diplomabachiller' => true,
            'id_carrera' => $carreraSistemas->id_carrera,
            'turno' => 3,
            'paralelo' => 'A',
            'deposito' => 'DEMO-REAPPLY-2026-009',
        ]);
        $postulacionNueva = Postulante::where('ci', 'DEMO-POST-009')
            ->where('id_carrera', $carreraSistemas->id_carrera)
            ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
            ->firstOrFail();
        $respuestaNuevaPostulacion->assertRedirect(route('postulantes.pdf', $postulacionNueva->id_postulante));

        $this->assertSame($carreraSistemas->id_carrera, $postulacionNueva->id_carrera);
        $this->assertSame($gestionActual->id_gestionacademica, $postulacionNueva->id_gestionacademica);
        $this->assertSame(2, Postulante::where('ci', 'DEMO-POST-009')->count());
        $this->assertFalse(Persona::where('ci', 'DEMO-POST-009')->exists());
    }

    private function crearCatalogoDemo(): void
    {
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Demostración']);

        foreach ([
            'SIS-2023' => 'SISTEMAS INFORMATICOS',
            'ADM-2023' => 'ADMINISTRACION DE EMPRESAS',
            'SEJ-2023' => 'SECRETARIADO EJECUTIVO',
        ] as $codigo => $nombre) {
            $carrera = Carrera::create([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'activo' => true,
                'regimen' => Carrera::REGIMEN_ANUAL,
                'version' => 2023,
                'id_instituto' => $instituto->id_instituto,
            ]);

            foreach ([1, 2, 3] as $nivel) {
                Materia::create([
                    'codigo' => $codigo.'-M'.$nivel,
                    'nombre' => "Materia {$nivel} de {$nombre}",
                    'activo' => true,
                    'nivel' => $nivel,
                    'numero' => 1,
                    'id_carrera' => $carrera->id_carrera,
                ]);
            }
        }

        GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'inicio' => '2026-01-01',
            'fin' => '2026-12-31',
            'vigente' => true,
            'activo' => true,
            'modalidadevaluacion' => 1,
        ]);
    }
}
