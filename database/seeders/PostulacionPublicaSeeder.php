<?php

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\GestionAcademica;
use App\Models\Recurso;
use App\Services\Postulantes\VentanaPostulacionPublica;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PostulacionPublicaSeeder extends Seeder
{
    private const NOMBRE_ACTIVIDAD = 'Ventana de postulación pública';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Este seeder de demostración no se puede ejecutar en producción.');
        }

        $gestion = GestionAcademica::where('vigente', true)
            ->where('activo', true)
            ->orderByDesc('id_gestionacademica')
            ->first();

        if (! $gestion) {
            throw new RuntimeException('Debe existir una gestión académica habilitada y vigente para abrir las postulaciones.');
        }

        $inicio = now();
        $fin = $inicio->copy()->addMonths(2);

        DB::transaction(function () use ($gestion, $inicio, $fin): void {
            $recurso = Recurso::updateOrCreate(
                ['urlpattern' => VentanaPostulacionPublica::RECURSO_URL],
                ['nombre' => 'Postulación pública (formulario y búsqueda por CI)']
            );

            $actividad = Actividad::updateOrCreate(
                [
                    'nombre' => self::NOMBRE_ACTIVIDAD,
                    'id_gestionacademica' => $gestion->id_gestionacademica,
                ],
                [
                    'descripcion' => 'Período de acceso al formulario público y a la búsqueda por CI.',
                    'inicio' => $inicio,
                    'fin' => $fin,
                    'activo' => true,
                    'funcionalidad' => null,
                ]
            );

            $actividad->funcionalidades()->sync([$recurso->id_recurso]);
            $actividad->carreras()->sync([]);
            $actividad->usuarios()->sync([]);
        });

        $this->command?->info(
            'Postulaciones públicas abiertas desde '.$inicio->toDateTimeString().' hasta '.$fin->toDateTimeString().'.'
        );
    }
}
