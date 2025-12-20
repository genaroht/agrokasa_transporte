<?php

namespace App\Http\Controllers;

use App\Models\TimeWindow;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TimeWindowStatusController extends Controller
{
    /**
     * Devuelve el estado de ventanas del usuario actual.
     * - Si la petición espera JSON -> JSON
     * - Si es web normal -> vista simple.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $timezone = $user->sucursal->timezone ?? config('app.timezone', 'America/Lima');
        $now      = Carbon::now($timezone);

        $ventanas = TimeWindow::where('sucursal_id', $user->sucursal_id)
            ->where(function ($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get()
            ->map(function (TimeWindow $w) use ($now) {
                $d = [
                    'id'            => $w->id,
                    'fecha'         => $w->fecha?->toDateString(),
                    'hora_inicio'   => $w->hora_inicio,
                    'hora_fin'      => $w->hora_fin,
                    'estado'        => $w->estado,
                    'reabierto_hasta'=> optional($w->reabierto_hasta)->toDateTimeString(),
                ];

                $d['ahora'] = $now->toDateTimeString();

                return $d;
            });

        if ($request->expectsJson()) {
            return response()->json([
                'now'      => $now->toDateTimeString(),
                'ventanas' => $ventanas,
            ]);
        }

        return view('timewindows.status', [
            'now'      => $now,
            'ventanas' => $ventanas,
        ]);
    }
}
