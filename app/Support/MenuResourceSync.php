<?php

namespace App\Support;

use App\Models\Recurso;

class MenuResourceSync
{
    /**
     * Sincroniza recursos desde el menú indicado o desde la configuración activa de navegación.
     */
    public static function sync(?array $items = null): void
    {
        self::syncItems($items ?? config('functionality.menu', config('adminlte.menu', [])));
    }

    /**
     * Recorre el menú y sus submenús, creando o actualizando los recursos para cada enlace válido.
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
