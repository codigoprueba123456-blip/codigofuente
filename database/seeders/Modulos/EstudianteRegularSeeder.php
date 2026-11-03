<?php

namespace Database\Seeders\Modulos;

use App\Models\Carrera;
use App\Models\Cursa;
use App\Models\Cuenta;
use App\Models\Estudiante;
use App\Models\GestionAcademica;
use App\Models\Persona;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EstudianteRegularSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AcademicoSeeder::class);

        $carrera = Carrera::where('codigo', 'SIS-2023')->firstOrFail();
        $gestion = GestionAcademica::where('gestion', 2025)
            ->where('periodo', 1)
            ->firstOrFail();

        $persona = Persona::updateOrCreate(
            ['ci' => '9843201'],
            [
                'tipo' => 'estudiante',
                'nombre' => 'Andrea Camila',
                'primerapellido' => 'Quispe',
                'segundoapellido' => 'Mamani',
                'email' => 'andrea.quispe@example.test',
                'fechanacimiento' => '2004-07-18',
                'lugarnacimiento' => 'Sacaba, Cochabamba',
                'lugarexpedicion' => 'CB',
                'nacionalidad' => 'Boliviana',
                'sexo' => 'FEMENINO',
                'direccion' => 'Zona Central, Sacaba, Cochabamba',
                'telefono' => 4452301,
                'celular' => 70012345,
            ]
        );

        Estudiante::updateOrCreate(
            ['id_persona' => $persona->id_persona],
            [
                'fecha' => '2025-01-15',
                'matricula' => 20250001,
                'diplomabachiller' => true,
                'nombrecontacto' => 'Rosa Mamani',
                'celularcontacto' => 70012346,
                'parentescocontacto' => 'Madre',
                'nombrecolegio' => 'Unidad Educativa 6 de Agosto',
                'caractercolegio' => 'Fiscal',
                'egresocolegio' => 2023,
                'becado' => false,
                'deposito' => 'DEMO-REG-2025-SIS-001',
                'traspaso_convalidacion' => false,
                'id_carrera' => $carrera->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
            ]
        );

        $rolEstudiante = Rol::firstOrCreate(['nombre' => 'Estudiante']);
        $password = Hash::make('Andrea2025!');
        $usuario = User::updateOrCreate(
            ['id_persona' => $persona->id_persona],
            [
                'usuario' => 'andrea_quispe_9843201',
                'password' => $password,
            ]
        );
        Cuenta::updateOrCreate(
            [
                'id_persona' => $persona->id_persona,
                'id_rol' => $rolEstudiante->id_rol,
            ],
            [
                'usuario' => $usuario->usuario,
                'password' => $password,
            ]
        );

        $ahora = now();
        DB::table('inscrito')->updateOrInsert(
            [
                'id_persona' => $persona->id_persona,
                'id_carrera' => $carrera->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
            ],
            [
                'codigo' => 20250001,
                'fecha' => '2025-01-15',
                'numero' => 1,
                'tipo' => 1,
                'becado' => false,
                'deposito' => 'DEMO-REG-2025-SIS-001',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]
        );

        Cursa::updateOrCreate(
            [
                'id_persona' => $persona->id_persona,
                'id_carrera' => $carrera->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
            ],
            ['nivelinicio' => 2]
        );
    }
}
