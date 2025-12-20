<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Muestra el formulario de login (código + contraseña).
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Procesa el login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'codigo'   => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'codigo.required'   => 'Ingrese su código de usuario.',
            'password.required' => 'Ingrese su contraseña.',
        ]);

        // Intento de login usando "codigo" en vez de "email"
        if (Auth::attempt([
            'codigo' => $credentials['codigo'],
            'password' => $credentials['password'],
            'activo' => true,
        ])) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['codigo' => 'Credenciales inválidas o usuario inactivo.'])
            ->withInput($request->only('codigo'));
    }

    /**
     * Cierra sesión.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
