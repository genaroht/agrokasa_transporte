<?php

namespace App\Http\Controllers;

use App\Models\Programacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user           = $request->user();
        $sucursalActual = $request->attributes->get('sucursalActual');

        // Timezone según sucursal seleccionada en el header (si tiene)
        $timezone = $sucursalActual->timezone
            ?? ($user->sucursal->timezone ?? config('app.timezone', 'America/Lima'));

        // ===============================
        // 1. Determinar fecha del dashboard
        // ===============================
        $fechaParam = $request->input('fecha');

        if ($fechaParam) {
            try {
                $fechaBase = Carbon::parse($fechaParam, $timezone)->startOfDay();
            } catch (\Exception $e) {
                $fechaBase = Carbon::now($timezone)->startOfDay();
            }
        } else {
            $fechaBase = Carbon::now($timezone)->startOfDay();
        }

        $fechaSeleccionada = $fechaBase->toDateString();

        // ===============================
        // 2. Query base por sucursal + fecha
        // ===============================
        $baseQuery = Programacion::query()
            ->whereDate('fecha', $fechaSeleccionada);

        if ($sucursalActual) {
            $baseQuery->where('sucursal_id', $sucursalActual->id);
        } else {
            // Fallback: para no admin, al menos su propia sucursal
            if (!method_exists($user, 'isAdminGeneral') || !$user->isAdminGeneral()) {
                if ($user->sucursal_id) {
                    $baseQuery->where('sucursal_id', $user->sucursal_id);
                }
            }
        }

        // Clonar queries para cada tipo
        $querySalida = (clone $baseQuery)->where('tipo', 'salida');
        $queryRecojo = (clone $baseQuery)->where('tipo', 'recojo');

        // ===============================
        // 3. Métricas SALIDA
        // ===============================
        $salidaProgramaciones = (int) $querySalida->count();
        $salidaTotalPersonas  = (int) (clone $querySalida)->sum('total_personas');

        $salidaPorAreaQuery = DB::table('programaciones')
            ->join('areas', 'programaciones.area_id', '=', 'areas.id')
            ->select(
                'areas.id as id',
                'areas.nombre as nombre',
                DB::raw('SUM(programaciones.total_personas) as total')
            )
            ->whereDate('programaciones.fecha', $fechaSeleccionada)
            ->where('programaciones.tipo', 'salida');

        if ($sucursalActual) {
            $salidaPorAreaQuery->where('programaciones.sucursal_id', $sucursalActual->id);
        } else {
            if (!method_exists($user, 'isAdminGeneral') || !$user->isAdminGeneral()) {
                if ($user->sucursal_id) {
                    $salidaPorAreaQuery->where('programaciones.sucursal_id', $user->sucursal_id);
                }
            }
        }

        $salidaPorArea = $salidaPorAreaQuery
            ->groupBy('areas.id', 'areas.nombre')
            ->orderBy('areas.nombre')
            ->get();

        // ===============================
        // 4. Métricas RECOJO
        // (se programa el día "fechaSeleccionada" para el día siguiente,
        // pero a nivel BD seguimos usando fecha = fechaSeleccionada)
        // ===============================
        $recojoProgramaciones = (int) $queryRecojo->count();
        $recojoTotalPersonas  = (int) (clone $queryRecojo)->sum('total_personas');

        $recojoPorAreaQuery = DB::table('programaciones')
            ->join('areas', 'programaciones.area_id', '=', 'areas.id')
            ->select(
                'areas.id as id',
                'areas.nombre as nombre',
                DB::raw('SUM(programaciones.total_personas) as total')
            )
            ->whereDate('programaciones.fecha', $fechaSeleccionada)
            ->where('programaciones.tipo', 'recojo');

        if ($sucursalActual) {
            $recojoPorAreaQuery->where('programaciones.sucursal_id', $sucursalActual->id);
        } else {
            if (!method_exists($user, 'isAdminGeneral') || !$user->isAdminGeneral()) {
                if ($user->sucursal_id) {
                    $recojoPorAreaQuery->where('programaciones.sucursal_id', $user->sucursal_id);
                }
            }
        }

        $recojoPorArea = $recojoPorAreaQuery
            ->groupBy('areas.id', 'areas.nombre')
            ->orderBy('areas.nombre')
            ->get();

        // ===============================
        // 5. Devolver vista con todas las métricas
        // ===============================
        return view('dashboard.index', [
            'fecha'          => $fechaSeleccionada,
            'sucursalActual' => $sucursalActual,

            'metricasSalida' => [
                'programaciones' => $salidaProgramaciones,
                'total_personas' => $salidaTotalPersonas,
                'por_area'       => $salidaPorArea,
            ],

            'metricasRecojo' => [
                'programaciones' => $recojoProgramaciones,
                'total_personas' => $recojoTotalPersonas,
                'por_area'       => $recojoPorArea,
            ],
        ]);
    }
}
