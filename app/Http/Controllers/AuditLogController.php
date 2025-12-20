<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Listado de registros de auditoría con filtros.
     * Respeta sucursal seleccionada en header para AdminGeneral.
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $query = AuditLog::query()
            ->with('user'); // asumiendo que AuditLog tiene ->user()

        // Contexto de sucursal para AdminGeneral (header / usuario).
        $sucursalContextoId = $this->resolveSucursalIdForListing($request);

        // Si NO es Admin General, solo ve sus propios logs
        if (!$user->isAdminGeneral()) {
            $query->where('user_id', $user->id);
        } else {
            // AdminGeneral: si hay sucursal seleccionada, filtramos por usuarios de esa sucursal
            if ($sucursalContextoId) {
                $query->whereHas('user', function ($q) use ($sucursalContextoId) {
                    $q->where('sucursal_id', $sucursalContextoId);
                });
            }
        }

        // Filtro por usuario
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filtro por acción
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // Filtro por fecha desde
        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->input('fecha_desde'));
        }

        // Filtro por fecha hasta
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->input('fecha_hasta'));
        }

        $logs = $query
            ->orderByDesc('created_at')
            ->paginate(50)
            ->appends($request->query());

        // Listado de usuarios para el combo del filtro
        $usuariosQuery = User::orderBy('nombre');

        if (!$user->isAdminGeneral()) {
            // No admin: solo usuarios de su sucursal
            $usuariosQuery->where('sucursal_id', $user->sucursal_id);
        } elseif ($sucursalContextoId) {
            // Admin: si hay sucursal en header, limitamos la lista a esa sucursal
            $usuariosQuery->where('sucursal_id', $sucursalContextoId);
        }

        $usuarios = $usuariosQuery->get(); // sin limitar columnas

        // Opcional: listado de acciones distintas para filtros
        $acciones = AuditLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('audit_logs.index', [
            'logs'              => $logs,
            'usuarios'          => $usuarios,
            'acciones'          => $acciones,
            'sucursalContextoId'=> $sucursalContextoId,
        ]);
    }

    /**
     * Ver detalle de un log concreto.
     */
    public function show(Request $request, AuditLog $log)
    {
        /** @var User $user */
        $user = $request->user();

        // Si no es Admin General, solo puede ver sus propios logs
        if (!$user->isAdminGeneral() && $log->user_id !== $user->id) {
            abort(403);
        }

        $log->load('user');

        return view('audit_logs.show', compact('log'));
    }

    /**
     * Determina la sucursal a usar para listados de auditoría,
     * respetando el header para AdminGeneral.
     */
    protected function resolveSucursalIdForListing(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return null;
        }

        if ($user->isAdminGeneral()) {
            $fromHeader = session('sucursal_actual_id'); // <- clave usada por el header
            if ($fromHeader) {
                return (int) $fromHeader;
            }

            return $user->sucursal_id ?: null;
        }

        return $user->sucursal_id ?: null;
    }
}
