<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Uso:
     *   ->middleware('role:admin_general')
     *   ->middleware('role:admin_general,admin_sucursal')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->deny($request, 'Usuario no autenticado.');
        }

        if (empty($roles)) {
            return $next($request);
        }

        // Admin General siempre pasa
        if (method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral()) {
            return $next($request);
        }

        $roles = array_map('strval', $roles);

        $hasAny = false;
        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole($roles)) {
                $hasAny = true;
            }
        }

        if (! $hasAny) {
            return $this->deny($request, 'No tiene el rol requerido para acceder a este recurso.');
        }

        return $next($request);
    }

    protected function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
            ], 403);
        }

        return redirect()->back()->withErrors($message);
    }
}
