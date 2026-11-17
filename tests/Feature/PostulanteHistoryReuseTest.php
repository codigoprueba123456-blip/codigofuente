<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureFunctionalityAccess;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\Postulante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Tests\Support\OpensPublicPostulationWindow;
use Tests\TestCase;

class PostulanteHistoryReuseTest extends TestCase
{
    use OpensPublicPostulationWindow;
    use RefreshDatabase;

    public function test_admin_postulation_search_does_not_fall_back_to_an_inactive_management(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'ADMIN-HIST-001',
            'tipo' => 'admin',
        ]);
        $this->actingAs(User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_historial',
            'password' => Hash::make('password123'),
        ]));

        $gestionAnterior = GestionAcademica::create([
            'gestion' => now()->year - 1,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => false,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'HIST-OLD',
            'nombre' => 'Carrera Histórica',
            'id_instituto' => $instituto->id_instituto,
        ]);
        Postulante::create([
            'codigo' => 'HIST-OLD-001',
            'ci' => 'HIST-OLD-001',
            'primerapellido' => 'Quispe',
            'nombre' => 'Valeria',
            'celular' => '70000001',
            'email' => 'valeria@example.test',
            'direccion' => 'El Alto, La Paz',
            'fechanacimiento' => '2005-04-15',
            'lugarnacimiento' => 'El Alto',
            'lugarexpedicion' => 'LP',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'foto' => 'postulantes/historico/foto.jpg',
            'fecha' => now(),
            'nombrecolegio' => 'U.E. de Prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'diplomabachiller' => true,
            'nombrecontacto' => 'Rosa Mamani',
            'parentescocontacto' => 'Madre',
            'celularcontacto' => '70000002',
            'deposito' => 'HIST-OLD-VOUCHER',
            'path_ci_frontal' => 'postulantes/historico/ci-frontal.jpg',
            'path_ci_reverso' => 'postulantes/historico/ci-reverso.jpg',
            'path_nacimiento' => 'postulantes/historico/nacimiento.jpg',
            'path_diploma' => 'postulantes/historico/diploma.jpg',
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestionAnterior->id_gestionacademica,
        ]);

        $this->get(route('estudiante_nuevo_postulacion.index'))
            ->assertOk()
            ->assertSee('Sin gestión disponible')
            ->assertDontSee('HIST-OLD-001');

        $this->get(route('estudiante_nuevo_postulacion.buscar', ['q' => 'HIST-OLD-001']))
            ->assertOk()
            ->assertJsonCount(0, 'postulantes');
    }

    public function test_new_management_reuses_historical_data_and_keeps_reuploaded_photo_separate(): void
    {
        Storage::fake('public');

        $gestionAnterior = GestionAcademica::create([
            'gestion' => now()->year - 1,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => false,
        ]);
        $gestionActual = GestionAcademica::create([
            'gestion' => now()->year,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $this->openPublicPostulationWindow($gestionActual);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carreraAnterior = Carrera::create([
            'codigo' => 'HIST-SIS',
            'nombre' => 'Sistemas Histórica',
            'resolucionministerial1' => 'R.M. N° 210/2024',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $carreraActual = Carrera::create([
            'codigo' => 'HIST-GAS',
            'nombre' => 'Gastronomía Vigente',
            'resolucionministerial1' => 'R.M. N° 145/2025',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $idMateria = DB::table('materia')->insertGetId([
            'codigo' => 'HIST-GAS-MAT',
            'nombre' => 'Materia de Gastronomía',
            'id_carrera' => $carreraActual->id_carrera,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('grupo')->insert([
            'codigo' => 'HIST-GAS-MAT-A',
            'capacidad' => 30,
            'turno' => 1,
            'abierto' => true,
            'activo' => true,
            'id_gestionacademica' => $gestionActual->id_gestionacademica,
            'id_materia' => $idMateria,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $postulanteAnterior = Postulante::create([
            'codigo' => 'HIST-POST-001',
            'ci' => 'HIST-001',
            'primerapellido' => 'Quispe',
            'segundoapellido' => 'Mamani',
            'nombre' => 'Valeria',
            'celular' => '70000001',
            'telefono' => '2000001',
            'email' => 'valeria@example.test',
            'direccion' => 'El Alto, La Paz',
            'fechanacimiento' => '2005-04-15',
            'lugarnacimiento' => 'El Alto',
            'lugarexpedicion' => 'LP',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'foto' => 'postulantes/historico/foto.jpg',
            'fecha' => now(),
            'nombrecolegio' => 'U.E. de Prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'diplomabachiller' => true,
            'nombrecontacto' => 'Rosa Mamani',
            'parentescocontacto' => 'Madre',
            'celularcontacto' => '70000002',
            'deposito' => 'HIST-VOUCHER-001',
            'path_ci_frontal' => 'postulantes/historico/ci-frontal.jpg',
            'path_ci_reverso' => 'postulantes/historico/ci-reverso.jpg',
            'path_nacimiento' => 'postulantes/historico/nacimiento.jpg',
            'path_diploma' => 'postulantes/historico/diploma.jpg',
            'id_carrera' => $carreraAnterior->id_carrera,
            'id_gestionacademica' => $gestionAnterior->id_gestionacademica,
        ]);

        $this->get(route('postulantes.bienvenida', ['ci_consulta' => 'HIST-001']))
            ->assertOk()
            ->assertSee('HIST-001');
        $this->get(route('postulantes.registrar', ['ci_consulta' => 'HIST-001']))
            ->assertOk()
            ->assertSee('Nueva postulación en la gestión vigente')
            ->assertSee('value="Valeria"', false)
            ->assertSee('value="HIST-001"', false)
            ->assertSee('value="U.E. de Prueba"', false)
            ->assertSee('storage/postulantes/historico/foto.jpg', false)
            ->assertSee('storage/postulantes/historico/ci-frontal.jpg', false)
            ->assertSee('storage/postulantes/historico/ci-reverso.jpg', false)
            ->assertSee('storage/postulantes/historico/nacimiento.jpg', false)
            ->assertSee('storage/postulantes/historico/diploma.jpg', false);

        $this->post(route('postulantes.store'), [
            'nombre' => 'Valeria',
            'primerapellido' => 'Quispe',
            'segundoapellido' => 'Mamani',
            'ci' => 'HIST-001',
            'lugarexpedicion' => 'LP',
            'fechanacimiento' => '2005-04-15',
            'lugarnacimiento' => 'El Alto',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'direccion' => 'El Alto, La Paz',
            'telefono' => '2000001',
            'celular' => '70000001',
            'email' => 'valeria@example.test',
            'nombrecontacto' => 'Rosa Mamani',
            'celularcontacto' => '70000002',
            'parentescocontacto' => 'Madre',
            'nombrecolegio' => 'U.E. de Prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'fecha' => now()->toDateString(),
            'diplomabachiller' => 1,
            'id_carrera' => $carreraActual->id_carrera,
            'turno' => 1,
            'paralelo' => 'A',
            'deposito' => 'HIST-VOUCHER-2026',
            'fotografia' => UploadedFile::fake()->image('foto-nueva.jpg'),
        ])->assertRedirect();

        $postulanteNuevo = Postulante::where('ci', 'HIST-001')
            ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
            ->firstOrFail();
        $this->get(route('postulantes.pdf', $postulanteNuevo->id_postulante))
            ->assertOk()
            ->assertSee('Gastronomía Vigente · R.M. 145/2025');
        $this->assertSame($carreraActual->id_carrera, $postulanteNuevo->id_carrera);
        $this->assertNotSame($postulanteAnterior->foto, $postulanteNuevo->foto);
        $this->assertStringContainsString('HIST-001_foto-', $postulanteNuevo->foto);
        $this->assertSame('postulantes/historico/ci-frontal.jpg', $postulanteNuevo->path_ci_frontal);
        $this->assertSame('postulantes/historico/ci-reverso.jpg', $postulanteNuevo->path_ci_reverso);
        $this->assertSame('postulantes/historico/nacimiento.jpg', $postulanteNuevo->path_nacimiento);
        $this->assertSame('postulantes/historico/diploma.jpg', $postulanteNuevo->path_diploma);
        $this->assertDatabaseHas('postulante', [
            'id_postulante' => $postulanteAnterior->id_postulante,
            'id_gestionacademica' => $gestionAnterior->id_gestionacademica,
            'foto' => 'postulantes/historico/foto.jpg',
            'deposito' => 'HIST-VOUCHER-001',
        ]);
        $this->assertSame(2, Postulante::where('ci', 'HIST-001')->count());
        $this->assertTrue(Storage::disk('public')->exists($postulanteNuevo->foto));

        $formularioEdicion = $this->get(route('postulantes.edit', $postulanteNuevo->id_postulante))
            ->assertOk()
            ->assertSee('storage/'.$postulanteNuevo->foto, false)
            ->assertSee('storage/postulantes/historico/ci-frontal.jpg', false)
            ->assertSee('storage/postulantes/historico/ci-reverso.jpg', false)
            ->assertSee('storage/postulantes/historico/nacimiento.jpg', false)
            ->assertSee('storage/postulantes/historico/diploma.jpg', false)
            ->getContent();
        preg_match_all('/<input\b[^>]*type="file"[^>]*>/i', $formularioEdicion, $inputsEdicion);
        $this->assertCount(5, $inputsEdicion[0]);
        foreach ($inputsEdicion[0] as $inputArchivo) {
            $this->assertDoesNotMatchRegularExpression('/\bmultiple\b/i', $inputArchivo);
        }

        $this->put(route('postulantes.update', $postulanteNuevo->id_postulante), [
            'nombre' => 'Valeria',
            'primerapellido' => 'Quispe',
            'segundoapellido' => 'Mamani',
            'ci' => 'HIST-001',
            'lugarexpedicion' => 'LP',
            'fechanacimiento' => '2005-04-15',
            'lugarnacimiento' => 'El Alto',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'direccion' => 'El Alto, La Paz',
            'telefono' => '2000001',
            'celular' => '70000001',
            'email' => 'valeria@example.test',
            'nombrecontacto' => 'Rosa Mamani',
            'celularcontacto' => '70000002',
            'parentescocontacto' => 'Madre',
            'nombrecolegio' => 'U.E. de Prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'id_carrera' => $carreraActual->id_carrera,
            'turno' => 1,
            'paralelo' => 'A',
            'deposito' => 'HIST-VOUCHER-2026',
            'diplomabachiller' => 1,
            'eliminar_foto' => 1,
            'eliminar_path_ci_frontal' => 1,
            'eliminar_path_ci_reverso' => 1,
            'eliminar_path_nacimiento' => 1,
            'eliminar_path_diploma' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('postulante', [
            'id_postulante' => $postulanteNuevo->id_postulante,
            'foto' => null,
            'path_ci_frontal' => null,
            'path_ci_reverso' => null,
            'path_nacimiento' => null,
            'path_diploma' => null,
        ]);
        $this->assertDatabaseHas('postulante', [
            'id_postulante' => $postulanteAnterior->id_postulante,
            'foto' => 'postulantes/historico/foto.jpg',
            'path_ci_frontal' => 'postulantes/historico/ci-frontal.jpg',
        ]);
    }
}
