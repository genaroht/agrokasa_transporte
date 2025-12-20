{{-- resources/views/catalogos/areas/index.blade.php --}}
@extends('layouts.app')

@section('title','Áreas de trabajo')
@section('header','Catálogo de áreas de trabajo')

@section('content')
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $esAdminGral = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
@endphp

<div class="bg-white rounded-lg shadow p-4 mb-4 text-sm">
    <form method="GET"
          action="{{ route('catalogos.areas.index') }}"
          class="grid grid-cols-1 md:grid-cols-4 gap-3">

        {{-- Sucursal actual --}}
        <div>
            <label class="block mb-1 text-xs font-semibold text-gray-600">
                Sucursal actual
            </label>
            <div class="w-full border rounded px-2 py-1 bg-gray-50 text-gray-700 text-xs">
                {{ $sucursalActual->nombre ?? 'Sin sucursal' }}
                @if($esAdminGral)
                    <span class="block text-[10px] text-gray-400">
                        (para cambiarla usa el selector del header)
                    </span>
                @endif
            </div>
        </div>

        {{-- Filtro por estado --}}
        <div>
            <label class="block mb-1 text-xs font-semibold text-gray-600">
                Estado
            </label>
            <select name="activo" class="w-full border rounded px-2 py-1 text-sm">
                <option value="">Todos</option>
                <option value="1" @selected(request('activo') === '1')>Activos</option>
                <option value="0" @selected(request('activo') === '0')>Inactivos</option>
            </select>
        </div>

        {{-- Botón filtrar --}}
        <div class="flex items-end">
            <button class="bg-[var(--primary)] text-white px-3 py-1 rounded w-full">
                Filtrar
            </button>
        </div>

        {{-- Botón nueva área --}}
        <div class="flex items-end">
            <a href="{{ route('catalogos.areas.create') }}"
               class="bg-emerald-500 text-white px-3 py-1 rounded w-full text-center">
                + Nueva área
            </a>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow p-4 text-xs md:text-sm overflow-x-auto">
    <table class="min-w-full border">
        <thead>
        <tr class="bg-gray-100">
            <th class="border px-2 py-1">Nombre</th>
            <th class="border px-2 py-1">Código</th>
            <th class="border px-2 py-1">Sucursal</th>
            <th class="border px-2 py-1">Responsable</th>
            <th class="border px-2 py-1">Estado</th>
            <th class="border px-2 py-1 text-center">Acciones</th>
        </tr>
        </thead>
        <tbody>
        @forelse($areas as $area)
            <tr>
                <td class="border px-2 py-1">{{ $area->nombre }}</td>
                <td class="border px-2 py-1">{{ $area->codigo }}</td>
                <td class="border px-2 py-1">
                    {{ optional($area->sucursal)->nombre ?? 'Global' }}
                </td>
                <td class="border px-2 py-1">
                    {{ $area->responsable ?: 'Sin responsable' }}
                </td>
                <td class="border px-2 py-1">
                    @if($area->activo)
                        <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs">Activo</span>
                    @else
                        <span class="px-2 py-0.5 bg-gray-200 text-gray-700 rounded text-xs">Inactivo</span>
                    @endif
                </td>
                <td class="border px-2 py-1 text-center">
                    <a href="{{ route('catalogos.areas.edit', $area) }}"
                       class="inline-block text-xs bg-gray-200 px-2 py-1 rounded hover:bg-gray-300">
                        Editar
                    </a>
                    @if($area->activo)
                        <form action="{{ route('catalogos.areas.destroy', $area) }}"
                              method="POST"
                              class="inline"
                              onsubmit="return confirm('¿Desactivar esta área?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-block text-xs bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200">
                                Desactivar
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="border px-2 py-2 text-center text-gray-500">
                    No hay áreas registradas.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-3">
        {{ $areas->appends(request()->query())->links() }}
    </div>
</div>
@endsection
