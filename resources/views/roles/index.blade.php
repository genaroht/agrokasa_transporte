@extends('layouts.app')

@section('title','Roles del sistema')
@section('header','Administración de roles')

@section('content')
<div class="bg-white rounded-lg shadow p-4 mb-4 text-sm">
    <form method="GET" action="{{ route('roles.index') }}"
          class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="md:col-span-2">
            <label class="block mb-1 text-xs text-gray-600">Buscar</label>
            <input type="text" name="search"
                   value="{{ request('search') }}"
                   placeholder="Nombre o slug del rol"
                   class="w-full border rounded px-2 py-1 text-sm">
        </div>

        <div>
            <label class="block mb-1 text-xs text-gray-600">Estado</label>
            <select name="estado" class="w-full border rounded px-2 py-1 text-sm">
                <option value="">Todos</option>
                <option value="activo" @selected(request('estado') === 'activo')>Activos</option>
                <option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivos</option>
            </select>
        </div>

        <div class="flex items-end justify-end gap-2">
            <a href="{{ route('roles.index') }}"
               class="px-3 py-1 border rounded text-xs">
                Limpiar
            </a>
            <button type="submit"
                    class="px-3 py-1 bg-[var(--primary)] text-white rounded text-xs">
                Filtrar
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden text-sm">
    <div class="flex justify-between items-center px-4 py-2 border-b">
        <h2 class="font-semibold text-sm">Roles registrados</h2>
        <a href="{{ route('roles.create') }}"
           class="inline-flex items-center px-3 py-1 bg-[var(--primary)] text-white rounded text-xs">
            + Nuevo rol
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Nombre</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Slug</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Descripción</th>
                    <th class="px-3 py-2 text-center font-semibold text-gray-600">Estado</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-600">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $rol)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium text-xs">{{ $rol->nombre }}</div>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <span class="font-mono text-[11px] bg-gray-100 px-1.5 py-0.5 rounded">
                                {{ $rol->slug }}
                            </span>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <span class="text-[11px] text-gray-700">
                                {{ $rol->descripcion ?? '—' }}
                            </span>
                        </td>
                        <td class="px-3 py-2 align-top text-center">
                            @if($rol->activo)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600 border border-gray-200">
                                    Inactivo
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <div class="inline-flex gap-1">
                                <a href="{{ route('roles.edit', $rol) }}"
                                   class="px-2 py-0.5 border rounded text-[11px] hover:bg-gray-50">
                                    Editar
                                </a>

                                @if($rol->activo)
                                    <form method="POST" action="{{ route('roles.destroy', $rol) }}"
                                          onsubmit="return confirm('¿Seguro que deseas desactivar este rol?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2 py-0.5 border rounded text-[11px] text-red-700 hover:bg-red-50">
                                            Desactivar
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-4 text-center text-xs text-gray-500">
                            No hay roles registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($roles->hasPages())
        <div class="px-4 py-2 border-t">
            {{ $roles->links() }}
        </div>
    @endif
</div>
@endsection
