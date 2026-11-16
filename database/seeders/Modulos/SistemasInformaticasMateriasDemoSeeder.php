<?php

namespace Database\Seeders\Modulos;

use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SistemasInformaticasMateriasDemoSeeder extends Seeder
{
    private const MATERIAS = [
        [
            'codigo' => 'SIS-DEMO-2026-N2-01',
            'nombre' => 'Programación Orientada a Objetos (Demo)',
            'nivel' => 2,
        ],
        [
            'codigo' => 'SIS-DEMO-2026-N3-01',
            'nombre' => 'Proyecto Integrador de Sistemas (Demo)',
            'nivel' => 3,
        ],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Este seeder demostrativo no se puede ejecutar en producción.');
        }

        $carrera = Carrera::query()
            ->where('codigo', 'SIS-2023')
            ->where('activo', true)
            ->first();

        if (! $carrera) {
            throw new RuntimeException('No se encontró la carrera activa SIS-2023. No se crearon materias demo.');
        }

        $creadas = DB::transaction(function () use ($carrera): int {
            $nivelesConMateriasActivas = Materia::activas()
                ->where('id_carrera', $carrera->id_carrera)
                ->whereIn('nivel', [2, 3])
                ->pluck('nivel')
                ->map(fn ($nivel): int => (int) $nivel)
                ->all();
            $cantidad = 0;

            foreach (self::MATERIAS as $datos) {
                if (in_array($datos['nivel'], $nivelesConMateriasActivas, true)) {
                    continue;
                }

                $existente = Materia::query()
                    ->where('codigo', $datos['codigo'])
                    ->where('id_carrera', $carrera->id_carrera)
                    ->first();

                if ($existente) {
                    throw new RuntimeException(
                        "La materia demo {$datos['codigo']} ya existe, pero no está activa o tiene otros datos. No se modificó."
                    );
                }

                if (Materia::where('codigo', $datos['codigo'])->exists()) {
                    throw new RuntimeException("El código {$datos['codigo']} ya pertenece a otra carrera. No se creó la materia.");
                }

                Materia::create([
                    ...$datos,
                    'activo' => true,
                    'curricular' => true,
                    'numero' => 1,
                    'id_carrera' => $carrera->id_carrera,
                ]);
                $nivelesConMateriasActivas[] = $datos['nivel'];
                $cantidad++;
            }

            return $cantidad;
        });

        $this->command?->info("Seeder de materias demo de Sistemas terminado: {$creadas} materias creadas.");
    }
}
