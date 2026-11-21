<?php

namespace Database\Seeders\Modulos;

use App\Models\Actividad;
use App\Models\Carrera;
use App\Models\Cursa;
use App\Models\GestionAcademica;
use App\Models\Instituto;
use App\Models\User;
use Database\Seeders\StudentPortalSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AcademicoSeeder extends Seeder
{
    private const CARRERAS_VIGENTES = [
        'ADM-2023',
        'COC-2023',
        'CNG-2023',
        'GAS-2023',
        'INF-2023',
        'MTZ-2023',
        'SEJ-2023',
        'SIS-2023',
        'TOP-2023',
    ];

    private const NOMBRES_CARRERAS_PERMITIDAS = [
        'ADMINISTRACION DE EMPRESAS',
        'CONSTRUCCION CIVIL',
        'CONTADURIA GENERAL',
        'GASTRONOMIA',
        'INFORMATICA INDUSTRIAL',
        'MECANICA AUTOMOTRIZ',
        'SECRETARIADO EJECUTIVO',
        'SISTEMAS INFORMATICOS',
        'TOPOGRAFIA Y GEODESIA',
    ];

    /**
     * Prepara instituto y portal, carga el catálogo académico y gestiones de referencia,
     * y agrega datos de demostración para calendario e historial de cursado.
     * Termina normalizando el catálogo al deshabilitar versiones anteriores y retirar carreras
     * fuera de la lista permitida, junto con sus dependencias en entornos no productivos.
     */
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
            ['nombreinstituto' => 'Instituto Tecnologico Sacaba'],
            ['abreviatura' => 'ITSA', 'preciocredito' => 5]
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
            $datosCarrera['activo'] = $datosCarrera['version'] === 2023;

            Carrera::updateOrCreate(
                ['codigo' => $datosCarrera['codigo']],
                $datosCarrera
            );
        }

        $this->limpiarCarrerasFueraDeLista();

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

        $this->call(ItSaCurriculumSeeder::class);

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

    /**
     * Compara las carreras existentes con las listas permitidas por nombre y código vigente.
     * Activa las versiones vigentes, desactiva versiones anteriores y, fuera de producción,
     * elimina las carreras ajenas al catálogo con sus registros dependientes.
     */
    private function limpiarCarrerasFueraDeLista(): void
    {
        $nombresPermitidos = array_map(fn (string $nombre): string => $this->normalizarNombreCarrera($nombre), self::NOMBRES_CARRERAS_PERMITIDAS);
        $carreras = DB::table('carreras')->get(['id_carrera', 'codigo', 'nombre']);
        $carrerasFueraDeLista = $carreras
            ->reject(fn (object $carrera): bool => in_array($this->normalizarNombreCarrera($carrera->nombre), $nombresPermitidos, true));
        $idsFueraDeLista = $carrerasFueraDeLista->pluck('id_carrera')->all();
        $idsVersionesAnteriores = $carreras
            ->filter(fn (object $carrera): bool =>
                in_array($this->normalizarNombreCarrera($carrera->nombre), $nombresPermitidos, true)
                && ! in_array($carrera->codigo, self::CARRERAS_VIGENTES, true)
            )
            ->pluck('id_carrera')
            ->all();

        if ($idsFueraDeLista !== [] && app()->environment('production')) {
            throw new RuntimeException(
                'Se encontraron carreras fuera del catálogo permitido. La eliminación de datos relacionados está bloqueada en producción.'
            );
        }

        $eliminados = DB::transaction(function () use ($idsFueraDeLista, $idsVersionesAnteriores): array {
            DB::table('carreras')
                ->whereIn('codigo', self::CARRERAS_VIGENTES)
                ->update(['activo' => true, 'updated_at' => now()]);

            if ($idsVersionesAnteriores !== []) {
                DB::table('carreras')
                    ->whereIn('id_carrera', $idsVersionesAnteriores)
                    ->update(['activo' => false, 'updated_at' => now()]);
            }

            if ($idsFueraDeLista === []) {
                return ['carreras' => 0, 'materias' => 0, 'estudiantes' => 0, 'postulantes' => 0];
            }

            return $this->eliminarDependenciasDeCarreras($idsFueraDeLista);
        });

        if ($eliminados['carreras'] > 0) {
            $this->command?->warn(
                "Se eliminaron {$eliminados['carreras']} carreras fuera de la lista, {$eliminados['materias']} materias, "
                ."{$eliminados['estudiantes']} perfiles de estudiante y {$eliminados['postulantes']} postulaciones, "
                .'junto con sus inscripciones, grupos, pagos y actividades relacionados.'
            );
        }
    }

    /**
     * Reúne y elimina los datos académicos y financieros vinculados a carreras obsoletas.
     * El método se invoca desde la transacción de limpiarCarrerasFueraDeLista().
     * Primero reúne IDs dependientes de materias, grupos, postulaciones, inscripciones y personas;
     * después elimina comprobantes, pagos, detalles, relaciones curriculares y los registros raíz.
     * Conserva personas que tengan un rol distinto de estudiante o un registro laboral y devuelve
     * cantidades eliminadas para el resumen del seeder.
     *
     * @param array<int, int> $carreraIds IDs de carreras que salieron del catálogo permitido.
     * @return array{carreras: int, materias: int, estudiantes: int, postulantes: int}
     */
    private function eliminarDependenciasDeCarreras(array $carreraIds): array
    {
        $materiaIds = DB::table('materia')->whereIn('id_carrera', $carreraIds)->pluck('id_materia')->all();
        $grupoIds = DB::table('grupo')->whereIn('id_materia', $materiaIds)->pluck('id_grupo')->all();
        $postulanteIds = DB::table('postulante')->whereIn('id_carrera', $carreraIds)->pluck('id_postulante')->all();
        $estudiantesCarrera = DB::table('estudiante')
            ->whereIn('id_carrera', $carreraIds)
            ->orWhereIn('id_postulante', $postulanteIds)
            ->get(['id_persona', 'id_postulante']);
        $inscritosCarrera = DB::table('inscrito')->whereIn('id_carrera', $carreraIds)->pluck('id_inscrito')->all();
        $inscritosMaterias = DB::table('inscrito_materia')
            ->whereIn('id_materia', $materiaIds)
            ->orWhereIn('id_grupo', $grupoIds)
            ->pluck('id_inscrito')
            ->all();
        $inscritoIds = array_values(array_unique(array_merge($inscritosCarrera, $inscritosMaterias)));
        $personIds = array_values(array_unique(array_merge(
            $estudiantesCarrera->pluck('id_persona')->all(),
            DB::table('inscrito')->whereIn('id_inscrito', $inscritoIds)->pluck('id_persona')->all(),
            DB::table('cursa')->whereIn('id_carrera', $carreraIds)->pluck('id_persona')->all()
        )));
        $inscritoIds = array_values(array_unique(array_merge(
            $inscritoIds,
            DB::table('inscrito')->whereIn('id_persona', $personIds)->pluck('id_inscrito')->all()
        )));
        $postulanteIds = array_values(array_unique(array_merge(
            $postulanteIds,
            $estudiantesCarrera->pluck('id_postulante')->filter()->all(),
            DB::table('inscrito')->whereIn('id_inscrito', $inscritoIds)->pluck('id_postulante_origen')->filter()->all()
        )));
        $pagoIds = DB::table('pago')
            ->whereIn('id_inscrito', $inscritoIds)
            ->orWhereIn('id_postulante', $postulanteIds)
            ->pluck('id_pago')
            ->all();
        $comprobanteIds = DB::table('comprobante')
            ->whereIn('id_carrera', $carreraIds)
            ->orWhereIn('id_inscrito', $inscritoIds)
            ->orWhereIn('id_postulante', $postulanteIds)
            ->orWhereIn('id_estudiante', $personIds)
            ->orWhereIn('id_usuario', $personIds)
            ->pluck('id_comprobante')
            ->all();
        $actividadIds = DB::table('actividad_carrera')
            ->whereIn('id_carrera', $carreraIds)
            ->pluck('id_actividad')
            ->all();

        DB::table('detalle')
            ->whereIn('id_pago', $pagoIds)
            ->orWhereIn('id_comprobante', $comprobanteIds)
            ->delete();
        DB::table('pago')->whereIn('id_pago', $pagoIds)->delete();
        DB::table('comprobante')->whereIn('id_comprobante', $comprobanteIds)->delete();
        DB::table('inscrito_materia')
            ->whereIn('id_inscrito', $inscritoIds)
            ->orWhereIn('id_materia', $materiaIds)
            ->orWhereIn('id_grupo', $grupoIds)
            ->delete();
        DB::table('grupo')->whereIn('id_grupo', $grupoIds)->delete();
        DB::table('actividad')->whereIn('id_actividad', $actividadIds)->delete();
        DB::table('cursa')
            ->whereIn('id_carrera', $carreraIds)
            ->orWhereIn('id_persona', $personIds)
            ->delete();
        DB::table('inscrito')->whereIn('id_inscrito', $inscritoIds)->delete();
        DB::table('postulante')->whereIn('id_postulante', $postulanteIds)->delete();
        $estudiantesEliminados = DB::table('estudiante')->whereIn('id_persona', $personIds)->delete();
        DB::table('materia')->whereIn('id_materia', $materiaIds)->delete();
        $carrerasEliminadas = DB::table('carreras')->whereIn('id_carrera', $carreraIds)->delete();

        $personasConOtrosRoles = DB::table('cuenta as cuenta')
            ->join('rol', 'rol.id_rol', '=', 'cuenta.id_rol')
            ->whereIn('cuenta.id_persona', $personIds)
            ->where('rol.nombre', '<>', 'Estudiante')
            ->pluck('cuenta.id_persona')
            ->all();
        $empleadoIds = DB::table('empleados')->whereIn('id_persona', $personIds)->pluck('id_persona')->all();
        $personasAConservar = array_values(array_unique(array_merge($personasConOtrosRoles, $empleadoIds)));
        $personasAEliminar = array_values(array_diff($personIds, $personasAConservar));
        $rolEstudianteId = DB::table('rol')->where('nombre', 'Estudiante')->value('id_rol');

        if ($rolEstudianteId !== null) {
            DB::table('cuenta')
                ->whereIn('id_persona', $personasAConservar)
                ->where('id_rol', $rolEstudianteId)
                ->delete();
        }

        DB::table('personas')->whereIn('id_persona', $personasAEliminar)->delete();

        return [
            'carreras' => $carrerasEliminadas,
            'materias' => count($materiaIds),
            'estudiantes' => $estudiantesEliminados,
            'postulantes' => count($postulanteIds),
        ];
    }

    /**
     * Normaliza nombres para comparar el catálogo sin diferencias por acentos, mayúsculas o espacios extremos.
     */
    private function normalizarNombreCarrera(string $nombre): string
    {
        return mb_strtoupper(trim(Str::ascii($nombre)));
    }
}
