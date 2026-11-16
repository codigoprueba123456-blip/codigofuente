<?php

namespace Tests\Support;

use App\Models\Actividad;
use App\Models\GestionAcademica;
use App\Models\Recurso;
use App\Services\Postulantes\VentanaPostulacionPublica;
use DateTimeInterface;

trait OpensPublicPostulationWindow
{
    protected function openPublicPostulationWindow(
        GestionAcademica $gestion,
        ?DateTimeInterface $inicio = null,
        ?DateTimeInterface $fin = null,
        bool $activo = true
    ): Actividad {
        $recurso = Recurso::firstOrCreate(
            ['urlpattern' => VentanaPostulacionPublica::RECURSO_URL],
            ['nombre' => 'Postulación pública (formulario y búsqueda por CI)']
        );

        $actividad = Actividad::create([
            'nombre' => 'Período de postulación de prueba',
            'inicio' => $inicio ?? now()->subMinute(),
            'fin' => $fin ?? now()->addMonths(2),
            'activo' => $activo,
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);
        $actividad->funcionalidades()->attach($recurso->id_recurso);

        return $actividad;
    }
}
