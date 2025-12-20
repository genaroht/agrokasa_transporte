<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Listado de logs (solo lectura).
     * Requiere permiso "view_audit_logs" (se asume ADMIN GENERAL).
     *
     * GET /api/v1/audit-logs?user_id=&action=&limit=...
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('view_audit_logs')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'user_id'     => 'nullable|integer|exists:users,id',
            'action'      => 'nullable|string|max:100',
            'limit'       => 'nullable|integer|min:1|max:500',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
        ]);

        $limit = $request->input('limit', 100);

        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->input('action') . '%');
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_hasta'));
        }

        $logs = $query->limit($limit)->get()->map(function (AuditLog $log) {
            return [
                'id'            => $log->id,
                'created_at'    => $log->created_at->toDateTimeString(),
                'user'          => $log->user ? $log->user->nombre_completo : null,
                'user_id'       => $log->user_id,
                'action'        => $log->action,
                'auditable'     => [
                    'type' => $log->auditable_type,
                    'id'   => $log->auditable_id,
                ],
                'old_values'    => $log->old_values,
                'new_values'    => $log->new_values,
                'ip_address'    => $log->ip_address,
                'user_agent'    => $log->user_agent,
            ];
        });

        return response()->json([
            'count' => $logs->count(),
            'logs'  => $logs,
        ]);
    }
}
