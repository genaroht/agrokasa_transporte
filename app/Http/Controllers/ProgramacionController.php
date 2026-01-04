<?php

namespace App\Http\Controllers;

use App\Models\Programacion;
use App\Models\ProgramacionDetalle;
use App\Models\Sucursal;
use App\Models\Area;
use App\Models\Horario;
use App\Models\Paradero;
use App\Models\Ruta;
use App\Models\Vehiculo;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProgramacionController extends Controller
{
    /**
     * Listado de programaciones (filtro por fecha, sucursal, área, horario, estado, tipo).
     * Mejora: por defecto muestra SOLO las programaciones de HOY.
     *
     * Acceso controlado por middleware:
     *  - permission:gestionar_programaciones|ver_reportes
     *  - sucursal (contexto de sucursal)
     */
    public function index(Request $request)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        $query = Programacion::with(['sucursal', 'area', 'horario', 'creador']);

        // Siempre filtramos por la sucursal del contexto (header)
        if ($contextSucursalId) {
            $query->where('sucursal_id', $contextSucursalId);
        }

        // ============================
        // Filtros de fecha con valor por defecto = HOY
        // ============================
        $fechaDesde = $request->input('fecha_desde', now()->toDateString());
        $fechaHasta = $request->input('fecha_hasta', now()->toDateString());

        $query->whereDate('fecha', '>=', $fechaDesde)
              ->whereDate('fecha', '<=', $fechaHasta);

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }

        if ($request->filled('horario_id')) {
            $query->where('horario_id', $request->input('horario_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        // Filtro por tipo (recojo / salida)
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        $programaciones = $query
            ->orderBy('fecha', 'desc')
            ->orderBy('horario_id')
            ->orderBy('area_id')
            ->paginate(25)
            ->appends($request->query());

        // Para filtros de sucursal en la vista (admin ve todas, otros solo actual)
        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        // Áreas activas, globales + sucursal actual
        $areas = Area::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->whereNull('sucursal_id')
                  ->orWhere('sucursal_id', $contextSucursalId);
            })
            ->orderBy('nombre')
            ->get();

        // Horarios activos, globales + sucursal actual
        $horarios = Horario::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->whereNull('sucursal_id')
                  ->orWhere('sucursal_id', $contextSucursalId);
            })
            ->orderBy('hora')
            ->get();

        // Opciones de tipo para el filtro
        $tipos = [
            Programacion::TIPO_RECOJO => 'Recojo',
            Programacion::TIPO_SALIDA => 'Salida',
        ];

        return view('programaciones.index', compact(
            'programaciones',
            'sucursales',
            'areas',
            'horarios',
            'tipos'
        ));
    }

    /**
     * Formulario para crear cabecera de programación.
     * Después de crearla se redirige a la matriz (edit).
     *
     * Acceso controlado por middleware:
     *  - permission:gestionar_programaciones
     *  - sucursal
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
            ->when(!$esAdminGral, function ($q) use ($user, $contextSucursalId) {
                if ($user->area_id) {
                    $q->where('id', $user->area_id);
                } else {
                    $q->whereNull('sucursal_id')
                      ->orWhere('sucursal_id', $contextSucursalId);
                }
            })
            ->when($esAdminGral && $contextSucursalId, function ($q) use ($contextSucursalId) {
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

        $horariosJson = $horarios->map(function (Horario $h) {
            return [
                'id'       => $h->id,
                'tipo'     => $h->tipo, // RECOJO / SALIDA (puede ser null)
                'etiqueta' => $h->etiqueta_completa
                    ?? trim(($h->nombre ? $h->nombre . ' ' : '') . ($h->hora_formateada ?? '')),
            ];
        })->values();

        $paraderos = Paradero::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->where('sucursal_id', $contextSucursalId);
            })
            ->orderBy('nombre')
            ->get();

        $rutas = Ruta::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->where('sucursal_id', $contextSucursalId);
            })
            ->orderBy('nombre')
            ->get();

        $vehiculos = Vehiculo::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->where('sucursal_id', $contextSucursalId);
            })
            ->orderBy('placa')
            ->get();

        $tipos = [
            Programacion::TIPO_RECOJO => 'Recojo',
            Programacion::TIPO_SALIDA => 'Salida',
        ];

        return view('programaciones.create', compact(
            'sucursales',
            'areas',
            'horarios',
            'paraderos',
            'rutas',
            'vehiculos',
            'tipos',
            'horariosJson'
        ));
    }

    /**
     * Guarda/crea la cabecera de la programación y redirige a la matriz.
     * Middleware time.window debe aplicarse en las rutas.
     *
     * Acceso controlado por middleware:
     *  - permission:gestionar_programaciones
     *  - sucursal
     *  - time.window
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Validar datos básicos
        $data = $request->validate([
            'fecha'      => 'required|date',
            'tipo'       => 'required|in:recojo,salida',
            'area_id'    => 'required|integer|exists:areas,id',
            'horario_id' => 'required|integer|exists:horarios,id',
        ]);

        // Sucursal de contexto (middleware) o la del usuario
        $sucursalActual = $request->attributes->get('sucursalActual');
        $sucursalId     = $sucursalActual->id ?? $user->sucursal_id;

        // Roles “admin” a nivel de sucursal
        $esAdminGral      = method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
        $esAdminSucursal  = method_exists($user, 'hasRole') && $user->hasRole('admin_sucursal');

        // Si NO es admin_general ni admin_sucursal, forzamos su propia área (Operador)
        if (! $esAdminGral && ! $esAdminSucursal && $user->area_id) {
            $data['area_id'] = $user->area_id;
        }

        // ¿Ya existe una programación con la misma combinación?
        $programacionExistente = Programacion::where('sucursal_id', $sucursalId)
            ->whereDate('fecha', $data['fecha'])
            ->where('tipo', $data['tipo'])
            ->where('area_id', $data['area_id'])
            ->where('horario_id', $data['horario_id'])
            ->first();

        if ($programacionExistente) {
            // Si ya existe, solo redirigimos a la matriz manual de esa programación
            return redirect()
                ->route('programaciones.edit', $programacionExistente)
                ->with('status', 'Ya existía una programación para esa Fecha / Tipo / Área / Horario. Se abrió para edición.');
        }

        // Crear nueva programación
        $programacion = new Programacion();
        $programacion->sucursal_id    = $sucursalId;
        $programacion->fecha          = $data['fecha'];
        $programacion->tipo           = $data['tipo'];
        $programacion->area_id        = $data['area_id'];
        $programacion->horario_id     = $data['horario_id'];
        $programacion->estado         = 'borrador';
        $programacion->total_personas = 0;
        $programacion->creado_por     = $user->id ?? null;
        $programacion->save();

        if (method_exists($programacion, 'recalcularTotalPersonas')) {
            $programacion->recalcularTotalPersonas();
        }

        return redirect()
            ->route('programaciones.edit', $programacion)
            ->with('status', 'Programación creada correctamente. Ahora puedes completar la matriz manual.');
    }

    /**
     * Muestra la matriz Paradero x Ruta para editar cantidades.
     * Aquí ya llevamos Rutas con Lotes/Comedores en JSON para el front.
     *
     * Acceso controlado por middleware:
     *  - permission:gestionar_programaciones|ver_reportes
     *  - sucursal
     */
    public function edit(Request $request, Programacion $programacion)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;

        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
        $esAdminSucursal  = $user && method_exists($user, 'hasRole') && $user->hasRole('admin_sucursal');
        $esOperador       = $user && method_exists($user, 'hasRole') && $user->hasRole('operador');

        // No permitir ver programaciones de otra sucursal (salvo que el contexto esté mal)
        if (! $esAdminGral && (int) $programacion->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede ver programaciones de otra sucursal.');
        }

        // Restricción por área:
        //   - admin_general y admin_sucursal pueden ver todas las áreas.
        //   - operador solo su área (si tiene).
        if (! $esAdminGral && ! $esAdminSucursal && $user->area_id && $programacion->area_id !== $user->area_id) {
            abort(403, 'No puede ver/editar programaciones de otra área.');
        }

        // Catálogos para editar cabecera (solo tendrá efecto para admin_general)
        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $programacion->sucursal_id)->get();

        $areas = Area::where('activo', true)
            ->when(! $esAdminGral, function ($q) use ($programacion) {
                $q->where('id', $programacion->area_id);
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

        $paraderos = Paradero::where('sucursal_id', $programacion->sucursal_id)
            ->where('activo', true)
            ->with('lugar')
            ->orderBy('nombre')
            ->get();

        $rutas = Ruta::where('sucursal_id', $programacion->sucursal_id)
            ->where('activo', true)
            ->with('lotes')
            ->orderBy('codigo')
            ->get();

        // JSON para el front: cada ruta con sus lotes y comedores
        $rutasJson = $rutas->map(function (Ruta $r) {
            return [
                'id'     => $r->id,
                'codigo' => $r->codigo,
                'nombre' => $r->nombre,
                'lotes'  => $r->lotes->map(function ($l) {
                    return [
                        'id'        => $l->id,
                        'nombre'    => $l->nombre,
                        'comedores' => $l->comedores_list,
                    ];
                })->values(),
            ];
        })->values();

        $detalles = $programacion->detalles()
            ->with(['paradero.lugar', 'ruta'])
            ->orderBy('id')
            ->get();

        // Solo se puede editar la matriz si NO está cerrada
        // y si el usuario puede gestionar programaciones (admin_general, admin_sucursal, operador)
        $puedeGestionar = $esAdminGral
            || ($user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin_sucursal', 'operador']));

        $soloLectura = $programacion->estaCerrada() || ! $puedeGestionar;

        return view('programaciones.edit', compact(
            'programacion',
            'sucursales',
            'areas',
            'horarios',
            'paraderos',
            'rutas',
            'detalles',
            'soloLectura',
            'rutasJson'
        ));
    }

    /**
     * Guarda la matriz de ProgramacionDetalle.
     *
     * Acceso controlado por middleware:
     *  - permission:gestionar_programaciones
     *  - sucursal
     *  - time.window
     */
    public function update(Request $request, Programacion $programacion)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;

        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
        $esAdminSucursal  = $user && method_exists($user, 'hasRole') && $user->hasRole('admin_sucursal');

        // No puede modificar de otra sucursal
        if (! $esAdminGral && (int) $programacion->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede modificar programaciones de otra sucursal.');
        }

        // Restricción por área
        if (! $esAdminGral && ! $esAdminSucursal && $user->area_id && $programacion->area_id !== $user->area_id) {
            abort(403, 'No puede modificar programaciones de otra área.');
        }

        // Solo se puede modificar la matriz en estado BORRADOR
        if ($programacion->estado !== 'borrador') {
            return redirect()
                ->back()
                ->withErrors('La programación no está en estado BORRADOR y no se puede modificar la matriz.');
        }

        $data = $request->validate([
            'lineas'               => 'nullable|array',
            'lineas.*.id'          => 'nullable|integer|exists:programacion_detalles,id',
            'lineas.*.paradero_id' => 'nullable|integer|exists:paraderos,id',
            'lineas.*.ruta_id'     => 'nullable|integer|exists:rutas,id',
            'lineas.*.lote'        => 'nullable|string|max:100',
            'lineas.*.comedor'     => 'nullable|string|max:100',
            'lineas.*.personas'    => 'nullable|integer|min:0|max:10000',
        ]);

        $lineas = $data['lineas'] ?? [];

        DB::transaction(function () use ($lineas, $programacion, $request, $user) {
            $programacion->load('detalles');
            $existentes = $programacion->detalles->keyBy('id');

            $idsConservados = [];

            foreach ($lineas as $linea) {
                $id         = $linea['id'] ?? null;
                $paraderoId = $linea['paradero_id'] ?? null;
                $personas   = isset($linea['personas']) ? (int) $linea['personas'] : 0;

                if (! $paraderoId || $personas <= 0) {
                    if ($id && $existentes->has($id)) {
                        $existentes[$id]->delete();
                    }
                    continue;
                }

                $rutaId = $linea['ruta_id'] ?? null;
                if ($rutaId === '' || $rutaId === 0 || $rutaId === '0') {
                    $rutaId = null;
                }

                $payload = [
                    'programacion_id' => $programacion->id,
                    'paradero_id'     => $paraderoId,
                    'ruta_id'         => $rutaId,
                    'lote'            => $linea['lote'] ?? null,
                    'comedor'         => $linea['comedor'] ?? null,
                    'personas'        => $personas,
                ];

                if ($id && $existentes->has($id)) {
                    $det = $existentes[$id];
                    $old = $det->getOriginal();

                    $det->fill($payload);
                    $det->save();

                    AuditLog::create([
                        'user_id'        => $user->id,
                        'action'         => 'programacion_detalle_updated',
                        'auditable_type' => ProgramacionDetalle::class,
                        'auditable_id'   => $det->id,
                        'old_values'     => $old,
                        'new_values'     => $det->toArray(),
                        'ip_address'     => $request->ip(),
                        'user_agent'     => $request->userAgent(),
                    ]);
                } else {
                    $det = ProgramacionDetalle::create($payload);

                    AuditLog::create([
                        'user_id'        => $user->id,
                        'action'         => 'programacion_detalle_created',
                        'auditable_type' => ProgramacionDetalle::class,
                        'auditable_id'   => $det->id,
                        'old_values'     => null,
                        'new_values'     => $det->toArray(),
                        'ip_address'     => $request->ip(),
                        'user_agent'     => $request->userAgent(),
                    ]);
                }

                $idsConservados[] = $det->id;
            }

            if (! empty($idsConservados)) {
                $programacion->detalles()
                    ->whereNotIn('id', $idsConservados)
                    ->delete();
            } else {
                $programacion->detalles()->delete();
            }

            $programacion->refresh();
            $programacion->recalcularTotalPersonas();

            $oldProg = $programacion->getOriginal();
            $programacion->actualizado_por = $user->id;
            $programacion->save();

            AuditLog::create([
                'user_id'        => $user->id,
                'action'         => 'programacion_matrix_saved',
                'auditable_type' => Programacion::class,
                'auditable_id'   => $programacion->id,
                'old_values'     => $oldProg,
                'new_values'     => $programacion->toArray(),
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);
        });

        return redirect()
            ->route('programaciones.edit', $programacion)
            ->with('status', 'Matriz guardada correctamente.');
    }

    /**
     * Actualizar cabecera de la programación (solo ADMIN_GENERAL).
     * Permite cambiar: sucursal, fecha, área, horario y tipo (recojo / salida).
     *
     * Acceso controlado por middleware:
     *  - role:admin_general
     *  - time.window
     */
    public function actualizarCabecera(Request $request, Programacion $programacion)
    {
        $user = $request->user();

        $esAdminGral = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
        if (! $esAdminGral) {
            abort(403, 'Solo el Administrador General puede modificar la cabecera de la programación.');
        }

        // Validación básica de campos
        $data = $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id',
            'fecha'       => 'required|date',
            'area_id'     => 'required|exists:areas,id',
            'horario_id'  => 'required|exists:horarios,id',
            'tipo'        => 'required|in:recojo,salida',
        ]);

        // Validar coherencia de horario vs tipo SOLO si el horario tiene tipo en BD
        $horario = Horario::findOrFail($data['horario_id']);

        if (! empty($horario->tipo)) {
            $tipoHorarioDb = strtoupper(trim($horario->tipo));  // 'SALIDA' / 'RECOJO'
            $tipoForm      = $data['tipo'];                     // 'salida' / 'recojo'

            if ($tipoHorarioDb === 'SALIDA' && $tipoForm !== Programacion::TIPO_SALIDA) {
                return back()
                    ->withErrors('El horario seleccionado es de SALIDA y el tipo elegido no coincide.')
                    ->withInput();
            }

            if ($tipoHorarioDb === 'RECOJO' && $tipoForm !== Programacion::TIPO_RECOJO) {
                return back()
                    ->withErrors('El horario seleccionado es de RECOJO y el tipo elegido no coincide.')
                    ->withInput();
            }
        }

        // Guardamos cambios en la cabecera
        $old = $programacion->getOriginal();

        $programacion->fill([
            'sucursal_id' => $data['sucursal_id'],
            'fecha'       => $data['fecha'],
            'area_id'     => $data['area_id'],
            'horario_id'  => $data['horario_id'],
            'tipo'        => $data['tipo'],
        ]);

        $programacion->actualizado_por = $user->id;
        $programacion->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'programacion_header_updated',
            'auditable_type' => Programacion::class,
            'auditable_id'   => $programacion->id,
            'old_values'     => $old,
            'new_values'     => $programacion->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('programaciones.edit', $programacion)
            ->with('status', 'Cabecera de programación actualizada correctamente.');
    }

    /**
     * Eliminar una programación y sus detalles.
     * Solo Admin General o Administrador de Sucursal.
     *
     * Acceso controlado por middleware:
     *  - permission:gestionar_programaciones
     *  - time.window
     */
    public function destroy(Request $request, Programacion $programacion)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;

        $esAdminGral     = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
        $esAdminSucursal = $user && method_exists($user, 'hasRole') && $user->hasRole('admin_sucursal');

        // Solo Admin General o Admin de Sucursal
        if (! $esAdminGral && ! $esAdminSucursal) {
            abort(403, 'Solo el Administrador General o el Administrador de Sucursal pueden eliminar programaciones.');
        }

        // Si NO es admin general, validar que sea de su sucursal
        if (! $esAdminGral && (int) $programacion->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede eliminar programaciones de otra sucursal.');
        }

        DB::transaction(function () use ($programacion, $user, $request) {
            $oldValues = $programacion->toArray();

            $programacion->detalles()->delete();
            $programacion->delete();

            AuditLog::create([
                'user_id'        => $user->id,
                'action'         => 'programacion_deleted',
                'auditable_type' => Programacion::class,
                'auditable_id'   => $oldValues['id'] ?? null,
                'old_values'     => $oldValues,
                'new_values'     => null,
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);
        });

        return redirect()
            ->route('programaciones.index')
            ->with('status', 'Programación eliminada correctamente.');
    }

    /**
     * Reabrir programación (cambiar estado a BORRADOR) para poder editar la matriz.
     * Solo Admin General o Administrador de Sucursal.
     *
     * Acceso controlado por middleware:
     *  - permission:gestionar_programaciones
     *  - time.window
     */
    public function reabrir(Request $request, Programacion $programacion)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;

        $esAdminGral     = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
        $esAdminSucursal = $user && method_exists($user, 'hasRole') && $user->hasRole('admin_sucursal');

        // Solo admin_general o admin_sucursal pueden reabrir
        if (! $esAdminGral && ! $esAdminSucursal) {
            abort(403, 'Solo el Administrador General o el Administrador de Sucursal pueden editar el estado.');
        }

        if (! $esAdminGral && (int) $programacion->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede modificar programaciones de otra sucursal.');
        }

        if (! $esAdminGral && ! $esAdminSucursal && $user->area_id && $programacion->area_id !== $user->area_id) {
            abort(403, 'No puede modificar programaciones de otra área.');
        }

        if ($programacion->estado === 'borrador') {
            return redirect()
                ->route('programaciones.edit', $programacion)
                ->with('status', 'La programación ya está en estado BORRADOR.');
        }

        $old = $programacion->getOriginal();
        $programacion->estado         = 'borrador';
        $programacion->actualizado_por = $user->id;
        $programacion->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'programacion_reopened',
            'auditable_type' => Programacion::class,
            'auditable_id'   => $programacion->id,
            'old_values'     => $old,
            'new_values'     => $programacion->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('programaciones.edit', $programacion)
            ->with('status', 'La programación fue cambiada a BORRADOR. Ahora puede editar la matriz.');
    }

    /**
     * Cambia estado a CONFIRMADO.
     *
     * Acceso controlado por middleware:
     *  - permission:gestionar_programaciones
     *  - time.window
     */
    public function confirmar(Request $request, Programacion $programacion)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;

        $esAdminGral     = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
        $esAdminSucursal = $user && method_exists($user, 'hasRole') && $user->hasRole('admin_sucursal');

        if (! $esAdminGral && (int) $programacion->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede confirmar programaciones de otra sucursal.');
        }

        if (! $esAdminGral && ! $esAdminSucursal && $user->area_id && $programacion->area_id !== $user->area_id) {
            abort(403, 'No puede confirmar programaciones de otra área.');
        }

        if ($programacion->estaCerrada()) {
            return redirect()->back()->withErrors('Ya está cerrada.');
        }

        $old = $programacion->getOriginal();
        $programacion->estado          = 'confirmado';
        $programacion->actualizado_por = $user->id;
        $programacion->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'programacion_confirmed',
            'auditable_type' => Programacion::class,
            'auditable_id'   => $programacion->id,
            'old_values'     => $old,
            'new_values'     => $programacion->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('programaciones.edit', $programacion)
            ->with('status', 'Programación confirmada.');
    }

    /**
     * Cambia estado a CERRADO (no editable).
     *
     * Acceso controlado por middleware:
     *  - permission:gestionar_programaciones
     *  - time.window
     */
    public function cerrar(Request $request, Programacion $programacion)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;

        $esAdminGral     = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
        $esAdminSucursal = $user && method_exists($user, 'hasRole') && $user->hasRole('admin_sucursal');

        if (! $esAdminGral && (int) $programacion->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede cerrar programaciones de otra sucursal.');
        }

        if (! $esAdminGral && ! $esAdminSucursal && $user->area_id && $programacion->area_id !== $user->area_id) {
            abort(403, 'No puede cerrar programaciones de otra área.');
        }

        $old = $programacion->getOriginal();
        $programacion->estado          = 'cerrado';
        $programacion->actualizado_por = $user->id;
        $programacion->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'programacion_closed',
            'auditable_type' => Programacion::class,
            'auditable_id'   => $programacion->id,
            'old_values'     => $old,
            'new_values'     => $programacion->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('programaciones.edit', $programacion)
            ->with('status', 'Programación cerrada.');
    }

    /**
     * Resumen Paradero x Horario para la sucursal del contexto, fecha y tipo (recojo/salida).
     * Solo muestra matriz cuando EXISTEN programaciones con personas > 0.
     *
     * Acceso controlado por middleware:
     *  - permission:ver_reportes
     *  - sucursal
     */
    public function resumenParaderoHorario(Request $request)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;

        $request->validate([
            'fecha' => 'nullable|date',
            'tipo'  => 'nullable|in:recojo,salida',
        ]);

        // Por defecto: fecha HOY y tipo RECOJO
        $fecha = $request->input('fecha', now()->toDateString());
        $tipo  = $request->input('tipo', Programacion::TIPO_RECOJO);

        $sucursalId = $contextSucursalId;
        if (! $sucursalId) {
            $sucursalId = $user->sucursal_id ?: Sucursal::where('activo', true)->value('id');
        }

        $rows = ProgramacionDetalle::selectRaw('
                programacion_detalles.paradero_id,
                programaciones.horario_id,
                SUM(programacion_detalles.personas) AS total
            ')
            ->join('programaciones', 'programacion_detalles.programacion_id', '=', 'programaciones.id')
            ->where('programaciones.sucursal_id', $sucursalId)
            ->whereDate('programaciones.fecha', $fecha)
            ->when($tipo, function ($q) use ($tipo) {
                $q->where('programaciones.tipo', $tipo);
            })
            ->groupBy(
                'programacion_detalles.paradero_id',
                'programaciones.horario_id'
            )
            ->get();

        $paraderos        = collect();
        $horarios         = collect();
        $matriz           = [];
        $totalesParadero  = [];
        $totalesHorario   = [];
        $totalGeneral     = 0;

        if ($rows->isNotEmpty()) {
            $paraderoIds = $rows->pluck('paradero_id')->unique()->filter()->values();
            $horarioIds  = $rows->pluck('horario_id')->unique()->filter()->values();

            $paraderos = Paradero::whereIn('id', $paraderoIds)
                ->with('lugar')
                ->orderBy('nombre')
                ->get();

            $horarios = Horario::whereIn('id', $horarioIds)
                ->orderBy('hora')
                ->get();

            foreach ($rows as $r) {
                $pid   = (int) $r->paradero_id;
                $hid   = (int) $r->horario_id;
                $total = (int) $r->total;

                if ($total <= 0 || ! $pid || ! $hid) {
                    continue;
                }

                if (! isset($matriz[$pid])) {
                    $matriz[$pid] = [];
                }

                $matriz[$pid][$hid] = $total;

                $totalesParadero[$pid] = ($totalesParadero[$pid] ?? 0) + $total;
                $totalesHorario[$hid]  = ($totalesHorario[$hid]  ?? 0) + $total;
                $totalGeneral         += $total;
            }
        }

        $tipos = [
            Programacion::TIPO_RECOJO => 'Recojo',
            Programacion::TIPO_SALIDA => 'Salida',
        ];

        return view('programaciones.resumen_paradero_horario', compact(
            'fecha',
            'paraderos',
            'horarios',
            'matriz',
            'tipo',
            'tipos',
            'totalesParadero',
            'totalesHorario',
            'totalGeneral'
        ));
    }

    /**
     * Vista rápida de captura – Opción 2 (por bloques de Lugar).
     *
     * - Admin General / Admin de Sucursal: pueden elegir cualquier Área y Horario.
     * - Operador: solo su Área y, si tienen horario asignado, solo ese Horario.
     *
     * Acceso controlado por middleware:
     *  - permission:gestionar_programaciones
     *  - sucursal
     */
    public function capturaRapida(Request $request)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;

        $esAdminGral     = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
        $esAdminSucursal = $user && method_exists($user, 'hasRole') && $user->hasRole('admin_sucursal');

        $request->validate([
            'fecha'      => 'nullable|date',
            'tipo'       => 'nullable|in:recojo,salida',
            'area_id'    => 'nullable|integer|exists:areas,id',
            'horario_id' => 'nullable|integer|exists:horarios,id',
        ]);

        $fecha = $request->input('fecha', now()->toDateString());
        $tipo  = $request->input('tipo', Programacion::TIPO_RECOJO);

        if (! $contextSucursalId) {
            $contextSucursalId = $user->sucursal_id ?: Sucursal::where('activo', true)->value('id');
        }

        // 1) Áreas según rol
        $areasQuery = Area::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->whereNull('sucursal_id')
                  ->orWhere('sucursal_id', $contextSucursalId);
            });

        if (! $esAdminGral && ! $esAdminSucursal) {
            if ($user->area_id) {
                $areasQuery->where('id', $user->area_id);
            } else {
                abort(403, 'No tiene un área asignada para capturar programación.');
            }
        }

        $areas = $areasQuery->orderBy('nombre')->get();

        $areaIdSeleccionado = (int) $request->input('area_id', 0);
        if (! $areas->contains('id', $areaIdSeleccionado)) {
            $areaIdSeleccionado = $areas->isNotEmpty()
                ? (int) $areas->first()->id
                : 0;
        }

        // 2) Horarios según tipo y rol
        $tipoHorarioDb = $tipo === Programacion::TIPO_SALIDA ? 'SALIDA' : 'RECOJO';

        $horariosQuery = Horario::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->whereNull('sucursal_id')
                  ->orWhere('sucursal_id', $contextSucursalId);
            })
            ->where('tipo', $tipoHorarioDb);

        if (! $esAdminGral && ! $esAdminSucursal && ! empty($user->horario_id)) {
            $horariosQuery->where('id', $user->horario_id);
        }

        $horarios = $horariosQuery->orderBy('hora')->get();

        $horarioIdSeleccionado = (int) $request->input('horario_id', 0);
        if (! $horarios->contains('id', $horarioIdSeleccionado)) {
            $horarioIdSeleccionado = $horarios->isNotEmpty()
                ? (int) $horarios->first()->id
                : 0;
        }

        // 3) Paraderos agrupados por Lugar
        $paraderos = Paradero::where('sucursal_id', $contextSucursalId)
            ->where('activo', true)
            ->with('lugar')
            ->orderBy('nombre')
            ->get();

        $paraderosPorLugar = $paraderos
            ->sortBy(function (Paradero $p) {
                $lugar = $p->lugar?->nombre ?? 'SIN LUGAR';
                return sprintf('%s|%s', $lugar, $p->nombre);
            })
            ->groupBy(function (Paradero $p) {
                return $p->lugar?->nombre ?? 'SIN LUGAR';
            });

        // 4) Construir filas Ruta / Lote / Comedor
        $filas        = [];
        $matrix       = [];
        $programacion = null;

        if ($areaIdSeleccionado && $horarioIdSeleccionado && $paraderos->isNotEmpty()) {
            $rutas = Ruta::where('sucursal_id', $contextSucursalId)
                ->where('activo', true)
                ->with('lotes')
                ->orderBy('codigo')
                ->get();

            foreach ($rutas as $ruta) {
                $rutaId  = (int) $ruta->id;
                $codigo  = $ruta->codigo ?: ('R-' . $rutaId);
                $nomRuta = $ruta->nombre ?? '';

                foreach ($ruta->lotes as $lote) {
                    $nomLote = trim($lote->nombre ?? '');

                    $comedores = $lote->comedores_list ?? [];
                    if (is_string($comedores)) {
                        $comedores = array_filter(
                            array_map('trim', explode(',', $comedores)),
                            fn ($c) => $c !== ''
                        );
                    } elseif ($comedores instanceof \Illuminate\Support\Collection) {
                        $comedores = $comedores->filter()->values()->all();
                    } elseif (! is_array($comedores)) {
                        $comedores = [];
                    }

                    if (empty($comedores)) {
                        $key = $rutaId . '|' . $nomLote . '|';
                        $filas[$key] = [
                            'ruta_id'     => $rutaId,
                            'ruta_codigo' => $codigo,
                            'ruta_nombre' => $nomRuta,
                            'lote'        => $nomLote,
                            'comedor'     => '',
                        ];
                    } else {
                        foreach ($comedores as $com) {
                            $nomCom = trim($com);
                            $key    = $rutaId . '|' . $nomLote . '|' . $nomCom;

                            $filas[$key] = [
                                'ruta_id'     => $rutaId,
                                'ruta_codigo' => $codigo,
                                'ruta_nombre' => $nomRuta,
                                'lote'        => $nomLote,
                                'comedor'     => $nomCom,
                            ];
                        }
                    }
                }
            }

            // Ordenar filas
            $filas = collect($filas)
                ->sortBy(function (array $f) {
                    return sprintf(
                        '%s|%s|%s',
                        str_pad($f['ruta_codigo'], 10, '0', STR_PAD_LEFT),
                        $f['lote'],
                        $f['comedor']
                    );
                })
                ->toArray();

            // 5) Cargar programación existente (para precargar cantidades)
            $programacion = Programacion::where('sucursal_id', $contextSucursalId)
                ->whereDate('fecha', $fecha)
                ->where('area_id', $areaIdSeleccionado)
                ->where('horario_id', $horarioIdSeleccionado)
                ->where('tipo', $tipo)
                ->first();

            if ($programacion) {
                $valores = ProgramacionDetalle::selectRaw('
                        COALESCE(ruta_id, 0)    AS ruta_id,
                        COALESCE(lote, \'\')    AS lote,
                        COALESCE(comedor, \'\') AS comedor,
                        paradero_id,
                        SUM(personas)          AS total
                    ')
                    ->where('programacion_id', $programacion->id)
                    ->groupBy('ruta_id', 'lote', 'comedor', 'paradero_id')
                    ->get();

                foreach ($valores as $v) {
                    $rutaId = (int) $v->ruta_id;
                    $lote   = $v->lote ?? '';
                    $com    = $v->comedor ?? '';
                    $pid    = (int) $v->paradero_id;
                    $total  = (int) $v->total;

                    if ($pid <= 0 || $total <= 0) {
                        continue;
                    }

                    $key = $rutaId . '|' . $lote . '|' . $com;

                    if (! isset($matrix[$key])) {
                        $matrix[$key] = [];
                    }

                    $matrix[$key][$pid] = $total;
                }
            }
        }

        return view('programaciones.captura_rapida', compact(
            'fecha',
            'tipo',
            'areas',
            'areaIdSeleccionado',
            'horarios',
            'horarioIdSeleccionado',
            'paraderosPorLugar',
            'filas',
            'matrix',
            'programacion',
            'esAdminGral',
            'esAdminSucursal'
        ));
    }

    /**
     * Guarda la matriz de captura rápida (Ruta/Lote/Comedor x Paradero).
     *
     * - Reemplaza completamente la programación existente para
     *   Sucursal + Fecha + Área + Horario + Tipo.
     *
     * Acceso controlado por middleware:
     *  - permission:gestionar_programaciones
     *  - sucursal
     *  - time.window
     */
    public function guardarCapturaRapida(Request $request)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;

        $esAdminGral     = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
        $esAdminSucursal = $user && method_exists($user, 'hasRole') && $user->hasRole('admin_sucursal');

        $data = $request->validate([
            'fecha'      => 'required|date',
            'tipo'       => 'required|in:recojo,salida',
            'area_id'    => 'required|integer|exists:areas,id',
            'horario_id' => 'required|integer|exists:horarios,id',
            'matrix'     => 'nullable|array',
        ]);

        if (! $contextSucursalId) {
            $contextSucursalId = $user->sucursal_id ?: Sucursal::where('activo', true)->value('id');
        }

        // Restricción por Área
        if (! $esAdminGral && ! $esAdminSucursal) {
            if (! $user->area_id || (int) $user->area_id !== (int) $data['area_id']) {
                abort(403, 'No puede capturar programación para otra área.');
            }
        }

        // Restricción por Horario (si el usuario tiene uno asignado)
        if (! $esAdminGral && ! $esAdminSucursal && ! empty($user->horario_id)) {
            if ((int) $user->horario_id !== (int) $data['horario_id']) {
                abort(403, 'No puede capturar programación para otro horario.');
            }
        }

        // Coherencia Horario vs Tipo
        $horario = Horario::findOrFail($data['horario_id']);
        if (! empty($horario->tipo)) {
            $tipoHorarioDb = strtoupper(trim($horario->tipo));

            if ($tipoHorarioDb === 'RECOJO' && $data['tipo'] !== Programacion::TIPO_RECOJO) {
                return back()
                    ->withErrors('El horario seleccionado es de RECOJO y el tipo elegido no coincide.')
                    ->withInput();
            }

            if ($tipoHorarioDb === 'SALIDA' && $data['tipo'] !== Programacion::TIPO_SALIDA) {
                return back()
                    ->withErrors('El horario seleccionado es de SALIDA y el tipo elegido no coincide.')
                    ->withInput();
            }
        }

        $fecha     = $data['fecha'];
        $tipo      = $data['tipo'];
        $areaId    = (int) $data['area_id'];
        $horarioId = (int) $data['horario_id'];
        $matrix    = $data['matrix'] ?? [];

        DB::transaction(function () use ($user, $request, $contextSucursalId, $fecha, $tipo, $areaId, $horarioId, $matrix) {
            $programacion = Programacion::firstOrCreate(
                [
                    'sucursal_id' => $contextSucursalId,
                    'fecha'       => $fecha,
                    'area_id'     => $areaId,
                    'horario_id'  => $horarioId,
                    'tipo'        => $tipo,
                ],
                [
                    'estado'          => 'borrador',
                    'total_personas'  => 0,
                    'creado_por'      => $user->id,
                    'actualizado_por' => $user->id,
                ]
            );

            $programacion->detalles()->delete();

            foreach ($matrix as $keyFila => $cols) {
                if (! is_array($cols)) {
                    continue;
                }

                // keyFila = "rutaId|lote|comedor"
                $parts   = explode('|', (string) $keyFila);
                $rutaId  = (int) ($parts[0] ?? 0);
                $lote    = trim($parts[1] ?? '');
                $comedor = trim($parts[2] ?? '');

                foreach ($cols as $paraderoId => $valor) {
                    if ($valor === null || $valor === '') {
                        continue;
                    }

                    $personas   = (int) $valor;
                    $paraderoId = (int) $paraderoId;

                    if ($personas <= 0 || $paraderoId <= 0) {
                        continue;
                    }

                    $detalle = ProgramacionDetalle::create([
                        'programacion_id' => $programacion->id,
                        'paradero_id'     => $paraderoId,
                        'ruta_id'         => $rutaId ?: null,
                        'lote'            => $lote !== '' ? $lote : null,
                        'comedor'         => $comedor !== '' ? $comedor : null,
                        'personas'        => $personas,
                    ]);

                    AuditLog::create([
                        'user_id'        => $user->id,
                        'action'         => 'programacion_detalle_created_rapida',
                        'auditable_type' => ProgramacionDetalle::class,
                        'auditable_id'   => $detalle->id,
                        'old_values'     => null,
                        'new_values'     => $detalle->toArray(),
                        'ip_address'     => $request->ip(),
                        'user_agent'     => $request->userAgent(),
                    ]);
                }
            }

            $oldProg = $programacion->getOriginal();

            $programacion->recalcularTotalPersonas();
            $programacion->actualizado_por = $user->id;
            $programacion->save();

            AuditLog::create([
                'user_id'        => $user->id,
                'action'         => 'programacion_captura_rapida_saved',
                'auditable_type' => Programacion::class,
                'auditable_id'   => $programacion->id,
                'old_values'     => $oldProg,
                'new_values'     => $programacion->toArray(),
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);
        });

        return redirect()
            ->route('programaciones.captura-rapida', [
                'fecha'      => $fecha,
                'tipo'       => $tipo,
                'area_id'    => $areaId,
                'horario_id' => $horarioId,
            ])
            ->with('status', 'Programación rápida guardada correctamente.');
    }

    /**
     * Resumen Ruta x Paradero (formato nuevo):
     *  - Filas: HORARIO / ÁREA
     *  - Columnas: Lugares y sus Paraderos
     *  - Subtotal por horario (fila extra debajo de cada bloque de horario).
     *
     * Acceso controlado por middleware:
     *  - permission:ver_reportes
     *  - sucursal
     */
    public function resumenRutaParadero(Request $request)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;

        $request->validate([
            'fecha' => 'nullable|date',
            'tipo'  => 'nullable|in:recojo,salida',
        ]);

        $fecha = $request->input('fecha', now()->toDateString());
        $tipo  = $request->input('tipo'); // null = ambos

        $sucursalId = $contextSucursalId;
        if (! $sucursalId) {
            $sucursalId = $user->sucursal_id ?: Sucursal::where('activo', true)->value('id');
        }

        $paraderos = Paradero::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->with('lugar')
            ->orderBy('nombre')
            ->get();

        $rows = ProgramacionDetalle::selectRaw('
                programaciones.horario_id,
                programaciones.area_id,
                programacion_detalles.paradero_id,
                SUM(programacion_detalles.personas) AS total
            ')
            ->join('programaciones', 'programacion_detalles.programacion_id', '=', 'programaciones.id')
            ->where('programaciones.sucursal_id', $sucursalId)
            ->whereDate('programaciones.fecha', $fecha)
            ->when($tipo, function ($q) use ($tipo) {
                $q->where('programaciones.tipo', $tipo);
            })
            ->groupBy(
                'programaciones.horario_id',
                'programaciones.area_id',
                'programacion_detalles.paradero_id'
            )
            ->get();

        $horarios              = collect();
        $areas                 = collect();
        $filas                 = [];
        $matriz                = [];
        $totalesFila           = [];
        $subtotalesHorario     = [];
        $subtotalesHorarioTot  = [];
        $totalesParadero       = [];
        $totalGeneral          = 0;

        if ($rows->isNotEmpty()) {
            $horarioIds = $rows->pluck('horario_id')->unique()->filter()->values();
            $areaIds    = $rows->pluck('area_id')->unique()->filter()->values();

            $horarios = Horario::whereIn('id', $horarioIds)
                ->orderBy('hora')
                ->get();

            $areas = Area::whereIn('id', $areaIds)
                ->orderBy('nombre')
                ->get();

            $horariosIndex = $horarios->keyBy('id');
            $areasIndex    = $areas->keyBy('id');

            foreach ($rows as $r) {
                $hid    = (int) $r->horario_id;
                $aid    = (int) $r->area_id;
                $pid    = (int) $r->paradero_id;
                $total  = (int) $r->total;

                if (! $hid || ! $aid || ! $pid) {
                    continue;
                }

                if (! isset($filas[$hid])) {
                    $filas[$hid] = [];
                }
                if (! isset($filas[$hid][$aid])) {
                    $filas[$hid][$aid] = [
                        'horario_id' => $hid,
                        'area_id'    => $aid,
                    ];
                }

                if (! isset($matriz[$hid])) {
                    $matriz[$hid] = [];
                }
                if (! isset($matriz[$hid][$aid])) {
                    $matriz[$hid][$aid] = [];
                }

                $matriz[$hid][$aid][$pid] = $total;

                if (! isset($totalesFila[$hid])) {
                    $totalesFila[$hid] = [];
                }
                $totalesFila[$hid][$aid] = ($totalesFila[$hid][$aid] ?? 0) + $total;

                if (! isset($subtotalesHorario[$hid])) {
                    $subtotalesHorario[$hid] = [];
                }
                $subtotalesHorario[$hid][$pid] = ($subtotalesHorario[$hid][$pid] ?? 0) + $total;

                $subtotalesHorarioTot[$hid] = ($subtotalesHorarioTot[$hid] ?? 0) + $total;

                $totalesParadero[$pid] = ($totalesParadero[$pid] ?? 0) + $total;
                $totalGeneral         += $total;
            }

            // Orden de filas por horario y luego por área/responsable
            $filas = collect($filas)
                ->sortBy(function ($filasPorArea, $hid) use ($horariosIndex) {
                    $h = $horariosIndex->get($hid);
                    return $h ? ($h->hora_formateada ?? '00:00') : '00:00';
                })
                ->map(function ($filasPorArea) use ($areasIndex) {
                    return collect($filasPorArea)
                        ->sortBy(function ($fila) use ($areasIndex) {
                            $area  = $areasIndex->get($fila['area_id']);
                            $resp  = $area?->responsable ?? '';
                            $nom   = $area?->nombre ?? '';
                            return sprintf('%s|%s', $resp, $nom);
                        })
                        ->values()
                        ->all();
                })
                ->toArray();
        }

        $paraderosPorLugar = $paraderos
            ->sortBy(function (Paradero $p) {
                $lugar = $p->lugar?->nombre ?? 'SIN LUGAR';
                return sprintf('%s|%s', $lugar, $p->nombre);
            })
            ->groupBy(function (Paradero $p) {
                return $p->lugar?->nombre ?? 'SIN LUGAR';
            });

        $tipos = [
            Programacion::TIPO_RECOJO => 'Recojo',
            Programacion::TIPO_SALIDA => 'Salida',
        ];

        return view('programaciones.resumen_ruta_paradero', compact(
            'fecha',
            'sucursalId',
            'paraderos',
            'paraderosPorLugar',
            'horarios',
            'areas',
            'filas',
            'matriz',
            'totalesFila',
            'subtotalesHorario',
            'subtotalesHorarioTot',
            'totalesParadero',
            'totalGeneral',
            'tipo',
            'tipos'
        ));
    }

    /**
     * Reporte Ruta / Lote / Comedor.
     *
     * Cada fila: Ruta - Lote - Comedor
     * Columnas: bloques Horario x Áreas + subtotal por horario.
     *
     * Acceso controlado por middleware:
     *  - permission:ver_reportes
     *  - sucursal
     */
    public function reporteRutaLoteCom(Request $request)
    {
        $user              = $request->user();
        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;

        $request->validate([
            'fecha' => 'nullable|date',
            'tipo'  => 'nullable|in:recojo,salida',
        ]);

        $fecha = $request->input('fecha', now()->toDateString());
        $tipo  = $request->input('tipo'); // recojo / salida / null

        $sucursalId = $contextSucursalId ?: $user->sucursal_id;
        if (! $sucursalId) {
            $sucursalId = Sucursal::where('activo', true)->value('id');
        }

        $rows = ProgramacionDetalle::selectRaw('
                programacion_detalles.ruta_id,
                COALESCE(programacion_detalles.lote, \'\')    AS lote,
                COALESCE(programacion_detalles.comedor, \'\') AS comedor,
                programaciones.horario_id,
                programaciones.area_id,
                SUM(programacion_detalles.personas)           AS total
            ')
            ->join('programaciones', 'programacion_detalles.programacion_id', '=', 'programaciones.id')
            ->where('programaciones.sucursal_id', $sucursalId)
            ->whereDate('programaciones.fecha', $fecha)
            ->when($tipo, function ($q) use ($tipo) {
                $q->where('programaciones.tipo', $tipo);
            })
            ->groupBy(
                'programacion_detalles.ruta_id',
                'programacion_detalles.lote',
                'programacion_detalles.comedor',
                'programaciones.horario_id',
                'programaciones.area_id'
            )
            ->get();

        if ($rows->isEmpty()) {
            $horarios        = collect();
            $areas           = collect();
            $filas           = collect();
            $matriz          = [];
            $totalesFila     = [];
            $totalesColumna  = [];
            $totalGeneral    = 0;
            $columnas        = [];

            return view('programaciones.reporte_ruta_lote_com', compact(
                'fecha',
                'tipo',
                'horarios',
                'areas',
                'filas',
                'matriz',
                'totalesFila',
                'totalesColumna',
                'totalGeneral',
                'columnas'
            ));
        }

        $rutaIds    = $rows->pluck('ruta_id')->filter()->unique()->values();
        $horarioIds = $rows->pluck('horario_id')->unique()->values();
        $areaIds    = $rows->pluck('area_id')->unique()->values();

        $rutas = Ruta::whereIn('id', $rutaIds)
            ->orderBy('codigo')
            ->get()
            ->keyBy('id');

        $horarios = Horario::whereIn('id', $horarioIds)
            ->orderBy('hora')
            ->get();

        $areas = Area::whereIn('id', $areaIds)
            ->orderBy('nombre')
            ->get();

        $filas          = [];
        $matriz         = [];
        $totalesFila    = [];
        $totalesColumna = [];
        $totalGeneral   = 0;

        foreach ($rows as $r) {
            $rutaId = $r->ruta_id ?: 0;
            $ruta   = $rutas->get($rutaId);
            $codigoRuta = $ruta ? $ruta->codigo : 'SIN RUTA';

            $lote    = trim($r->lote ?? '');
            $comedor = trim($r->comedor ?? '');

            $keyFila = $rutaId . '|' . $lote . '|' . $comedor;

            if (! isset($filas[$keyFila])) {
                $filas[$keyFila] = [
                    'ruta_id'     => $rutaId,
                    'ruta_codigo' => $codigoRuta,
                    'lote'        => $lote,
                    'comedor'     => $comedor,
                ];
            }

            $hId    = (int) $r->horario_id;
            $areaId = (int) $r->area_id;
            $total  = (int) $r->total;

            if (! isset($matriz[$keyFila])) {
                $matriz[$keyFila] = [];
            }
            if (! isset($matriz[$keyFila][$hId])) {
                $matriz[$keyFila][$hId] = [];
            }

            $matriz[$keyFila][$hId][$areaId] = $total;

            $totalesFila[$keyFila]              = ($totalesFila[$keyFila] ?? 0) + $total;
            $totalesColumna[$hId][$areaId]      = ($totalesColumna[$hId][$areaId] ?? 0) + $total;
            $totalGeneral                       += $total;
        }

        $filas = collect($filas)->sortBy(function ($f) {
            return sprintf(
                '%s|%s|%s',
                str_pad($f['ruta_codigo'], 10, '0', STR_PAD_LEFT),
                $f['lote'],
                $f['comedor']
            );
        });

        $areasArr = $areas->map(function (Area $a) {
            return [
                'id'     => $a->id,
                'nombre' => $a->nombre,
            ];
        })->values()->all();

        $columnas = $horarios->map(function (Horario $h) use ($areasArr) {
            $label = $h->etiqueta_completa
                ?? trim(
                    ($h->nombre ? $h->nombre . ' ' : '') .
                    ($h->hora_formateada ?? '')
                );

            if ($label === '') {
                $label = 'Horario ' . $h->id;
            }

            return [
                'horario_id' => $h->id,
                'label'      => $label,
                'areas'      => $areasArr,
            ];
        })->values()->all();

        $horarios = $horarios->values();
        $areas    = $areas->values();

        return view('programaciones.reporte_ruta_lote_com', compact(
            'fecha',
            'tipo',
            'horarios',
            'areas',
            'filas',
            'matriz',
            'totalesFila',
            'totalesColumna',
            'totalGeneral',
            'columnas'
        ));
    }
}
