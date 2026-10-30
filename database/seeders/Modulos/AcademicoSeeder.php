<?php

namespace Database\Seeders\Modulos;

use Database\Seeders\Concerns\InsertsFilledRows;
use Illuminate\Database\Seeder;

class AcademicoSeeder extends Seeder
{
    use InsertsFilledRows;

    public function run(): void
    {
        // Crea primero institutos, empleados y carreras; completa sus IDs en las claves foráneas.
        $carreras = [
            [
                'codigo' => 'COC-2023',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'CONSTRUCCIÓN CIVIL',
                'regimen' => 'ANUAL',
                'resolucionministerial1' => '0210/2023',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2023,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'ADM-2019',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'ADMINISTRACION DE EMPRESAS',
                'regimen' => 'ANUAL',
                'resolucionministerial1' => '0637/2019',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2019,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'ADM-2023',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'ADMINISTRACION DE EMPRESAS',
                'regimen' => 'ANUAL',
                'resolucionministerial1' => '0210/2023',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2023,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'COC-2019',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'CONSTRUCCIÓN CIVIL',
                'regimen' => 'SEMESTRAL',
                'resolucionministerial1' => '0637/2019',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2019,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'CNG-2019',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'CONTADURIA GENERAL',
                'regimen' => 'ANUAL',
                'resolucionministerial1' => '0637/2019',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2019,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'CNG-2023',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'CONTADURIA GENERAL',
                'regimen' => 'ANUAL',
                'resolucionministerial1' => '0210/2023',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2023,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'GAS-2019',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'GASTRONOMIA',
                'regimen' => 'SEMESTRAL',
                'resolucionministerial1' => '0637/2019',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2019,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'GAS-2023',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'GASTRONOMIA',
                'regimen' => 'ANUAL',
                'resolucionministerial1' => '0210/2023',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2023,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'INF-2019',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'INFORMATICA INDUSTRIAL',
                'regimen' => 'SEMESTRAL',
                'resolucionministerial1' => '0637/2019',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2019,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'INF-2023',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'INFORMATICA INDUSTRIAL',
                'regimen' => 'SEMESTRAL',
                'resolucionministerial1' => '0210/2023',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2023,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'MTZ-2019',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'MECANICA AUTOMOTRIZ',
                'regimen' => 'SEMESTRAL',
                'resolucionministerial1' => '0637/2019',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2019,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'MTZ-2023',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'MECANICA AUTOMOTRIZ',
                'regimen' => 'ANUAL',
                'resolucionministerial1' => '0210/2023',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2023,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'SEJ-2019',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'SECRETARIADO EJECUTIVO',
                'regimen' => 'ANUAL',
                'resolucionministerial1' => '0637/2019',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2019,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'SEJ-2023',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'SECRETARIADO EJECUTIVO',
                'regimen' => 'ANUAL',
                'resolucionministerial1' => '0210/2023',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2023,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'SIS-2019',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'SISTEMAS INFORMATICOS',
                'regimen' => 'ANUAL',
                'resolucionministerial1' => '0637/2019',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2019,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'SIS-2023',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'SISTEMAS INFORMATICOS',
                'regimen' => 'ANUAL',
                'resolucionministerial1' => '0210/2023',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2023,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'TOP-2019',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'TOPOGRAFIA Y GEODESIA',
                'regimen' => 'SEMESTRAL',
                'resolucionministerial1' => '0637/2019',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2019,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
            [
                'codigo' => 'TOP-2023',
                'creditajeadmision' => null,
                'creditajematricula' => null,
                'nivelacademico' => 'TÉCNICO SUPERIOR',
                'nombre' => 'TOPOGRAFIA Y GEODESIA',
                'regimen' => 'ANUAL',
                'resolucionministerial1' => '0210/2023',
                'resolucionministerial2' => null,
                'resolucionministerial3' => null,
                'version' => 2023,
                'id_instituto' => 1,
                'id_jefecarrera' => null,
            ],
        ];
        $materias = [
            [
                'codigo' => null,
                'nombre' => null,
                'creditajemateria' => null,
                'curricular' => null,
                'horas' => null,
                'nivel' => null,
                'numero' => null,
                'id_carrera' => null,
            ],
        ];
        $prerequisitos = [
            [
                'id_materia' => null,
                'id_prerequisito' => null,
            ],
        ];
        $gestiones = [
            [
                'gestion' => null,
                'periodo' => null,
                'regimen' => null, // 1 = anual, 2 = semestral
                'inicio' => null, // AAAA-MM-DD
                'fin' => null, // AAAA-MM-DD
                'vigente' => null,
                'modalidadevaluacion' => null, // 1 = parciales, 2 = bimestres
            ],
        ];
        $grupos = [
            [
                'codigo' => null,
                'capacidad' => null,
                'turno' => null,
                'abierto' => null,
                'id_persona' => null, // id_persona del docente; opcional
                'id_gestionacademica' => null,
                'id_materia' => null, // Opcional
            ],
        ];
        $actividades = [
            [
                'nombre' => null,
                'descripcion' => null,
                'inicio' => null, // AAAA-MM-DD HH:MM:SS
                'fin' => null, // AAAA-MM-DD HH:MM:SS
                'funcionalidad' => null, // id_recurso; opcional
                'id_gestionacademica' => null,
            ],
        ];
        $actividadCarreras = [
            [
                'id_actividad' => null,
                'id_carrera' => null,
            ],
        ];
        $actividadUsuarios = [
            [
                'id_actividad' => null,
                'id_persona' => null, // id_persona de users
            ],
        ];
        $cursas = [
            [
                'id_persona' => null,
                'id_carrera' => null,
                'id_gestionacademica' => null,
                'id_mencion' => null, // Opcional
                'nivelinicio' => null,
            ],
        ];

        $this->insertFilledRows('carreras', $carreras);
        $this->insertFilledRows('materia', $materias);
        $this->insertFilledRows('prerequisito', $prerequisitos);
        $this->insertFilledRows('gestionacademica', $gestiones);
        $this->insertFilledRows('grupo', $grupos);
        $this->insertFilledRows('actividad', $actividades);
        $this->insertFilledRows('actividad_carrera', $actividadCarreras, [], false);
        $this->insertFilledRows('actividad_usuario', $actividadUsuarios, [], false);
        $this->insertFilledRows('cursa', $cursas);
    }
}
