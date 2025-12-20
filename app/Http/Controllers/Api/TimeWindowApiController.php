<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Models\TimeWindow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class TimeWindowApiController extends Controller
{
    /**
     * Lista de ventanas de tiempo (solo admin_general o usuarios con permiso).
     * GET /api/v1/ventanas-tiempo
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->hasPermissionTo('gestionar_timewindows')) {
            return response()->json([
                'message' => 'No autorizado para listar ventanas de tiempo.',
            ], 403);
        }

        $sucursalId = $request->input('sucursal_id') ?: $user->sucursal_id;
        $fecha      = $request->input('fecha');

        $query = TimeWindow::query()
            ->with(['sucursal', 'area', 'horario', 'user', 'role'])
            ->when($sucursalId, function ($q) use ($sucursalId) {
                $q->where('sucursal_id', $sucursalId);
            })
            ->when($fecha, function ($q) use ($fecha) {
                $q->whereDate('fecha', $fecha);
            })
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_inicio');

        // Si no es admin_general, restringimos a su sucursal
        if (! $user->isAdminGeneral()) {
            $query->where('sucursal_id', $user->sucursal_id);
        }

        $ventanas = $query->get();

        return response()->json([
            'data' => $ventanas->map(fn (TimeWindow $w) => $this->transform($w)),
        ]);
    }

    /**
     * Crea una ventana de tiempo.
     * POST /api/v1/ventanas-tiempo
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->hasPermissionTo('gestionar_timewindows')) {
            return response()->json([
                'message' => 'No autorizado para crear ventanas de tiempo.',
            ], 403);
        }

        $data = $this->validateData($request);

        // Si no es admin_general, forzar sucursal del usuario
        if (! $user->isAdminGeneral()) {
            $data['sucursal_id'] = $user->sucursal_id;
        }

        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;

        $window = TimeWindow::create($data);

        return response()->json([
            'message' => 'Ventana de tiempo creada correctamente.',
            'data'    => $this->transform($window->fresh(['sucursal', 'area', 'horario', 'user', 'role'])),
        ], 201);
    }

    /**
     * Muestra una ventana específica.
     * GET /api/v1/ventanas-tiempo/{timewindow}
     */
    public function show(Request $request, TimeWindow $timewindow)
    {
        $user = $request->user();

        if (! $user->hasPermissionTo('gestionar_timewindows')) {
            return response()->json([
                'message' => 'No autorizado para ver ventanas de tiempo.',
            ], 403);
        }

        if (! $user->isAdminGeneral() && $user->sucursal_id !== $timewindow->sucursal_id) {
            return response()->json([
                'message' => 'No puede ver ventanas de otra sucursal.',
            ], 403);
        }

        $timewindow->load(['sucursal', 'area', 'horario', 'user', 'role']);

        return response()->json([
            'data' => $this->transform($timewindow),
        ]);
    }

    /**
     * Actualiza una ventana de tiempo.
     * PUT /api/v1/ventanas-tiempo/{timewindow}
     */
    public function update(Request $request, TimeWindow $timewindow)
    {
        $user = $request->user();

        if (! $user->hasPermissionTo('gestionar_timewindows')) {
            return response()->json([
                'message' => 'No autorizado para actualizar ventanas de tiempo.',
            ], 403);
        }

        if (! $user->isAdminGeneral() && $user->sucursal_id !== $timewindow->sucursal_id) {
            return response()->json([
                'message' => 'No puede actualizar ventanas de otra sucursal.',
            ], 403);
        }

        $data = $this->validateData($request, $timewindow);

        // No permitimos cambiar de sucursal salvo admin_general
        if (! $user->isAdminGeneral()) {
            $data['sucursal_id'] = $timewindow->sucursal_id;
        }

        $data['updated_by'] = $user->id;

        $timewindow->update($data);

        return response()->json([
            'message' => 'Ventana de tiempo actualizada correctamente.',
            'data'    => $this->transform($timewindow->fresh(['sucursal', 'area', 'horario', 'user', 'role'])),
        ]);
    }

    /**
     * Elimina una ventana de tiempo.
     * DELETE /api/v1/ventanas-tiempo/{timewindow}
     */
    public function destroy(Request $request, TimeWindow $timewindow)
    {
        $user = $request->user();

        if (! $user->hasPermissionTo('gestionar_timewindows')) {
            return response()->json([
                'message' => 'No autorizado para eliminar ventanas de tiempo.',
            ], 403);
        }

        if (! $user->isAdminGeneral() && $user->sucursal_id !== $timewindow->sucursal_id) {
            return response()->json([
                'message' => 'No puede eliminar ventanas de otra sucursal.',
            ], 403);
        }

        $timewindow->delete();

        return response()->json([
            'message' => 'Ventana de tiempo eliminada correctamente.',
        ]);
    }

    /**
     * Reabre temporalmente una ventana (ej: +30 minutos).
     * POST /api/v1/ventanas-tiempo/{timewindow}/reopen
     */
    public function reopen(Request $request, TimeWindow $timewindow)
    {
        $user = $request->user();

        if (! $user->hasPermissionTo('gestionar_timewindows')) {
            return response()->json([
                'message' => 'No autorizado para reabrir ventanas de tiempo.',
            ], 403);
        }

        if (! $user->isAdminGeneral() && $user->sucursal_id !== $timewindow->sucursal_id) {
            return response()->json([
                'message' => 'No puede reabrir ventanas de otra sucursal.',
            ], 403);
        }

        $data = $request->validate([
            'minutos' => ['nullable', 'integer', 'min:1', 'max:720'],
        ]);

        $minutos = $data['minutos'] ?? 30;

        // Tomamos la zona horaria de la sucursal
        $timezone = $timewindow->sucursal?->timezone ?: config('app.timezone', 'UTC');
        $now      = Carbon::now($timezone);

        $timewindow->estado          = 'reabierto';
        $timewindow->reabierto_hasta = $now->copy()->addMinutes($minutos);
        $timewindow->updated_by      = $user->id;
        $timewindow->save();

        return response()->json([
            'message' => "Ventana reabierta por {$minutos} minutos.",
            'data'    => $this->transform($timewindow->fresh(['sucursal', 'area', 'horario', 'user', 'role'])),
        ]);
    }

    /**
     * Ventanas vigentes para el usuario autenticado (apps).
     * GET /api/v1/ventanas-tiempo/mi-estado
     */
    public function myWindows(Request $request)
    {
        $user = $request->user();

        $sucursalId = $user->sucursal_id;
        if (! $sucursalId) {
            return response()->json([
                'data'    => [],
                'message' => 'El usuario no tiene sucursal asignada.',
            ]);
        }

        $sucursal = Sucursal::find($sucursalId);
        $timezone = $sucursal?->timezone ?: config('app.timezone', 'UTC');
        $now      = Carbon::now($timezone);
        $nowTime  = $now->format('H:i:s');

        $rolesIds = $user->roles->pluck('id')->all();

        $ventanas = TimeWindow::query()
            ->with(['sucursal', 'area', 'horario'])
            ->where('sucursal_id', $sucursalId)
            ->whereDate('fecha', $now->toDateString())
            ->where(function ($q) use ($user, $rolesIds) {
                // globales
                $q->where(function ($q2) {
                    $q2->whereNull('user_id')
                       ->whereNull('role_id');
                });

                // específicas por usuario
                $q->orWhere('user_id', $user->id);

                // específicas por rol
                if (! empty($rolesIds)) {
                    $q->orWhereIn('role_id', $rolesIds);
                }
            })
            ->where(function ($q) use ($nowTime, $now) {
                $q->where(function ($q2) use ($nowTime) {
                    $q2->where('estado', 'activo')
                       ->where('hora_inicio', '<=', $nowTime)
                       ->where('hora_fin', '>=', $nowTime);
                })->orWhere(function ($q2) use ($now) {
                    $q2->where('estado', 'reabierto')
                       ->whereNotNull('reabierto_hasta')
                       ->where('reabierto_hasta', '>=', $now);
                });
            })
            ->get();

        return response()->json([
            'ahora'   => $now->toDateTimeString(),
            'data'    => $ventanas->map(fn (TimeWindow $w) => $this->transform($w)),
        ]);
    }

    /**
     * Versión corta: solo ventanas activas para la sucursal del usuario.
     * GET /api/v1/time-windows/activos
     */
    public function activos(Request $request)
    {
        $user = $request->user();

        $sucursalId = $request->input('sucursal_id') ?: $user->sucursal_id;

        if (! $sucursalId) {
            return response()->json([
                'data'    => [],
                'message' => 'El usuario no tiene sucursal asignada.',
            ]);
        }

        $sucursal = Sucursal::find($sucursalId);
        $timezone = $sucursal?->timezone ?: config('app.timezone', 'UTC');
        $now      = Carbon::now($timezone);
        $nowTime  = $now->format('H:i:s');

        $ventanas = TimeWindow::query()
            ->where('sucursal_id', $sucursalId)
            ->whereDate('fecha', $now->toDateString())
            ->where(function ($q) use ($nowTime, $now) {
                $q->where(function ($q2) use ($nowTime) {
                    $q2->where('estado', 'activo')
                       ->where('hora_inicio', '<=', $nowTime)
                       ->where('hora_fin', '>=', $nowTime);
                })->orWhere(function ($q2) use ($now) {
                    $q2->where('estado', 'reabierto')
                       ->whereNotNull('reabierto_hasta')
                       ->where('reabierto_hasta', '>=', $now);
                });
            })
            ->orderBy('hora_inicio')
            ->get();

        return response()->json([
            'ahora'   => $now->toDateTimeString(),
            'data'    => $ventanas->map(fn (TimeWindow $w) => $this->transform($w)),
        ]);
    }

    /**
     * Validación centralizada para crear/actualizar.
     */
    protected function validateData(Request $request, ?TimeWindow $current = null): array
    {
        return $request->validate([
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
            'area_id'     => ['nullable', 'integer', 'exists:areas,id'],
            'user_id'     => ['nullable', 'integer', 'exists:users,id'],
            'role_id'     => ['nullable', 'integer', 'exists:roles,id'],
            'horario_id'  => ['nullable', 'integer', 'exists:horarios,id'],

            'fecha'       => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin'    => ['required', 'date_format:H:i', 'after:hora_inicio'],

            'estado'      => [
                'nullable',
                Rule::in(['activo', 'expirado', 'reabierto']),
            ],

            'reabierto_hasta' => ['nullable', 'date'],
        ]);
    }

    /**
     * Transformador a estructura JSON estándar.
     */
    protected function transform(TimeWindow $w): array
    {
        return [
            'id'              => $w->id,
            'sucursal_id'     => $w->sucursal_id,
            'sucursal'        => $w->sucursal?->nombre,
            'area_id'         => $w->area_id,
            'area'            => $w->area?->nombre,
            'user_id'         => $w->user_id,
            'user'            => $w->user?->nombre_completo ?? $w->user?->codigo,
            'role_id'         => $w->role_id,
            'role'            => $w->role?->nombre ?? $w->role?->slug,
            'horario_id'      => $w->horario_id,
            'horario'         => $w->horario?->nombre,
            'fecha'           => optional($w->fecha)->toDateString(),
            'hora_inicio'     => $w->hora_inicio,
            'hora_fin'        => $w->hora_fin,
            'estado'          => $w->estado,
            'reabierto_hasta' => $w->reabierto_hasta?->toDateTimeString(),
            'created_by'      => $w->created_by,
            'updated_by'      => $w->updated_by,
            'created_at'      => optional($w->created_at)->toDateTimeString(),
            'updated_at'      => optional($w->updated_at)->toDateTimeString(),
        ];
    }
}
