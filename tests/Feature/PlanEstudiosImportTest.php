<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Instituto;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\User;
use App\Support\PlanEstudiosImporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\Support\EnablesFunctionality;
use Tests\TestCase;

class PlanEstudiosImportTest extends TestCase
{
    use RefreshDatabase;
    use EnablesFunctionality;

    public function test_it_imports_the_materias_xls_layout_and_links_prerequisites(): void
    {
        $user = $this->createAdmin();
        $instituto = Instituto::create([
            'nombreinstituto' => 'Instituto de Prueba',
            'preciocredito' => 12,
        ]);
        $carrera = Carrera::create([
            'codigo' => 'INF-001',
            'nombre' => 'Informática',
            'id_instituto' => $instituto->id_instituto,
        ]);
        $this->enableFunctionality('planes/carreras');

        $file = new UploadedFile(
            base_path('materias.xls'),
            'materias.xls',
            'application/vnd.ms-excel',
            UPLOAD_ERR_OK,
            true
        );

        $this->actingAs($user)
            ->post(route('planes.carreras.materias.import.store', $carrera->id_carrera), [
                'archivo' => $file,
            ])
            ->assertRedirect(route('planes.materias.index', ['id_carrera' => $carrera->id_carrera]))
            ->assertSessionHas('success');

        $this->assertSame(23, Materia::where('id_carrera', $carrera->id_carrera)->count());
        $this->assertDatabaseHas('materia', [
            'id_carrera' => $carrera->id_carrera,
            'codigo' => 'MPI-101',
            'nombre' => 'MATEMÁTICA PARA LA INFORMÁTICA',
            'nivel' => 1,
            'numero' => 1,
            'horas' => 4,
            'curricular' => true,
        ]);

        $materiaConPrerequisito = Materia::where('id_carrera', $carrera->id_carrera)
            ->where('codigo', 'PRG-202')
            ->firstOrFail();
        $this->assertSame(
            ['PRG-102'],
            $materiaConPrerequisito->prerequisitos()->pluck('codigo')->all()
        );
    }

    public function test_it_imports_a_pdf_table_using_the_materias_xls_columns(): void
    {
        $pdf = Pdf::loadHTML(
            '<table><thead><tr><th>Nivel</th><th>Nº</th><th>Codigo</th><th>Nombre</th><th>Horas</th><th>Creditaje materia</th><th>Curricular</th><th>Prerequisitos</th></tr></thead>'
            . '<tbody><tr><td>PRIMER AÑO</td><td>1</td><td>MPI-101</td><td>MATEMÁTICA PARA LA INFORMÁTICA</td><td>4</td><td>5</td><td>Sí</td><td></td></tr></tbody></table>'
        )->output();
        $file = UploadedFile::fake()->createWithContent('materias.pdf', $pdf);

        $rows = (new PlanEstudiosImporter)->rows($file);

        $this->assertSame([
            'codigo' => 'MPI-101',
            'nombre' => 'MATEMÁTICA PARA LA INFORMÁTICA',
            'horas' => '4',
            'nivel' => 1,
            'numero' => '1',
            'curricular' => true,
            'prerequisitos' => [],
        ], $rows[0]);
    }

    public function test_it_imports_pdf_rows_that_are_extracted_as_single_lines(): void
    {
        $pdf = Pdf::loadHTML(
            '<p>PRIMER AÑO 1 MPI-101 MATEMÁTICA PARA LA INFORMÁTICA 4 5 Sí</p>'
            . '<p>SEGUNDO AÑO 1 PRG-202 PROGRAMACIÓN II 6 5 Sí PRG-102</p>'
        )->output();
        $file = UploadedFile::fake()->createWithContent('materias.pdf', $pdf);

        $rows = (new PlanEstudiosImporter)->rows($file);

        $this->assertSame('MPI-101', $rows[0]['codigo']);
        $this->assertSame(1, $rows[0]['nivel']);
        $this->assertSame('PRG-202', $rows[1]['codigo']);
        $this->assertSame(2, $rows[1]['nivel']);
        $this->assertSame(['PRG-102'], $rows[1]['prerequisitos']);
    }

    private function createAdmin(): User
    {
        $persona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Prueba',
            'ci' => '11111111',
            'tipo' => 'admin',
        ]);

        return User::create([
            'id_persona' => $persona->id_persona,
            'usuario' => 'admin_import_test',
            'password' => Hash::make('password123'),
        ]);
    }
}
