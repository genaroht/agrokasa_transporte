<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Programacion;
use Illuminate\Http\Request;

class ProgramacionRutaLoteController extends Controller
{
    /**
     * GET /api/v1/programaciones/{programacion}/rutas-lotes
     *
     * Devuelve la matriz Ruta / Paradero / (opcional Lote / Comedor)
     * para consumirla desde la app de escritorio / móvil.
     */
    public function index(Request $request, Programacion $programacion)
    {
        $user = $request->user();

        // Permisos: ver reportes o gestionar programaciones
        if (! $user->hasPermissionTo('ver_reportes') &&
            ! $user->hasPermissionTo('gestionar_programaciones')) {
            return response()->json([
                'message' => 'No autorizado para ver rutas y lotes.',
            ], 403);
        }

        // Restringir por sucursal si no es admin_general
        if (! $user->isAdminGeneral() && $user->sucursal_id !== $programacion->sucursal_id) {
            return response()->json([
                'message' => 'No puede ver programaciones de otra sucursal.',
            ], 403);
        }

        $programacion->load([
            'sucursal',
            'area',
            'horario',
            'detalles.paradero',
            'detalles.ruta',
        ]);

        $items = [];

        foreach ($programacion->detalles as $det) {
            $items[] = [
                'detalle_id'   => $det->id,
                'paradero_id'  => $det->paradero_id,
                'paradero'     => $det->paradero?->nombre,
                'ruta_id'      => $det->ruta_id,
                'ruta_codigo'  => $det->ruta?->codigo,
                'ruta_nombre'  => $det->ruta?->nombre ?? $det->ruta?->descripcion ?? null,
                'personas'     => (int) ($det->personas ?? 0),
                // Estos campos son opcionales: si existen en BD se devuelven,
                // si no existen simplemente irán como null en el JSON.
                'lote'         => $det->lote ?? null,
                'comedor'      => $det->comedor ?? null,
                'tipo'         => $det->tipo ?? null,
            ];
        }

        return response()->json([
            'programacion' => [
                'id'          => $programacion->id,
                'fecha'       => $programacion->fecha?->toDateString(),
                'sucursal_id' => $programacion->sucursal_id,
                'sucursal'    => $programacion->sucursal?->nombre,
                'area_id'     => $programacion->area_id,
                'area'        => $programacion->area?->nombre,
                'horario_id'  => $programacion->horario_id,
                'horario'     => $programacion->horario?->nombre,
            ],
            'detalles' => $items,
        ]);
    }

    /**
     * PUT /api/v1/programaciones/{programacion}/rutas-lotes
     *
     * Actualiza SOLO cantidades de personas por detalle
     * (NO tocamos lote/comedor para no depender de columnas que quizá no existan).
     *
     * Este endpoint debe ir siempre con middleware 'time.window'.
     */
    public function update(Request $request, Programacion $programacion)
    {
        $user = $request->user();

        if (! $user->hasPermissionTo('gestionar_programaciones')) {
            return response()->json([
                'message' => 'No autorizado para modificar programaciones.',
            ], 403);
        }

        if (! $user->isAdminGeneral() && $user->sucursal_id !== $programacion->sucursal_id) {
            return response()->json([
                'message' => 'No puede modificar programaciones de otra sucursal.',
            ], 403);
        }

        $data = $request->validate([
            'items'                 => ['required', 'array'],
            'items.*.detalle_id'    => ['required', 'integer', 'exists:programacion_detalles,id'],
            'items.*.personas'      => ['required', 'integer', 'min:0'],
        ]);

        // Cargamos detalles relacionados a esta programación
        $detalles = $programacion->detalles()->get()->keyBy('id');

        foreach ($data['items'] as $item) {
            $detalleId = $item['detalle_id'];

            if (! $detalles->has($detalleId)) {
                // Si el detalle no pertenece a esta programación, lo ignoramos
                continue;
            }

            $detalle = $detalles[$detalleId];
            $detalle->personas = $item['personas'];
            $detalle->save();
        }

        return response()->json([
            'message' => 'Programación actualizada correctamente.',
        ]);
    }

    /**
     * GET /api/v1/reportes/ruta-lote-com
     *
     * Reporte resumido Ruta / Lote / Comedor (JSON) para apps.
     * Filtros opcionales: fecha, sucursal_id.
     */
    public function reporteRutaLoteCom(Request $request)
    {
        $user = $request->user();

        if (! $user->hasPermissionTo('ver_reportes') &&
            ! $user->hasPermissionTo('gestionar_programaciones')) {
            return response()->json([
                'message' => 'No autorizado para ver reportes.',
            ], 403);
        }

        $fecha      = $request->input('fecha');        // yyyy-mm-dd opcional
        $sucursalId = $request->input('sucursal_id') ?: $user->sucursal_id;

        $query = Programacion::query()
            ->with(['detalles.ruta'])
            ->when($sucursalId, function ($q) use ($sucursalId) {
                $q->where('sucursal_id', $sucursalId);
            })
            ->when($fecha, function ($q) use ($fecha) {
                $q->whereDate('fecha', $fecha);
            });

        // Si no es admin_general, restringimos a su sucursal
        if (! $user->isAdminGeneral()) {
            $query->where('sucursal_id', $user->sucursal_id);
        }

        $programaciones = $query->get();

        $rows = [];

        foreach ($programaciones as $prog) {
            foreach ($prog->detalles as $det) {
                $key = implode('|', [
                    $prog->fecha?->toDateString(),
                    $det->ruta_id ?? 0,
                    $det->lote ?? '',
                    $det->comedor ?? '',
                ]);

                if (! isset($rows[$key])) {
                    $rows[$key] = [
                        'fecha'       => $prog->fecha?->toDateString(),
                        'sucursal_id' => $prog->sucursal_id,
                        'ruta_id'     => $det->ruta_id,
                        'ruta_codigo' => $det->ruta?->codigo,
                        'ruta_nombre' => $det->ruta?->nombre ?? $det->ruta?->descripcion ?? null,
                        'lote'        => $det->lote ?? null,
                        'comedor'     => $det->comedor ?? null,
                        'personas'    => 0,
                    ];
                }

                $rows[$key]['personas'] += (int) ($det->personas ?? 0);
            }
        }

        return response()->json([
            'data' => array_values($rows),
        ]);
    }
}
