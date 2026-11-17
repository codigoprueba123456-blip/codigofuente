<?php

namespace Database\Seeders\Modulos;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CatalogoDemoCarrerasSeeder extends Seeder
{
    private const CARRERAS = [
        'ADM-2023' => ['regimen' => Carrera::REGIMEN_ANUAL, 'niveles' => [1, 2, 3]],
        'COC-2023' => ['regimen' => Carrera::REGIMEN_ANUAL, 'niveles' => [1, 2, 3]],
        'CNG-2023' => ['regimen' => Carrera::REGIMEN_ANUAL, 'niveles' => [1, 2, 3]],
        'GAS-2023' => ['regimen' => Carrera::REGIMEN_ANUAL, 'niveles' => [1, 2, 3]],
        'INF-2023' => ['regimen' => Carrera::REGIMEN_SEMESTRAL, 'niveles' => [1, 2, 3, 4, 5, 6]],
        'MTZ-2023' => ['regimen' => Carrera::REGIMEN_ANUAL, 'niveles' => [1, 2, 3]],
        'SEJ-2023' => ['regimen' => Carrera::REGIMEN_ANUAL, 'niveles' => [1, 2, 3]],
        'SIS-2023' => ['regimen' => Carrera::REGIMEN_ANUAL, 'niveles' => [1, 2, 3]],
        'TOP-2023' => ['regimen' => Carrera::REGIMEN_ANUAL, 'niveles' => [1, 2, 3]],
    ];

