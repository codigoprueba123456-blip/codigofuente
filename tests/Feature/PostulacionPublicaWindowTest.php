<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Postulante;
use App\Models\Recurso;
use Database\Seeders\PostulacionPublicaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\OpensPublicPostulationWindow;
use Tests\TestCase;

class PostulacionPublicaWindowTest extends TestCase
{
    use OpensPublicPostulationWindow;
    use RefreshDatabase;

    public function test_public_ci_search_and_registration_are_closed_without_an_open_calendar_activity(): void
    {
        $this->crearCatalogo();
        $gestionActual = $this->crearGestion(now()->year, true);
        $gestionAnterior = $this->crearGestion(now()->year - 1, false);
        $carrera = Carrera::where('codigo', 'VENTANA-TEST')->firstOrFail();
        $this->crearPostulanteHistorico($gestionAnterior, $carrera);

        $this->get(route('postulantes.bienvenida', ['ci_consulta' => 'VENTANA-HIST-001']))
            ->assertOk()
            ->assertSee('El período de postulación está cerrado')
            ->assertDontSee('Nombre Histórico VENTANA')
            ->assertDontSee('ci-lookup-form');

        $this->get(route('postulantes.registrar'))
            ->assertRedirect(route('postulantes.bienvenida'));
        $this->post(route('postulantes.store'), ['ci' => 'VENTANA-NUEVO-001'])
            ->assertRedirect(route('postulantes.bienvenida'));

        $this->assertDatabaseMissing('postulante', [
            'ci' => 'VENTANA-NUEVO-001',
            'id_gestionacademica' => $gestionActual->id_gestionacademica,
        ]);
    }

    public function test_open_calendar_activity_enables_ci_search_and_the_public_form(): void
    {
        $this->crearCatalogo();
        $gestionActual = $this->crearGestion(now()->year, true);
        $gestionAnterior = $this->crearGestion(now()->year - 1, false);
        $carrera = Carrera::where('codigo', 'VENTANA-TEST')->firstOrFail();
        $this->crearPostulanteHistorico($gestionAnterior, $carrera);
        $actividad = $this->openPublicPostulationWindow($gestionActual);

        $this->get(route('postulantes.bienvenida', ['ci_consulta' => 'VENTANA-HIST-001']))
            ->assertOk()
            ->assertSee('Historial anterior encontrado')
            ->assertSee('ci-lookup-form');
        $this->get(route('postulantes.registrar', ['ci_consulta' => 'VENTANA-HIST-001']))
            ->assertOk()
            ->assertSee('Nueva postulación en la gestión vigente')
            ->assertSee('value="VENTANA-HIST-001"', false)
            ->assertSee('value="Nombre"', false)
            ->assertSee('value="Histórico"', false)
            ->assertSee('value="VENTANA"', false);

        $actividad->update(['activo' => false]);
        $this->get(route('postulantes.registrar'))
            ->assertRedirect(route('postulantes.bienvenida'));
    }

    public function test_postulation_seeder_opens_the_current_management_for_two_months(): void
    {
        $gestion = $this->crearGestion(now()->year, true);

        $this->seed(PostulacionPublicaSeeder::class);

        $actividad = Actividad::where('nombre', 'Ventana de postulación pública')
            ->where('id_gestionacademica', $gestion->id_gestionacademica)
            ->firstOrFail();

        $this->assertTrue($actividad->activo);
        $this->assertSame(
            $actividad->inicio->copy()->addMonths(2)->format('Y-m-d H:i:s'),
            $actividad->fin->format('Y-m-d H:i:s')
        );
        $this->assertDatabaseHas('actividad_funcionalidad', [
            'id_actividad' => $actividad->id_actividad,
            'id_recurso' => Recurso::where('urlpattern', 'postulantes-publica')->value('id_recurso'),
        ]);
        $this->get(route('postulantes.registrar'))->assertOk();
    }

    private function crearGestion(int $anio, bool $vigente): GestionAcademica
    {
        return GestionAcademica::create([
            'gestion' => $anio,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => $vigente,
            'activo' => $vigente,
        ]);
    }

    private function crearCatalogo(): void
    {
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto Ventana']);
        Carrera::create([
            'codigo' => 'VENTANA-TEST',
            'nombre' => 'Carrera Ventana',
            'id_instituto' => $instituto->id_instituto,
        ]);
    }

    private function crearPostulanteHistorico(GestionAcademica $gestion, Carrera $carrera): void
    {
        Postulante::create([
            'codigo' => 'VENTANA-HIST-001',
            'ci' => 'VENTANA-HIST-001',
            'primerapellido' => 'Histórico',
            'segundoapellido' => 'VENTANA',
            'nombre' => 'Nombre',
            'celular' => '70000001',
            'email' => 'historico@example.test',
            'direccion' => 'Zona de Prueba',
            'fechanacimiento' => '2005-04-15',
            'lugarnacimiento' => 'Cochabamba',
            'lugarexpedicion' => 'CB',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'fecha' => now(),
            'nombrecolegio' => 'Colegio de Prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'diplomabachiller' => true,
            'nombrecontacto' => 'Contacto de Prueba',
            'parentescocontacto' => 'Madre',
            'celularcontacto' => '70000002',
            'deposito' => 'VENTANA-HIST-VOUCHER',
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);
    }
}
