<?php

namespace Database\Seeders\Modulos;

use App\Models\Carrera;
use App\Models\GestionAcademica;
use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Database\Seeder;
use RuntimeException;

class InscripcionManualGruposSeeder extends Seeder
{
    public function run(): void
    {
        $carrera = Carrera::query()
            ->where('codigo', 'SIS-2023')
            ->where('activo', true)
            ->first();

        if (! $carrera) {
            throw new RuntimeException('No se encontró la carrera activa SIS-2023. Carga primero el catálogo académico.');
        }

        $gestion = GestionAcademica::query()
            ->where('vigente', true)
            ->orderByDesc('id_gestionacademica')
            ->first();

        if (! $gestion) {
            throw new RuntimeException('No hay una gestión académica vigente para crear grupos de inscripción manual.');
        }

        $materias = Materia::query()
            ->where('id_carrera', $carrera->id_carrera)
            ->whereNotNull('nivel')
            ->orderBy('nivel')
            ->orderBy('numero')
            ->orderBy('id_materia')
            ->get();

        if ($materias->isEmpty()) {
            throw new RuntimeException('La carrera activa SIS-2023 no tiene materias con nivel definido.');
        }

        foreach ($materias as $materia) {
            $codigoMateria = trim((string) $materia->codigo);
            if ($codigoMateria === '') {
                $codigoMateria = 'SIS-2023-'.$materia->id_materia;
            }

            foreach ([
                ['paralelo' => 'A', 'turno' => 1],
                ['paralelo' => 'B', 'turno' => 3],
            ] as $datosGrupo) {
                $codigoGrupo = $codigoMateria.'-'.$datosGrupo['paralelo'];
                $grupoExistente = Grupo::query()
                    ->where('codigo', $codigoGrupo)
                    ->where('id_gestionacademica', $gestion->id_gestionacademica)
                    ->first();

                if ($grupoExistente) {
                    if ((int) $grupoExistente->id_materia !== (int) $materia->id_materia) {
                        throw new RuntimeException("El código de grupo {$codigoGrupo} ya pertenece a otra materia en la gestión vigente.");
                    }

                    continue;
                }

                Grupo::create([
                    'codigo' => $codigoGrupo,
                    'capacidad' => 30,
                    'turno' => $datosGrupo['turno'],
                    'abierto' => true,
                    'id_persona' => null,
                    'id_gestionacademica' => $gestion->id_gestionacademica,
                    'id_materia' => $materia->id_materia,
                ]);
            }
        }
    }
}