    private const CURRICULAS_DEMO = [
        'COC-2023' => [
            1 => [
                'Matemática Aplicada a la Construcción',
                'Física General',
                'Dibujo Técnico',
                'Materiales de Construcción',
                'Topografía I',
                'Seguridad Industrial',
                'Comunicación Técnica',
            ],
            2 => [
                'Resistencia de Materiales',
                'Tecnología del Hormigón',
                'Construcción de Obra Gruesa',
                'Instalaciones Sanitarias',
                'Instalaciones Eléctricas',
                'Topografía II',
                'Costos y Presupuestos de Obra',
            ],
            3 => [
                'Estructuras de Hormigón Armado',
                'Construcción de Obra Fina',
                'Diseño de Carreteras',
                'Supervisión y Fiscalización de Obras',
                'Gestión de Proyectos',
                'Legislación y Contratos',
                'Proyecto de Grado',
            ],
        ],
        'CNG-2023' => [
            1 => [
                'Contabilidad Básica',
                'Matemática Financiera',
                'Administración General',
                'Economía General',
                'Legislación Comercial',
                'Documentación Mercantil',
                'Ofimática Contable',
            ],
            2 => [
                'Contabilidad Intermedia',
                'Contabilidad de Costos',
                'Tributación I',
                'Estadística Aplicada',
                'Finanzas Empresariales',
                'Auditoría I',
                'Derecho Tributario',
            ],
            3 => [
                'Contabilidad Superior',
                'Contabilidad Bancaria',
                'Tributación II',
                'Auditoría II',
                'Análisis Financiero',
                'Presupuestos Empresariales',
                'Taller de Grado',
            ],
        ],
        'INF-2023' => [
            1 => [
                'Matemática Discreta',
                'Fundamentos de Programación',
                'Física Aplicada',
                'Dibujo Técnico Industrial',
                'Herramientas Digitales',
            ],
            2 => [
                'Programación Estructurada',
                'Circuitos Eléctricos',
                'Cálculo I',
                'Electrónica Básica',
                'Seguridad Industrial',
            ],
            3 => [
                'Estructuras de Datos',
                'Sistemas Digitales',
                'Programación Orientada a Objetos',
                'Cálculo II',
                'Estadística Aplicada',
            ],
            4 => [
                'Redes Industriales',
                'Bases de Datos',
                'Automatización I',
                'Mantenimiento Informático',
                'Seguridad de Sistemas',
            ],
            5 => [
                'Controladores Lógicos Programables',
                'Instrumentación Industrial',
                'Internet de las Cosas Industrial',
                'Desarrollo de Software',
                'Gestión de Proyectos',
            ],
            6 => [
                'Automatización II',
                'Robótica Industrial',
                'Ciberseguridad Industrial',
                'Integración de Sistemas',
                'Proyecto de Grado',
            ],
        ],
        'MTZ-2023' => [
            1 => [
                'Dibujo Técnico Automotriz',
                'Matemática Aplicada',
                'Física Aplicada',
                'Seguridad en Taller',
                'Herramientas y Metrología',
                'Motores de Combustión I',
                'Electricidad Automotriz I',
            ],
            2 => [
                'Motores de Combustión II',
                'Sistemas de Transmisión',
                'Sistemas de Frenos',
                'Electricidad Automotriz II',
                'Inyección Electrónica',
                'Hidráulica y Neumática',
                'Diagnóstico Automotriz',
            ],
            3 => [
                'Electrónica Automotriz Avanzada',
                'Sistemas ABS y Seguridad',
                'Aire Acondicionado Automotriz',
                'Vehículos Híbridos',
                'Gestión de Taller',
                'Mantenimiento Preventivo',
                'Proyecto de Grado',
            ],
        ],
        'SIS-2023' => [
            1 => [
                'Fundamentos de Programación',
                'Matemática Discreta',
                'Arquitectura de Computadoras',
                'Ofimática Aplicada',
                'Inglés Técnico',
                'Comunicación Técnica',
                'Sistemas Operativos',
            ],
            2 => [
                'Estructuras de Datos',
                'Bases de Datos I',
                'Desarrollo Web I',
                'Redes de Computadoras I',
                'Análisis y Diseño de Sistemas',
                'Programación de Aplicaciones',
            ],
            3 => [
                'Ingeniería de Software',
                'Desarrollo Web II',
                'Aplicaciones Móviles',
                'Administración de Redes',
                'Seguridad Informática',
                'Calidad de Software',
            ],
        ],
        'TOP-2023' => [
            1 => [
                'Matemática Aplicada',
                'Dibujo Técnico',
                'Topografía General I',
                'Geometría Analítica',
                'Cartografía',
                'Geodesia Básica',
                'Informática Aplicada',
            ],
            2 => [
                'Topografía General II',
                'Topografía de Obras',
                'Fotogrametría',
                'Sistemas de Información Geográfica I',
                'GNSS',
                'Catastro y Ordenamiento Territorial',
                'Ajuste de Redes',
            ],
            3 => [
                'Geodesia Satelital',
                'Sistemas de Información Geográfica II',
                'Teledetección',
                'Avalúos y Peritajes',
                'Gestión Territorial',
                'Legislación Catastral',
                'Proyecto de Grado',
            ],
        ],
    ];

    private const NUMERO_INICIAL = [
        'SIS-2023' => [2 => 2, 3 => 2],
    ];

    private const GRUPOS_POR_NIVEL = [
        'COC-2023' => [
            1 => [['paralelo' => 'A', 'turno' => 1]],
            2 => [['paralelo' => 'A', 'turno' => 1]],
            3 => [['paralelo' => 'A', 'turno' => 2]],
        ],
        'CNG-2023' => [
            1 => [['paralelo' => 'A', 'turno' => 3]],
            2 => [['paralelo' => 'A', 'turno' => 3]],
            3 => [['paralelo' => 'A', 'turno' => 3]],
        ],
        'INF-2023' => [
            1 => [['paralelo' => 'A', 'turno' => 1]],
            2 => [['paralelo' => 'A', 'turno' => 1]],
            3 => [['paralelo' => 'A', 'turno' => 1]],
            4 => [['paralelo' => 'A', 'turno' => 1]],
            5 => [['paralelo' => 'A', 'turno' => 1]],
            6 => [['paralelo' => 'A', 'turno' => 1]],
        ],
        'MTZ-2023' => [
            1 => [['paralelo' => 'A', 'turno' => 1], ['paralelo' => 'B', 'turno' => 1]],
            2 => [['paralelo' => 'A', 'turno' => 1], ['paralelo' => 'B', 'turno' => 1]],
            3 => [['paralelo' => 'A', 'turno' => 3], ['paralelo' => 'B', 'turno' => 3]],
        ],
        'SIS-2023' => [
            1 => [['paralelo' => 'A', 'turno' => 3]],
            2 => [['paralelo' => 'A', 'turno' => 3]],
            3 => [['paralelo' => 'A', 'turno' => 3]],
        ],
        'TOP-2023' => [
            1 => [['paralelo' => 'A', 'turno' => 3]],
            2 => [['paralelo' => 'A', 'turno' => 3]],
            3 => [['paralelo' => 'A', 'turno' => 3]],
        ],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Este seeder de datos ficticios no se puede ejecutar en producción.');
        }

