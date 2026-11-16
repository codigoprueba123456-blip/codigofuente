<?php

namespace Database\Seeders\Modulos;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class InicioInscripcionDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Este seeder demostrativo no se puede ejecutar en producción.');
        }

        $gestion = GestionAcademica::habilitadas()
            ->where('gestion', 2026)
            ->orderBy('periodo')
            ->orderByDesc('id_gestionacademica')
            ->first();

        if (! $gestion) {
            throw new RuntimeException('No existe una gestión 2026 vigente y habilitada. El seeder no crea ni modifica gestiones.');
        }

        $configuraciones = [
            [
                'nombre' => 'Sistemas Informáticos',
                'resolucion' => '210',
                'paralelosPorNivel' => [
                    1 => [['nombre' => 'A', 'turno' => 3, 'capacidad' => 30]],
                    2 => [['nombre' => 'A', 'turno' => 3, 'capacidad' => 30]],
                    3 => [['nombre' => 'A', 'turno' => 3, 'capacidad' => 30]],
                ],
            ],
            [
                'nombre' => 'Gastronomía',
                'paralelosPorNivel' => [
                    1 => [
                        ['nombre' => 'A', 'turno' => 1, 'capacidad' => 30],
                        ['nombre' => 'B', 'turno' => 1, 'capacidad' => 30],
                    ],
                    2 => [
                        ['nombre' => 'A', 'turno' => 3, 'capacidad' => 30],
                        ['nombre' => 'B', 'turno' => 3, 'capacidad' => 30],
                    ],
                    3 => [
                        ['nombre' => 'A', 'turno' => 3, 'capacidad' => 30],
                        ['nombre' => 'B', 'turno' => 3, 'capacidad' => 30],
                    ],
                ],
            ],
            [
                'nombre' => 'Secretariado Ejecutivo',
                'paralelosPorNivel' => [
                    1 => [['nombre' => 'A', 'turno' => 3, 'capacidad' => 30]],
                    2 => [['nombre' => 'A', 'turno' => 3, 'capacidad' => 30]],
                    3 => [['nombre' => 'A', 'turno' => 3, 'capacidad' => 30]],
                ],
            ],
        ];

        $carrerasActivas = Carrera::activas()->get();
        $gruposPorCrear = [];

        foreach ($configuraciones as $configuracion) {
            $carrera = $this->buscarCarrera($carrerasActivas, $configuracion['nombre']);

            if (isset($configuracion['resolucion']) && ! $this->tieneResolucion($carrera, $configuracion['resolucion'])) {
                throw new RuntimeException(
                    "La carrera {$carrera->nombre} no tiene registrada la R.M. {$configuracion['resolucion']}. No se modificó su ficha."
                );
            }

            $materias = Materia::activas()
                ->where('id_carrera', $carrera->id_carrera)
                ->orderBy('nivel')
                ->orderBy('numero')
                ->orderBy('id_materia')
                ->get();

            if ($materias->isEmpty()) {
                throw new RuntimeException("La carrera {$carrera->nombre} no tiene materias activas. No se crean materias automáticamente.");
            }

            foreach ($materias as $materia) {
                $paralelos = $configuracion['paralelosPorNivel'][(int) $materia->nivel] ?? [];
                if ($paralelos === []) {
                    throw new RuntimeException(
                        "No hay turno ni paralelo configurado para {$carrera->nombre}, nivel {$materia->nivel}."
                    );
                }

                $codigoBase = trim((string) $materia->codigo);
                if ($codigoBase === '') {
                    $codigoBase = 'MAT-'.$materia->id_materia;
                }

                foreach ($paralelos as $paralelo) {
                    $gruposPorCrear[] = [
                        'codigo' => $codigoBase.'-'.$paralelo['nombre'],
                        'capacidad' => $paralelo['capacidad'],
                        'turno' => $paralelo['turno'],
                        'abierto' => true,
                        'activo' => true,
                        'id_persona' => null,
                        'id_gestionacademica' => $gestion->id_gestionacademica,
                        'id_materia' => $materia->id_materia,
                    ];
                }
            }
        }

        $creados = DB::transaction(function () use ($gestion, $gruposPorCrear): int {
            $codigos = collect($gruposPorCrear)->pluck('codigo')->unique()->all();
            $gruposExistentes = Grupo::query()
                ->where('id_gestionacademica', $gestion->id_gestionacademica)
                ->whereIn('codigo', $codigos)
                ->get();
            $porCrear = [];

            foreach ($gruposPorCrear as $datosGrupo) {
                $mismoCodigo = $gruposExistentes->where('codigo', $datosGrupo['codigo']);

                if ($mismoCodigo->contains(fn (Grupo $grupo): bool => (int) $grupo->id_materia !== (int) $datosGrupo['id_materia'])) {
                    throw new RuntimeException("El código {$datosGrupo['codigo']} ya pertenece a otra materia en la gestión 2026.");
                }

                $existente = $mismoCodigo->first(fn (Grupo $grupo): bool =>
                    (int) $grupo->id_materia === (int) $datosGrupo['id_materia']
                    && (int) $grupo->turno === (int) $datosGrupo['turno']
                );

                if ($existente) {
                    if ((int) $existente->capacidad !== (int) $datosGrupo['capacidad']
                        || ! $existente->activo
                        || ! $existente->abierto) {
                        throw new RuntimeException(
                            "El grupo {$datosGrupo['codigo']} ya existe con otro cupo o estado. No se sobrescribió."
                        );
                    }

                    continue;
                }

                $porCrear[] = $datosGrupo;
            }

            foreach ($porCrear as $datosGrupo) {
                Grupo::create($datosGrupo);
            }

            return count($porCrear);
        });

        $this->command?->info("Seeder de demostración terminado: {$creados} grupos creados; carreras, materias, gestiones y resoluciones quedaron intactas.");
    }

    private function buscarCarrera(Collection $carreras, string $nombre): Carrera
    {
        $coincidencias = $carreras->filter(fn (Carrera $carrera): bool =>
            $this->normalizarNombre($carrera->nombre) === $this->normalizarNombre($nombre)
        );

        if ($coincidencias->count() !== 1) {
            throw new RuntimeException(
                "Se esperaba una sola carrera activa llamada {$nombre}; se encontraron {$coincidencias->count()}. No se crean carreras."
            );
        }

        return $coincidencias->first();
    }

    private function normalizarNombre(string $nombre): string
    {
        return mb_strtolower(trim(Str::ascii($nombre)));
    }

    private function tieneResolucion(Carrera $carrera, string $numero): bool
    {
        return collect($carrera->resolucionesMinisteriales())
            ->contains(fn (string $resolucion): bool => preg_match('/^0*'.preg_quote($numero, '/').'(?:\D|$)/', $resolucion) === 1);
    }
}
