<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\Programacion;
use App\Models\Sucursal;
use App\Models\TimeWindow;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class TimeWindowMiddleware
{
    /**
     * Debe aplicarse a TODAS las rutas que GUARDAN programación:
     *  - programaciones.store
     *  - programaciones.update
     *  - programaciones.rutas_lotes.update
     *  - APIs que actualicen matrices o rutas/lotes/comedores.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->deny($request, null, 'Usuario no autenticado.');
        }

        // Si aún no existe la tabla (desarrollo / primeras migraciones), no bloqueamos.
        if (!Schema::hasTable('time_windows')) {
            return $next($request);
        }

        // Si quieres que el Admin General nunca sea bloqueado, lo dejamos pasar aquí.
        if (method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral()) {
            return $next($request);
        }

        // 1) Determinar contexto (sucursal, fecha programación, área, horario)
        $programacion = $request->route('programacion');

        // Por si en alguna ruta llegara solo el ID numérico:
        if ($programacion && !$programacion instanceof Programacion && is_numeric($programacion)) {
            $programacion = Programacion::find($programacion);
        }

        if ($programacion instanceof Programacion) {
            $sucursalId = $programacion->sucursal_id;
            $fechaProg  = $programacion->fecha?->toDateString();
            $areaId     = $programacion->area_id;
            $horarioId  = $programacion->horario_id;
        } else {
            // Para creación (store), viene por request
            $sucursalId = (int) ($request->input('sucursal_id') ?: $user->sucursal_id);
            $fechaProg  = $request->input('fecha') ?: now()->toDateString();
            $areaId     = $request->input('area_id');
            $horarioId  = $request->input('horario_id');
        }

        if (!$sucursalId || !$fechaProg) {
            return $this->deny(
                $request,
                $programacion instanceof Programacion ? $programacion : null,
                'No se pudo determinar sucursal/fecha para validar ventana de tiempo.'
            );
        }

        $sucursal = Sucursal::find($sucursalId);

        // 2) Hora oficial del servidor según timezone de la sucursal
        $timezone = $sucursal?->timezone ?: config('app.timezone');
        $now      = Carbon::now($timezone); // hora del servidor
        $nowTime  = $now->format('H:i:s');

        // 3) Buscar ventana vigente que aplique al usuario/contexto
        $rolesIds = method_exists($user, 'roles') ? $user->roles->pluck('id')->all() : [];

        $ventana = TimeWindow::query()
            ->vigentes()
            ->where('sucursal_id', $sucursalId)
            ->whereDate('fecha', $fechaProg)
            // Área (opcional)
            ->where(function ($q) use ($areaId) {
                $q->whereNull('area_id');
                if ($areaId) {
                    $q->orWhere('area_id', $areaId);
                }
            })
            // Horario (opcional)
            ->where(function ($q) use ($horarioId) {
                $q->whereNull('horario_id');
                if ($horarioId) {
                    $q->orWhere('horario_id', $horarioId);
                }
            })
            // Usuario / rol
            ->where(function ($q) use ($user, $rolesIds) {
                // Ventanas globales
                $q->where(function ($q2) {
                    $q2->whereNull('user_id')
                       ->whereNull('role_id');
                });

                // Ventanas por usuario
                $q->orWhere('user_id', $user->id);

                // Ventanas por rol
                if (!empty($rolesIds)) {
                    $q->orWhereIn('role_id', $rolesIds);
                }
            })
            // Validación de tiempo (normal o reabierta)
            ->where(function ($q) use ($nowTime, $now) {
                $q->where(function ($q2) use ($nowTime) {
                    // Dentro de la ventana normal
                    $q2->where('hora_inicio', '<=', $nowTime)
                       ->where('hora_fin', '>=', $nowTime);
                })->orWhere(function ($q2) use ($now) {
                    // O reabierta temporalmente
                    $q2->where('estado', 'reabierto')
                       ->whereNotNull('reabierto_hasta')
                       ->where('reabierto_hasta', '>=', $now);
                });
            })
            ->first();

        if (!$ventana) {
            // No hay ventana que permita esta edición → bloquear y auditar
            return $this->deny(
                $request,
                $programacion instanceof Programacion ? $programacion : null,
                'Fuera de la ventana de tiempo permitida.'
            );
        }

        return $next($request);
    }

    /**
     * Maneja la denegación y registra en auditoría el intento.
     */
    protected function deny(Request $request, ?Programacion $programacion, string $message): Response
    {
        $user = $request->user();

        if ($user && class_exists(AuditLog::class)) {
            AuditLog::create([
                'user_id'        => $user->id,
                'action'         => 'time_window_denied',
                'auditable_type' => $programacion ? Programacion::class : null,
                'auditable_id'   => $programacion?->id,
                'old_values'     => null,
                'new_values'     => [
                    'sucursal_id' => $programacion?->sucursal_id ?? $request->input('sucursal_id'),
                    'fecha'       => $programacion?->fecha?->toDateString() ?? $request->input('fecha'),
                    'area_id'     => $programacion?->area_id ?? $request->input('area_id'),
                    'horario_id'  => $programacion?->horario_id ?? $request->input('horario_id'),
                    'endpoint'    => $request->path(),
                    'method'      => $request->method(),
                ],
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
                'code'    => 'TIME_WINDOW_FORBIDDEN',
            ], 403);
        }

        return redirect()->back()->withErrors($message);
    }
}
    