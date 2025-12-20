@extends('layouts.app')

@section('title', 'Lugares')
@section('header', 'Lugares')

@section('content')
<div class="flex flex-col gap-4">

    @if(session('status'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-3 py-2 rounded">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">
            Lugares (agrupan varios paraderos)
        </h2>

        <a href="{{ route('catalogos.lugares.create') }}"
           class="inline-flex items-center px-4 py-2 rounded-md text-xs font-semibold
                  bg-[var(--primary)] text-white hover:bg-emerald-700 transition">
            Nuevo lugar
        </a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full text-xs">
            <thead>
                <tr class="bg-slate-900 text-white text-[11px] uppercase">
                    <th class="px-3 py-2 text-left font-semibold border-b border-slate-800">
                        Sucursal
                    </th>
                    <th class="px-3 py-2 text-left font-semibold border-b border-slate-800">
                        Nombre del lugar
                    </th>
                    <th class="px-3 py-2 text-center font-semibold border-b border-slate-800">
                        Activo
                    </th>
                    <th class="px-3 py-2 text-center font-semibold border-b border-slate-800">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($lugares as $lugar)
                    <tr class="border-b border-slate-100 hover:bg-slate-50/70">
                        <td class="px-3 py-2">
                            {{ $lugar->sucursal->nombre ?? '—' }}
                        </td>
                        <td class="px-3 py-2 font-semibold text-slate-800">
                            {{ $lugar->nombre }}
                        </td>
                        <td class="px-3 py-2 text-center">
                            @if($lugar->activo)
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold
                                             bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    ACTIVO
                                </span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold
                                             bg-rose-50 text-rose-700 border border-rose-200">
                                    INACTIVO
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('catalogos.lugares.edit', $lugar) }}"
                                   class="text-[11px] text-sky-600 hover:text-sky-800 hover:underline">
                                    Editar
                                </a>

                                <form method="POST"
                                      action="{{ route('catalogos.lugares.destroy', $lugar) }}"
                                      onsubmit="return confirm('¿Desactivar este lugar?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-[11px] text-red-600 hover:text-red-800 hover:underline">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 py-4 text-center text-[11px] text-slate-400">
                            No hay lugares registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $lugares->links() }}
    </div>
</div>
@endsection