        $gestion = GestionAcademica::query()
            ->where('vigente', true)
            ->where('activo', true)
            ->orderByDesc('id_gestionacademica')
            ->first();

        if (! $gestion) {
            throw new RuntimeException('No hay una gestión vigente y activa para crear el catálogo demostrativo.');
        }

        $carreras = Carrera::query()
            ->whereIn('codigo', array_keys(self::CARRERAS))
            ->where('activo', true)
            ->get()
            ->keyBy('codigo');
        $carrerasFaltantes = array_diff(array_keys(self::CARRERAS), $carreras->keys()->all());

        if ($carrerasFaltantes !== []) {
            throw new RuntimeException(
                'No están activas estas carreras del catálogo de demostración: '.implode(', ', $carrerasFaltantes).'.'
            );
        }

        foreach (self::CARRERAS as $codigo => $configuracion) {
            if ((int) $carreras[$codigo]->regimen !== $configuracion['regimen']) {
                throw new RuntimeException(
                    "El régimen de {$codigo} no coincide con el plan demostrativo; no se modificó la carrera."
                );
            }
        }

        $creados = DB::transaction(function () use ($carreras, $gestion): array {
            $materiasCreadas = 0;
            $gruposCreados = 0;

            foreach (self::CURRICULAS_DEMO as $codigoCarrera => $niveles) {
                $carrera = $carreras[$codigoCarrera];
                $horas = self::CARRERAS[$codigoCarrera]['regimen'] === Carrera::REGIMEN_SEMESTRAL ? 64 : 80;

                foreach ($niveles as $nivel => $nombres) {
                    $numero = self::NUMERO_INICIAL[$codigoCarrera][$nivel] ?? 1;

                    foreach ($nombres as $nombre) {
                        [$materia, $materiaCreada] = $this->obtenerOCrearMateria(
                            $carrera,
                            (int) $nivel,
                            $numero,
                            $nombre,
                            $horas
                        );
                        $materiasCreadas += $materiaCreada ? 1 : 0;

                        foreach (self::GRUPOS_POR_NIVEL[$codigoCarrera][(int) $nivel] as $grupo) {
                            $gruposCreados += $this->obtenerOCrearGrupo(
                                $materia,
                                (int) $gestion->id_gestionacademica,
                                $grupo
                            );
                        }

                        $numero++;
                    }
                }
            }

            $this->validarCatalogoCompleto($carreras, (int) $gestion->id_gestionacademica);

            return compact('materiasCreadas', 'gruposCreados');
        });

