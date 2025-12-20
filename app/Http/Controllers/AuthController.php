<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador de autenticación:
 * - Login WEB (panel Blade)
 * - Login API (Sanctum)
 * - Logout (web y API)
 * - Cambio de contraseña
 * - Info básica de recuperación de contraseña
 */
class AuthController extends Controller
{
    /**
     * Determina a qué ruta redirigir a un usuario según su rol.
     * Aquí decides el "home" de cada perfil.
     */
    protected function redirectPathFor(User $user): string
    {
        // Si es Admin General -> va al dashboard
        if (method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral()) {
            return route('dashboard');
        }

        // Para el resto de usuarios, por ejemplo, que vayan a Programaciones
        // Cambia esta ruta si quieres otro "home" para usuarios normales.
        return route('programaciones.index');
    }

    // ====== LOGIN WEB ======

    /**
     * Muestra el formulario de login web.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            $redirectTo = $this->redirectPathFor($user);

            return redirect()->to($redirectTo);
        }

        return view('auth.login');
    }

    /**
     * Procesa el login web (sesión).
     */
    public function loginWeb(Request $request)
    {
        $request->validate([
            'codigo'   => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            'codigo'   => $request->codigo,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();

            // Si el usuario está inactivo, cerramos sesión inmediatamente
            if (! $user->activo) {
                Auth::logout();

                return back()
                    ->withErrors([
                        'codigo' => 'El usuario está inactivo. Contacte al administrador.',
                    ])
                    ->withInput();
            }

            // Registrar auditoría de login web
            AuditLog::create([
                'user_id'        => $user->id,
                'action'         => 'login_web',
                'auditable_type' => null,
                'auditable_id'   => null,
                'old_values'     => null,
                'new_values'     => ['codigo' => $request->codigo],
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);

            // 🔁 Redirección según rol
            $redirectTo = $this->redirectPathFor($user);

            return redirect()->intended($redirectTo);
        }

        return back()
            ->withErrors([
                'codigo' => 'Credenciales inválidas.',
            ])
            ->withInput();
    }

    /**
     * Cierra sesión web.
     */
    public function logoutWeb(Request $request)
    {
        // Si estabas usando impersonación, limpiar marca
        if ($request->session()->has('impersonated_by')) {
            $request->session()->forget('impersonated_by');
        }

        AuditLog::create([
            'user_id'        => optional($request->user())->id,
            'action'         => 'logout_web',
            'auditable_type' => null,
            'auditable_id'   => null,
            'old_values'     => null,
            'new_values'     => null,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ====== LOGIN API (SANCTUM) ======

    /**
     * Login para apps (Windows / Android / iOS) usando Sanctum.
     * Recibe: codigo, password, device_name
     * Devuelve: token + datos básicos de usuario.
     */
    public function loginApi(Request $request)
    {
        $request->validate([
            'codigo'      => 'required|string',
            'password'    => 'required|string',
            'device_name' => 'required|string',
        ]);

        /** @var User|null $user */
        $user = User::where('codigo', $request->codigo)->first();

        if (
            ! $user ||
            ! Hash::check($request->password, $user->password) ||
            ! $user->activo
        ) {
            return response()->json(
                ['message' => 'Credenciales inválidas o usuario inactivo'],
                401
            );
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'login_api',
            'auditable_type' => null,
            'auditable_id'   => null,
            'old_values'     => null,
            'new_values'     => ['device_name' => $request->device_name],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'              => $user->id,
                'codigo'          => $user->codigo,
                'nombre_completo' => $user->nombre_completo,
                'sucursal_id'     => $user->sucursal_id,
                // Con Spatie: lista de nombres de roles
                'roles'           => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Devuelve la información del usuario autenticado (API).
     */
    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => [
                'id'              => $user->id,
                'codigo'          => $user->codigo,
                'nombre_completo' => $user->nombre_completo,
                'sucursal_id'     => $user->sucursal_id,
                'roles'           => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Cierra sesión API (revoca el token actual).
     */
    public function logoutApi(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

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

        return response()->json(['message' => 'Sesión cerrada']);
    }

    // ====== CAMBIO DE CONTRASEÑA (USUARIO LOGUEADO) ======

    /**
     * Muestra la vista de cambio de contraseña.
     */
    public function showChangePasswordForm()
    {
        return view('profile.password');
    }

    /**
     * Cambia la contraseña del usuario autenticado.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'password_actual' => 'required|string',
            'password_nueva'  => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'confirmed',
            ],
        ], [
            'password_nueva.regex' =>
                'La nueva contraseña debe tener al menos una mayúscula, una minúscula y un número.',
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($request->password_actual, $user->password)) {
            return back()->withErrors([
                'password_actual' => 'La contraseña actual no es correcta.',
            ]);
        }

        $old = ['password' => '***'];

        $user->password = Hash::make($request->password_nueva);
        $user->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'password_changed',
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
            'old_values'     => $old,
            'new_values'     => ['password' => '***'],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return back()->with('status', 'Contraseña actualizada correctamente.');
    }

    // ====== INFO BÁSICA PARA "OLVIDÉ MI CONTRASEÑA" ======

    /**
     * Muestra una vista informando cómo recuperar la contraseña
     * (por ahora flujo manual vía admin / RRHH).
     */
    public function showForgotPasswordInfo()
    {
        return view('auth.forgot_password_info');
    }
}
