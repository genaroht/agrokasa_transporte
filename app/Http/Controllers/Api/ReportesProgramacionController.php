<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Horario;
use App\Models\Paradero;
use App\Models\ProgramacionDetalle;
use App\Models\Ruta;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class ReportesProgramacionController extends Controller
{
    /**
     * GET /api/v1/reportes/paradero-horario
     * Parámetros: fecha, sucursal_id (opcional si es Admin General)
     */
    public function paraderoHorario(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('view_reports')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'fecha'       => 'nullable|date',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $fecha = $request->input('fecha', now()->toDateString());

        if ($user->isAdminGeneral()) {
            $sucursalId = $request->input('sucursal_id') ?: Sucursal::where('activo', true)->value('id');
        } else {
            $sucursalId = $user->sucursal_id;
        }

        $paraderos = Paradero::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $horarios = Horario::where(function ($q) use ($sucursalId) {
                $q->whereNull('sucursal_id')
                  ->orWhere('sucursal_id', $sucursalId);
            })
            ->where('activo', true)
            ->orderBy('hora')
            ->get(['id', 'nombre', 'hora']);

        $rows = ProgramacionDetalle::selectRaw('
                programacion_detalles.paradero_id,
                programaciones.horario_id,
                SUM(programacion_detalles.personas) as total
            ')
            ->join('programaciones', 'programacion_detalles.programacion_id', '=', 'programaciones.id')
            ->where('programaciones.sucursal_id', $sucursalId)
            ->whereDate('programaciones.fecha', $fecha)
            ->groupBy('programacion_detalles.paradero_id', 'programaciones.horario_id')
            ->get();

        $matriz = [];
        foreach ($rows as $r) {
            $p = $r->paradero_id;
            $h = $r->horario_id;
            if (!isset($matriz[$p])) {
                $matriz[$p] = [];
            }
            $matriz[$p][$h] = (int) $r->total;
        }

        return response()->json([
            'fecha'       => $fecha,
            'sucursal_id' => $sucursalId,
            'paraderos'   => $paraderos,
            'horarios'    => $horarios,
            'matriz'      => $matriz,
        ]);
    }

    /**
     * GET /api/v1/reportes/ruta-paradero
     * Parámetros: fecha, sucursal_id
     */
    public function rutaParadero(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('view_reports')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'fecha'       => 'nullable|date',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $fecha = $request->input('fecha', now()->toDateString());

        if ($user->isAdminGeneral()) {
            $sucursalId = $request->input('sucursal_id') ?: Sucursal::where('activo', true)->value('id');
        } else {
            $sucursalId = $user->sucursal_id;
        }

        $rutas = Ruta::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre']);

        $paraderos = Paradero::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $rows = ProgramacionDetalle::selectRaw('
                programacion_detalles.ruta_id,
                programacion_detalles.paradero_id,
                SUM(programacion_detalles.personas) as total
            ')
            ->join('programaciones', 'programacion_detalles.programacion_id', '=', 'programaciones.id')
            ->where('programaciones.sucursal_id', $sucursalId)
            ->whereDate('programaciones.fecha', $fecha)
            ->groupBy('programacion_detalles.ruta_id', 'programacion_detalles.paradero_id')
            ->get();

        $matriz = [];
        foreach ($rows as $r) {
            $rutaId = $r->ruta_id ?? 0;
            $p = $r->paradero_id;
            if (!isset($matriz[$rutaId])) {
                $matriz[$rutaId] = [];
            }
            $matriz[$rutaId][$p] = (int) $r->total;
        }

        return response()->json([
            'fecha'       => $fecha,
            'sucursal_id' => $sucursalId,
            'rutas'       => $rutas,
            'paraderos'   => $paraderos,
            'matriz'      => $matriz,
        ]);
    }
}
