<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureFunctionalityAccess;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InscritosFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_bth_and_transfer_filters_can_be_applied_independently_and_together(): void
    {
        $this->withoutMiddleware(EnsureFunctionalityAccess::class);
        $this->actingAs($this->createAdmin());

        $gestion = GestionAcademica::create([
            'gestion' => 2027,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'TEST-INSCRITOS',
            'nombre' => 'Carrera de Prueba',
            'id_instituto' => $instituto->id_instituto,
        ]);

        $bth = $this->createInscrito($carrera, $gestion, 'BTH', true, false);
        $traspaso = $this->createInscrito($carrera, $gestion, 'TRASPASO', false, true);
        $ambos = $this->createInscrito($carrera, $gestion, 'AMBOS', true, true);

        $bthResponse = $this->get(route('inscriptos.index', ['bth' => '1']));
        $bthResponse->assertOk()
            ->assertSee('Certificado BTH')
            ->assertSee('Traspaso / convalidación');
        $this->assertEqualsCanonicalizing(
            [$bth, $ambos],
            $bthResponse->viewData('inscritos')->getCollection()->pluck('id_persona')->all()
        );

        $traspasoResponse = $this->get(route('inscriptos.index', ['traspaso_convalidacion' => '1']));
        $traspasoResponse->assertOk();
        $this->assertEqualsCanonicalizing(
            [$traspaso, $ambos],
            $traspasoResponse->viewData('inscritos')->getCollection()->pluck('id_persona')->all()
        );

        $combinadoResponse = $this->get(route('inscriptos.index', [
            'bth' => '1',
            'traspaso_convalidacion' => '1',
        ]));
        $combinadoResponse->assertOk();
        $this->assertEquals(
            [$ambos],
            $combinadoResponse->viewData('inscritos')->getCollection()->pluck('id_persona')->all()
        );
    }

    private function createInscrito(
        Carrera $carrera,
        GestionAcademica $gestion,
        string $suffix,
        bool $hasBth,
        bool $isTransfer
    ): int {
        $persona = Persona::create([
            'nombre' => 'Estudiante '.$suffix,
            'primerapellido' => 'Prueba',
            'ci' => 'CI-'.$suffix,
            'tipo' => 'Estudiante',
        ]);
        DB::table('estudiante')->insert([
            'id_persona' => $persona->id_persona,
            'path_bth' => $hasBth ? 'documentos/'.$suffix.'.jpg' : null,
            'traspaso_convalidacion' => (int) $isTransfer,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inscrito')->insert([
            'fecha' => '2027-01-10',
            'id_carrera' => $carrera->id_carrera,
            'id_persona' => $persona->id_persona,
            'id_gestionacademica' => $gestion->id_gestionacademica,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $persona->id_persona;
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => 'ADMIN-INSCRITOS-001',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_inscritos',
            'password' => Hash::make('password123'),
        ]);
    }
}
