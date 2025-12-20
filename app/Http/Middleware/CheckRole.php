<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'No autenticado');
        }

        if ($user->isAdminGeneral()) {
            return $next($request); // siempre puede
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        abort(403, 'No tiene rol autorizado');
    }
}
