<?php

namespace App\Http\Controllers;

use App\Models\TimeWindow;
use App\Models\Sucursal;
use App\Models\Area;
use App\Models\Horario;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class TimeWindowController extends Controller
{
    /**
     * Listado de ventanas de tiempo con filtros.
     */
    public function index(Request $request)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        // Sucursal seleccionada en el filtro (si no viene, usamos la del contexto)
        $selectedSucursalId = $request->filled('sucursal_id')
            ? (int) $request->input('sucursal_id')
            : $contextSucursalId;

        $query = TimeWindow::query();

        // Siempre filtramos por la sucursal seleccionada
        if ($selectedSucursalId) {
            $query->where('sucursal_id', $selectedSucursalId);
        }

        // Filtro por tipo (salida / recojo)
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        // Filtros opcionales
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }

        if ($request->filled('horario_id')) {
            $query->where('horario_id', $request->input('horario_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->input('role_id'));
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->input('fecha'));
        }

        // estado: 1 = activas (activo / reabierto), 0 = inactivas (cualquier otro estado), '' = todas
        if ($request->filled('estado')) {
            if ($request->input('estado') === '1') {
                $query->whereIn('estado', [TimeWindow::ESTADO_ACTIVO, TimeWindow::ESTADO_REABIERTO]);
            } elseif ($request->input('estado') === '0') {
                $query->whereNotIn('estado', [TimeWindow::ESTADO_ACTIVO, TimeWindow::ESTADO_REABIERTO]);
            }
        }

        $timeWindows = $query
            ->orderByDesc('fecha')
            ->orderBy('hora_inicio')
            ->paginate(25)
            ->appends($request->query());

        // Listas para filtros
        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        $areas = Area::where('activo', true)
            ->when($selectedSucursalId, function ($q) use ($selectedSucursalId) {
                $q->whereNull('sucursal_id')
                  ->orWhere('sucursal_id', $selectedSucursalId);
            })
            ->orderBy('nombre')
            ->get();

        $horarios = Horario::where('activo', true)
            ->when($selectedSucursalId, function ($q) use ($selectedSucursalId) {
                $q->whereNull('sucursal_id')
                  ->orWhere('sucursal_id', $selectedSucursalId);
            })
            ->orderBy('hora')
            ->get();

        $usersQuery = User::orderBy('nombre');

        if (!$esAdminGral && $contextSucursalId) {
            // No admin => solo su sucursal
            $usersQuery->where('sucursal_id', $contextSucursalId);
        } elseif ($esAdminGral && $selectedSucursalId) {
            // Admin general => filtra por sucursal seleccionada si hay
            $usersQuery->where('sucursal_id', $selectedSucursalId);
        }

        $users = $usersQuery->get();

        $roles = Role::orderBy('nombre')->get();

        return view('time_windows.index', compact(
            'timeWindows',
            'sucursales',
            'areas',
            'horarios',
            'users',
            'roles',
            'selectedSucursalId'
        ));
    }

    /**
     * Formulario de creación.
     */
    public function create(Request $request)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        $areas = Area::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->whereNull('sucursal_id')
                  ->orWhere('sucursal_id', $contextSucursalId);
            })
            ->orderBy('nombre')
            ->get();

        $horarios = Horario::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->whereNull('sucursal_id')
                  ->orWhere('sucursal_id', $contextSucursalId);
            })
            ->orderBy('hora')
            ->get();

        $usuariosQuery = User::orderBy('nombre');

        if (!$esAdminGral && $contextSucursalId) {
            $usuariosQuery->where('sucursal_id', $contextSucursalId);
        }

        $usuarios = $usuariosQuery->get();

        $roles = Role::orderBy('nombre')->get();

        return view('time_windows.create', compact(
            'sucursales',
            'areas',
            'horarios',
            'usuarios',
            'roles'
        ));
    }

    /**
     * Guarda una nueva ventana de tiempo.
     *
     * La ventana aplica TODOS los días mientras su estado sea "activo" (o "reabierto"),
     * y dentro del rango hora_inicio – hora_fin.
     */
    public function store(Request $request)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        $data = $request->validate([
            'sucursal_id'     => 'nullable|integer|exists:sucursales,id',
            'tipo'            => 'nullable|string|in:salida,recojo',
            'area_id'         => 'nullable|integer|exists:areas,id',
            'horario_id'      => 'nullable|integer|exists:horarios,id',
            'user_id'         => 'nullable|integer|exists:users,id',
            'role_id'         => 'nullable|integer|exists:roles,id',
            'fecha'           => 'nullable|date',
            'hora_inicio'     => 'required|date_format:H:i',
            'hora_fin'        => 'required|date_format:H:i|after:hora_inicio',
            'descripcion'     => 'nullable|string|max:255',
            'estado'          => 'nullable|string|in:activo,inactivo,reabierto',
            'reabierto_hasta' => 'nullable|date',
        ], [
            'hora_inicio.required' => 'La hora inicio es obligatoria.',
            'hora_fin.required'    => 'La hora fin es obligatoria.',
            'hora_fin.after'       => 'La hora fin debe ser mayor que la hora inicio.',
        ]);

        // Si no es admin general, forzamos su sucursal
        if (!$esAdminGral) {
            $data['sucursal_id'] = $contextSucursalId;
        }

        // Si no se envía fecha, usamos hoy solo como referencia
        if (empty($data['fecha'])) {
            $data['fecha'] = now()->toDateString();
        }

        // Tipo por defecto: salida
        $tipo = $data['tipo'] ?? 'salida';
        $data['tipo'] = $tipo;

        // Estado por defecto: activo
        $estado = $data['estado'] ?? TimeWindow::ESTADO_ACTIVO;
        if (! in_array($estado, [TimeWindow::ESTADO_ACTIVO, TimeWindow::ESTADO_INACTIVO, TimeWindow::ESTADO_REABIERTO], true)) {
            $estado = TimeWindow::ESTADO_ACTIVO;
        }
        $data['estado']     = $estado;
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;

        TimeWindow::create($data);

        return redirect()
            ->route('timewindows.index')
            ->with('status', 'Ventana de tiempo creada correctamente.');
    }

    /**
     * Formulario de edición.
     */
    public function edit(Request $request, TimeWindow $timewindow)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $timewindow->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede editar ventanas de otra sucursal.');
        }

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        $areas = Area::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->whereNull('sucursal_id')
                  ->orWhere('sucursal_id', $contextSucursalId);
            })
            ->orderBy('nombre')
            ->get();

        $horarios = Horario::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->whereNull('sucursal_id')
                  ->orWhere('sucursal_id', $contextSucursalId);
            })
            ->orderBy('hora')
            ->get();

        $usuariosQuery = User::orderBy('nombre');

        if (!$esAdminGral && $contextSucursalId) {
            $usuariosQuery->where('sucursal_id', $contextSucursalId);
        }

        $usuarios = $usuariosQuery->get();

        $roles = Role::orderBy('nombre')->get();

        return view('time_windows.edit', compact(
            'timewindow',
            'sucursales',
            'areas',
            'horarios',
            'usuarios',
            'roles'
        ));
    }

    /**
     * Actualiza una ventana de tiempo.
     */
    public function update(Request $request, TimeWindow $timewindow)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $timewindow->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede actualizar ventanas de otra sucursal.');
        }

        $data = $request->validate([
            'sucursal_id'     => 'nullable|integer|exists:sucursales,id',
            'tipo'            => 'nullable|string|in:salida,recojo',
            'area_id'         => 'nullable|integer|exists:areas,id',
            'horario_id'      => 'nullable|integer|exists:horarios,id',
            'user_id'         => 'nullable|integer|exists:users,id',
            'role_id'         => 'nullable|integer|exists:roles,id',
            'fecha'           => 'nullable|date',
            'hora_inicio'     => 'required|date_format:H:i',
            'hora_fin'        => 'required|date_format:H:i|after:hora_inicio',
            'descripcion'     => 'nullable|string|max:255',
            'estado'          => 'nullable|string|in:activo,inactivo,reabierto',
            'reabierto_hasta' => 'nullable|date',
        ], [
            'hora_inicio.required' => 'La hora inicio es obligatoria.',
            'hora_fin.required'    => 'La hora fin es obligatoria.',
            'hora_fin.after'       => 'La hora fin debe ser mayor que la hora inicio.',
        ]);

        if (!$esAdminGral) {
            $data['sucursal_id'] = $contextSucursalId;
        }

        // Si no envían fecha en el formulario, mantenemos la que ya tenía
        if (empty($data['fecha'])) {
            $data['fecha'] = $timewindow->fecha ?? now()->toDateString();
        }

        // Tipo
        $tipo = $data['tipo'] ?? $timewindow->tipo ?? 'salida';
        $data['tipo'] = $tipo;

        // Estado
        $estado = $data['estado'] ?? $timewindow->estado ?? TimeWindow::ESTADO_ACTIVO;
        if (! in_array($estado, [TimeWindow::ESTADO_ACTIVO, TimeWindow::ESTADO_INACTIVO, TimeWindow::ESTADO_REABIERTO], true)) {
            $estado = $timewindow->estado ?? TimeWindow::ESTADO_ACTIVO;
        }
        $data['estado']     = $estado;
        $data['updated_by'] = $user->id;

        $timewindow->update($data);

        return redirect()
            ->route('timewindows.index')
            ->with('status', 'Ventana de tiempo actualizada correctamente.');
    }

    /**
     * Activar / desactivar rápidamente desde el listado.
     */
    public function toggle(Request $request, TimeWindow $timewindow)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $timewindow->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede cambiar el estado de ventanas de otra sucursal.');
        }

        $estaActiva = $timewindow->esta_activa;

        $timewindow->estado     = $estaActiva ? TimeWindow::ESTADO_INACTIVO : TimeWindow::ESTADO_ACTIVO;
        $timewindow->updated_by = $user->id;
        $timewindow->save();

        return redirect()
            ->route('timewindows.index', $request->query())
            ->with('status', 'Ventana de tiempo ' . ($estaActiva ? 'desactivada' : 'activada') . '.');
    }

    /**
     * Reabrir una ventana de tiempo por un periodo adicional (opcional).
     */
    public function reopen(Request $request, TimeWindow $timewindow)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $timewindow->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede reabrir ventanas de otra sucursal.');
        }

        $minutos = (int) $request->input('minutos', 30);

        $timewindow->estado          = TimeWindow::ESTADO_REABIERTO;
        $timewindow->reabierto_hasta = now()->addMinutes($minutos);
        $timewindow->updated_by      = $user->id;
        $timewindow->save();

        return redirect()
            ->route('timewindows.index')
            ->with('status', "Ventana de tiempo reabierta por {$minutos} minutos.");
    }

    /**
     * Estado actual para el usuario (endpoint de consulta rápida).
     * Si se manda ?tipo=salida o ?tipo=recojo, filtra por ese tipo.
     */
    public function status(Request $request)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;

        $timezone  = $sucursalActual->timezone ?? config('app.timezone', 'America/Lima');
        $ahora     = now($timezone);
        $hora      = $ahora->format('H:i:s');
        $fechaHoy  = $ahora->toDateString();
        $tipo      = $request->input('tipo'); // salida | recojo | null

        $ventana = TimeWindow::query()
            ->where('sucursal_id', $contextSucursalId)
            ->vigentes($tipo)
            ->whereDate('fecha', '<=', $fechaHoy)
            ->whereTime('hora_inicio', '<=', $hora)
            ->whereTime('hora_fin', '>=', $hora)
            ->orderByDesc('fecha')
            ->orderBy('hora_inicio')
            ->first();

        return response()->json([
            'ok'          => true,
            'ahora'       => $ahora->toDateTimeString(),
            'tipo'        => $tipo,
            'hayVentana'  => (bool) $ventana,
            'ventana'     => $ventana ? [
                'id'              => $ventana->id,
                'tipo'            => $ventana->tipo,
                'estado'          => $ventana->estado,
                'fecha'           => optional($ventana->fecha)->toDateString(),
                'hora_inicio'     => $ventana->hora_inicio,
                'hora_fin'        => $ventana->hora_fin,
                'reabierto_hasta' => optional($ventana->reabierto_hasta)->toDateTimeString(),
            ] : null,
        ]);
    }

    /**
     * Desactiva (soft) una ventana de tiempo.
     */
    public function destroy(Request $request, TimeWindow $timewindow)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $timewindow->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede desactivar ventanas de otra sucursal.');
        }

        $timewindow->estado     = TimeWindow::ESTADO_INACTIVO;
        $timewindow->updated_by = $user->id;
        $timewindow->save();

        return redirect()
            ->route('timewindows.index')
            ->with('status', 'Ventana de tiempo desactivada.');
    }
}
