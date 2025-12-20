@extends('layouts.app')

@section('title','Usuarios')
@section('header','Usuarios del sistema')

@section('content')
<div class="bg-white rounded-lg shadow p-4 text-sm">

    <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold text-base">Listado de usuarios</h2>

        <a href="{{ route('usuarios.create') }}"
           class="px-3 py-1 bg-[var(--primary)] text-white text-xs rounded hover:bg-[var(--primary-dark)]">
            Nuevo usuario
        </a>
    </div>

    {{-- FILTROS --}}
    <form method="GET"
          action="{{ route('usuarios.index') }}"
          class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">

        {{-- Sucursal (controlada por header / AdminGeneral) --}}
        <div>
            <label class="block mb-1 text-xs text-gray-600">Sucursal</label>

            @php
                /** @var \App\Models\User|null $auth */
                $auth = $authUser ?? auth()->user();
                $esAdminGeneral = $auth && method_exists($auth, 'isAdminGeneral') ? $auth->isAdminGeneral() : false;
                $sucCont = $sucursalContextoId ?? null;
            @endphp

            @if($esAdminGeneral)
                <select name="sucursal_id" class="w-full border rounded px-2 py-1 text-xs">
                    <option value="">(Todas las sucursales)</option>
                    @foreach($sucursales as $s)
                        <option value="{{ $s->id }}"
                            @if(request('sucursal_id') == $s->id
                                || (!request()->has('sucursal_id') && $sucCont == $s->id))
                                selected
                            @endif
                        >
                            {{ $s->nombre }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-gray-400">
                    Si no eliges nada, se usa la sucursal seleccionada en el header.
                </p>
            @else
                <div class="px-2 py-1 rounded border bg-gray-50 text-xs">
                    {{ $auth?->sucursal->nombre ?? 'Sin sucursal' }}
                </div>
            @endif
        </div>

        {{-- Rol --}}
        <div>
            <label class="block mb-1 text-xs text-gray-600">Rol</label>
            <select name="rol_slug" class="w-full border rounded px-2 py-1 text-xs">
                <option value="">Todos</option>
                @foreach($roles as $r)
                    <option value="{{ $r->slug }}" @selected(request('rol_slug') == $r->slug)>
                        {{ $r->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Estado --}}
        <div>
            <label class="block mb-1 text-xs text-gray-600">Estado</label>
            <select name="estado" class="w-full border rounded px-2 py-1 text-xs">
                <option value="">Todos</option>
                <option value="1" @selected(request('estado') === '1')>Activos</option>
                <option value="0" @selected(request('estado') === '0')>Inactivos</option>
            </select>
        </div>

        {{-- Buscar --}}
        <div class="md:col-span-2">
            <label class="block mb-1 text-xs text-gray-600">Buscar</label>
            <div class="flex gap-2">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Código, nombre, apellido o correo"
                       class="flex-1 border rounded px-2 py-1 text-xs">
                <button type="submit"
                        class="px-3 py-1 border rounded bg-gray-50 hover:bg-gray-100 text-xs">
                    Filtrar
                </button>
            </div>
        </div>
    </form>

    {{-- TABLA --}}
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="px-2 py-1 text-left">Código</th>
                    <th class="px-2 py-1 text-left">Nombre</th>
                    <th class="px-2 py-1 text-left">Sucursal</th>
                    <th class="px-2 py-1 text-left">Roles</th>
                    <th class="px-2 py-1 text-left">Estado</th>
                    <th class="px-2 py-1 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-2 py-1 font-mono">{{ $u->codigo }}</td>
                        <td class="px-2 py-1">{{ $u->nombre_completo }}</td>
                        <td class="px-2 py-1">{{ $u->sucursal->nombre ?? '—' }}</td>
                        <td class="px-2 py-1">
                            @foreach($u->roles as $r)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-[10px] mr-1">
                                    {{ $r->nombre }}
                                </span>
                            @endforeach
                        </td>
                        <td class="px-2 py-1">
                            @if($u->activo)
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] border border-emerald-200">
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-[10px] border border-red-200">
                                    Inactivo
                                </span>
                            @endif
                        </td>
                        <td class="px-2 py-1 text-right space-x-1">
                            <a href="{{ route('usuarios.edit', $u) }}"
                               class="inline-flex px-2 py-0.5 border rounded text-[10px] hover:bg-gray-100">
                                Editar
                            </a>

                            @if($u->id !== auth()->id())
                                <form action="{{ route('usuarios.destroy', $u) }}"
                                      method="POST"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            onclick="return confirm('¿Seguro que deseas desactivar este usuario?')"
                                            class="inline-flex px-2 py-0.5 border rounded text-[10px] text-red-700 hover:bg-red-50">
                                        Desactivar
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-2 py-4 text-center text-gray-500">
                            No hay usuarios registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $users->links() }}
    </div>
</div>
@endsection
