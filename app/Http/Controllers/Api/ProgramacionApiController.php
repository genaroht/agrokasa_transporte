<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Programacion;
use App\Models\ProgramacionDetalle;
use App\Models\Paradero;
use App\Models\Ruta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProgramacionApiController extends Controller
{
    // Devuelve la matriz Paradero x Ruta para un día/área/horario
    public function showMatrix(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'fecha' => 'required|date',
            'area_id' => 'required|exists:areas,id',
            'horario_id' => 'required|exists:horarios,id',
        ]);

        $sucursalId = $user->sucursal_id;
        if (!$sucursalId && ! $user->isAdminGeneral()) {
            return response()->json(['message' => 'Usuario sin sucursal asociada.'], 403);
        }

        $programacion = Programacion::with('detalles')
            ->whereDate('fecha', $data['fecha'])
            ->where('sucursal_id', $sucursalId)
            ->where('area_id', $data['area_id'])
            ->where('horario_id', $data['horario_id'])
            ->first();

        $paraderos = Paradero::where('activo', true)
            ->where('sucursal_id', $sucursalId)
            ->orderBy('nombre')
            ->get();

        $rutas = Ruta::where('activo', true)
            ->where('sucursal_id', $sucursalId)
            ->orderBy('codigo')
            ->get();

        $valores = [];
        $totalGeneral = 0;

        if ($programacion) {
            foreach ($programacion->detalles as $det) {
                $pId = $det->paradero_id;
                $rId = $det->ruta_id ?: 0;
                $valores[$pId][$rId] = $det->total_personas;
                $totalGeneral += $det->total_personas;
            }
        }

        return response()->json([
            'programacion_id' => $programacion?->id,
            'estado' => $programacion?->estado ?? 'borrador',
            'total_personas' => $totalGeneral,
            'paraderos' => $paraderos->map(fn($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
            ]),
            'rutas' => $rutas->map(fn($r) => [
                'id' => $r->id,
                'codigo' => $r->codigo,
                'nombre' => $r->nombre,
            ]),
            'valores' => $valores, // [paradero_id][ruta_id] = total_personas
        ]);
    }

    // Guarda la matriz (similar al store web, pero en JSON)
    public function storeMatrix(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'fecha' => 'required|date',
            'area_id' => 'required|exists:areas,id',
            'horario_id' => 'required|exists:horarios,id',
            'detalles' => 'array',
        ]);

        $sucursalId = $user->sucursal_id;
        if (!$sucursalId && ! $user->isAdminGeneral()) {
            return response()->json(['message' => 'Usuario sin sucursal asociada.'], 403);
        }

        $programacion = Programacion::firstOrNew([
            'fecha' => $data['fecha'],
            'sucursal_id' => $sucursalId,
            'area_id' => $data['area_id'],
            'horario_id' => $data['horario_id'],
        ]);

        if ($programacion->exists && $programacion->estado === 'cerrado') {
            return response()->json([
                'message' => 'La programación está cerrada y no puede editarse.',
            ], 403);
        }

        if (!$programacion->exists) {
            $programacion->estado = 'borrador';
        }

        $programacion->save();

        // Borrar y recrear detalles
        $programacion->detalles()->delete();

        $totalGeneral = 0;

        if (isset($data['detalles']) && is_array($data['detalles'])) {
            foreach ($data['detalles'] as $paraderoId => $rutasData) {
                foreach ($rutasData as $rutaId => $valor) {
                    $cantidad = (int)$valor;
                    if ($cantidad > 0) {
                        ProgramacionDetalle::create([
                            'programacion_id' => $programacion->id,
                            'paradero_id' => $paraderoId,
                            'ruta_id' => $rutaId == 0 ? null : $rutaId,
                            'total_personas' => $cantidad,
                        ]);
                        $totalGeneral += $cantidad;
                    }
                }
            }
        }

        $programacion->total_personas = $totalGeneral;
        $programacion->save();

        return response()->json([
            'message' => 'Programación guardada correctamente.',
            'programacion_id' => $programacion->id,
            'estado' => $programacion->estado,
            'total_personas' => $totalGeneral,
        ]);
    }

    // Cambio de estado vía API
    public function changeEstado(Request $request, Programacion $programacion)
    {
        $user = $request->user();

        if (!$user->isAdminGeneral() && $programacion->sucursal_id !== $user->sucursal_id) {
            return response()->json(['message' => 'No puede cambiar estados de otra sucursal.'], 403);
        }

        $request->validate([
            'estado' => 'required|in:borrador,confirmado,cerrado',
        ]);

        $nuevoEstado = $request->estado;
        $estadoActual = $programacion->estado;

        $esAdminGeneral = $user->isAdminGeneral();
        $esAdminSucursal = $user->hasRole('admin_sucursal');
        $esOperador = $user->hasRole('operador');

        $permitido = false;

        if ($esOperador) {
            if ($estadoActual === 'borrador' && $nuevoEstado === 'confirmado') {
                $permitido = true;
            }
        }

        if ($esAdminSucursal) {
            if (
                in_array($estadoActual, ['borrador', 'confirmado']) &&
                in_array($nuevoEstado, ['confirmado', 'cerrado'])
            ) {
                $permitido = true;
            }
        }

        if ($esAdminGeneral) {
            $permitido = true;
        }

        if (! $permitido) {
            return response()->json(['message' => 'No tiene permisos para este cambio de estado.'], 403);
        }

        $programacion->estado = $nuevoEstado;
        $programacion->save();

        return response()->json([
            'message' => 'Estado actualizado.',
            'estado' => $nuevoEstado,
        ]);
    }
}
