<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class RutaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Ruta::with('sucursal')->orderBy('codigo');

        if (!$user->isAdminGeneral()) {
            $query->where('sucursal_id', $user->sucursal_id);
        }

        $rutas = $query->paginate(50);

        return view('catalogos.rutas.index', compact('rutas'));
    }

    public function create()
    {
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('catalogos.rutas.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'codigo'      => ['required', 'string'],
            'nombre'      => ['required', 'string'],
            'activo'      => ['boolean'],
        ]);

        Ruta::create($data);

        return redirect()->route('catalogos.rutas.index')
            ->with('status', 'Ruta creada correctamente.');
    }

    public function edit(Ruta $ruta)
    {
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('catalogos.rutas.edit', compact('ruta', 'sucursales'));
    }

    public function update(Request $request, Ruta $ruta)
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'codigo'      => ['required', 'string'],
            'nombre'      => ['required', 'string'],
            'activo'      => ['boolean'],
        ]);

        $ruta->update($data);

        return redirect()->route('catalogos.rutas.index')
            ->with('status', 'Ruta actualizada.');
    }

    public function destroy(Ruta $ruta)
    {
        $ruta->delete();

        return redirect()->route('catalogos.rutas.index')
            ->with('status', 'Ruta eliminada.');
    }
}
