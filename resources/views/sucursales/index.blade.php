{{-- resources/views/sucursales/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Sucursales')
@section('header', 'Sucursales')

@section('content')
<div class="bg-white shadow rounded-lg p-4 text-sm">

    {{-- Barra superior --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-base font-semibold text-slate-800">
                Sucursales
            </h2>
            <p class="text-xs text-slate-500">
                Gestión de sucursales. Solo visible para el Administrador General.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <form method="GET" class="flex items-center gap-2">
                <select name="activo"
                        class="border rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--primary)]">
                    <option value="">Todas</option>
                    <option value="1" @selected(request('activo') === '1')>Solo activas</option>
                    <option value="0" @selected(request('activo') === '0')>Solo inactivas</option>
                </select>
                <button type="submit"
                        class="px-3 py-1 rounded-md text-xs bg-slate-100 text-slate-700 hover:bg-slate-200">
                    Filtrar
                </button>
            </form>

            <a href="{{ route('sucursales.create') }}"
               class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold
                      bg-[var(--primary)] text-white hover:bg-emerald-700">
                Nueva sucursal
            </a>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="border border-slate-200 rounded-lg overflow-hidden">
        <table class="min-w-full text-xs border-collapse">
            <thead>
                <tr class="bg-slate-900 text-white">
                    <th class="px-3 py-2 text-left font-semibold">Código</th>
                    <th class="px-3 py-2 text-left font-semibold">Nombre</th>
                    <th class="px-3 py-2 text-left font-semibold">Dirección</th>
                    <th class="px-3 py-2 text-left font-semibold">Zona horaria</th>
                    <th class="px-3 py-2 text-center font-semibold">Activo</th>
                    <th class="px-3 py-2 text-center font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sucursales as $sucursal)
                    <tr class="border-t border-slate-200 hover:bg-slate-50">
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ $sucursal->codigo }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $sucursal->nombre }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $sucursal->direccion ?: '—' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $sucursal->timezone }}
                        </td>
                        <td class="px-3 py-2 text-center">
                            @if($sucursal->activo)
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold
                                             bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    ACTIVO
                                </span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold
                                             bg-slate-100 text-slate-500 border border-slate-200">
                                    INACTIVO
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('sucursales.edit', ['sucursal' => $sucursal->id]) }}"
                                    class="text-[11px] text-sky-700 hover:underline">
                                    Editar
                                </a>


                                @if($sucursal->activo)
                                    <form method="POST"
                                          action="{{ route('sucursales.destroy', $sucursal) }}"
                                          onsubmit="return confirm('¿Desactivar esta sucursal?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-[11px] text-red-600 hover:underline">
                                            Desactivar
                                        </button>
                                    </form>
                                @else
                                    <form method="POST"
                                          action="{{ route('sucursales.activar', $sucursal) }}"
                                          onsubmit="return confirm('¿Activar esta sucursal nuevamente?');">
                                        @csrf
                                        <button type="submit"
                                                class="text-[11px] text-emerald-700 hover:underline">
                                            Activar
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-4 text-center text-slate-500">
                            No hay sucursales registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $sucursales->withQueryString()->links() }}
    </div>
</div>
@endsection
