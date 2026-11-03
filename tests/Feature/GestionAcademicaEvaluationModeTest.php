<?php

namespace Tests\Feature;

use App\Models\GestionAcademica;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GestionAcademicaEvaluationModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluation_modes_can_be_selected_for_annual_and_semester_regimes(): void
    {
        $this->actingAs($this->createAdmin());

        foreach ([1, 2] as $regimen) {
            foreach ([1, 2] as $modalidad) {
                $periodo = (($regimen - 1) * 2) + $modalidad;
                $this->post(route('admin.gestionacademica.store'), [
                    'gestion' => 2027,
                    'periodo' => $periodo,
                    'regimen' => $regimen,
                    'modalidadevaluacion' => $modalidad,
                    'vigente' => 1,
                ])->assertRedirect(route('admin.gestionacademica.index'));

                $this->assertDatabaseHas('gestionacademica', [
                    'gestion' => 2027,
                    'periodo' => $periodo,
                    'regimen' => $regimen,
                    'modalidadevaluacion' => $modalidad,
                ]);
            }
        }
    }

    public function test_invalid_evaluation_mode_is_rejected(): void
    {
        $this->actingAs($this->createAdmin())
            ->from(route('admin.gestionacademica.create'))
            ->post(route('admin.gestionacademica.store'), [
                'gestion' => 2027,
                'periodo' => 1,
                'regimen' => 1,
                'modalidadevaluacion' => 3,
            ])
            ->assertSessionHasErrors('modalidadevaluacion');

        $this->assertDatabaseCount('gestionacademica', 0);
    }

    public function test_end_date_before_start_date_is_rejected_on_create_and_edit(): void
    {
        $this->actingAs($this->createAdmin());
        $mensaje = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
        $datos = [
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => 1,
            'inicio' => '2027-09-10',
            'fin' => '2027-09-09',
        ];

        $this->from(route('admin.gestionacademica.create'))
            ->post(route('admin.gestionacademica.store'), $datos)
            ->assertSessionHasErrors(['fin' => $mensaje])
            ->assertRedirect(route('admin.gestionacademica.create'));
        $this->assertDatabaseCount('gestionacademica', 0);

        $gestion = GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => 1,
            'inicio' => '2027-01-01',
            'fin' => '2027-12-31',
        ]);

        $this->from(route('admin.gestionacademica.edit', $gestion->id_gestionacademica))
            ->put(route('admin.gestionacademica.update', $gestion->id_gestionacademica), $datos)
            ->assertSessionHasErrors(['fin' => $mensaje])
            ->assertRedirect(route('admin.gestionacademica.edit', $gestion->id_gestionacademica));

        $this->assertSame('2027-12-31', $gestion->fresh()->fin);
    }

    public function test_create_and_edit_views_offer_both_evaluation_modes(): void
    {
        $this->actingAs($this->createAdmin())
            ->get(route('admin.gestionacademica.create'))
            ->assertOk()
            ->assertSee('fin-date-error', false)
            ->assertSee('setCustomValidity', false)
            ->assertSee('value="1"', false)
            ->assertSee('Parciales')
            ->assertSee('value="2"', false)
            ->assertSee('Bimestres');

        $gestion = GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => 1,
            'modalidadevaluacion' => 2,
        ]);

        $response = $this->get(route('admin.gestionacademica.edit', $gestion->id_gestionacademica));

        $response->assertOk()
            ->assertSee('Bimestres');
        $this->assertSame(
            1,
            preg_match('/<option value="2"\s+selected>Bimestres<\/option>/', $response->getContent())
        );
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => '12345678',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_prueba',
            'password' => Hash::make('password123'),
        ]);
    }
}
