<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Models\TimeWindow;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TimeWindowStatusController extends Controller
{
    /**
     * Devuelve las ventanas activas/reabiertas para el usuario actual,
     * opcionalmente filtradas por fecha/área/horario.
     *
     * GET /api/v1/time-windows/activos
     */
    public function activos(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'fecha'       => 'nullable|date',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'area_id'     => 'nullable|integer',
            'horario_id'  => 'nullable|integer',
        ]);

        $fecha      = $request->input('fecha', now()->toDateString());
        $sucursalId = $request->input('sucursal_id', $user->sucursal_id);
        $areaId     = $request->input('area_id');
        $horarioId  = $request->input('horario_id');

        $sucursal = Sucursal::find($sucursalId);
        $timezone = $sucursal?->timezone ?: config('app.timezone');
        $now      = Carbon::now($timezone);
        $nowTime  = $now->format('H:i:s');

        $rolesIds = $user->roles->pluck('id')->all();

        $query = TimeWindow::vigentes()
            ->where('sucursal_id', $sucursalId)
            ->whereDate('fecha', $fecha)
            ->where(function ($q) use ($areaId) {
                $q->whereNull('area_id');
                if ($areaId) {
                    $q->orWhere('area_id', $areaId);
                }
            })
            ->where(function ($q) use ($horarioId) {
                $q->whereNull('horario_id');
                if ($horarioId) {
                    $q->orWhere('horario_id', $horarioId);
                }
            })
            ->where(function ($q) use ($user, $rolesIds) {
                $q->where(function ($q2) {
                    $q2->whereNull('user_id')->whereNull('role_id');
                });

                $q->orWhere('user_id', $user->id);

                if (!empty($rolesIds)) {
                    $q->orWhereIn('role_id', $rolesIds);
                }
            })
            ->where(function ($q) use ($nowTime, $now) {
                $q->where(function ($q2) use ($nowTime) {
                    $q2->where('hora_inicio', '<=', $nowTime)
                       ->where('hora_fin', '>=', $nowTime);
                })->orWhere(function ($q2) use ($now) {
                    $q2->where('estado', 'reabierto')
                       ->whereNotNull('reabierto_hasta')
                       ->where('reabierto_hasta', '>=', $now);
                });
            })
            ->with(['area', 'horario'])
            ->orderBy('hora_inicio');

        $ventanas = $query->get()->map(function (TimeWindow $tw) use ($timezone) {
            return [
                'id'          => $tw->id,
                'sucursal_id' => $tw->sucursal_id,
                'fecha'       => $tw->fecha->toDateString(),
                'area_id'     => $tw->area_id,
                'area_nombre' => $tw->area?->nombre,
                'horario_id'  => $tw->horario_id,
                'horario'     => $tw->horario?->nombre,
                'hora_inicio' => $tw->hora_inicio,
                'hora_fin'    => $tw->hora_fin,
                'estado'      => $tw->estado,
                'reabierto_hasta' => $tw->reabierto_hasta
                    ? $tw->reabierto_hasta->setTimezone($timezone)->toDateTimeString()
                    : null,
            ];
        });

        return response()->json([
            'now_server'  => $now->toDateTimeString(),
            'timezone'    => $timezone,
            'sucursal_id' => $sucursalId,
            'fecha'       => $fecha,
            'ventanas'    => $ventanas,
        ]);
    }
}
