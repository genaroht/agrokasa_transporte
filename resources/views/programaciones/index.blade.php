{{-- resources/views/programaciones/index.blade.php --}}
@extends('layouts.app')

@section('title','Programaciones')
@section('header','Programaciones por fecha / horario / área')

@section('content')
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();

    // Solo estos roles podrán ver y usar el botón ELIMINAR
    $puedeEliminar = $user && (
        $user->hasRole('admin_general') ||
        $user->hasRole('admin')
    );
@endphp

@if(session('status'))
    <div class="mb-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-3 py-2 rounded">
        {{ session('status') }}
    </div>
@endif

{{-- HEADER + BOTONES DE PROGRAMACIÓN --}}
<div class="bg-white dark:bg-slate-950 rounded-lg shadow p-3 md:p-4 text-sm mb-3">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <div class="font-semibold text-sm">Programaciones</div>
            <div class="text-[11px] text-gray-500 dark:text-slate-400">
                Gestión de programación diaria de transporte de personal
            </div>
        </div>

        {{-- Botones para crear programación (manual y rápida) --}}
        @if($user && $user->hasPermissionTo('gestionar_programaciones'))
            <div class="flex flex-wrap gap-2">
                {{-- Programación rápida (captura por paradero) --}}
                <a href="{{ route('programaciones.captura-rapida') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-emerald-600 text-white text-xs rounded shadow hover:bg-emerald-700">
                    Programación rápida
                </a>

                {{-- Programación manual (matriz completa) --}}
                <a href="{{ route('programaciones.create') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-[var(--primary)] text-white text-xs rounded shadow hover:bg-[var(--primary-dark)]">
                    Programación manual
                </a>
            </div>
        @endif
    </div>

    {{-- FILTROS --}}
    <form method="GET" action="{{ route('programaciones.index') }}"
          class="mt-3 grid grid-cols-2 md:grid-cols-7 gap-2 md:gap-3 items-end">
        <div>
            <label class="block text-[11px] text-gray-500 dark:text-slate-400 mb-1">Fecha desde</label>
            <input type="date" name="fecha_desde"
                   value="{{ request('fecha_desde', now()->toDateString()) }}"
                   class="w-full border border-gray-300 dark:border-slate-700 rounded px-2 py-1 text-xs bg-white dark:bg-slate-900">
        </div>

        <div>
            <label class="block text-[11px] text-gray-500 dark:text-slate-400 mb-1">Fecha hasta</label>
            <input type="date" name="fecha_hasta"
                   value="{{ request('fecha_hasta', now()->toDateString()) }}"
                   class="w-full border border-gray-300 dark:border-slate-700 rounded px-2 py-1 text-xs bg-white dark:bg-slate-900">
        </div>

        <div>
            <label class="block text-[11px] text-gray-500 dark:text-slate-400 mb-1">Área</label>
            <select name="area_id"
                    class="w-full border border-gray-300 dark:border-slate-700 rounded px-2 py-1 text-xs bg-white dark:bg-slate-900">
                <option value="">Todas</option>
                @foreach($areas as $a)
                    <option value="{{ $a->id }}" @selected(request('area_id') == $a->id)>
                        {{ $a->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-[11px] text-gray-500 dark:text-slate-400 mb-1">Horario</label>
            <select name="horario_id"
                    class="w-full border border-gray-300 dark:border-slate-700 rounded px-2 py-1 text-xs bg-white dark:bg-slate-900">
                <option value="">Todos</option>
                @foreach($horarios as $h)
                    <option value="{{ $h->id }}" @selected(request('horario_id') == $h->id)>
                        {{ $h->nombre }} ({{ \Carbon\Carbon::parse($h->hora)->format('H:i') }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Tipo: recojo / salida --}}
        <div>
            <label class="block text-[11px] text-gray-500 dark:text-slate-400 mb-1">Tipo</label>
            <select name="tipo"
                    class="w-full border border-gray-300 dark:border-slate-700 rounded px-2 py-1 text-xs bg-white dark:bg-slate-900">
                <option value="">Todos</option>
                <option value="recojo" @selected(request('tipo') === 'recojo')>Recojo</option>
                <option value="salida" @selected(request('tipo') === 'salida')>Salida</option>
            </select>
        </div>

        <div>
            <label class="block text-[11px] text-gray-500 dark:text-slate-400 mb-1">Estado</label>
            <select name="estado"
                    class="w-full border border-gray-300 dark:border-slate-700 rounded px-2 py-1 text-xs bg-white dark:bg-slate-900">
                <option value="">Todos</option>
                <option value="borrador" @selected(request('estado') === 'borrador')>Borrador</option>
                <option value="confirmado" @selected(request('estado') === 'confirmado')>Confirmado</option>
                <option value="cerrado" @selected(request('estado') === 'cerrado')>Cerrado</option>
            </select>
        </div>

        <div class="flex justify-end gap-2">
            <button class="inline-flex items-center gap-1 px-3 py-1.5 rounded bg-gray-100 dark:bg-slate-800 text-xs">
                <svg viewBox="0 0 24 24" class="w-3 h-3">
                    <path fill="currentColor" d="M3 4h18v2H3V4zm4 4h10v2H7V8zm-4 4h18v2H3v-2zm4 4h10v2H7v-2z"/>
                </svg>
                <span>Filtrar</span>
            </button>
        </div>
    </form>
</div>

{{-- ESCRITORIO: TABLA --}}
<div class="hidden md:block bg-white dark:bg-slate-950 rounded-lg shadow p-4 text-xs overflow-x-auto">
    <table class="min-w-full border border-gray-200 dark:border-slate-800">
        <thead>
        <tr class="bg-gray-100 dark:bg-slate-900">
            <th class="border px-2 py-1 text-left">Fecha</th>
            <th class="border px-2 py-1 text-left">Sucursal</th>
            <th class="border px-2 py-1 text-left">Área</th>
            <th class="border px-2 py-1 text-left">Horario</th>
            <th class="border px-2 py-1 text-center">Estado</th>
            <th class="border px-2 py-1 text-right">Total personas</th>
            <th class="border px-2 py-1 text-center">Acciones</th>
        </tr>
        </thead>
        <tbody>
        @forelse($programaciones as $p)
            <tr class="hover:bg-gray-50 dark:hover:bg-slate-900/60 transition">
                <td class="border px-2 py-1">
                    {{ $p->fecha->format('d/m/Y') }}
                </td>
                <td class="border px-2 py-1">
                    {{ $p->sucursal->nombre }}
                </td>
                <td class="border px-2 py-1">
                    {{ $p->area->nombre }}
                </td>
                <td class="border px-2 py-1">
                    {{ $p->horario->nombre }} ({{ \Carbon\Carbon::parse($p->horario->hora)->format('H:i') }})
                </td>
                <td class="border px-2 py-1 text-center">
                    @php
                        $estadoColor = [
                            'borrador'   => 'bg-gray-100 text-gray-700',
                            'confirmado' => 'bg-amber-100 text-amber-800',
                            'cerrado'    => 'bg-emerald-100 text-emerald-800',
                        ][$p->estado] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $estadoColor }}">
                        {{ ucfirst($p->estado) }}
                    </span>
                </td>
                <td class="border px-2 py-1 text-right font-semibold">
                    {{ $p->total_personas }}
                </td>
                <td class="border px-2 py-1 text-center">
                    <div class="inline-flex items-center gap-1">
                        @if($user && $user->hasPermissionTo('gestionar_programaciones'))
                            <a href="{{ route('programaciones.edit', $p) }}"
                               class="px-2 py-0.5 rounded bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-[11px]">
                                Editar
                            </a>
                        @endif

                        @if($puedeEliminar)
                            <form method="POST"
                                  action="{{ route('programaciones.destroy', $p) }}"
                                  onsubmit="return confirm('¿Seguro que deseas eliminar esta programación?');"
                                  class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-2 py-0.5 rounded bg-red-600 text-white text-[11px] hover:bg-red-700">
                                    Eliminar
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="border px-2 py-2 text-center text-gray-500 dark:text-slate-400">
                    No hay programaciones para los filtros seleccionados.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-3">
        {{ $programaciones->links() }}
    </div>
