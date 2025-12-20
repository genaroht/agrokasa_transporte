<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador de autenticación para la API (apps móvil / escritorio).
 *
 * Autenticación por:
 *  - codigo (ID de colaborador)
 *  - password
 *
 * Usa tokens de Laravel Sanctum.
 */
class AuthController extends Controller
{
    /**
     * Login API.
     *
     * POST /api/v1/auth/login
     *
     * Body JSON:
     * {
     *   "codigo": "U000123",
     *   "password": "********",
     *   "device_name": "android-genaro"
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'codigo'      => 'required|string',
            'password'    => 'required|string',
            'device_name' => 'required|string|max:255',
        ]);

        /** @var User|null $user */
        $user = User::where('codigo', $request->codigo)->first();

        if (
            ! $user ||
            ! Hash::check($request->password, $user->password) ||
            ! $user->activo
        ) {
            return response()->json([
                'message' => 'Credenciales inválidas o usuario inactivo.',
            ], 401);
        }

        // Nombre del token (para identificar el dispositivo)
        $tokenName = $request->device_name;

        // Crear token de acceso (Sanctum)
        $token = $user->createToken($tokenName)->plainTextToken;

        // Registrar auditoría de login API
        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'login_api',
            'auditable_type' => null,
            'auditable_id'   => null,
            'old_values'     => null,
            'new_values'     => [
                'codigo'      => $user->codigo,
                'device_name' => $tokenName,
            ],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        $sucursal = $user->sucursal;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'              => $user->id,
                'codigo'          => $user->codigo,
                'nombre'          => $user->nombre,
                'apellido'        => $user->apellido,
                'nombre_completo' => $user->nombre_completo,
                'sucursal_id'     => $user->sucursal_id,
                'sucursal_nombre' => $sucursal?->nombre,
                'roles'           => $user->roles()->pluck('slug'),
            ],
            'server_time'      => now()->toDateTimeString(),
            'timezone_app'     => config('app.timezone'),
            'timezone_sucursal'=> $sucursal?->timezone,
        ]);
    }

    /**
     * Información del usuario autenticado.
     *
     * GET /api/v1/auth/me
     * Header: Authorization: Bearer {token}
     */
    public function me(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        $sucursal = $user->sucursal;

        return response()->json([
            'user' => [
                'id'              => $user->id,
                'codigo'          => $user->codigo,
                'nombre'          => $user->nombre,
                'apellido'        => $user->apellido,
                'nombre_completo' => $user->nombre_completo,
                'sucursal_id'     => $user->sucursal_id,
                'sucursal_nombre' => $sucursal?->nombre,
                'roles'           => $user->roles()->pluck('slug'),
            ],
            'server_time'       => now()->toDateTimeString(),
            'timezone_app'      => config('app.timezone'),
            'timezone_sucursal' => $sucursal?->timezone,
        ]);
    }

    /**
     * Logout API.
     *
     * POST /api/v1/auth/logout
     * Header: Authorization: Bearer {token}
     */
    public function logout(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        // Registrar auditoría de logout API
        AuditLog::create([
            'user_id'        => $user?->id,
            'action'         => 'logout_api',
            'auditable_type' => null,
            'auditable_id'   => null,
            'old_values'     => null,
            'new_values'     => null,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
