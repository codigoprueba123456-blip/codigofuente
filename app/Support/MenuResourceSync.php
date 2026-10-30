<?php

namespace App\Support;

use App\Models\Recurso;

class MenuResourceSync
{
    public static function sync(?array $items = null): void
    {
        self::syncItems($items ?? config('functionality.menu', config('adminlte.menu', [])));
    }

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
