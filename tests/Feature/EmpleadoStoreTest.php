<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\Support\EnablesFunctionality;
use Tests\TestCase;

class EmpleadoStoreTest extends TestCase
{
    use RefreshDatabase;
    use EnablesFunctionality;

    public function test_employee_can_be_created_and_user_record_generated_with_pdf_response(): void
    {
        Storage::fake('public');

        $adminPersona = Persona::create([
            'nombre' => 'Admin',
            'primerapellido' => 'Sistema',
            'segundoapellido' => 'Test',
            'dni' => '11111111',
            'tipo' => 'admin',
        ]);

        $adminUser = User::create([
            'id_persona' => $adminPersona->id_persona,
            'usuario' => 'admin_test',
            'password' => Hash::make('password123'),
        ]);

        $rol = Rol::create(['nombre' => 'Administrador']);
        $this->enableFunctionality('admin/empleados');

        $response = $this->actingAs($adminUser)->post(route('admin.empleados.store'), [
            'nombre' => 'Juan',
            'primerapellido' => 'Pérez',
            'segundoapellido' => 'Gómez',
            'dni' => '98765432',
            'fechanacimiento' => '1990-05-10',
            'lugarnacimiento' => 'La Paz',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'MASCULINO',
            'lugarexpedicion' => 'Santa Cruz',
            'direccion' => 'Av. Principal 123',
            'telefono' => '3456789',
            'celular' => '71234567',
            'email' => 'juan@example.com',
            'foto' => '',
            'documentos' => [
                1 => UploadedFile::fake()->image('documento-1.jpg'),
                2 => UploadedFile::fake()->image('documento-2.jpg'),
                3 => UploadedFile::fake()->image('documento-3.jpg'),
                4 => UploadedFile::fake()->image('documento-4.jpg'),
            ],
            'abreviaturaprofesion' => 'Ing.',
            'codigo' => 'EMP-001',
            'id_rol' => $rol->id_rol,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('personas', ['dni' => '98765432']);
        $this->assertDatabaseHas('empleados', ['codigo' => 'EMP-001']);
        $empleadoId = Persona::where('dni', '98765432')->value('id_persona');
        $this->assertDatabaseCount('empleado_documentos', 4);
        $empleado = Empleado::with('documentos')->findOrFail($empleadoId);
        $this->assertCount(4, $empleado->documentos);
        foreach (range(1, 4) as $slot) {
            $ruta = \App\Models\EmpleadoDocumento::where('id_persona', $empleadoId)
                ->where('slot', $slot)
                ->value('ruta');
            $this->assertNotNull($ruta);
            Storage::disk('public')->assertExists($ruta);
        }
        $this->assertDatabaseCount('users', 2);

        $empleadoUser = User::where('id_persona', Persona::where('dni', '98765432')->value('id_persona'))->first();
        $this->assertNotNull($empleadoUser);
        $this->assertMatchesRegularExpression('/^' . date('Y') . '\d{4}$/', $empleadoUser->usuario);

        $rutaDocumentoAnterior = \App\Models\EmpleadoDocumento::where('id_persona', $empleadoId)
            ->where('slot', 1)
            ->value('ruta');
        $updateResponse = $this->actingAs($adminUser)->put(route('admin.empleados.update', $empleadoId), [
            'nombre' => 'Juan',
            'primerapellido' => 'Pérez',
            'segundoapellido' => 'Gómez',
            'dni' => '98765432',
            'fechanacimiento' => '1990-05-10',
            'lugarnacimiento' => 'La Paz',
            'nacionalidad' => 'Boliviana',
            'sexo' => 'MASCULINO',
            'lugarexpedicion' => 'Santa Cruz',
            'direccion' => 'Av. Principal 123',
            'telefono' => '3456789',
            'celular' => '71234567',
            'email' => 'juan@example.com',
            'abreviaturaprofesion' => 'Ing.',
            'codigo' => 'EMP-001',
            'id_rol' => $rol->id_rol,
            'usuario' => $empleadoUser->usuario,
            'documentos' => [1 => UploadedFile::fake()->image('documento-reemplazo.jpg')],
        ]);

        $updateResponse->assertRedirect(route('admin.empleados.index'));
        $this->assertDatabaseCount('empleado_documentos', 4);
        $rutaDocumentoNuevo = \App\Models\EmpleadoDocumento::where('id_persona', $empleadoId)
            ->where('slot', 1)
            ->value('ruta');
        $this->assertNotSame($rutaDocumentoAnterior, $rutaDocumentoNuevo);
        Storage::disk('public')->assertMissing($rutaDocumentoAnterior);
        Storage::disk('public')->assertExists($rutaDocumentoNuevo);

        $pdfContent = $response->getContent();
        $this->assertNotEmpty($pdfContent);
        $this->assertMatchesRegularExpression('/[A-Za-z0-9]{10}/', $pdfContent);

        $this->assertDatabaseHas('cuenta', ['id_persona' => $empleadoUser->id_persona, 'id_rol' => $rol->id_rol]);
    }
}
