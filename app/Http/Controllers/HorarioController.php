<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Sucursal;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HorarioController extends Controller
{
    public function index(Request $request)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        // Normalizamos posible parámetro ?tipo=recojo|salida|RECOJO|SALIDA
        $tipoFiltro = null;
        if ($request->filled('tipo')) {
            $t = strtoupper($request->input('tipo'));
            if (in_array($t, ['RECOJO', 'SALIDA'], true)) {
                $tipoFiltro = $t;
            }
        }

        // --- API / JSON ---
        if ($request->expectsJson() || $request->is('api/*')) {
            $query = Horario::where('activo', true);

            if ($esAdminGral) {
                if ($request->filled('sucursal_id')) {
                    $query->where('sucursal_id', $request->input('sucursal_id'));
                } elseif ($contextSucursalId) {
                    $query->where(function ($q) use ($contextSucursalId) {
                        $q->whereNull('sucursal_id')
                          ->orWhere('sucursal_id', $contextSucursalId);
                    });
                }
            } else {
                $query->where(function ($q) use ($contextSucursalId) {
                    $q->whereNull('sucursal_id');
                    if ($contextSucursalId) {
                        $q->orWhere('sucursal_id', $contextSucursalId);
                    }
                });
            }

            if ($tipoFiltro) {
                $query->where('tipo', $tipoFiltro);
            }

            $horarios = $query->orderBy('hora')->get();

            return response()->json($horarios->map(function ($h) {
                return [
                    'id'                  => $h->id,
                    'nombre'              => $h->nombre,
                    'tipo'                => $h->tipo,
                    'tipo_label'          => $h->tipo_label,
                    'hora'                => $h->hora,
                    'hora_fin'            => $h->hora_fin,
                    'hora_formateada'     => $h->hora_formateada,
                    'hora_fin_formateada' => $h->hora_fin_formateada,
                    'descripcion_rango'   => $h->descripcion_rango,
                    'etiqueta_completa'   => $h->etiqueta_completa,
                    'sucursal_id'         => $h->sucursal_id,
                    'activo'              => (bool) $h->activo,
                ];
            }));
        }

        // --- Vista web ---
        $query = Horario::query()->with('sucursal');

        if ($request->filled('activo')) {
            $query->where('activo', $request->input('activo') === '1');
        }

        if ($esAdminGral) {
            if ($contextSucursalId) {
                $query->where(function ($q) use ($contextSucursalId) {
                    $q->whereNull('sucursal_id')
                      ->orWhere('sucursal_id', $contextSucursalId);
                });
            }
        } else {
            $query->where(function ($q) use ($contextSucursalId) {
                $q->whereNull('sucursal_id');
                if ($contextSucursalId) {
                    $q->orWhere('sucursal_id', $contextSucursalId);
                }
            });
        }

        if ($tipoFiltro) {
            $query->where('tipo', $tipoFiltro);
        }

        $horarios = $query->orderBy('hora')->paginate(25);

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        return view('catalogos.horarios.index', compact('horarios', 'sucursales'));
    }

    public function create(Request $request)
    {
        $user              = $request->user();
        $this->authorize('manageCatalogs', Horario::class);

        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        $horario = new Horario();
        $horario->activo      = true;
        $horario->sucursal_id = $contextSucursalId;
        $horario->tipo        = 'RECOJO';

        return view('catalogos.horarios.create', compact('horario', 'sucursales'));
    }

    public function store(Request $request)
    {
        $user              = $request->user();
        $this->authorize('manageCatalogs', Horario::class);

        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        $data = $request->validate([
            'nombre'      => 'required|string|max:100',
            'tipo'        => 'required|in:SALIDA,RECOJO',
            'hora'        => 'required|date_format:H:i',
            'hora_fin'    => 'nullable|date_format:H:i',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'activo'      => 'nullable|boolean',
        ]);

        if (!$esAdminGral) {
            $data['sucursal_id'] = $contextSucursalId;
        }

        $data['tipo'] = strtoupper($data['tipo']);

        $data['hora'] = Carbon::createFromFormat('H:i', $data['hora'])
            ->format('H:i:s');

        $horaFin = $data['hora_fin'] ?? null;
        if ($horaFin) {
            $data['hora_fin'] = Carbon::createFromFormat('H:i', $horaFin)
                ->format('H:i:s');
        } else {
            $data['hora_fin'] = null;
        }

        $data['activo'] = isset($data['activo']) ? (bool) $data['activo'] : true;

        $horario = Horario::create($data);

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'horario_created',
            'auditable_type' => Horario::class,
            'auditable_id'   => $horario->id,
            'old_values'     => null,
            'new_values'     => $horario->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('catalogos.horarios.index')
            ->with('status', 'Horario creado correctamente.');
    }

    public function edit(Request $request, Horario $horario)
    {
        $user              = $request->user();
        $this->authorize('manageCatalogs', Horario::class);

        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (
            !$esAdminGral
            && $horario->sucursal_id
            && (int) $horario->sucursal_id !== (int) $contextSucursalId
        ) {
            abort(403, 'No puede editar horarios de otra sucursal.');
        }

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        return view('catalogos.horarios.edit', compact('horario', 'sucursales'));
    }

    public function update(Request $request, Horario $horario)
    {
        $user              = $request->user();
        $this->authorize('manageCatalogs', Horario::class);

        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (
            !$esAdminGral
            && $horario->sucursal_id
            && (int) $horario->sucursal_id !== (int) $contextSucursalId
        ) {
            abort(403, 'No puede editar horarios de otra sucursal.');
        }

        $data = $request->validate([
            'nombre'      => 'required|string|max:100',
            'tipo'        => 'required|in:SALIDA,RECOJO',
            'hora'        => 'required|date_format:H:i',
            'hora_fin'    => 'nullable|date_format:H:i',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'activo'      => 'nullable|boolean',
        ]);

        if (!$esAdminGral) {
            $data['sucursal_id'] = $contextSucursalId;
        }

        $data['tipo'] = strtoupper($data['tipo']);

        $data['hora'] = Carbon::createFromFormat('H:i', $data['hora'])
            ->format('H:i:s');

        $horaFin = $data['hora_fin'] ?? null;
        if ($horaFin) {
            $data['hora_fin'] = Carbon::createFromFormat('H:i', $horaFin)
                ->format('H:i:s');
        } else {
            $data['hora_fin'] = null;
        }

        $data['activo'] = isset($data['activo']) ? (bool) $data['activo'] : false;

        $old = $horario->getOriginal();
        $horario->update($data);

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'horario_updated',
            'auditable_type' => Horario::class,
            'auditable_id'   => $horario->id,
            'old_values'     => $old,
            'new_values'     => $horario->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('catalogos.horarios.index')
            ->with('status', 'Horario actualizado correctamente.');
    }

    public function destroy(Request $request, Horario $horario)
    {
        $user              = $request->user();
        $this->authorize('manageCatalogs', Horario::class);

        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (
            !$esAdminGral
            && $horario->sucursal_id
            && (int) $horario->sucursal_id !== (int) $contextSucursalId
        ) {
            abort(403, 'No puede desactivar horarios de otra sucursal.');
        }

        $old = $horario->getOriginal();

        $horario->activo = false;
        $horario->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'horario_deactivated',
            'auditable_type' => Horario::class,
            'auditable_id'   => $horario->id,
            'old_values'     => $old,
            'new_values'     => $horario->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('catalogos.horarios.index')
            ->with('status', 'Horario desactivado correctamente.');
    }
}
