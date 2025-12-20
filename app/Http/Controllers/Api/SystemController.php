<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SystemController extends Controller
{
    public function serverTime(Request $request)
    {
        $user = $request->user();
        $timezone = optional($user->sucursal)->timezone ?? config('app.timezone');
        $now = Carbon::now($timezone);

        return response()->json([
            'datetime' => $now->toIso8601String(),
            'date' => $now->toDateString(),
            'time' => $now->format('H:i:s'),
            'timezone' => $timezone,
        ]);
    }
}
