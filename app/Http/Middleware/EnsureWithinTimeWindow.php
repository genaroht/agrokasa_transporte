<?php

namespace App\Http\Middleware;

use App\Models\Programacion;
use App\Models\TimeWindow;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class EnsureWithinTimeWindow
{
    /**
     * Aplica la validación de ventana de tiempo.
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo aplicamos a métodos que modifican datos
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // Admin general podría saltarse la restricción (si quieres)
        if (method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral()) {
            return $next($request);
        }

        /*
         * 1) Identificar la programación sobre la que se está operando
         *    - Puede venir como {programacion} en la ruta (route model binding)
         *    - O como programacion_id en el body
         */
        $programacion = null;

        $routeProgramacion = $request->route('programacion');
        if ($routeProgramacion instanceof Programacion) {
            $programacion = $routeProgramacion;
        } elseif ($routeProgramacion) {
            $programacion = Programacion::find($routeProgramacion);
        } elseif ($request->has('programacion_id')) {
            $programacion = Programacion::find($request->input('programacion_id'));
        }

        if (!$programacion) {
            // Si no hay contexto de programación, por ahora dejamos pasar.
            // Si quieres ser más estricto, aquí podrías abortar.
            return $next($request);
        }

        $programacion->loadMissing('sucursal', 'area', 'horario');

        $sucursalId = $programacion->sucursal_id;
        $areaId     = $programacion->area_id;
        $horarioId  = $programacion->horario_id;
        $fecha      = $programacion->fecha?->toDateString();

        $timezone = $programacion->sucursal?->timezone ?? config('app.timezone', 'America/Lima');
        $now      = Carbon::now($timezone);

        /*
         * 2) Buscar ventana de tiempo:
         *    - Primero específica por usuario.
         *    - Luego por área (sin usuario).
         */
        $window = TimeWindow::where('sucursal_id', $sucursalId)
            ->where('user_id', $user->id)
            ->when($areaId, fn ($q) => $q->where(function ($qq) use ($areaId) {
                $qq->whereNull('area_id')->orWhere('area_id', $areaId);
            }))
            ->when($horarioId, fn ($q) => $q->where(function ($qq) use ($horarioId) {
                $qq->whereNull('horario_id')->orWhere('horario_id', $horarioId);
            }))
            ->when($fecha, fn ($q) => $q->where(function ($qq) use ($fecha) {
                $qq->whereNull('fecha')->orWhere('fecha', $fecha);
            }))
            ->orderByDesc('id')
            ->first();

        if (!$window) {
            // Fallback por área (sin user_id)
            $window = TimeWindow::where('sucursal_id', $sucursalId)
                ->whereNull('user_id')
                ->when($areaId, fn ($q) => $q->where(function ($qq) use ($areaId) {
                    $qq->whereNull('area_id')->orWhere('area_id', $areaId);
                }))
                ->when($horarioId, fn ($q) => $q->where(function ($qq) use ($horarioId) {
                    $qq->whereNull('horario_id')->orWhere('horario_id', $horarioId);
                }))
                ->when($fecha, fn ($q) => $q->where(function ($qq) use ($fecha) {
                    $qq->whereNull('fecha')->orWhere('fecha', $fecha);
                }))
                ->orderByDesc('id')
                ->first();
        }

        // Si no hay ventana definida, por ahora permitimos (modo "relajado" en desarrollo)
        if (!$window) {
            return $next($request);
        }

        // Si está marcada como expirada, bloquear
        if ($window->estado === 'expirado') {
            abort(403, 'La ventana de tiempo para este horario ya expiró.');
        }

        // Si está reabierta temporalmente y aún estamos dentro de reabierto_hasta
        if ($window->estado === 'reabierto' && $window->reabierto_hasta) {
            if ($now->lessThanOrEqualTo($window->reabierto_hasta)) {
                return $next($request);
            }
        }

        // Caso normal: validar entre hora_inicio y hora_fin en la fecha de la programación
        if ($fecha) {
            $inicio = Carbon::parse($fecha . ' ' . $window->hora_inicio, $timezone);
            $fin    = Carbon::parse($fecha . ' ' . $window->hora_fin, $timezone);
        } else {
            // sin fecha, asumimos hoy
            $inicio = Carbon::today($timezone)->setTimeFromTimeString($window->hora_inicio);
            $fin    = Carbon::today($timezone)->setTimeFromTimeString($window->hora_fin);
        }

        if ($now->lt($inicio) || $now->gt($fin)) {
            abort(403, 'Fuera de la ventana de tiempo permitida para registrar o editar esta programación.');
        }

        return $next($request);
    }
}
