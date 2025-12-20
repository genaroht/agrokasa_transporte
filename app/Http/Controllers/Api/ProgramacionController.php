<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paradero;
use App\Models\Programacion;
use App\Models\ProgramacionDetalle;
use App\Models\Ruta;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class ProgramacionController extends Controller
{
    /**
     * Listado de programaciones (por sucursal/fecha/estado).
     *
     * GET /api/v1/programaciones
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('view_reports') && !$user->hasPermissionTo('manage_programs')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
            'area_id'     => 'nullable|integer',
            'horario_id'  => 'nullable|integer',
            'estado'      => 'nullable|string|in:borrador,confirmado,cerrado',
        ]);

        // Sucursal por defecto
        if ($user->isAdminGeneral()) {
            $sucursalId = $request->input('sucursal_id') ?: Sucursal::where('activo', true)->value('id');
        } else {
            $sucursalId = $user->sucursal_id;
        }

        $query = Programacion::with(['area', 'horario', 'sucursal'])
            ->where('sucursal_id', $sucursalId);

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->input('fecha_hasta'));
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }
        if ($request->filled('horario_id')) {
            $query->where('horario_id', $request->input('horario_id'));
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $programaciones = $query->orderBy('fecha', 'desc')
            ->orderBy('horario_id')
            ->orderBy('area_id')
            ->limit(200)
            ->get()
            ->map(function (Programacion $p) {
                return [
                    'id'             => $p->id,
                    'fecha'          => $p->fecha->toDateString(),
                    'sucursal_id'    => $p->sucursal_id,
                    'sucursal'       => $p->sucursal->nombre,
                    'area_id'        => $p->area_id,
                    'area'           => $p->area->nombre,
                    'horario_id'     => $p->horario_id,
                    'horario'        => $p->horario->nombre,
                    'hora'           => $p->horario->hora,
                    'estado'         => $p->estado,
                    'total_personas' => $p->total_personas,
                ];
            });

        return response()->json([
            'sucursal_id'   => $sucursalId,
            'programaciones'=> $programaciones,
        ]);
    }

    /**
     * Detalle de una programación (cabecera).
     *
     * GET /api/v1/programaciones/{programacion}
     */
    public function show(Request $request, Programacion $programacion)
    {
        $user = $request->user();

        if (
            !$user->hasPermissionTo('view_reports') &&
            !$user->hasPermissionTo('manage_programs')
        ) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if (!$user->isAdminGeneral() && $programacion->sucursal_id !== $user->sucursal_id) {
            return response()->json(['message' => 'No puede ver programaciones de otra sucursal'], 403);
        }

        $soloLectura = $programacion->estaCerrada() || !$user->hasPermissionTo('manage_programs');

        return response()->json([
            'id'             => $programacion->id,
            'fecha'          => $programacion->fecha->toDateString(),
            'sucursal_id'    => $programacion->sucursal_id,
            'sucursal'       => $programacion->sucursal->nombre,
            'area_id'        => $programacion->area_id,
            'area'           => $programacion->area->nombre,
            'horario_id'     => $programacion->horario_id,
            'horario'        => $programacion->horario->nombre,
            'hora'           => $programacion->horario->hora,
            'estado'         => $programacion->estado,
            'total_personas' => $programacion->total_personas,
            'solo_lectura'   => $soloLectura,
        ]);
    }

    /**
     * Matriz (Paradero x Ruta) para una programación.
     *
     * GET /api/v1/programaciones/{programacion}/matrix
     */
    public function matrix(Request $request, Programacion $programacion)
    {
        $user = $request->user();

        if (
            !$user->hasPermissionTo('view_reports') &&
            !$user->hasPermissionTo('manage_programs')
        ) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if (!$user->isAdminGeneral() && $programacion->sucursal_id !== $user->sucursal_id) {
            return response()->json(['message' => 'No puede ver programaciones de otra sucursal'], 403);
        }

        $paraderos = Paradero::where('sucursal_id', $programacion->sucursal_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $rutas = Ruta::where('sucursal_id', $programacion->sucursal_id)
            ->where('activo', true)
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre']);

        $detalles = $programacion->detalles()->get();

        $valores = [];

        foreach ($detalles as $det) {
            $p = $det->paradero_id;
            $r = $det->ruta_id ?? 0;

            if (!isset($valores[$p])) {
                $valores[$p] = [];
            }

            $valores[$p][$r] = $det->personas;
        }

        return response()->json([
            'programacion_id' => $programacion->id,
            'fecha'           => $programacion->fecha->toDateString(),
            'paraderos'       => $paraderos,
            'rutas'           => $rutas,
            'valores'         => $valores,
        ]);
    }

    /**
     * Actualiza la matriz Paradero x Ruta (personas).
     *
     * PUT /api/v1/programaciones/{programacion}/matrix
     * Body JSON:
     * {
     *   "detalles": {
     *      "paradero_id": {
     *         "ruta_id": personas,
     *         "0": personas_sin_ruta
     *      }
     *   }
     * }
     *
     * IMPORTANTE: esta ruta debe tener middleware "time.window".
     */
    public function updateMatrix(Request $request, Programacion $programacion)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('manage_programs')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if (!$user->isAdminGeneral() && $programacion->sucursal_id !== $user->sucursal_id) {
            return response()->json(['message' => 'No puede modificar programaciones de otra sucursal'], 403);
        }

        if ($programacion->estaCerrada()) {
            return response()->json(['message' => 'La programación está cerrada.'], 422);
        }

        $data = $request->validate([
            'detalles'     => 'nullable|array',
            'detalles.*.*' => 'nullable|integer|min:0|max:10000',
        ]);

        $detallesInput = $data['detalles'] ?? [];

        // La lógica de escritura es equivalente a la del controlador web
        $programacion->load('detalles');

        $existentes = [];
        foreach ($programacion->detalles as $det) {
            $p = $det->paradero_id;
            $r = $det->ruta_id ?? 0;
            $existentes[$p][$r] = $det;
        }

        $idsConservados = [];

        foreach ($detallesInput as $paraderoId => $rutasMap) {
            foreach ($rutasMap as $rutaId => $personas) {
                $rutaId = (int) $rutaId;
                $personas = (int) $personas;

                if ($personas < 0) {
                    $personas = 0;
                }

                if ($personas === 0) {
                    if (isset($existentes[$paraderoId][$rutaId])) {
                        $existentes[$paraderoId][$rutaId]->delete();
                    }
                    continue;
                }

                if (isset($existentes[$paraderoId][$rutaId])) {
                    $det = $existentes[$paraderoId][$rutaId];
                    $det->personas = $personas;
                    $det->save();
                } else {
                    $det = ProgramacionDetalle::create([
                        'programacion_id' => $programacion->id,
                        'paradero_id'     => $paraderoId,
                        'ruta_id'         => $rutaId ?: null,
                        'personas'        => $personas,
                    ]);
                }

                $idsConservados[] = $det->id;
            }
        }

        if (!empty($idsConservados)) {
            $programacion->detalles()->whereNotIn('id', $idsConservados)->delete();
        } else {
            $programacion->detalles()->delete();
        }

        $programacion->recalcularTotalPersonas();

        return response()->json([
            'message'         => 'Matriz actualizada correctamente.',
            'total_personas'  => $programacion->total_personas,
        ]);
    }
}
