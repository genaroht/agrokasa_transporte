<?php

namespace App\Http\Middleware;

use App\Models\TimeWindow;
use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CheckTimeWindow
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'No autenticado');
        }

        // Admin general ignora ventanas (puede revisarlas pero no bloquearlo)
        if ($user->isAdminGeneral()) {
            return $next($request);
        }

        $fecha = $request->input('fecha');
        $areaId = $request->input('area_id');
        $horarioId = $request->input('horario_id');

        if (!$fecha || !$areaId || !$horarioId) {
            abort(400, 'Faltan datos para validar ventana de tiempo');
        }

        $sucursalId = $user->sucursal_id;

        // Hora actual según zona horaria de la sucursal
        $timezone = optional($user->sucursal)->timezone ?? config('app.timezone');
        $ahora = Carbon::now($timezone);

        // Buscar ventana de usuario específico
        $window = TimeWindow::where('sucursal_id', $sucursalId)
            ->where('fecha', $fecha)
            ->where('area_id', $areaId)
            ->where('horario_id', $horarioId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->whereNull('user_id')
                         ->whereIn('role_id', $user->roles()->pluck('id'));
                  });
            })
            ->orderBy('user_id', 'desc') // primero reglas específicas de usuario
            ->first();

        if (!$window || !$window->estaActivaPara($ahora)) {
            // Registrar en auditoría
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'action' => 'timewindow_blocked',
                'auditable_type' => null,
                'auditable_id' => null,
                'old_values' => null,
                'new_values' => [
                    'fecha' => $fecha,
                    'area_id' => $areaId,
                    'horario_id' => $horarioId,
                    'sucursal_id' => $sucursalId,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(403, 'Fuera de la ventana de tiempo permitida para esta área/horario.');
        }

        return $next($request);
    }
}
