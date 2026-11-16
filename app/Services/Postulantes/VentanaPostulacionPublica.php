<?php

namespace App\Services\Postulantes;

use App\Models\Actividad;
use App\Models\GestionAcademica;
use App\Models\Recurso;

class VentanaPostulacionPublica
{
    public const RECURSO_URL = 'postulantes-publica';

    public function actividadAbierta(): ?Actividad
    {
        $gestionActual = GestionAcademica::where('vigente', true)
            ->where('activo', true)
            ->orderByDesc('id_gestionacademica')
            ->first();

        if (! $gestionActual) {
            return null;
        }

        $recursoId = Recurso::where('urlpattern', self::RECURSO_URL)->value('id_recurso');
        if (! $recursoId) {
            return null;
        }

        $ahora = now();

        return Actividad::query()
            ->where('id_gestionacademica', $gestionActual->id_gestionacademica)
            ->where('activo', true)
            ->where('inicio', '<=', $ahora)
            ->where('fin', '>=', $ahora)
            ->whereDoesntHave('carreras')
            ->whereDoesntHave('usuarios')
            ->whereHas('funcionalidades', fn ($query) => $query->where('recurso.id_recurso', $recursoId))
            ->first();
    }
}
