@extends('layouts.app')

@section('title', 'Estado de ventanas de tiempo')
@section('page_title', 'Mis ventanas de tiempo')

@section('content')
<div class="bg-white rounded-lg shadow p-4 text-xs">
    <div class="mb-3 text-gray-600">
        <div>Hora actual servidor: <span class="font-semibold">{{ $now->format('d/m/Y H:i') }}</span></div>
        <div>Usuario: <span class="font-semibold">{{ auth()->user()->nombre_completo }}</span></div>
    </div>

    @if($ventanas->isEmpty())
        <div class="text-gray-500">
            No hay ventanas de tiempo asignadas a usted (o reglas generales) para su sucursal.
        </div>
    @else
        <table class="min-w-full border text-[11px]">
            <thead class="bg-gray-50 text-gray-600">
            <tr>
                <th class="border px-2 py-1 text-left">Fecha</th>
                <th class="border px-2 py-1 text-left">Hora inicio</th>
                <th class="border px-2 py-1 text-left">Hora fin</th>
                <th class="border px-2 py-1 text-left">Estado</th>
                <th class="border px-2 py-1 text-left">Reabierto hasta</th>
            </tr>
            </thead>
            <tbody>
            @foreach($ventanas as $v)
                <tr>
                    <td class="border px-2 py-1">{{ $v['fecha'] ?? '-' }}</td>
                    <td class="border px-2 py-1 font-mono">{{ $v['hora_inicio'] }}</td>
                    <td class="border px-2 py-1 font-mono">{{ $v['hora_fin'] }}</td>
                    <td class="border px-2 py-1">
                        {{ strtoupper($v['estado']) }}
                    </td>
                    <td class="border px-2 py-1">
                        {{ $v['reabierto_hasta'] ?? '-' }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
