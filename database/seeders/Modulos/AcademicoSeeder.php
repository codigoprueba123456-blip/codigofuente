<?php

namespace Database\Seeders\Modulos;

use App\Models\Actividad;
use App\Models\Carrera;
use App\Models\Cursa;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Instituto;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Database\Seeder;

class AcademicoSeeder extends Seeder
{
    public function run(): void
    {
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
        $instituto = Instituto::firstOrCreate(
            ['nombreinstituto' => 'Instituto de Prueba'],
            ['abreviatura' => 'ITSA-DEMO']
        );

        foreach ($carreras as $datosCarrera) {
            $datosCarrera['nivelacademico'] = 1;
            $datosCarrera['regimen'] = $datosCarrera['regimen'] === 'ANUAL' ? 1 : 2;
            $datosCarrera['id_instituto'] = $instituto->id_instituto;

            Carrera::updateOrCreate(
                ['codigo' => $datosCarrera['codigo']],
                $datosCarrera
            );
        }

        $carrera = Carrera::where('codigo', 'SIS-2023')->firstOrFail();
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
            ['codigo' => 'SIS-2023-101', 'id_carrera' => $carrera->id_carrera],
            [
                'nombre' => 'Fundamentos de Sistemas (Demo)',
                'creditajemateria' => 4,
                'curricular' => true,
                'horas' => 6,
                'nivel' => 1,
                'numero' => 1,
            ]
        );
        $materiaAvanzada = Materia::updateOrCreate(
            ['codigo' => 'SIS-2023-201', 'id_carrera' => $carrera->id_carrera],
            [
                'nombre' => 'Sistemas Avanzados (Demo)',
                'creditajemateria' => 5,
                'curricular' => true,
                'horas' => 6,
                'nivel' => 2,
                'numero' => 1,
            ]
        );
        $materiaAvanzada->prerequisitos()->sync([$materiaInicial->id_materia]);

        Grupo::updateOrCreate(
            ['codigo' => 'SIS-2023-201-A', 'id_gestionacademica' => $gestion->id_gestionacademica],
            [
                'capacidad' => 30,
                'turno' => 1,
                'abierto' => true,
                'id_persona' => null,
                'id_materia' => $materiaAvanzada->id_materia,
            ]
        );

        $actividad = Actividad::updateOrCreate(
            [
                'nombre' => 'Actividad académica de demostración',
                'id_gestionacademica' => $gestion->id_gestionacademica,
            ],
            [
                'descripcion' => 'Actividad de prueba para la carrera de Sistemas.',
                'inicio' => now()->startOfDay(),
                'fin' => now()->addDays(30)->endOfDay(),
            ]
        );
        $actividad->carreras()->sync([$carrera->id_carrera]);
        $actividad->usuarios()->sync([]);

        $estudiante = User::where('usuario', '20260003')->firstOrFail();
        Cursa::updateOrCreate(
            [
                'id_persona' => $estudiante->id_persona,
                'id_carrera' => $carrera->id_carrera,
                'id_gestionacademica' => $gestion->id_gestionacademica,
            ],
            ['nivelinicio' => 1]
        );
    }
}
