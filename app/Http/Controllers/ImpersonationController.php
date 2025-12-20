<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user)
    {
        $admin = $request->user();

        if (!$admin->isAdminGeneral()) {
            abort(403, 'Solo el Administrador General puede impersonar usuarios.');
        }

        if ($admin->id === $user->id) {
            return back()->with('status', 'Ya estás usando tu propio usuario.');
        }

        // Guardamos quién impersona, si aún no existe en sesión
        if (!$request->session()->has('impersonated_by')) {
            $request->session()->put('impersonated_by', $admin->id);
        }

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'impersonate_start',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => null,
            'new_values' => ['impersonated_user_id' => $user->id],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('status', 'Ahora estás viendo el sistema como ' . $user->nombre);
    }

    public function stop(Request $request)
    {
        if (!$request->session()->has('impersonated_by')) {
            return back()->with('status', 'No estás impersonando a nadie.');
        }

        $originalId = $request->session()->pull('impersonated_by');
        $originalUser = User::find($originalId);

        if (!$originalUser) {
            Auth::logout();
            return redirect()->route('login')->withErrors('No se pudo restaurar el usuario original.');
        }

        AuditLog::create([
            'user_id' => $originalUser->id,
            'action' => 'impersonate_stop',
            'auditable_type' => User::class,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Auth::login($originalUser);

        return redirect()->route('dashboard')->with('status', 'Has vuelto a tu usuario original.');
    }
}
