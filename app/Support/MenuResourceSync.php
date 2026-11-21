<?php

namespace App\Support;

use App\Models\Recurso;

class MenuResourceSync
{
    /**
     * Actualiza el catálogo de recursos desde el menú recibido o la configuración de navegación.
     * Si no se entrega un menú, prioriza functionality.menu y usa adminlte.menu como alternativa.
     */
    public static function sync(?array $items = null): void
    {
        self::syncItems($items ?? config('functionality.menu', config('adminlte.menu', [])));
    }

    /**
     * Visita entradas del menú y submenús para mantener un recurso por URL navegable.
     * Omite encabezados, enlaces vacíos y el marcador '#'; updateOrCreate evita duplicar rutas.
     */
    private static function syncItems(array $items): void
    {
        foreach ($items as $item) {
            if (!empty($item['url']) && $item['url'] !== '#'
                && !empty($item['text']) && !isset($item['type'])) {
                Recurso::updateOrCreate(
                    ['urlpattern' => trim($item['url'], '/')],
                    ['nombre' => $item['text']]
                );
            }

            if (!empty($item['submenu']) && is_array($item['submenu'])) {
                self::syncItems($item['submenu']);
            }
        }
    }
}
