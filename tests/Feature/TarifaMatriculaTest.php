<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\TarifaMatricula;
use App\Models\User;
use App\Support\MatriculaAmountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TarifaMatriculaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tariff_can_be_created_and_replaced_from_the_academic_management_screen(): void
    {
        $this->actingAs($this->createAdmin());
        $gestion = $this->createGestion(2027);
        $carrera = $this->createCarrera();

        $this->get(route('admin.gestionacademica.tarifas.index', $gestion->id_gestionacademica))
            ->assertOk()
            ->assertSee('Agregar o reemplazar tarifa');

        $datos = [
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 2,
            'monto' => '1250.50',
        ];

        $this->post(route('admin.gestionacademica.tarifas.store', $gestion->id_gestionacademica), $datos)
            ->assertRedirect(route('admin.gestionacademica.tarifas.index', $gestion->id_gestionacademica));
        $this->assertDatabaseHas('tarifas_matricula', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 2,
            'monto' => '1250.50',
        ]);

        $this->post(route('admin.gestionacademica.tarifas.store', $gestion->id_gestionacademica), array_merge($datos, [
            'monto' => '1400.00',
        ]))->assertRedirect(route('admin.gestionacademica.tarifas.index', $gestion->id_gestionacademica));

        $this->assertDatabaseCount('tarifas_matricula', 1);
        $this->assertDatabaseHas('tarifas_matricula', [
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 2,
            'monto' => '1400.00',
        ]);

        $tarifa = TarifaMatricula::firstOrFail();
        $this->put(route('admin.gestionacademica.tarifas.update', [
            $gestion->id_gestionacademica,
            $tarifa->id_tarifa_matricula,
        ]), ['monto' => '1500.00'])
            ->assertRedirect(route('admin.gestionacademica.tarifas.index', $gestion->id_gestionacademica));
        $this->assertDatabaseHas('tarifas_matricula', [
            'id_tarifa_matricula' => $tarifa->id_tarifa_matricula,
            'monto' => '1500.00',
        ]);

        $this->delete(route('admin.gestionacademica.tarifas.destroy', [
            $gestion->id_gestionacademica,
            $tarifa->id_tarifa_matricula,
        ]))->assertRedirect(route('admin.gestionacademica.tarifas.index', $gestion->id_gestionacademica));
        $this->assertDatabaseMissing('tarifas_matricula', [
            'id_tarifa_matricula' => $tarifa->id_tarifa_matricula,
        ]);
    }

    public function test_level_tariff_precedes_general_rate_and_missing_tariffs_use_the_existing_calculation(): void
    {
        $gestion = $this->createGestion(2027);
        $otraGestion = $this->createGestion(2028);
        $carrera = $this->createCarrera(100, 10);
        TarifaMatricula::create([
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 0,
            'monto' => 900,
        ]);
        TarifaMatricula::create([
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'id_carrera' => $carrera->id_carrera,
            'nivel' => 2,
            'monto' => 1250.50,
        ]);

        $service = app(MatriculaAmountService::class);

        $this->assertSame(1250.5, $service->calcular(
            $carrera->id_carrera,
            $gestion->id_gestionacademica,
            2
        ));
        $this->assertSame(900.0, $service->calcular(
            $carrera->id_carrera,
            $gestion->id_gestionacademica,
            3
        ));
        $this->assertSame(1000.0, $service->calcular(
            $carrera->id_carrera,
            $otraGestion->id_gestionacademica,
            1
        ));
    }

    public function test_tariff_level_must_match_the_career_regime(): void
    {
        $this->actingAs($this->createAdmin());
        $gestion = $this->createGestion(2027);
        $carrera = $this->createCarrera(regimen: Carrera::REGIMEN_ANUAL);

        $this->from(route('admin.gestionacademica.tarifas.index', $gestion->id_gestionacademica))
            ->post(route('admin.gestionacademica.tarifas.store', $gestion->id_gestionacademica), [
                'id_carrera' => $carrera->id_carrera,
                'nivel' => 4,
                'monto' => '500.00',
            ])
            ->assertSessionHasErrors('nivel');

        $this->assertDatabaseCount('tarifas_matricula', 0);
    }

    private function createGestion(int $year): GestionAcademica
    {
        return GestionAcademica::create([
            'gestion' => $year,
            'periodo' => 1,
            'regimen' => Carrera::REGIMEN_ANUAL,
            'vigente' => true,
            'activo' => true,
        ]);
    }

    private function createCarrera(
        int $precioCredito = 0,
        ?int $creditajeMatricula = null,
        int $regimen = Carrera::REGIMEN_ANUAL
    ): Carrera {
        $instituto = Instituto::create([
            'nombreinstituto' => 'Instituto de Prueba',
            'preciocredito' => $precioCredito,
        ]);

        return Carrera::create([
            'codigo' => 'TARIFA-TEST',
            'nombre' => 'Carrera de Prueba',
            'regimen' => $regimen,
            'activo' => true,
            'creditajematricula' => $creditajeMatricula,
            'id_instituto' => $instituto->id_instituto,
        ]);
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'TARIFA-ADMIN',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_tarifa',
            'password' => Hash::make('password123'),
        ]);
    }
}
