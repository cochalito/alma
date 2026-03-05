<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user)
            return redirect('/login');

        $role = $user->role;
        $path = $request->path();

        if ($role === 'GERENCIA') {
            return $next($request);
        }

        if (str_starts_with($role, 'ADMINISTRACION')) {
            if (str_contains($path, 'reports/reservations') || str_contains($path, 'reports/activity')) {
                abort(403, 'No tienes permiso para acceder a este reporte.');
            }
            return $next($request);
        }

        if (str_starts_with($role, 'RECEPCIONISTA')) {
            $allowed = ['reservations', 'charter', 'customers', 'reports/kardex', 'dashboard'];
            $canAccess = false;
            foreach ($allowed as $a) {
                if (str_contains($path, $a)) {
                    $canAccess = true;
                    break;
                }
            }
            if (!$canAccess) {
                abort(403, 'No tienes permiso para acceder a esta sección.');
            }
            return $next($request);
        }

        return $next($request);
    }
}
