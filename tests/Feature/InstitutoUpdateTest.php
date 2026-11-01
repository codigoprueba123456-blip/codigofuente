<?php

namespace Tests\Feature;

use App\Models\Instituto;
use App\Models\Carrera;
use App\Models\Empleado;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\EnablesFunctionality;
use Tests\TestCase;

class InstitutoUpdateTest extends TestCase
{
    use RefreshDatabase;
    use EnablesFunctionality;

    public function test_institute_form_saves_and_displays_all_configured_data(): void
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Sistema',
            'ci' => '11111111',
            'tipo' => 'admin',
        ]);
        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_test',
            'password' => Hash::make('password123'),
        ]);
        $this->enableFunctionality('admin/instituto');
        $rectorPersona = Persona::create([
            'nombre' => 'Rector',
            'primerapellido' => 'Prueba',
            'ci' => '11111112',
            'tipo' => 'empleado',
        ]);
        $rector = Empleado::create(['id_persona' => $rectorPersona->id_persona]);
        $directorPersona = Persona::create([
            'nombre' => 'Director',
            'primerapellido' => 'Academico',
            'ci' => '11111113',
            'tipo' => 'empleado',
        ]);
        $director = Empleado::create(['id_persona' => $directorPersona->id_persona]);

        $datosInstituto = [
            'nombreinstituto' => 'Instituto de Prueba',
            'nombreregulador' => 'Ministerio de Educación',
            'ubicacion' => 'La Paz',
            'caracter' => 'FISCAL',
            'nombreimpuestos' => 'Instituto de Prueba',
            'nit' => '123456789',
            'actividadeconomica' => 'Educación superior',
            'preciocredito' => 150,
            'resolucionministerial' => 'RM-123',
            'abreviatura' => 'IP',
            'id_rector' => $rector->id_persona,
            'id_directoracademico' => $director->id_persona,
        ];

        $this->actingAs($user)
            ->get(route('admin.instituto.index'))
            ->assertOk()
            ->assertSee('name="nombreinstituto"', false)
            ->assertSee('Rector Prueba')
            ->assertSee('Director Academico');

        $this->put(route('admin.instituto.update'), $datosInstituto)
            ->assertRedirect(route('admin.instituto.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('instituto', $datosInstituto);

        $this->get(route('admin.instituto.index'))
            ->assertOk()
            ->assertSee('value="Instituto de Prueba"', false)
            ->assertSee('value="La Paz"', false)
            ->assertSee('value="123456789"', false)
            ->assertSee('value="IP"', false)
            ->assertSee('value="' . $rector->id_persona . '" selected', false)
            ->assertSee('value="' . $director->id_persona . '" selected', false);

        $this->assertSame(1, Instituto::count());
    }

    public function test_institute_form_reports_missing_required_values_instead_of_silently_failing(): void
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Sistema',
            'ci' => '11111111',
            'tipo' => 'admin',
        ]);
        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_test',
            'password' => Hash::make('password123'),
        ]);
        $this->enableFunctionality('admin/instituto');

        $this->actingAs($user)
            ->put(route('admin.instituto.update'), [])
            ->assertSessionHasErrors([
                'nombreinstituto',
                'nombreregulador',
                'ubicacion',
                'caracter',
                'preciocredito',
                'resolucionministerial',
                'abreviatura',
            ]);
    }

    public function test_changing_credit_price_recalculates_all_career_and_subject_credits(): void
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Sistema',
            'ci' => '44444444',
            'tipo' => 'admin',
        ]);
        $user = User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_credit_update_test',
            'password' => Hash::make('password123'),
        ]);
        $this->enableFunctionality('admin/instituto');

        $instituto = Instituto::create([
            'nombreinstituto' => 'Instituto de Prueba',
            'preciocredito' => 5,
        ]);
        $carrera = Carrera::create([
            'codigo' => 'INF-004',
            'nombre' => 'Informática',
            'id_instituto' => $instituto->id_instituto,
            'creditajeadmision' => 50,
            'creditajematricula' => 50,
        ]);
        $materia = Materia::create([
            'nombre' => 'Programación',
            'creditajemateria' => 50,
            'curricular' => true,
            'id_carrera' => $carrera->id_carrera,
        ]);

        $this->actingAs($user)
            ->get(route('admin.instituto.index'))
            ->assertOk();

        $this->put(route('admin.instituto.update'), [
            'nombreinstituto' => 'Instituto de Prueba',
            'nombreregulador' => 'Ministerio de Educación',
            'ubicacion' => 'La Paz',
            'caracter' => 'FISCAL',
            'preciocredito' => 6,
            'resolucionministerial' => 'RM-123',
            'abreviatura' => 'IP',
        ])
            ->assertRedirect(route('admin.instituto.index'));

        $this->assertSame(60, (int) $carrera->fresh()->creditajematricula);
        $this->assertSame(60, (int) $carrera->fresh()->creditajeadmision);
        $this->assertSame(60, (int) $materia->fresh()->creditajemateria);
    }
}
