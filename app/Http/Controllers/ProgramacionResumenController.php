<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Paradero;
use App\Models\ProgramacionDetalle;
use App\Models\Ruta;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProgramacionResumenController extends Controller
{
    public function paraderoHorario(Request $request)
    {
        $user = $request->user();

        $fechaStr = $request->input('fecha', Carbon::today()->toDateString());
        $fecha    = Carbon::parse($fechaStr);

        if ($user->isAdminGeneral()) {
            $sucursalId = (int)($request->input('sucursal_id', $user->sucursal_id));
        } else {
            $sucursalId = $user->sucursal_id;
        }

        $sucursal = Sucursal::findOrFail($sucursalId);

        $paraderos = Paradero::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $horarios = Horario::where(function ($q) use ($sucursalId) {
                $q->whereNull('sucursal_id')->orWhere('sucursal_id', $sucursalId);
            })
            ->where('activo', true)
            ->orderBy('hora')
            ->get();

        $rows = ProgramacionDetalle::selectRaw('
                programacion_detalles.paradero_id,
                programaciones.horario_id,
                SUM(programacion_detalles.personas) as total
            ')
            ->join('programaciones', 'programacion_detalles.programacion_id', '=', 'programaciones.id')
            ->where('programaciones.sucursal_id', $sucursalId)
            ->whereDate('programaciones.fecha', $fecha->toDateString())
            ->groupBy('programacion_detalles.paradero_id', 'programaciones.horario_id')
            ->get();

        $matriz = [];
        foreach ($rows as $r) {
            $matriz[$r->paradero_id][$r->horario_id] = (int)$r->total;
        }

        return view('programaciones.resumen_paradero_horario', compact(
            'fecha',
            'sucursal',
            'paraderos',
            'horarios',
            'matriz'
        ));
    }

    public function rutaParadero(Request $request)
    {
        $user = $request->user();

        $fechaStr = $request->input('fecha', Carbon::today()->toDateString());
        $fecha    = Carbon::parse($fechaStr);

        if ($user->isAdminGeneral()) {
            $sucursalId = (int)($request->input('sucursal_id', $user->sucursal_id));
        } else {
            $sucursalId = $user->sucursal_id;
        }

        $sucursal = Sucursal::findOrFail($sucursalId);

        $rutas = Ruta::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();

        $paraderos = Paradero::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $rows = ProgramacionDetalle::selectRaw('
                programacion_detalles.ruta_id,
                programacion_detalles.paradero_id,
                SUM(programacion_detalles.personas) as total
            ')
            ->join('programaciones', 'programacion_detalles.programacion_id', '=', 'programaciones.id')
            ->where('programaciones.sucursal_id', $sucursalId)
            ->whereDate('programaciones.fecha', $fecha->toDateString())
            ->groupBy('programacion_detalles.ruta_id', 'programacion_detalles.paradero_id')
            ->get();

        $matriz = [];
        foreach ($rows as $r) {
            $rId = $r->ruta_id ?? 0;
            $matriz[$rId][$r->paradero_id] = (int)$r->total;
        }

        return view('programaciones.resumen_ruta_paradero', compact(
            'fecha',
            'sucursal',
            'rutas',
            'paraderos',
            'matriz'
        ));
    }
}
