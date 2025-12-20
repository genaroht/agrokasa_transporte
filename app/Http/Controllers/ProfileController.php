<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('profile.show', [
            'user' => $request->user(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'password_actual'      => ['required', 'string'],
            'password_nuevo'       => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($data['password_actual'], $user->password)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual no es correcta.']);
        }

        $user->password = Hash::make($data['password_nuevo']);
        $user->save();

        return back()->with('status', 'Contraseña actualizada correctamente.');
    }
}
