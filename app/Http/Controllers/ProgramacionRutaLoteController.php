<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Programacion;
use App\Models\ProgramacionDetalle;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class ProgramacionRutaLoteController extends Controller
{
    /**
     * Lista detalles de una programación con lote/comedor.
     */
    public function index(Request $request, Programacion $programacion)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('view_reports') && !$user->hasPermissionTo('manage_programs')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if (!$user->isAdminGeneral() && $programacion->sucursal_id !== $user->sucursal_id) {
            return response()->json(['message' => 'No puede ver programaciones de otra sucursal'], 403);
        }

        $detalles = $programacion->detalles()
            ->with(['paradero', 'ruta'])
            ->orderByRaw('COALESCE(ruta_id, 0)')
            ->orderBy('paradero_id')
            ->get()
            ->map(function (ProgramacionDetalle $det) {
                return [
                    'id'        => $det->id,
                    'paradero'  => $det->paradero ? $det->paradero->nombre : null,
                    'paradero_id' => $det->paradero_id,
                    'ruta'      => $det->ruta ? $det->ruta->codigo . ' - ' . $det->ruta->nombre : null,
                    'ruta_id'   => $det->ruta_id,
                    'lote'      => $det->lote,
                    'comedor'   => $det->comedor,
                    'personas'  => $det->personas,
                ];
            });

        return response()->json([
            'programacion_id' => $programacion->id,
            'fecha'           => $programacion->fecha->toDateString(),
            'sucursal_id'     => $programacion->sucursal_id,
            'detalles'        => $detalles,
        ]);
    }

    /**
     * Actualiza lote/comedor de varios detalles.
     * Usa el mismo esquema que el web: detalles[detalle_id][lote/comedor].
     */
    public function update(Request $request, Programacion $programacion)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('manage_programs')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if (!$user->isAdminGeneral() && $programacion->sucursal_id !== $user->sucursal_id) {
            return response()->json(['message' => 'No puede modificar programaciones de otra sucursal'], 403);
        }

        if ($programacion->estaCerrada()) {
            return response()->json(['message' => 'La programación está cerrada'], 422);
        }

        $data = $request->validate([
            'detalles'           => 'required|array',
            'detalles.*.lote'    => 'nullable|string|max:50',
            'detalles.*.comedor' => 'nullable|string|max:50',
        ]);

        foreach ($data['detalles'] as $detalleId => $campos) {
            $detalle = ProgramacionDetalle::where('programacion_id', $programacion->id)
                ->where('id', $detalleId)
                ->first();

            if (!$detalle) {
                continue;
            }

            $detalle->lote    = $campos['lote'] ?? null;
            $detalle->comedor = $campos['comedor'] ?? null;
            $detalle->save();
        }

        return response()->json(['message' => 'Lotes y comedores actualizados'], 200);
    }

    /**
     * Reporte Ruta → Lote → Comedor → Total personas (JSON).
     */
    public function reporteRutaLoteCom(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('view_reports')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'fecha'       => 'required|date',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $fecha = $request->input('fecha');

        if ($user->isAdminGeneral()) {
            $sucursalId = $request->input('sucursal_id');
            if (!$sucursalId) {
                $sucursalId = Sucursal::where('activo', true)->value('id');
            }
        } else {
            $sucursalId = $user->sucursal_id;
        }

        $rows = ProgramacionDetalle::selectRaw('
                COALESCE(rutas.id, 0) as ruta_id,
                COALESCE(rutas.codigo, "SIN") as ruta_codigo,
                COALESCE(rutas.nombre, "Sin ruta") as ruta_nombre,
                COALESCE(programacion_detalles.lote, "SIN") as lote,
                COALESCE(programacion_detalles.comedor, "SIN") as comedor,
                SUM(programacion_detalles.personas) as total
            ')
            ->join('programaciones', 'programacion_detalles.programacion_id', '=', 'programaciones.id')
            ->leftJoin('rutas', 'programacion_detalles.ruta_id', '=', 'rutas.id')
            ->where('programaciones.sucursal_id', $sucursalId)
            ->whereDate('programaciones.fecha', $fecha)
            ->groupBy('ruta_id', 'ruta_codigo', 'ruta_nombre', 'lote', 'comedor')
            ->orderBy('ruta_codigo')
            ->orderBy('lote')
            ->orderBy('comedor')
            ->get();

        return response()->json([
            'fecha'       => $fecha,
            'sucursal_id' => $sucursalId,
            'data'        => $rows,
        ]);
    }
}
