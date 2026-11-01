<?php

namespace Database\Seeders\Modulos;

use App\Models\Actividad;
use App\Models\Carrera;
use App\Models\Cursa;
use App\Models\Empleado;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Instituto;
use App\Models\Materia;
use App\Models\User;
use Database\Seeders\StudentPortalSeeder;
use Illuminate\Database\Seeder;

class AcademicoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            InstitucionalSeeder::class,
            StudentPortalSeeder::class,
        ]);

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
            $datosCarrera['nivelacademico'] = match ($datosCarrera['nivelacademico']) {
                'TÉCNICO SUPERIOR' => Carrera::NIVEL_TECNICO_SUPERIOR,
                'TÉCNICO MEDIO' => Carrera::NIVEL_TECNICO_MEDIO,
            };
            $datosCarrera['regimen'] = match ($datosCarrera['regimen']) {
                'ANUAL' => Carrera::REGIMEN_ANUAL,
                'SEMESTRAL' => Carrera::REGIMEN_SEMESTRAL,
            };
            $datosCarrera['id_instituto'] = $instituto->id_instituto;

            Carrera::updateOrCreate(
                ['codigo' => $datosCarrera['codigo']],
                $datosCarrera
            );
        }

        $this->seedCurrentDemoCatalog($instituto->id_instituto);

        $carrera = Carrera::where('codigo', 'SIS-2023')->firstOrFail();
        GestionAcademica::updateOrCreate(
            ['gestion' => 2025, 'periodo' => 1],
            [
                'regimen' => 1,
                'inicio' => '2025-01-01',
                'fin' => '2025-12-31',
                'vigente' => false,
                'modalidadevaluacion' => 1,
            ]
        );

        $gestion = GestionAcademica::updateOrCreate(
            ['gestion' => now()->year, 'periodo' => 1],
            [
                'regimen' => 1,
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

    private function seedCurrentDemoCatalog(int $institutoId): void
    {
        $jefeCarreraId = Empleado::query()
            ->where('codigo', 'EMP-DEMO-001')
            ->value('id_persona')
            ?? Empleado::query()
                ->where('codigo', 'EMP-PRUEBA-003')
                ->value('id_persona');

        $carreras = [];
        foreach ([
            ['codigo' => 'DEMO-SIS', 'nombre' => 'Ingeniería de Sistemas (Prueba)'],
            ['codigo' => 'DEMO-MTZ', 'nombre' => 'Mecánica Automotriz (Prueba)'],
        ] as $datos) {
            $carreras[$datos['codigo']] = Carrera::updateOrCreate(
                ['codigo' => $datos['codigo']],
                [
                    'creditajeadmision' => 10,
                    'creditajematricula' => 20,
                    'nivelacademico' => Carrera::NIVEL_TECNICO_SUPERIOR,
                    'nombre' => $datos['nombre'],
                    'activo' => true,
                    'regimen' => Carrera::REGIMEN_ANUAL,
                    'resolucionministerial1' => 'R.M. 001/2026',
                    'version' => 1,
                    'id_instituto' => $institutoId,
                    'id_jefecarrera' => $jefeCarreraId,
                ]
            );
        }

        foreach ([
            ['career' => 'DEMO-SIS', 'code' => 'DEMO-SIS-101', 'name' => 'Fundamentos de Programación (Prueba)', 'credits' => 4, 'level' => 1],
            ['career' => 'DEMO-SIS', 'code' => 'DEMO-SIS-201', 'name' => 'Programación Avanzada (Prueba)', 'credits' => 5, 'level' => 2],
            ['career' => 'DEMO-MTZ', 'code' => 'DEMO-MTZ-101', 'name' => 'Fundamentos de Mecánica Automotriz (Prueba)', 'credits' => 4, 'level' => 1],
            ['career' => 'DEMO-MTZ', 'code' => 'DEMO-MTZ-201', 'name' => 'Sistemas del Motor y Diagnóstico (Prueba)', 'credits' => 5, 'level' => 2],
        ] as $datos) {
            $materia = Materia::updateOrCreate(
                [
                    'codigo' => $datos['code'],
                    'id_carrera' => $carreras[$datos['career']]->id_carrera,
                ],
                [
                    'nombre' => $datos['name'],
                    'creditajemateria' => $datos['credits'],
                    'curricular' => true,
                    'horas' => 6,
                    'nivel' => $datos['level'],
                    'numero' => 1,
                ]
            );

            if ($datos['level'] === 2) {
                $materiaInicial = Materia::where('codigo', str_replace('201', '101', $datos['code']))
                    ->where('id_carrera', $carreras[$datos['career']]->id_carrera)
                    ->firstOrFail();
                $materia->prerequisitos()->sync([$materiaInicial->id_materia]);
            }
        }

        $gestion = GestionAcademica::updateOrCreate(
            ['gestion' => now()->year, 'periodo' => 1],
            [
                'regimen' => 1,
                'inicio' => now()->startOfYear()->toDateString(),
                'fin' => now()->endOfYear()->toDateString(),
                'vigente' => true,
                'modalidadevaluacion' => 1,
            ]
        );

        foreach ([
            ['career' => 'DEMO-SIS', 'code' => 'DEMO-SIS-201-A'],
            ['career' => 'DEMO-MTZ', 'code' => 'DEMO-MTZ-201-A'],
        ] as $datos) {
            $materia = Materia::where('codigo', str_replace('-A', '', $datos['code']))
                ->where('id_carrera', $carreras[$datos['career']]->id_carrera)
                ->firstOrFail();

            Grupo::updateOrCreate(
                ['codigo' => $datos['code'], 'id_gestionacademica' => $gestion->id_gestionacademica],
                [
                    'capacidad' => 30,
                    'turno' => 1,
                    'abierto' => true,
                    'id_persona' => $jefeCarreraId,
                    'id_materia' => $materia->id_materia,
                ]
            );
        }
    }
}
