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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdmisionesPostulantesCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_careers_filter_returns_each_application_for_the_same_management(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());

        $gestionActual = GestionAcademica::create([
            'gestion' => now()->year,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $gestionAnterior = GestionAcademica::create([
            'gestion' => now()->year - 1,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => false,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $gastronomia = Carrera::create([
            'codigo' => 'ADM-GAS',
            'nombre' => 'Gastronomía',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $sistemas = Carrera::create([
            'codigo' => 'ADM-SIS',
            'nombre' => 'Sistemas Informáticos',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $contabilidad = Carrera::create([
            'codigo' => 'ADM-CON',
            'nombre' => 'Contabilidad',
            'id_instituto' => $instituto->id_instituto,
        ]);

        $this->crearPostulante('APP-GAS', 'ADM-APP-001', $gastronomia, $gestionActual, 'APP-VOUCHER-1');
        $this->crearPostulante('APP-SIS', 'ADM-APP-001', $sistemas, $gestionActual, 'APP-VOUCHER-2');
        $this->crearPostulante('APP-OLD', 'ADM-APP-001', $gastronomia, $gestionAnterior, 'APP-VOUCHER-3');
        $this->crearPostulante('SINGLE-NEW', 'ADM-SINGLE-001', $gastronomia, $gestionActual, 'SINGLE-VOUCHER-1');
        $this->crearPostulante('SINGLE-OLD', 'ADM-SINGLE-001', $contabilidad, $gestionAnterior, 'SINGLE-VOUCHER-2');

        $response = $this->get(route('admisiones.postulantes.index', ['categoria' => 'multiples']))
            ->assertOk()
            ->assertSee('APP-GAS', false)
            ->assertSee('APP-SIS', false)
            ->assertDontSee('APP-OLD', false)
            ->assertDontSee('SINGLE-NEW', false)
            ->assertDontSee('SINGLE-OLD', false)
            ->assertSee('de 2 registros');

        $this->assertSame(
            1,
            substr_count($response->getContent(), '<span class="badge badge-secondary">APP-GAS</span>')
        );
        $this->assertSame(
            1,
            substr_count($response->getContent(), '<span class="badge badge-secondary">APP-SIS</span>')
        );

        $this->get(route('admisiones.postulantes.index', ['categoria' => 'unica']))
            ->assertOk()
            ->assertSee('APP-OLD', false)
            ->assertSee('SINGLE-NEW', false)
            ->assertSee('SINGLE-OLD', false)
            ->assertDontSee('APP-GAS', false)
            ->assertDontSee('APP-SIS', false)
            ->assertSee('de 3 registros');
    }

    public function test_turno_and_parallel_filters_show_matching_applications_in_the_list_and_pdf(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());

        $gestion = GestionAcademica::create([
            'gestion' => now()->year,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'ADM-TURNO',
            'nombre' => 'Carrera de prueba',
            'id_instituto' => $instituto->id_instituto,
        ]);

        $this->crearPostulante('SHIFT-MORNING-A', 'SHIFT-001', $carrera, $gestion, 'SHIFT-VOUCHER-1', 1, 'A');
        $this->crearPostulante('SHIFT-NIGHT-B', 'SHIFT-002', $carrera, $gestion, 'SHIFT-VOUCHER-2', 3, 'B');

        $this->get(route('admisiones.postulantes.index', ['turno' => 1, 'paralelo' => 'a']))
            ->assertOk()
            ->assertSee('SHIFT-MORNING-A', false)
            ->assertDontSee('SHIFT-NIGHT-B', false)
            ->assertSee('Mañana')
            ->assertSee('Paralelo');

        $this->get(route('admisiones.postulantes.index', [
            'turno' => 1,
            'paralelo' => 'A',
            'exportar' => 'filtrado',
            'formato' => 'pdf',
        ]))
            ->assertOk()
            ->assertSee('SHIFT-MORNING-A', false)
            ->assertDontSee('SHIFT-NIGHT-B', false)
            ->assertSee('TURNO')
            ->assertSee('PARALELO');

        $this->get(route('admisiones.postulantes.index', [
            'turno' => 1,
            'paralelo' => 'A',
            'exportar' => 'filtrado',
            'formato' => 'excel',
        ]))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
    }

    private function crearPostulante(
        string $codigo,
        string $ci,
        Carrera $carrera,
        GestionAcademica $gestion,
        string $voucher,
        ?int $turno = null,
        ?string $paralelo = null
    ): Postulante {
        return Postulante::create([
            'codigo' => $codigo,
            'ci' => $ci,
            'primerapellido' => 'Prueba',
            'nombre' => 'Postulante',
            'celular' => '70000000',
            'email' => strtolower($codigo).'@example.test',
            'direccion' => 'Sacaba',
            'fechanacimiento' => '2005-01-01',
            'lugarnacimiento' => 'Sacaba',
            'lugarexpedicion' => 'CB',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'fecha' => now(),
            'nombrecolegio' => 'Colegio de Prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'diplomabachiller' => true,
            'nombrecontacto' => 'Contacto',
            'parentescocontacto' => 'Familiar',
            'celularcontacto' => '70000001',
            'deposito' => $voucher,
            'path_ci_frontal' => 'postulantes/ci.jpg',
            'path_ci_reverso' => 'postulantes/ci-reverso.jpg',
            'path_nacimiento' => 'postulantes/nacimiento.jpg',
            'path_diploma' => 'postulantes/diploma.jpg',
            'id_carrera' => $carrera->id_carrera,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'turno' => $turno,
            'paralelo' => $paralelo,
        ]);
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'ADMIN-ADM-FILTER',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_admisiones_filtro',
            'password' => Hash::make('password123'),
        ]);
    }
}
