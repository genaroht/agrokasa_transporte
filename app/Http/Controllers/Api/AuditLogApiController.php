<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogApiController extends Controller
{
    /**
     * GET /api/v1/auditoria
     *
     * Lista de logs de auditoría (JSON) con filtros opcionales.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        if (! $user->hasPermissionTo('ver_auditoria')) {
            return response()->json([
                'message' => 'No autorizado para ver auditoría.',
            ], 403);
        }

        // Filtros opcionales
        $validated = $request->validate([
            'user_id'   => ['nullable', 'integer'],
            'action'    => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $query = AuditLog::query()
            ->with('user')
            ->orderBy('created_at', 'desc');

        if (! empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (! empty($validated['action'])) {
            $query->where('action', 'like', '%' . $validated['action'] . '%');
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        // Puedes cambiar el tamaño de página si quieres
        $logs = $query->paginate(50);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/auditoria/{log}
     *
     * Detalle de un log específico.
     */
    public function show(Request $request, AuditLog $log)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        if (! $user->hasPermissionTo('ver_auditoria')) {
            return response()->json([
                'message' => 'No autorizado para ver auditoría.',
            ], 403);
        }

        $log->load('user');

        return response()->json([
            'data' => [
                'id'            => $log->id,
                'user_id'       => $log->user_id,
                'user'          => $log->user?->nombre_completo ?? $log->user?->codigo,
                'action'        => $log->action,
                'auditable_type'=> $log->auditable_type,
                'auditable_id'  => $log->auditable_id,
                'old_values'    => $log->old_values,
                'new_values'    => $log->new_values,
                'ip_address'    => $log->ip_address,
                'user_agent'    => $log->user_agent,
                'created_at'    => optional($log->created_at)->toDateTimeString(),
            ],
        ]);
    }
}
