<?php

namespace App\Http\Middleware;

use App\Models\Sucursal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SucursalMiddleware
{
    /**
     * Establece una sucursal de contexto para TODA la app.
     *
     * - Para usuarios normales: siempre su sucursal fija.
     * - Para admin_general: usa la sucursal elegida en el header (guardada en sesión).
     *
     * Comparte la sucursal como:
     *  - $sucursalActual en TODAS las vistas Blade
     *  - $request->attributes['sucursalActual'] para controladores
     *  - Y reescribe $user->sucursal_id en memoria para que el código viejo
     *    que filtra por auth()->user()->sucursal_id use siempre la sucursal activa.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->deny($request, 'Usuario no autenticado.');
        }

        $sucursal = null;

        // =========================================================
        // ADMIN GENERAL  -> puede cambiar de sucursal con el combo
        // =========================================================
        if (method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral()) {

            // 1) Ver si hay una sucursal elegida en sesión
            $sessionId = $request->session()->get('sucursal_activa_id');

            if ($sessionId) {
                $sucursal = Sucursal::where('activo', true)->find($sessionId);
            }

            // 2) Si no hay o es inválida, buscamos una de respaldo
            if (! $sucursal) {
                // a) su sucursal asignada en BD, si existe y está activa
                if ($user->sucursal_id) {
                    $sucursal = Sucursal::where('activo', true)->find($user->sucursal_id);
                }

                // b) si tampoco, la primera sucursal activa
                if (! $sucursal) {
                    $sucursal = Sucursal::where('activo', true)->orderBy('nombre')->first();
                }

                // c) guardamos esa en la sesión como sucursal activa
                if ($sucursal) {
                    $request->session()->put('sucursal_activa_id', $sucursal->id);
                }
            }

        // =========================================================
        // USUARIOS NORMALES  -> siempre su sucursal fija
        // =========================================================
        } else {
            if (! $user->sucursal_id) {
                return $this->deny($request, 'No tiene una sucursal asignada. Contacte a TI.');
            }

            $sucursal = Sucursal::where('activo', true)->find($user->sucursal_id);

            if (! $sucursal) {
                return $this->deny($request, 'La sucursal asignada no es válida o está inactiva.');
            }

            // por consistencia, guardamos la misma en sesión
            $request->session()->put('sucursal_activa_id', $sucursal->id);
        }

        if (! $sucursal) {
            return $this->deny($request, 'No se encontró una sucursal activa disponible.');
        }

        // 1) Compartimos con TODAS las vistas Blade
        view()->share('sucursalActual', $sucursal);

        // 2) La dejamos también disponible para controladores
        $request->attributes->set('sucursalActual', $sucursal);

        // 3) TRUCO IMPORTANTE:
        //    Sobrescribimos el atributo sucursal_id del usuario EN MEMORIA
        //    (NO guardamos en BD) para que cualquier código que haga:
        //      $user = auth()->user();
        //      $user->sucursal_id
        //    obtenga SIEMPRE la sucursal activa.
        $user->setAttribute('sucursal_id', $sucursal->id);

        return $next($request);
    }

    protected function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('dashboard')->withErrors($message);
    }
}
