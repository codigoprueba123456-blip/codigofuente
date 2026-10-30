<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureResourceAccess
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if ($roles !== []) {
            $user = $request->user();
            $allowedRoles = array_map(
                static fn (string $name): string => match (mb_strtolower($name)) {
                    'admin' => 'administrador',
                    'alumno', 'student' => 'estudiante',
                    default => mb_strtolower($name),
                },
                $roles
            );

            $roleNames = $user->cuentas()
                ->whereHas('rol')
                ->with('rol:id_rol,nombre')
                ->get()
                ->pluck('rol.nombre')
                ->map(static fn (string $name): string => match (mb_strtolower($name)) {
                    'admin' => 'administrador',
                    'alumno', 'student' => 'estudiante',
                    default => mb_strtolower($name),
                });

            $personaType = mb_strtolower(trim((string) $user->persona?->tipo));
            $normalizedPersonaType = match ($personaType) {
                'admin' => 'administrador',
                'alumno', 'student' => 'estudiante',
                default => $personaType,
            };

            if (in_array('estudiante', $allowedRoles, true)) {
                $hasStudentRecord = DB::table('estudiante')
                    ->where('id_persona', $user->id_persona)
                    ->exists();
                $hasStudentRoleOnly = $roleNames->isNotEmpty()
                    && $roleNames->contains('estudiante')
                    && $roleNames->every(static fn (string $role): bool => $role === 'estudiante');
                $hasLegacyStudentIdentity = $roleNames->isEmpty()
                    && in_array($normalizedPersonaType, $allowedRoles, true);

                abort_unless(
                    $hasStudentRecord && ($hasStudentRoleOnly || $hasLegacyStudentIdentity),
                    403
                );
            } else {
                $hasMatchingRole = $roleNames->contains(
                    static fn (string $role): bool => in_array($role, $allowedRoles, true)
                );
                $hasLegacyMatchingType = $roleNames->isEmpty()
                    && in_array($normalizedPersonaType, $allowedRoles, true);

                abort_unless($hasMatchingRole || $hasLegacyMatchingType, 403);
            }

            return $next($request);
        }

        $requestedPath = trim((string) $request->path(), '/');
        if ($requestedPath === '' || $requestedPath === 'home') {
            return $next($request);
        }

        $user = auth()->user();

        if (!$user || !$user->canAccess($requestedPath)) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
