<?php

namespace App\MenuFilters;

use Illuminate\Support\Facades\Auth;
use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

class RoleMenuFilter implements FilterInterface
{
    public function transform($item)
    {
        if (empty($item['url'])) {
            return $item;
        }

        $user = Auth::user();
        $path = trim((string) $item['url'], '/');
        if ($path === 'reportes/dashboard') {
            $canViewReportDashboard = $user
                && ($user->isAdministrator()
                    || $user->canAccess('reportes/inscripciones/lista-inscritos-carrera'));

            if (! $canViewReportDashboard) {
                $item['restricted'] = true;
            }

            return $item;
        }

        if (! $user || ! $user->canAccess($path)) {
            $item['restricted'] = true;
        }

        return $item;
    }
}
