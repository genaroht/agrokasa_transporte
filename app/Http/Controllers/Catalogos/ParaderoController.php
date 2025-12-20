<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Paradero;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class ParaderoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Paradero::with('sucursal')->orderBy('nombre');

        if (!$user->isAdminGeneral()) {
            $query->where('sucursal_id', $user->sucursal_id);
        }

        $paraderos = $query->paginate(50);

        return view('catalogos.paraderos.index', compact('paraderos'));
    }

    public function create()
    {
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('catalogos.paraderos.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'nombre'      => ['required', 'string'],
            'codigo'      => ['nullable', 'string'],
            'direccion'   => ['nullable', 'string'],
            'activo'      => ['boolean'],
        ]);

        Paradero::create($data);

        return redirect()->route('catalogos.paraderos.index')
            ->with('status', 'Paradero creado correctamente.');
    }

    public function edit(Paradero $paradero)
    {
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('catalogos.paraderos.edit', compact('paradero', 'sucursales'));
    }

    public function update(Request $request, Paradero $paradero)
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'nombre'      => ['required', 'string'],
            'codigo'      => ['nullable', 'string'],
            'direccion'   => ['nullable', 'string'],
            'activo'      => ['boolean'],
        ]);

        $paradero->update($data);

        return redirect()->route('catalogos.paraderos.index')
            ->with('status', 'Paradero actualizado.');
    }

    public function destroy(Paradero $paradero)
    {
        $paradero->delete();

        return redirect()->route('catalogos.paraderos.index')
            ->with('status', 'Paradero eliminado.');
    }
}
