<?php

namespace App\Http\Middleware;

use App\Services\Postulantes\VentanaPostulacionPublica;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicPostulacionAbierta
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(VentanaPostulacionPublica::class)->actividadAbierta()) {
            return redirect()
                ->route('postulantes.bienvenida')
                ->with('error', 'El período de postulación está cerrado.');
        }

        return $next($request);
    }
}
