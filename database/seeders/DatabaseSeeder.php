<?php

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\Carrera;
use App\Models\Cuenta;
use App\Models\Empleado;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Instituto;
use App\Models\Materia;
use App\Models\Persona;
use App\Models\Recurso;
use App\Models\Rol;
use App\Models\User;
use App\Support\MenuResourceSync;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        MenuResourceSync::sync();

        $rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador']);
        $recursos = Recurso::all();
        $rolAdmin->recursos()->sync($recursos->pluck('id_recurso')->all());

        Persona::firstOrCreate(
            ['id_persona' => 1],
            [
                'tipo' => 'admin',
                'celular' => '123456789',
                'direccion' => 'Calle Falsa 123',
                'ci' => '12345678',
                'email' => 'admin@example.com',
                'fechanacimiento' => '1990-01-01',
                'foto' => null,
                'lugarexpedicion' => 'Ciudad',
                'lugarnacimiento' => 'Ciudad',
                'nacionalidad' => 'País',
                'nombre' => 'Admin',
                'primerapellido' => 'Root',
                'segundoapellido' => 'Admin',
                'sexo' => 'M',
                'telefono' => '123456789',
            ]
        );

        $usuario = User::firstOrCreate(
            ['id_persona' => 1],
            [
                'usuario' => '20260001',
                'password' => Hash::make('adminroot'),
            ]
        );

        Cuenta::updateOrCreate(
            ['id_persona' => 1, 'id_rol' => $rolAdmin->id_rol],
            [
                'usuario' => $usuario->usuario,
                'password' => Hash::make('adminroot'),
            ]
        );

        $rolDocente = Rol::firstOrCreate(['nombre' => 'Docente']);
        $personaDocente = Persona::updateOrCreate(
            ['ci' => '87654321'],
            [
                'tipo' => 'empleado',
                'celular' => 71234567,
                'direccion' => 'Av. de Prueba 123',
                'email' => 'docente.prueba@example.com',
                'fechanacimiento' => '1985-05-15',
                'lugarexpedicion' => 'La Paz',
                'lugarnacimiento' => 'Cochabamba',
                'nacionalidad' => 'Boliviana',
                'nombre' => 'María Fernanda',
                'primerapellido' => 'Docente',
                'segundoapellido' => 'Prueba',
                'sexo' => 'FEMENINO',
                'telefono' => 2234567,
            ]
        );

        $docente = Empleado::updateOrCreate(
            ['id_persona' => $personaDocente->id_persona],
            ['abreviaturaprofesion' => 'Lic.', 'codigo' => 'EMP-DEMO-001']
        );

        $usuarioDocente = User::updateOrCreate(
            ['id_persona' => $personaDocente->id_persona],
            [
                'usuario' => '20260002',
                'password' => Hash::make('docente123'),
            ]
        );

        Cuenta::updateOrCreate(
            ['id_persona' => $usuarioDocente->id_persona, 'id_rol' => $rolDocente->id_rol],
            [
                'usuario' => $usuarioDocente->usuario,
                'password' => $usuarioDocente->password,
            ]
        );

        $instituto = Instituto::updateOrCreate(
            ['id_instituto' => 1],
            [
                'actividadeconomica' => 'Educación superior',
                'caracter' => 'PRIVADO',
                'nit' => '123456789',
                'nombreimpuestos' => 'Instituto de Prueba',
                'nombreregulador' => 'Ministerio de Educación',
                'preciocredito' => 100,
                'resolucionministerial' => 'R.M. 001/2026',
                'ubicacion' => 'La Paz, Bolivia',
                'abreviatura' => 'ITSA-DEMO',
                'nombreinstituto' => 'Instituto de Prueba',
                'telefono' => 2234567,
                'id_directoracademico' => $docente->id_persona,
                'id_rector' => $docente->id_persona,
            ]
        );

        $carrera = Carrera::updateOrCreate(
            ['codigo' => 'DEMO-SIS'],
            [
                'creditajeadmision' => 10,
                'creditajematricula' => 20,
                'nivelacademico' => 1,
                'nombre' => 'Ingeniería de Sistemas (Prueba)',
                'regimen' => 1,
                'resolucionministerial1' => 'R.M. 001/2026',
                'version' => 1,
                'id_instituto' => $instituto->id_instituto,
                'id_jefecarrera' => $docente->id_persona,
            ]
        );

        $carreraMecanica = Carrera::updateOrCreate(
            ['codigo' => 'DEMO-MTZ'],
            [
                'creditajeadmision' => 10,
                'creditajematricula' => 20,
                'nivelacademico' => 1,
                'nombre' => 'Mecánica Automotriz (Prueba)',
                'activo' => true,
                'regimen' => 1,
                'resolucionministerial1' => 'R.M. 001/2026',
                'version' => 1,
                'id_instituto' => $instituto->id_instituto,
                'id_jefecarrera' => $docente->id_persona,
            ]
        );

        $gestion = GestionAcademica::updateOrCreate(
            ['gestion' => now()->year, 'periodo' => 1],
            [
                'regimen' => 2,
                'inicio' => now()->startOfYear()->toDateString(),
                'fin' => now()->endOfYear()->toDateString(),
                'vigente' => true,
                'modalidadevaluacion' => 1,
            ]
        );

        $materiaInicial = Materia::updateOrCreate(
            ['codigo' => 'DEMO-SIS-101', 'id_carrera' => $carrera->id_carrera],
            [
                'nombre' => 'Fundamentos de Programación (Prueba)',
                'creditajemateria' => 4,
                'curricular' => true,
                'horas' => 6,
                'nivel' => 1,
                'numero' => 1,
            ]
        );

        $materiaAvanzada = Materia::updateOrCreate(
            ['codigo' => 'DEMO-SIS-201', 'id_carrera' => $carrera->id_carrera],
            [
                'nombre' => 'Programación Avanzada (Prueba)',
                'creditajemateria' => 5,
                'curricular' => true,
                'horas' => 6,
                'nivel' => 2,
                'numero' => 1,
            ]
        );
        $materiaAvanzada->prerequisitos()->sync([$materiaInicial->id_materia]);

        $materiaMecanicaInicial = Materia::updateOrCreate(
            ['codigo' => 'DEMO-MTZ-101', 'id_carrera' => $carreraMecanica->id_carrera],
            [
                'nombre' => 'Fundamentos de Mecánica Automotriz (Prueba)',
                'creditajemateria' => 4,
                'curricular' => true,
                'horas' => 6,
                'nivel' => 1,
                'numero' => 1,
            ]
        );

        $materiaMecanicaAvanzada = Materia::updateOrCreate(
            ['codigo' => 'DEMO-MTZ-201', 'id_carrera' => $carreraMecanica->id_carrera],
            [
                'nombre' => 'Sistemas del Motor y Diagnóstico (Prueba)',
                'creditajemateria' => 5,
                'curricular' => true,
                'horas' => 6,
                'nivel' => 2,
                'numero' => 1,
            ]
        );
        $materiaMecanicaAvanzada->prerequisitos()->sync([$materiaMecanicaInicial->id_materia]);

        Grupo::updateOrCreate(
            ['codigo' => 'DEMO-SIS-201-A', 'id_gestionacademica' => $gestion->id_gestionacademica],
            [
                'capacidad' => 30,
                'turno' => 1,
                'abierto' => true,
                'id_persona' => $docente->id_persona,
                'id_materia' => $materiaAvanzada->id_materia,
            ]
        );

        Grupo::updateOrCreate(
            ['codigo' => 'DEMO-MTZ-201-A', 'id_gestionacademica' => $gestion->id_gestionacademica],
            [
                'capacidad' => 30,
                'turno' => 1,
                'abierto' => true,
                'id_persona' => $docente->id_persona,
                'id_materia' => $materiaMecanicaAvanzada->id_materia,
            ]
        );

        $actividadGeneral = Actividad::updateOrCreate(
            ['nombre' => 'Actividad de prueba general', 'id_gestionacademica' => $gestion->id_gestionacademica],
            [
                'descripcion' => 'Registro de demostración visible para toda la comunidad.',
                'inicio' => now()->startOfDay(),
                'fin' => now()->addDays(30)->endOfDay(),
                'funcionalidad' => null,
            ]
        );
        $actividadGeneral->carreras()->sync([]);
        $actividadGeneral->usuarios()->sync([]);

        $actividadCarrera = Actividad::updateOrCreate(
            ['nombre' => 'Actividad de prueba por carrera', 'id_gestionacademica' => $gestion->id_gestionacademica],
            [
                'descripcion' => 'Registro de demostración dirigido a una carrera.',
                'inicio' => now()->startOfDay(),
                'fin' => now()->addDays(30)->endOfDay(),
                'funcionalidad' => null,
            ]
        );
        $actividadCarrera->carreras()->sync([$carrera->id_carrera]);
        $actividadCarrera->usuarios()->sync([]);

        $actividadUsuario = Actividad::updateOrCreate(
            ['nombre' => 'Actividad de prueba por usuario', 'id_gestionacademica' => $gestion->id_gestionacademica],
            [
                'descripcion' => 'Registro de demostración dirigido a un usuario.',
                'inicio' => now()->startOfDay(),
                'fin' => now()->addDays(30)->endOfDay(),
                'funcionalidad' => null,
            ]
        );
        $actividadUsuario->carreras()->sync([]);
        $actividadUsuario->usuarios()->sync([$usuario->id_persona]);

        $this->call(PostulantesDemoSeeder::class);
    }
}
