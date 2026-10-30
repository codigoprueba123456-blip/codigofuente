<?php

namespace Tests\Support;

use App\Models\Actividad;
use App\Models\GestionAcademica;
use App\Models\Recurso;
use App\Support\MenuResourceSync;

trait EnablesFunctionality
{
    protected function enableFunctionality(string $path): void
    {
        MenuResourceSync::sync();
        $resource = Recurso::where('urlpattern', trim($path, '/'))->firstOrFail();
        $gestion = GestionAcademica::create([
            'gestion' => 2026,
            'periodo' => 1,
            'regimen' => 1,
            'vigente' => true,
        ]);

        Actividad::create([
            'nombre' => 'Habilitación de prueba',
            'inicio' => now()->subHour(),
            'fin' => now()->addHour(),
            'funcionalidad' => $resource->id_recurso,
            'id_gestionacademica' => $gestion->id_gestionacademica,
        ]);
    }
}
