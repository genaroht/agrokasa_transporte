<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function index()
    {
        return view('config.index', [
            'appName'       => config('app.name'),
            'appTimezone'   => config('app.timezone'),
            'sucursales'    => Sucursal::orderBy('nombre')->get(),
        ]);
    }

    /**
     * Stub simple: aquí podrías guardar en una tabla "settings".
     * Por ahora solo devuelve un mensaje para que no falle nada.
     */
    public function update(Request $request)
    {
        // Aquí podrías implementar persistencia en BD (settings)
        return back()->with('status', 'Configuración global pendiente de implementación en BD.');
    }
}
