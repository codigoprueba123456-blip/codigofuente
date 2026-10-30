<?php

namespace App\Http\Middleware;

use App\Models\Actividad;
use App\Models\Recurso;
use App\Models\User;
use App\Support\MenuResourceSync;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFunctionalityAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $resourcePath = $this->resourcePath($request->path());
        if (!$resourcePath) {
            return $next($request);
        }

        if (!auth()->check()) {
            return redirect()->guest(route('login'));
        }

        $resource = Recurso::where('urlpattern', $resourcePath)->first();
        if (!$resource) {
            MenuResourceSync::sync();
            $resource = Recurso::where('urlpattern', $resourcePath)->first();
        }
        if (!$resource || in_array($resourcePath, [
            'planes/calendario',
            'planes/carreras',
            'planes/gestiones',
            'planes/materias',
        ], true)) {
            return $next($request);
        }

        if (str_starts_with($resourcePath, 'admin/')) {
            return $next($request);
        }

        if (!$this->requiresEnabledFunctionality($request)) {
            return $next($request);
        }

        if ($this->functionalityIsEnabled($resource->id_recurso, $request)) {
            return $next($request);
        }

        return response()->view('errors.functionality-locked', [
            'resourcePath' => $resourcePath,
        ], Response::HTTP_FORBIDDEN);
    }

    private function resourcePath(string $requestPath): ?string
    {
        $requestPath = trim($requestPath, '/');
        $paths = collect(config('functionality.menu', []))
            ->flatMap(fn (array $item) => $this->menuPaths($item))
            ->unique()
            ->sortByDesc(fn (string $path) => strlen($path));

        foreach ($paths as $path) {
            if ($requestPath === $path || str_starts_with($requestPath, $path . '/')) {
                return $path;
            }
        }

        return null;
    }

    private function menuPaths(array $item): array
    {
        $paths = [];

        if (!empty($item['url']) && $item['url'] !== '#' && !isset($item['type'])) {
            $paths[] = trim($item['url'], '/');
        }

        foreach ($item['submenu'] ?? [] as $child) {
            $paths = [...$paths, ...$this->menuPaths($child)];
        }

        return $paths;
    }

    private function requiresEnabledFunctionality(Request $request): bool
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        $action = last(explode('.', (string) $request->route()?->getName()));

        return in_array($action, ['create', 'edit', 'import', 'programar'], true);
    }

    private function functionalityIsEnabled(int $resourceId, Request $request): bool
    {
        $now = now();
        $user = $request->user();
        if (!$user instanceof User) {
            return false;
        }

        return Actividad::query()
            ->where('funcionalidad', $resourceId)
            ->where(fn ($query) => $query->whereNull('inicio')->orWhere('inicio', '<=', $now))
            ->where(fn ($query) => $query->whereNull('fin')->orWhere('fin', '>=', $now))
            ->visibleTo($user)
            ->exists();
    }
}
