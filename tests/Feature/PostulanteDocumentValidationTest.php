<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\OpensPublicPostulationWindow;
use Tests\TestCase;

class PostulanteDocumentValidationTest extends TestCase
{
    use OpensPublicPostulationWindow;
    use RefreshDatabase;

    public function test_registration_rejects_non_image_documents(): void
    {
        $gestion = GestionAcademica::create([
            'gestion' => now()->year,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);
        $this->openPublicPostulationWindow($gestion);
        $instituto = Instituto::create(['nombreinstituto' => 'Instituto de Prueba']);
        $carrera = Carrera::create([
            'codigo' => 'TEST-IMG',
            'nombre' => 'Carrera de Prueba',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $datosFormulario = [
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
        ];

        $this->post(route('postulantes.store'), $datosFormulario + [
            'fotografia' => UploadedFile::fake()->image('foto.jpg'),
            'path_ci_frontal' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
            'path_ci_reverso' => UploadedFile::fake()->image('ci-reverso.jpg'),
            'path_nacimiento' => UploadedFile::fake()->image('nacimiento.jpg'),
            'path_diploma' => UploadedFile::fake()->image('diploma.jpg'),
        ])->assertSessionHasErrors('path_ci_frontal');

        $this->post(route('postulantes.store'), $datosFormulario + [
            'fotografia' => UploadedFile::fake()->image('foto.jpg'),
            'path_ci_frontal' => UploadedFile::fake()->create('documento.webp', 100, 'image/webp'),
            'path_ci_reverso' => UploadedFile::fake()->image('ci-reverso.jpg'),
            'path_nacimiento' => UploadedFile::fake()->image('nacimiento.jpg'),
            'path_diploma' => UploadedFile::fake()->image('diploma.jpg'),
        ])->assertSessionHasErrors('path_ci_frontal');

        $this->post(route('postulantes.store'), $datosFormulario + [
            'fotografia' => UploadedFile::fake()->image('foto.jpg'),
            'path_ci_frontal' => [
                UploadedFile::fake()->image('ci-frontal-1.jpg'),
                UploadedFile::fake()->image('ci-frontal-2.jpg'),
            ],
            'path_ci_reverso' => UploadedFile::fake()->image('ci-reverso.jpg'),
            'path_nacimiento' => UploadedFile::fake()->image('nacimiento.jpg'),
            'path_diploma' => UploadedFile::fake()->image('diploma.jpg'),
        ])->assertSessionHasErrors('path_ci_frontal');

        $formulario = $this->get(route('postulantes.registrar'))
            ->assertOk()
            ->getContent();
        $this->assertSame(5, substr_count($formulario, 'accept="image/jpeg,image/png"'));
        preg_match_all('/<input\b[^>]*type="file"[^>]*>/i', $formulario, $inputsArchivo);
        $this->assertCount(5, $inputsArchivo[0]);
        foreach ($inputsArchivo[0] as $inputArchivo) {
            $this->assertDoesNotMatchRegularExpression('/\bmultiple\b/i', $inputArchivo);
        }
        $this->assertStringContainsString('novalidate', $formulario);
        $this->assertStringNotContainsString('webp', mb_strtolower($formulario));
    }
}