        $this->command?->info(
            "Catálogo demostrativo listo para {$gestion->gestion}: {$creados['materiasCreadas']} materias y "
            ."{$creados['gruposCreados']} grupos creados; los registros existentes no fueron sobrescritos."
        );
    }

    private function obtenerOCrearMateria(
        Carrera $carrera,
        int $nivel,
        int $numero,
        string $nombre,
        int $horas
    ): array {
        $codigo = sprintf('DEMO-%s-N%d-%02d', $carrera->codigo, $nivel, $numero);
        $existente = Materia::query()->where('codigo', $codigo)->first();

        if ($existente) {
            $coincide = (int) $existente->id_carrera === (int) $carrera->id_carrera
                && (int) $existente->nivel === $nivel
                && (int) $existente->numero === $numero
                && $existente->nombre === $nombre
                && (bool) $existente->activo
                && (bool) $existente->curricular;

            if (! $coincide) {
                throw new RuntimeException("La materia {$codigo} ya existe con datos diferentes o inactivos.");
            }

            return [$existente, false];
        }

        if (Materia::query()
            ->where('id_carrera', $carrera->id_carrera)
            ->where('nivel', $nivel)
            ->where('numero', $numero)
            ->exists()) {
            throw new RuntimeException(
                "La carrera {$carrera->codigo} ya tiene una materia con nivel {$nivel}, número {$numero}; no se duplicó."
            );
        }

        $materia = Materia::create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'creditajemateria' => 3,
            'curricular' => true,
            'horas' => $horas,
            'nivel' => $nivel,
            'numero' => $numero,
            'activo' => true,
            'id_carrera' => $carrera->id_carrera,
        ]);

        return [$materia, true];
    }

    private function obtenerOCrearGrupo(Materia $materia, int $gestionId, array $datosGrupo): int
    {
        $codigo = $materia->codigo.'-'.$datosGrupo['paralelo'];
        $existentes = Grupo::query()
            ->where('codigo', $codigo)
            ->where('id_gestionacademica', $gestionId)
            ->get();

        if ($existentes->count() > 1) {
            throw new RuntimeException("Hay más de un grupo con el código {$codigo}; no se modificó ninguno.");
        }

        $existente = $existentes->first();
        if ($existente) {
            $coincide = (int) $existente->id_materia === (int) $materia->id_materia
                && (int) $existente->capacidad === 30
                && (int) $existente->turno === (int) $datosGrupo['turno']
                && (bool) $existente->activo
                && (bool) $existente->abierto;

            if (! $coincide) {
                throw new RuntimeException("El grupo {$codigo} ya existe con otros datos o está inactivo.");
            }

            return 0;
        }

        Grupo::create([
            'codigo' => $codigo,
            'capacidad' => 30,
            'turno' => $datosGrupo['turno'],
            'abierto' => true,
            'activo' => true,
            'id_persona' => null,
            'id_gestionacademica' => $gestionId,
            'id_materia' => $materia->id_materia,
        ]);

        return 1;
    }

    private function validarCatalogoCompleto($carreras, int $gestionId): void
    {
        foreach (self::CARRERAS as $codigo => $configuracion) {
            $carrera = $carreras[$codigo];
            $materias = Materia::query()
                ->where('id_carrera', $carrera->id_carrera)
                ->where('activo', true)
                ->where('curricular', true)
                ->get();
            $nivelesConMaterias = $materias
                ->pluck('nivel')
                ->filter()
                ->map(fn ($nivel): int => (int) $nivel)
                ->unique()
                ->all();
            $nivelesFaltantes = array_diff($configuracion['niveles'], $nivelesConMaterias);

            if ($nivelesFaltantes !== []) {
                throw new RuntimeException(
                    "La carrera {$codigo} no tiene materias curriculares activas para los niveles "
                    .implode(', ', $nivelesFaltantes).'.'
                );
            }

            foreach ($materias as $materia) {
                $tieneGrupoDisponible = Grupo::query()
                    ->where('id_materia', $materia->id_materia)
                    ->where('id_gestionacademica', $gestionId)
                    ->where('activo', true)
                    ->where('abierto', true)
                    ->whereIn('turno', [1, 2, 3])
                    ->get(['codigo'])
                    ->contains(fn (Grupo $grupo): bool =>
                        preg_match('/(?:^|[-_])[A-D]$/i', trim((string) $grupo->codigo)) === 1
                    );

                if (! $tieneGrupoDisponible) {
                    throw new RuntimeException(
                        "La materia {$materia->codigo} no tiene un grupo abierto con paralelo válido en la gestión vigente."
                    );
                }
            }
        }
    }
}
