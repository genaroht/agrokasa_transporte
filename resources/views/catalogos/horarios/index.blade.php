@extends('layouts.app')

@section('title', 'Horarios')
@section('page_title', 'Catálogo de Horarios')

@section('content')
<div class="flex justify-between items-center mb-4">
    <h2 class="text-sm font-semibold text-gray-700">Horarios</h2>
    <a href="{{ route('catalogos.horarios.create') }}"
       class="inline-flex items-center rounded bg-[#007037] px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
        Nuevo horario
    </a>
</div>

@if(session('status'))
    <div class="mb-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-3 py-2 rounded">
        {{ session('status') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="min-w-full text-xs">
        <thead class="bg-gray-50 text-gray-600">
        <tr>
            <th class="px-3 py-2 text-left">Sucursal</th>
            <th class="px-3 py-2 text-left">Tipo</th>
            <th class="px-3 py-2 text-left">Nombre</th>
            <th class="px-3 py-2 text-left">Rango horario</th>
            <th class="px-3 py-2 text-center">Activo</th>
            <th class="px-3 py-2 text-right">Acciones</th>
        </tr>
        </thead>
        <tbody class="divide-y">
        @forelse($horarios as $h)
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2">
                    {{ $h->sucursal?->nombre ?? 'Global' }}
                </td>
                <td class="px-3 py-2">
                    {{ $h->tipo_label ?? '-' }}
                </td>
                <td class="px-3 py-2">{{ $h->nombre }}</td>
                <td class="px-3 py-2 font-mono">
                    {{ $h->descripcion_rango ?: $h->hora_formateada }}
                </td>
                <td class="px-3 py-2 text-center">
                    @if($h->activo)
                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px]">ACTIVO</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full bg-gray-200 text-gray-600 text-[10px]">INACTIVO</span>
                    @endif
                </td>
                <td class="px-3 py-2 text-right space-x-2">
                    <a href="{{ route('catalogos.horarios.edit', $h) }}"
                       class="text-xs text-[#007037] hover:underline">Editar</a>
                    <form action="{{ route('catalogos.horarios.destroy', $h) }}" method="POST" class="inline"
                          onsubmit="return confirm('¿Eliminar este horario?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-600 hover:underline">
                            Eliminar
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-3 py-4 text-center text-gray-500">
                    No hay horarios registrados.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $horarios->links() }}
</div>
@endsection
