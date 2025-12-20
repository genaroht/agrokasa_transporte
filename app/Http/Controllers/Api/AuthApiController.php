<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    /**
     * POST /api/v1/auth/login
     *
     * Login para apps (Desktop / Android / iOS).
     * Devuelve un token de Sanctum y datos básicos del usuario.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'codigo'   => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $deviceName = $credentials['device_name'] ?? $request->userAgent() ?? 'device';

        /** @var User|null $user */
        $user = User::where('codigo', $credentials['codigo'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'codigo' => ['Código o contraseña incorrectos.'],
            ]);
        }

        if (property_exists($user, 'activo') && ! $user->activo) {
            throw ValidationException::withMessages([
                'codigo' => ['El usuario está inactivo. Contacte a TI.'],
            ]);
        }

        // Opcional: actualizar último login
        if ($user->isFillable('last_login_at')) {
            $user->forceFill([
                'last_login_at' => now(),
            ])->save();
        }

        // Crear token de Sanctum
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id'        => $user->id,
                'codigo'    => $user->codigo,
                'nombre'    => $user->nombre,
                'apellido'  => $user->apellido,
                'email'     => $user->email,
                'sucursal_id' => $user->sucursal_id,
                'sucursal'    => $user->sucursal?->nombre,
                'roles'       => $user->roles->pluck('slug'),
                // si tienes método rolePermissions():
                'permisos'    => method_exists($user, 'rolePermissions')
                    ? $user->rolePermissions()->pluck('slug')
                    : [],
            ],
        ]);
    }

    /**
     * GET /api/v1/auth/me
     *
     * Devuelve datos del usuario autenticado (token Sanctum).
     */
    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'id'        => $user->id,
            'codigo'    => $user->codigo,
            'nombre'    => $user->nombre,
            'apellido'  => $user->apellido,
            'email'     => $user->email,
            'sucursal_id' => $user->sucursal_id,
            'sucursal'    => $user->sucursal?->nombre,
            'roles'       => $user->roles->pluck('slug'),
            'permisos'    => method_exists($user, 'rolePermissions')
                ? $user->rolePermissions()->pluck('slug')
                : [],
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Revoca el token actual de Sanctum.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
