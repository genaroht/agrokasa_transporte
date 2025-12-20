<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RutaApiController extends Controller
{
    /**
     * GET /api/v1/catalogos/rutas
     * Lista de rutas de la sucursal del usuario autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $query = Ruta::query()
            ->where('sucursal_id', $user->sucursal_id)
            ->orderBy('codigo');

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

        // Filtro por tipo (si tu tabla tiene una columna "tipo": SALIDA / RECOJO / MIXTA, etc.)
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        // Búsqueda texto libre por código / nombre
        if ($search = $request->input('search')) {
            $like = '%' . $search . '%';

            // En PostgreSQL usamos ilike para case-insensitive
            $query->where(function ($q) use ($like) {
                $q->where('codigo', 'ilike', $like)
                  ->orWhere('nombre', 'ilike', $like);
            });
        }

        $rutas = $query->get();

        return response()->json($rutas);
    }

    /**
     * POST /api/v1/catalogos/rutas
     * Crea una nueva ruta en la sucursal del usuario.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $data = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('rutas', 'codigo')
                    ->where('sucursal_id', $user->sucursal_id),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'tipo'   => ['sometimes', 'nullable', 'string', 'max:20'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $ruta = new Ruta();
        $ruta->sucursal_id = $user->sucursal_id;
        $ruta->codigo      = $data['codigo'];
        $ruta->nombre      = $data['nombre'];
        $ruta->tipo        = $data['tipo'] ?? null;
        $ruta->activo      = $data['activo'] ?? true;
        $ruta->save();

        return response()->json($ruta, 201);
    }

    /**
     * GET /api/v1/catalogos/rutas/{ruta}
     * Devuelve una ruta específica.
     */
    public function show(Request $request, Ruta $ruta)
    {
        $this->ensureCanAccessRuta($request, $ruta);

        return response()->json($ruta);
    }

    /**
     * PUT/PATCH /api/v1/catalogos/rutas/{ruta}
     * Actualiza una ruta existente.
     */
    public function update(Request $request, Ruta $ruta)
    {
        $this->ensureCanAccessRuta($request, $ruta);

        $user = $request->user();

        $data = $request->validate([
            'codigo' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('rutas', 'codigo')
                    ->ignore($ruta->id)
                    ->where('sucursal_id', $user->sucursal_id),
            ],
            'nombre' => ['sometimes', 'string', 'max:150'],
            'tipo'   => ['sometimes', 'nullable', 'string', 'max:20'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        foreach (['codigo', 'nombre', 'tipo', 'activo'] as $field) {
            if (array_key_exists($field, $data)) {
                $ruta->{$field} = $data[$field];
            }
        }

        $ruta->save();

        return response()->json($ruta);
    }

    /**
     * DELETE /api/v1/catalogos/rutas/{ruta}
     * Elimina una ruta.
     * (Si prefieres solo desactivar, podrías cambiar a activo=false aquí).
     */
    public function destroy(Request $request, Ruta $ruta)
    {
        $this->ensureCanAccessRuta($request, $ruta);

        $ruta->delete();

        return response()->json([
            'message' => 'Ruta eliminada correctamente.',
        ]);
    }

    /**
     * Verifica que el usuario pueda acceder a esta ruta.
     * Debe ser de su sucursal o ser admin_general.
     */
    protected function ensureCanAccessRuta(Request $request, Ruta $ruta): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        if (! $user->isAdminGeneral() && $ruta->sucursal_id !== $user->sucursal_id) {
            abort(403, 'No autorizado para acceder a esta ruta.');
        }
    }
}
