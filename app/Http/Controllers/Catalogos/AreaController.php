<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Area::with('sucursal')->orderBy('nombre');

        if (!$user->isAdminGeneral()) {
            $query->where('sucursal_id', $user->sucursal_id);
        }

        $areas = $query->paginate(30);

        return view('catalogos.areas.index', compact('areas'));
    }

    public function create()
    {
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('catalogos.areas.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'codigo'      => ['nullable', 'string'],
            'nombre'      => ['required', 'string'],
            'activo'      => ['boolean'],
        ]);

        Area::create($data);

        return redirect()->route('catalogos.areas.index')
            ->with('status', 'Área creada correctamente.');
    }

    public function edit(Area $area)
    {
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('catalogos.areas.edit', compact('area', 'sucursales'));
    }

    public function update(Request $request, Area $area)
    {
        $data = $request->validate([
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'codigo'      => ['nullable', 'string'],
            'nombre'      => ['required', 'string'],
            'activo'      => ['boolean'],
        ]);

        $area->update($data);

        return redirect()->route('catalogos.areas.index')
            ->with('status', 'Área actualizada.');
    }

    public function destroy(Area $area)
    {
        $area->delete();

        return redirect()->route('catalogos.areas.index')
            ->with('status', 'Área eliminada.');
    }
}
