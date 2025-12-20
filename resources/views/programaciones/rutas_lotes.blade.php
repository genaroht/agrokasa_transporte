@extends('layouts.app')

@section('title','Rutas / Lotes / Comedor')
@section('header','Gestión de Lotes y Comedores por Ruta')

@section('content')
<div class="bg-white rounded-lg shadow p-4 text-sm mb-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
            <div class="text-xs text-gray-500">Sucursal</div>
            <div class="font-semibold">{{ $programacion->sucursal->nombre }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Fecha</div>
            <div class="font-semibold">{{ $programacion->fecha->format('d/m/Y') }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Área</div>
            <div class="font-semibold">{{ $programacion->area->nombre }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500">Horario</div>
            <div class="font-semibold">
                {{ $programacion->horario->nombre }}
                ({{ \Carbon\Carbon::parse($programacion->horario->hora)->format('H:i') }})
            </div>
        </div>
    </div>

    <div class="mt-3 flex gap-2">
        <a href="{{ route('programaciones.edit', $programacion) }}" class="px-3 py-1 border rounded text-xs">
            ← Volver a matriz Paradero x Ruta
        </a>
        <a href="{{ route('programaciones.index') }}" class="px-3 py-1 border rounded text-xs">
            Volver al listado
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-4 text-[11px] md:text-xs overflow-x-auto">
    @if($programacion->estaCerrada())
        <div class="mb-2 text-red-600 text-xs">
            Esta programación está cerrada. Los Lotes/Comedores se muestran en modo lectura.
        </div>
    @endif

    <form method="POST" action="{{ route('programaciones.rutas_lotes.update', $programacion) }}">
        @csrf
        @method('PUT')

        <table class="min-w-full border">
            <thead>
            <tr class="bg-gray-100">
                <th class="border px-2 py-1">Ruta</th>
                <th class="border px-2 py-1">Paradero</th>
                <th class="border px-2 py-1">Lote</th>
                <th class="border px-2 py-1">Comedor</th>
                <th class="border px-2 py-1 text-right">Personas</th>
            </tr>
            </thead>
            <tbody>
            @php
                $totalGeneral = 0;
            @endphp
            @forelse($detalles as $det)
                @php $totalGeneral += $det->personas; @endphp
                <tr>
                    <td class="border px-2 py-1">
                        @if($det->ruta)
                            {{ $det->ruta->codigo }} - {{ $det->ruta->nombre }}
                        @else
                            <span class="text-gray-500">Sin ruta</span>
                        @endif
                    </td>
                    <td class="border px-2 py-1">
                        {{ optional($det->paradero)->nombre ?? '-' }}
                    </td>
                    <td class="border px-2 py-1">
                        @if($programacion->estaCerrada())
                            {{ $det->lote ?? '-' }}
                        @else
                            <input type="text"
                                   name="detalles[{{ $det->id }}][lote]"
                                   value="{{ old('detalles.'.$det->id.'.lote', $det->lote) }}"
                                   class="w-28 border rounded px-1 py-0.5 text-[11px]"
                                   maxlength="50">
                        @endif
                    </td>
                    <td class="border px-2 py-1">
                        @if($programacion->estaCerrada())
                            {{ $det->comedor ?? '-' }}
                        @else
                            <input type="text"
                                   name="detalles[{{ $det->id }}][comedor]"
                                   value="{{ old('detalles.'.$det->id.'.comedor', $det->comedor) }}"
                                   class="w-28 border rounded px-1 py-0.5 text-[11px]"
                                   maxlength="50">
                        @endif
                    </td>
                    <td class="border px-2 py-1 text-right">
                        {{ $det->personas }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="border px-2 py-2 text-center text-gray-500">
                        No hay detalles registrados en esta programación.
                    </td>
                </tr>
            @endforelse
            </tbody>
            <tfoot>
            <tr class="bg-gray-100">
                <th colspan="4" class="border px-2 py-1 text-right">Total general</th>
                <th class="border px-2 py-1 text-right font-bold">
                    {{ $totalGeneral }}
                </th>
            </tr>
            </tfoot>
        </table>

        @if(!$programacion->estaCerrada())
            <div class="mt-3 flex justify-end">
                <button type="submit" class="px-4 py-1 bg-[var(--primary)] text-white rounded text-sm">
                    Guardar Lotes y Comedores
                </button>
            </div>
        @endif
    </form>
</div>
@endsection
