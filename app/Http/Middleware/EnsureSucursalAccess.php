<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSucursalAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'No autenticado');
        }

        // Admin General puede ver todas las sucursales
        if ($user->isAdminGeneral()) {
            return $next($request);
        }

        // Para usuarios con sucursal fija, verificamos parámetros
        $sucursalId = $request->route('sucursal_id') ?? $request->input('sucursal_id');

        if ($sucursalId && (int)$sucursalId !== (int)$user->sucursal_id) {
            abort(403, 'No puede acceder a otra sucursal');
        }

        return $next($request);
    }
}
