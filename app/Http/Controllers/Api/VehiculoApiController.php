<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehiculoApiController extends Controller
{
    /**
     * GET /api/v1/catalogos/vehiculos
     * Lista de vehículos de la sucursal del usuario autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $query = Vehiculo::query()
            ->where('sucursal_id', $user->sucursal_id)
            ->orderBy('codigo_interno');

        // Filtro: activo=true/false
        if ($request->filled('activo')) {
            $activo = filter_var(
                $request->input('activo'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if (! is_null($activo)) {
                $query->where('activo', $activo);
            }
        }

        // Búsqueda texto libre por placa / código interno
        if ($search = $request->input('search')) {
            $like = '%' . $search . '%';

            $query->where(function ($q) use ($like) {
                // Postgres: ilike para case-insensitive
                $q->where('placa', 'ilike', $like)
                  ->orWhere('codigo_interno', 'ilike', $like);
            });
        }

        $vehiculos = $query->get();

        return response()->json($vehiculos);
    }

    /**
     * POST /api/v1/catalogos/vehiculos
     * Crea un nuevo vehículo en la sucursal del usuario.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $data = $request->validate([
            'placa' => [
                'required',
                'string',
                'max:15',
                Rule::unique('vehiculos', 'placa')
                    ->where('sucursal_id', $user->sucursal_id),
            ],
            'codigo_interno' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehiculos', 'codigo_interno')
                    ->where('sucursal_id', $user->sucursal_id),
            ],
            'capacidad' => ['required', 'integer', 'min:1', 'max:999'],
            'activo'    => ['sometimes', 'boolean'],
        ]);

        $vehiculo = Vehiculo::create([
            'sucursal_id'    => $user->sucursal_id,
            'placa'          => $data['placa'],
            'codigo_interno' => $data['codigo_interno'],
            'capacidad'      => $data['capacidad'],
            'activo'         => $data['activo'] ?? true,
        ]);

        return response()->json($vehiculo, 201);
    }

    /**
     * GET /api/v1/catalogos/vehiculos/{vehiculo}
     * Devuelve un vehículo específico.
     */
    public function show(Request $request, Vehiculo $vehiculo)
    {
        $this->ensureCanAccessVehiculo($request, $vehiculo);

        return response()->json($vehiculo);
    }

    /**
     * PUT/PATCH /api/v1/catalogos/vehiculos/{vehiculo}
     * Actualiza un vehículo existente.
     */
    public function update(Request $request, Vehiculo $vehiculo)
    {
        $this->ensureCanAccessVehiculo($request, $vehiculo);

        $user = $request->user();

        $data = $request->validate([
            'placa' => [
                'sometimes',
                'string',
                'max:15',
                Rule::unique('vehiculos', 'placa')
                    ->ignore($vehiculo->id)
                    ->where('sucursal_id', $user->sucursal_id),
            ],
            'codigo_interno' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('vehiculos', 'codigo_interno')
                    ->ignore($vehiculo->id)
                    ->where('sucursal_id', $user->sucursal_id),
            ],
            'capacidad' => ['sometimes', 'integer', 'min:1', 'max:999'],
            'activo'    => ['sometimes', 'boolean'],
        ]);

        foreach (['placa', 'codigo_interno', 'capacidad', 'activo'] as $field) {
            if (array_key_exists($field, $data)) {
                $vehiculo->{$field} = $data[$field];
            }
        }

        $vehiculo->save();

        return response()->json($vehiculo);
    }

    /**
     * DELETE /api/v1/catalogos/vehiculos/{vehiculo}
     * Elimina un vehículo.
     */
    public function destroy(Request $request, Vehiculo $vehiculo)
    {
        $this->ensureCanAccessVehiculo($request, $vehiculo);

        $vehiculo->delete();

        return response()->json([
            'message' => 'Vehículo eliminado correctamente.',
        ]);
    }

    /**
     * Verifica que el usuario pueda acceder a este vehículo.
     * Debe ser de su sucursal o ser admin_general.
     */
    protected function ensureCanAccessVehiculo(Request $request, Vehiculo $vehiculo): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        if (! $user->isAdminGeneral() && $vehiculo->sucursal_id !== $user->sucursal_id) {
            abort(403, 'No autorizado para acceder a este vehículo.');
        }
    }
}
