<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HorarioApiController extends Controller
{
    /**
     * GET /api/v1/catalogos/horarios
     * Lista de horarios de la sucursal del usuario autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $query = Horario::query()
            ->where('sucursal_id', $user->sucursal_id)
            ->orderBy('hora_inicio');

        // Filtro por activo (true / false)
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

        // Búsqueda texto libre: código / nombre
        if ($search = $request->input('search')) {
            $like = '%' . $search . '%';

            // En PostgreSQL usamos ilike para case-insensitive
            $query->where(function ($q) use ($like) {
                $q->where('codigo', 'ilike', $like)
                  ->orWhere('nombre', 'ilike', $like);
            });
        }

        $horarios = $query->get();

        return response()->json($horarios);
    }

    /**
     * POST /api/v1/catalogos/horarios
     * Crea un nuevo horario para la sucursal del usuario.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $data = $request->validate([
            'codigo'       => [
                'required',
                'string',
                'max:20',
                Rule::unique('horarios', 'codigo')
                    ->where('sucursal_id', $user->sucursal_id),
            ],
            'nombre'       => ['required', 'string', 'max:150'],
            'hora_inicio'  => ['required', 'date_format:H:i'],
            'hora_fin'     => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'tipo'         => ['required', 'string', 'max:20'], // ej: salida, recojo, mixto
            'activo'       => ['sometimes', 'boolean'],
        ]);

        $horario = new Horario();
        $horario->sucursal_id = $user->sucursal_id;
        $horario->codigo      = $data['codigo'];
        $horario->nombre      = $data['nombre'];
        $horario->hora_inicio = $data['hora_inicio'];
        $horario->hora_fin    = $data['hora_fin'];
        $horario->tipo        = $data['tipo'];
        $horario->activo      = $data['activo'] ?? true;
        $horario->save();

        return response()->json($horario, 201);
    }

    /**
     * GET /api/v1/catalogos/horarios/{horario}
     * Devuelve un horario específico.
     */
    public function show(Request $request, Horario $horario)
    {
        $this->ensureCanAccessHorario($request, $horario);

        return response()->json($horario);
    }

    /**
     * PUT/PATCH /api/v1/catalogos/horarios/{horario}
     * Actualiza un horario existente.
     */
    public function update(Request $request, Horario $horario)
    {
        $this->ensureCanAccessHorario($request, $horario);

        $user = $request->user();

        $data = $request->validate([
            'codigo'       => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('horarios', 'codigo')
                    ->ignore($horario->id)
                    ->where('sucursal_id', $user->sucursal_id),
            ],
            'nombre'       => ['sometimes', 'string', 'max:150'],
            'hora_inicio'  => ['sometimes', 'date_format:H:i'],
            'hora_fin'     => ['sometimes', 'date_format:H:i'],
            'tipo'         => ['sometimes', 'string', 'max:20'],
            'activo'       => ['sometimes', 'boolean'],
        ]);

        foreach (['codigo', 'nombre', 'hora_inicio', 'hora_fin', 'tipo', 'activo'] as $field) {
            if (array_key_exists($field, $data)) {
                $horario->{$field} = $data[$field];
            }
        }

        // Si mando solo hora_fin sin hora_inicio, la regla "after" ya no aplica,
        // asumo que la validación funcional ya se hizo en el front.

        $horario->save();

        return response()->json($horario);
    }

    /**
     * DELETE /api/v1/catalogos/horarios/{horario}
     * Elimina un horario.
     * (Si prefieres “desactivar” en lugar de borrar, aquí se podría
     * hacer solo activo = false).
     */
    public function destroy(Request $request, Horario $horario)
    {
        $this->ensureCanAccessHorario($request, $horario);

        $horario->delete();

        return response()->json([
            'message' => 'Horario eliminado correctamente.',
        ]);
    }

    /**
     * Verifica que el usuario pueda acceder a este horario.
     * Debe ser de su misma sucursal o ser admin_general.
     */
    protected function ensureCanAccessHorario(Request $request, Horario $horario): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        if (! $user->isAdminGeneral() && $horario->sucursal_id !== $user->sucursal_id) {
            abort(403, 'No autorizado para acceder a este horario.');
        }
    }
}
