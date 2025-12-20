<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'No autenticado');
        }

        // Admin General pasa siempre
        if ($user->isAdminGeneral()) {
            return $next($request);
        }

        foreach ($permissions as $perm) {
            if ($user->hasPermission($perm)) {
                return $next($request);
            }
        }

        abort(403, 'No tiene permisos suficientes.');
    }
}
