<?php

namespace Database\Seeders\Modulos;

use Database\Seeders\Concerns\InsertsFilledRows;
use Illuminate\Database\Seeder;

class InscripcionesSeeder extends Seeder
{
    use InsertsFilledRows;

    public function run(): void
    {
        // Completa los IDs de carrera, gestión y persona con registros existentes.
        $postulantes = [
            [
                'codigo' => null,
                'ci' => null,
                'primerapellido' => null,
                'segundoapellido' => null,
                'nombre' => null,
                'celular' => null,
                'telefono' => null,
                'email' => null,
                'direccion' => null,
                'fechanacimiento' => null, // AAAA-MM-DD
                'lugarnacimiento' => null,
                'lugarexpedicion' => null,
                'nacionalidad' => null,
                'sexo' => null,
                'foto' => null,
                'fecha' => null, // Fecha de registro; puede omitirse para usar la fecha actual
                'nombrecolegio' => null,
                'caractercolegio' => null,
                'egresocolegio' => null,
                'diplomabachiller' => null,
                'nombrecontacto' => null,
                'parentescocontacto' => null,
                'celularcontacto' => null,
                'deposito' => null,
                'path_ci_frontal' => null,
                'path_ci_reverso' => null,
                'path_nacimiento' => null,
                'path_diploma' => null,
                'id_carrera' => null,
                'id_gestionacademica' => null,
            ],
        ];
        $estudiantes = [
            [
                'id_persona' => null,
                'fecha' => null,
                'matricula' => null,
                'diplomabachiller' => null,
                'nombrecontacto' => null,
                'celularcontacto' => null,
                'parentescocontacto' => null,
                'nombrecolegio' => null,
                'caractercolegio' => null,
                'egresocolegio' => null,
                'path_ci' => null,
                'path_ci_reverso' => null,
                'path_nacimiento' => null,
                'path_diploma' => null,
                'path_bth' => null,
                'becado' => null,
                'deposito' => null,
                'traspaso_convalidacion' => null,
                'id_carrera' => null,
                'id_gestionacademica' => null,
                'id_postulante' => null,
            ],
        ];
        $inscritos = [
            [
                'codigo' => null,
                'fecha' => null, // AAAA-MM-DD
                'numero' => null,
                'tipo' => null,
                'becado' => null,
                'deposito' => null,
                'id_postulante_origen' => null,
                'id_carrera' => null,
                'id_persona' => null,
                'id_gestionacademica' => null,
            ],
        ];
        $inscritoMaterias = [
            [
                'id_inscrito' => null,
                'id_materia' => null,
                'id_grupo' => null, // Opcional
            ],
        ];

        $this->insertFilledRows('postulante', $postulantes);
        $this->insertFilledRows('estudiante', $estudiantes);
        $this->insertFilledRows('inscrito', $inscritos);
        $this->insertFilledRows('inscrito_materia', $inscritoMaterias);
    }
}
