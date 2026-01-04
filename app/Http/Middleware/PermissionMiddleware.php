<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Uso en rutas:
     *   ->middleware('permission:ver_dashboard')
     *   ->middleware('permission:gestionar_programaciones|ver_reportes')
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->deny($request, 'Usuario no autenticado.');
        }

        // Si no se pasa ningún permiso, dejamos pasar
        if (empty($permissions)) {
            return $next($request);
        }

        // Soportar sintaxis con "|" -> permission:perm1|perm2
        $flatPerms = [];
        foreach ($permissions as $perm) {
            foreach (explode('|', (string) $perm) as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $flatPerms[] = $p;
                }
            }
        }
        $flatPerms = array_values(array_unique($flatPerms));

        // Admin General pasa siempre
        if (method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral()) {
            return $next($request);
        }

        // Verificamos si tiene AL MENOS uno de los permisos
        $allowed = false;
        if (method_exists($user, 'hasPermissionTo')) {
            foreach ($flatPerms as $slug) {
                if ($user->hasPermissionTo($slug)) {
                    $allowed = true;
                    break;
                }
            }
        }

        if (! $allowed) {
            return $this->deny($request, 'No tiene permisos para acceder a este recurso.');
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

        // ⚠️ Nada de redirect()->back(), causa bucles con /login
        abort(403, $message);
    }
}
