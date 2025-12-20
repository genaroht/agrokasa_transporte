@extends('layouts.app')

@section('title', 'Reportes')
@section('page_title', 'Reportes y resúmenes')

@section('content')
<div class="grid md:grid-cols-2 gap-4 text-sm">
    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="text-xs font-semibold text-gray-700 mb-2">Resúmenes operativos</h2>
        <ul class="list-disc list-inside text-xs space-y-1">
            <li>
                <a href="{{ route('programaciones.resumen.paradero-horario') }}"
                   class="text-[#007037] hover:underline">
                    Resumen Paradero x Horario
                </a>
            </li>
            <li>
                <a href="{{ route('programaciones.resumen.ruta-paradero') }}"
                   class="text-[#007037] hover:underline">
                    Resumen Ruta x Paradero
                </a>
            </li>
        </ul>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="text-xs font-semibold text-gray-700 mb-2">Exportaciones</h2>
        <p class="text-xs text-gray-600">
            Desde los resúmenes puedes exportar a Excel o PDF usando los botones correspondientes.
        </p>
    </div>
</div>
@endsection
