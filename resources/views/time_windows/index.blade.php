@extends('layouts.app')

@section('title', 'Ventanas de tiempo')
@section('header', 'Ventanas de tiempo')

@section('content')
<div class="bg-white rounded-lg shadow p-4 text-sm">

    {{-- CABECERA --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="font-semibold text-base">Gestión de ventanas de tiempo</h2>
            <p class="text-[11px] text-gray-500">
                Configura franjas horarias por sucursal, área, usuario o rol, y actívalas / desactívalas según necesidad.
            </p>
        </div>

        <a href="{{ route('timewindows.create') }}"
           class="px-3 py-1 bg-[var(--primary)] text-white text-xs rounded hover:bg-[var(--primary-dark)]">
            Nueva ventana
        </a>
    </div>

    {{-- FILTROS --}}
    <form method="GET"
          action="{{ route('timewindows.index') }}"
          class="grid grid-cols-1 md:grid-cols-8 gap-3 mb-4">

        {{-- Sucursal --}}
        <div>
            <label class="block mb-1 text-xs font-semibold text-gray-600">Sucursal</label>
            <select name="sucursal_id" class="w-full border rounded px-2 py-1 text-xs">
                <option value="">Todas</option>
                @foreach($sucursales as $s)
                    <option value="{{ $s->id }}"
                        @selected(request('sucursal_id', $selectedSucursalId ?? null) == $s->id)>
                        {{ $s->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Tipo (salida / recojo) --}}
        <div>
            <label class="block mb-1 text-xs font-semibold text-gray-600">Tipo</label>
            <select name="tipo" class="w-full border rounded px-2 py-1 text-xs">
                <option value="">Todos</option>
                <option value="salida" @selected(request('tipo') === 'salida')>Salida</option>
                <option value="recojo" @selected(request('tipo') === 'recojo')>Recojo</option>
            </select>
        </div>

        {{-- Área --}}
        <div>
            <label class="block mb-1 text-xs font-semibold text-gray-600">Área</label>
            <select name="area_id" class="w-full border rounded px-2 py-1 text-xs">
                <option value="">Todas</option>
                @foreach($areas as $a)
                    <option value="{{ $a->id }}" @selected(request('area_id') == $a->id)>
                        {{ $a->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Horario --}}
        <div>
            <label class="block mb-1 text-xs font-semibold text-gray-600">Horario</label>
            <select name="horario_id" class="w-full border rounded px-2 py-1 text-xs">
                <option value="">Todos</option>
                @foreach($horarios as $h)
                    <option value="{{ $h->id }}" @selected(request('horario_id') == $h->id)>
                        {{ $h->hora }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Usuario responsable --}}
        <div>
            <label class="block mb-1 text-xs font-semibold text-gray-600">Usuario</label>
            <select name="user_id" class="w-full border rounded px-2 py-1 text-xs">
                <option value="">Todos</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>
                        {{ $u->nombre_completo ?? $u->codigo }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Rol --}}
        <div>
            <label class="block mb-1 text-xs font-semibold text-gray-600">Rol</label>
            <select name="role_id" class="w-full border rounded px-2 py-1 text-xs">
                <option value="">Todos</option>
                @foreach($roles as $r)
                    <option value="{{ $r->id }}" @selected(request('role_id') == $r->id)>
                        {{ $r->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Fecha (opcional) --}}
        <div>
            <label class="block mb-1 text-xs font-semibold text-gray-600">Fecha (referencia)</label>
            <input
                type="date"
                name="fecha"
                value="{{ request('fecha') }}"
                class="w-full border rounded px-2 py-1 text-xs"
            >
        </div>

        {{-- Estado --}}
        <div>
            <label class="block mb-1 text-xs font-semibold text-gray-600">Estado</label>
            <select name="estado" class="w-full border rounded px-2 py-1 text-xs">
                <option value="">Todos</option>
                <option value="1" @selected(request('estado') === '1')>Activas</option>
                <option value="0" @selected(request('estado') === '0')>Inactivas</option>
            </select>
        </div>

        {{-- Botones filtros --}}
        <div class="md:col-span-8 flex justify-end gap-2 mt-1">
            <button type="submit"
                    class="px-3 py-1.5 text-xs rounded bg-[var(--primary)] text-white hover:bg-[var(--primary-dark)]">
                Aplicar filtros
            </button>
            <a href="{{ route('timewindows.index') }}"
               class="px-3 py-1.5 text-xs rounded border border-gray-300 text-gray-600 hover:bg-gray-50">
                Limpiar
            </a>
        </div>
    </form>

    {{-- TABLA --}}
    <div class="overflow-x-auto">
        <table class="min-w-full text-xs border border-gray-200 rounded">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="px-2 py-1 text-left">ID</th>
                    <th class="px-2 py-1 text-left">Sucursal</th>
                    <th class="px-2 py-1 text-left">Tipo</th>
                    <th class="px-2 py-1 text-left">Área</th>
                    <th class="px-2 py-1 text-left">Horario</th>
                    <th class="px-2 py-1 text-left">Fecha / Rango</th>
                    <th class="px-2 py-1 text-left">Usuario / Rol</th>
                    <th class="px-2 py-1 text-left">Descripción</th>
                    <th class="px-2 py-1 text-left">Estado</th>
                    <th class="px-2 py-1 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $sucursalesById = $sucursales->keyBy('id');
                    $areasById      = $areas->keyBy('id');
                    $horariosById   = $horarios->keyBy('id');
                    $usersById      = $users->keyBy('id');
                    $rolesById      = $roles->keyBy('id');
                @endphp

                @forelse($timeWindows as $tw)
                    <tr class="border-b hover:bg-gray-50">
                        {{-- ID --}}
                        <td class="px-2 py-1 font-mono">
                            {{ $tw->id }}
                        </td>

                        {{-- Sucursal --}}
                        <td class="px-2 py-1">
                            @php
                                $suc = isset($sucursalesById[$tw->sucursal_id ?? null])
                                    ? $sucursalesById[$tw->sucursal_id]->nombre
                                    : ($tw->sucursal->nombre ?? '—');
                            @endphp
                            {{ $suc }}
                        </td>

                        {{-- Tipo --}}
                        <td class="px-2 py-1 capitalize">
                            {{ $tw->tipo ?? 'salida' }}
                        </td>

                        {{-- Área --}}
                        <td class="px-2 py-1">
                            @php
                                $ar = isset($areasById[$tw->area_id ?? null])
                                    ? $areasById[$tw->area_id]->nombre
                                    : ($tw->area->nombre ?? '—');
                            @endphp
                            {{ $ar }}
                        </td>

                        {{-- Horario --}}
                        <td class="px-2 py-1">
                            @php
                                $hor = isset($horariosById[$tw->horario_id ?? null])
                                    ? $horariosById[$tw->horario_id]->hora
                                    : ($tw->horario->hora ?? '—');
                            @endphp
                            {{ $hor }}
                        </td>

                        {{-- Fecha + rango horario --}}
                        <td class="px-2 py-1">
                            @php
                                $fechaStr = $tw->fecha
                                    ? \Carbon\Carbon::parse($tw->fecha)->format('d/m/Y')
                                    : '—';
                            @endphp
                            <div>{{ $fechaStr }}</div>
                            <div class="text-[10px] text-gray-500">
                                @if($tw->hora_inicio || $tw->hora_fin)
                                    {{ $tw->hora_inicio ?? '??:??' }} - {{ $tw->hora_fin ?? '??:??' }}
                                @else
                                    Sin rango
                                @endif
                            </div>
                        </td>

                        {{-- Usuario / Rol --}}
                        <td class="px-2 py-1">
                            @php
                                $usr = isset($usersById[$tw->user_id ?? null])
                                    ? ($usersById[$tw->user_id]->nombre_completo ?? $usersById[$tw->user_id]->codigo)
                                    : ($tw->user->nombre_completo ?? $tw->user->codigo ?? null);

                                $rol = isset($rolesById[$tw->role_id ?? null])
                                    ? $rolesById[$tw->role_id]->nombre
                                    : ($tw->role->nombre ?? null);
                            @endphp

                            @if($usr)
                                <div>{{ $usr }}</div>
                            @endif
                            @if($rol)
                                <div class="text-[10px] text-gray-500">Rol: {{ $rol }}</div>
                            @endif
                            @if(!$usr && !$rol)
                                —
                            @endif
                        </td>

                        {{-- Descripción --}}
                        <td class="px-2 py-1">
                            {{ $tw->descripcion ?? '—' }}
                        </td>

                        {{-- Estado --}}
                        <td class="px-2 py-1">
                            @if($tw->esta_activa)
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] border border-emerald-200">
                                    Activa
                                </span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-[10px] border border-red-200">
                                    Inactiva
                                </span>
                            @endif
                            <div class="text-[10px] text-gray-400 capitalize">
                                {{ $tw->estado ?? '—' }}
                            </div>
                        </td>

                        {{-- Acciones: Editar + Activar/Desactivar --}}
                        <td class="px-2 py-1 text-right space-x-1 whitespace-nowrap">
                            {{-- Editar --}}
                            <a href="{{ route('timewindows.edit', $tw) }}"
                               class="inline-flex px-2 py-0.5 border rounded text-[10px] hover:bg-gray-100">
                                Editar
                            </a>

                            {{-- Toggle activo usando ruta específica --}}
                            <form action="{{ route('timewindows.toggle', $tw) }}"
                                  method="POST"
                                  class="inline">
                                @csrf
                                <button type="submit"
                                        class="inline-flex px-2 py-0.5 border rounded text-[10px]
                                               {{ $tw->esta_activa ? 'text-red-700 hover:bg-red-50' : 'text-emerald-700 hover:bg-emerald-50' }}">
                                    {{ $tw->esta_activa ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-2 py-4 text-center text-gray-500">
                            No hay ventanas de tiempo configuradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINACIÓN --}}
    <div class="mt-3">
        {{ $timeWindows->links() }}
    </div>
</div>
@endsection