</div>

{{-- MÓVIL: TARJETAS --}}
<div class="md:hidden space-y-2">
    @forelse($programaciones as $p)
        <div class="bg-white dark:bg-slate-950 rounded-lg shadow-sm border border-gray-100 dark:border-slate-800 p-3 text-xs">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <div class="font-semibold text-[13px]">
                        {{ $p->area->nombre }}
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-slate-400">
                        {{ $p->sucursal->nombre }}
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-[11px] text-gray-500 dark:text-slate-400">
                        {{ $p->fecha->format('d/m/Y') }}
                    </div>
                    <div class="text-[11px] font-medium">
                        {{ $p->horario->nombre }}
                        ({{ \Carbon\Carbon::parse($p->horario->hora)->format('H:i') }})
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mb-2">
                <div class="flex items-center gap-1 text-[11px]">
                    <span class="text-gray-500 dark:text-slate-400">Personas:</span>
                    <span class="font-semibold">{{ $p->total_personas }}</span>
                </div>
                <div>
                    @php
                        $estadoColor = [
                            'borrador'   => 'bg-gray-100 text-gray-700',
                            'confirmado' => 'bg-amber-100 text-amber-800',
                            'cerrado'    => 'bg-emerald-100 text-emerald-800',
                        ][$p->estado] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $estadoColor }}">
                        {{ ucfirst($p->estado) }}
                    </span>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                @if($user && $user->hasPermissionTo('gestionar_programaciones'))
                    <a href="{{ route('programaciones.edit', $p) }}"
                       class="px-2 py-1 rounded bg-[var(--primary)] text-white text-[11px]">
                        Editar
                    </a>
                @endif

                @if($puedeEliminar)
                    <form method="POST"
                          action="{{ route('programaciones.destroy', $p) }}"
                          onsubmit="return confirm('¿Seguro que deseas eliminar esta programación?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-2 py-1 rounded bg-red-600 text-white text-[11px] hover:bg-red-700">
                            Eliminar
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-950 rounded-lg shadow-sm border border-gray-100 dark:border-slate-800 p-3 text-xs text-center text-gray-500 dark:text-slate-400">
            No hay programaciones para los filtros seleccionados.
        </div>
    @endforelse

    <div class="mt-2">
        {{ $programaciones->links() }}
    </div>
</div>
@endsection
