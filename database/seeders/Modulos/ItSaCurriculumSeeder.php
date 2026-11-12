<?php

namespace Database\Seeders\Modulos;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Instituto;
use App\Models\Materia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class ItSaCurriculumSeeder extends Seeder
{
    // Curricula are based on the official career pages linked from https://itsa.edu.bo/518-2/.
    public function run(): void
    {
        $gestion = GestionAcademica::query()
            ->where('vigente', true)
            ->orderByDesc('id_gestionacademica')
            ->first();

        if (! $gestion) {
            throw new RuntimeException('No hay una gestión académica vigente para crear grupos curriculares.');
        }

        $curricula = [
            'ADM-2023' => [
                [
                    'Fundamentos de la Administración',
                    'Contabilidad General para Administradores',
                    'Matemática Financiera',
                    'Estadística para los Negocios',
                    'Práctica Laboral y Tributaria',
                    'Informática Empresarial',
                    'Ingles Técnico',
                ],
                [
                    'Organización de la Administración',
                    'Investigación y Estrategias de Mercado',
                    'Administración de Operaciones',
                    'Gestión del Talento Humano',
                    'Administración Financiera I',
                    'Preparación y Evaluación de Proyectos',
                    'Administración de Costos y Presupuestos',
                    'Economía de la Empresa',
                ],
                [
                    'Administración de da Micro, Pequeña y Mediana Empresa',
                    'Incubación y Modelos de Negocios',
                    'Administración Financiera II',
                    'Taller de Modalidad de Graduación',
                    'Emprendimiento Productivo',
                    'Comercio Internacional',
                    'Gestión Estratégica',
                ],
            ],
            'GAS-2023' => [
                [
                    'Inocuidad Alimentaria, Seguridad Ocupacional y Medio Ambiente',
                    'Ingles',
                    'Técnicas Culinarias e Historia de la Gastronomía',
                    'Procedimientos Básicos de Repostería, Pastelería y Panadería',
                    'Informática Aplicada a la Gastronomía',
                    'Nutrición y Dietética',
                    'Organización y Gestión de Alimentos y Bebidas',
                    'Servicio y Comedor, Etiqueta y Protocolo',
                ],
                [
                    'Frances Técnico',
                    'Gastronomía Nacional',
                    'Repostería y Pastelería',
                    'Gastronomía Internacional',
                    'Panadería',
                    'Emprendimiento Productivo',
                ],
                [
                    'Cocina Creativa',
                    'Bar y Coctelería',
                    'Enología y Maridaje',
                    'Eventos, Banquetes y Garnish',
                    'Marketing Gastronómico',
                    'Industria y Especialidades Gastronómicas',
                    'Taller de Modalidades de Graduación',
                ],
            ],
            'SEJ-2023' => [
                [
                    'Gestión Secretarial',
                    'Mecanografía Computarizada',
                    'Computación I',
                    'Lenguaje y Comunicación',
                    'Idioma Originario I',
                    'Matemática Comercial',
                    'Inglés Técnico',
                    'Relaciones Interpersonales',
                    'Legislación, Documentos Mercantiles, derecho Laboral',
                    'Historia de Sociedades del Mundo',
                ],
                [
                    'Redacción y Correspondencia I',
                    'Relaciones Públicas y Ética',
                    'Administración y Organización',
                    'Gestión Secretarial II',
                    'Ingles Técnico II',
                    'Computación II',
                    'Contabilidad I',
                    'Idioma Originario II',
                    'Desarrollo de las Sociedades',
                ],
                [
                    'Redacción y Correspondencia II',
                    'Gestión Secretarial III',
                    'Contabilidad II',
                    'Ingles Técnico III',
                    'Idioma Originario III',
                    'Computación III',
                    'Taller de Grado',
                    'Pensamiento Contemporáneo y Cosmovisión',
                    'Electivas I',
                    'Electivas II',
                ],
            ],
        ];

        $careers = Carrera::query()
            ->whereIn('codigo', array_keys($curricula))
            ->get()
            ->keyBy('codigo');
        $missingCareers = array_diff(array_keys($curricula), $careers->keys()->all());

        if ($missingCareers !== []) {
            throw new RuntimeException(
                'No se encontraron las carreras '.implode(', ', $missingCareers).'. Ejecuta primero el seeder académico.'
            );
        }

        foreach ($curricula as $careerCode => $levels) {
            $career = $careers[$careerCode];

            foreach ($levels as $levelIndex => $subjects) {
                $level = $levelIndex + 1;

                foreach ($subjects as $subjectIndex => $name) {
                    $number = $subjectIndex + 1;
                    $code = sprintf('%s-M%02d-%02d', $careerCode, $level, $number);

                    Materia::updateOrCreate(
                        [
                            'codigo' => $code,
                            'id_carrera' => $career->id_carrera,
                        ],
                        [
                            'nombre' => $name,
                            'creditajemateria' => null,
                            'curricular' => true,
                            'horas' => null,
                            'nivel' => $level,
                            'numero' => $number,
                        ]
                    );
                }
            }

            $this->assignCareerCredits($career);
            $this->seedSequentialPrerequisites($career);
            $this->seedGroups($career, $gestion->id_gestionacademica);
        }
    }

    private function seedGroups(Carrera $career, int $gestionId): void
    {
        $subjects = Materia::query()
            ->where('id_carrera', $career->id_carrera)
            ->where('curricular', true)
            ->whereNotNull('nivel')
            ->get();

        foreach ($subjects as $subject) {
            foreach ([
                ['parallel' => 'A', 'turno' => 1],
                ['parallel' => 'B', 'turno' => 3],
            ] as $groupData) {
                Grupo::updateOrCreate(
                    [
                        'codigo' => $subject->codigo.'-'.$groupData['parallel'],
                        'id_gestionacademica' => $gestionId,
                    ],
                    [
                        'capacidad' => 30,
                        'turno' => $groupData['turno'],
                        'abierto' => true,
                        'id_persona' => null,
                        'id_materia' => $subject->id_materia,
                    ]
                );
            }
        }
    }

    private function assignCareerCredits(Carrera $career): void
    {
        $institute = Instituto::findOrFail($career->id_instituto);
        $total = (int) ($institute->preciocredito ?? 0) * 10;

        $career->update([
            'creditajeadmision' => $total,
            'creditajematricula' => $total,
        ]);

        $subjects = Materia::query()
            ->where('id_carrera', $career->id_carrera)
            ->where('curricular', true)
            ->orderBy('nivel')
            ->orderBy('numero')
            ->orderBy('id_materia')
            ->get();

        $count = $subjects->count();
        if ($count === 0) {
            return;
        }

        $base = intdiv($total, $count);
        $remainder = $total % $count;

        foreach ($subjects as $index => $subject) {
            $subject->update([
                'creditajemateria' => $base + ($index < $remainder ? 1 : 0),
            ]);
        }
    }

    private function seedSequentialPrerequisites(Carrera $career): void
    {
        $subjects = Materia::query()
            ->where('id_carrera', $career->id_carrera)
            ->where('curricular', true)
            ->get();

        $sequences = [];
        foreach ($subjects as $subject) {
            $name = Str::ascii(Str::lower(trim($subject->nombre)));
            $sequence = 1;

            if (preg_match('/\s+(iii|ii|i)$/', $name, $matches) === 1) {
                $sequence = match ($matches[1]) {
                    'i' => 1,
                    'ii' => 2,
                    'iii' => 3,
                };
                $name = trim(substr($name, 0, -strlen($matches[0])));
            }

            $sequences[$name][$sequence][$subject->nivel] = $subject;
        }

        foreach ($sequences as $levels) {
            foreach ([2 => 1, 3 => 2] as $sequence => $prerequisiteSequence) {
                foreach ($levels[$sequence] ?? [] as $level => $subject) {
                    $prerequisite = $levels[$prerequisiteSequence][$level - 1] ?? null;

                    if ($prerequisite !== null) {
                        $subject->prerequisitos()->syncWithoutDetaching([$prerequisite->id_materia]);
                    }
                }
            }
        }
    }
}
