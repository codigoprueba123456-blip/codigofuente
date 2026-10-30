<?php

namespace Tests\Feature;

use App\Http\Controllers\Requests\Postulantes\PostulanteValidator;
use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PostulanteDocumentValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_rejects_non_image_documents(): void
    {
        $gestion = GestionAcademica::create([
            'gestion' => now()->year,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'TEST-IMG',
            'nombre' => 'Carrera de Prueba',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $request = Request::create('/postulantes/store', 'POST', [
            'nombre' => 'Postulante',
            'primerapellido' => 'Prueba',
            'ci' => 'IMG-POST-001',
            'lugarexpedicion' => 'LP',
            'fechanacimiento' => '2005-01-01',
            'lugarnacimiento' => 'La Paz',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'F',
            'direccion' => 'La Paz',
            'celular' => '70000000',
            'email' => 'postulante@example.test',
            'nombrecontacto' => 'Contacto',
            'celularcontacto' => '70000001',
            'parentescocontacto' => 'Familiar',
            'nombrecolegio' => 'Colegio de Prueba',
            'caractercolegio' => 'Fiscal',
            'egresocolegio' => 2024,
            'id_carrera' => $carrera->id_carrera,
            'deposito' => 'IMG-VOUCHER-001',
        ], [], [
            'fotografia' => UploadedFile::fake()->image('foto.jpg'),
            'path_ci_frontal' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
            'path_ci_reverso' => UploadedFile::fake()->image('ci-reverso.jpg'),
            'path_nacimiento' => UploadedFile::fake()->image('nacimiento.jpg'),
            'path_diploma' => UploadedFile::fake()->image('diploma.jpg'),
        ]);

        try {
            app(PostulanteValidator::class)->validarRegistro($request, $gestion, null);
            $this->fail('A non-image document must not be accepted as a postulant attachment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('path_ci_frontal', $exception->errors());
        }
    }
}
