<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserSucursalScope
{
    /**
     * Fuerza el acceso a solo la sucursal del usuario (salvo admin_general).
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Admin General puede ver todas las sucursales
        if (method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral()) {
            return $next($request);
        }

        $userSucursalId = (int) $user->sucursal_id;

        // 1) Si la ruta tiene {sucursal} como modelo
        $routeSucursal = $request->route('sucursal');
        if ($routeSucursal instanceof \App\Models\Sucursal) {
            if ((int) $routeSucursal->id !== $userSucursalId) {
                abort(403, 'No puede acceder a otra sucursal.');
            }
        }

        // 2) Si viene un parámetro sucursal_id en URL o request
        $routeSucursalId = $request->route('sucursal_id');
        $inputSucursalId = $request->input('sucursal_id');

        $requestedId = $routeSucursalId ?? $inputSucursalId;

        if ($requestedId !== null && (int) $requestedId !== $userSucursalId) {
            abort(403, 'No puede acceder a otra sucursal.');
        }

        // 3) Si no hay nada, forzamos sucursal_id del usuario
        if ($requestedId === null) {
            $request->merge(['sucursal_id' => $userSucursalId]);
        }

        return $next($request);
    }
}
