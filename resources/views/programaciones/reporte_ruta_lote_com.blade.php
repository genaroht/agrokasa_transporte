{{-- resources/views/programaciones/reporte_ruta_lote_com.blade.php --}}

@extends('layouts.app')

@section('title', 'Ruta / Lote / Comedor')
@section('header', 'Ruta / Lote / Comedor')

@section('content')
@php
    use App\Models\Sucursal;

    /** @var \App\Models\Sucursal|null $sucursalContexto */
    $sucursalContexto = $sucursalActual
        ?? (auth()->user()->sucursal ?? null);

    // Tipo seleccionado (igual que en Resumen Paradero x Horario)
    $tipoSeleccionado = request('tipo', $tipo ?? 'recojo');
    if (! in_array($tipoSeleccionado, ['recojo', 'salida'], true)) {
        $tipoSeleccionado = 'recojo';
    }

    $fechaSeleccionada = request('fecha', $fecha ?? now()->toDateString());

    // Aseguramos colecciones
    $filasCollection    = $filas instanceof \Illuminate\Support\Collection ? $filas : collect($filas ?? []);
    $horariosCollection = $horarios instanceof \Illuminate\Support\Collection ? $horarios : collect($horarios ?? []);
    $areasCollection    = $areas instanceof \Illuminate\Support\Collection ? $areas : collect($areas ?? []);
    $columnasArray      = $columnas ?? [];

    // Totales por horario (sumando todas las áreas de cada horario)
    $totalesHorario = [];
    foreach ($totalesColumna ?? [] as $hId => $porArea) {
        $totalesHorario[$hId] = array_sum($porArea ?? []);
    }

    $totalGeneral = $totalGeneral ?? 0;

    // ==============================
    // AGRUPAR FILAS POR RUTA
    // ==============================
    // Para que la columna "Ruta" salga UNA sola vez con rowspan,
    // agrupamos las filas por ruta_id manteniendo la key $keyFila
    // (que usamos en la matriz $matriz y en $totalesFila).
    $filasPorRuta = [];
    foreach ($filasCollection as $keyFila => $fila) {
        $rutaId = $fila['ruta_id'] ?? 0;

        if (! isset($filasPorRuta[$rutaId])) {
            $filasPorRuta[$rutaId] = [
                'ruta_codigo' => $fila['ruta_codigo'] ?? 'SIN RUTA',
                'filas'       => [],
            ];
        }

        $filasPorRuta[$rutaId]['filas'][$keyFila] = $fila;
    }

    // Query para exportar con los mismos filtros
    $queryExport = [
        'fecha' => $fechaSeleccionada,
        'tipo'  => $tipoSeleccionado,
    ];
@endphp

<div class="flex flex-col gap-4">

    {{-- Barra de filtros / acciones (misma que Resumen Paradero x Horario) --}}
    <div class="bg-white shadow rounded-lg p-4 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <form method="GET"
              action="{{ route('programaciones.reporte_ruta_lote_com') }}"
              class="flex flex-wrap gap-4 items-end">

            {{-- Fecha --}}
            <div>
                <label for="fecha" class="block text-xs font-semibold text-gray-600 mb-1">
                    Fecha
                </label>
                <input
                    type="date"
                    id="fecha"
                    name="fecha"
                    value="{{ $fechaSeleccionada }}"
                    class="border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)] focus:border-[var(--primary)]"
                >
            </div>

            {{-- Tipo (Recojo / Salida) --}}
            <div>
                <label for="tipo" class="block text-xs font-semibold text-gray-600 mb-1">
                    Tipo de programación
                </label>
                <select
                    id="tipo"
                    name="tipo"
                    class="border rounded-md px-3 py-2 text-sm min-w-[160px] focus:outline-none focus:ring-2 focus:ring-[var(--primary)] focus:border-[var(--primary)]"
                >
                    <option value="recojo" @selected($tipoSeleccionado === 'recojo')}>
                        Recojo
                    </option>
                    <option value="salida" @selected($tipoSeleccionado === 'salida')}>
                        Salida
                    </option>
                </select>
            </div>

            {{-- Botones --}}
            <div class="flex gap-2">
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold
                           bg-[var(--primary)] text-white hover:bg-emerald-700 transition"
                >
                    Aplicar filtros
                </button>

                <a href="{{ route('programaciones.reporte_ruta_lote_com', ['tipo' => $tipoSeleccionado]) }}"
                   class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold
                          border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
                    Limpiar
                </a>
            </div>
        </form>

        {{-- Lado derecho: info rápida + exportaciones --}}
        <div class="flex flex-col items-end text-xs text-gray-600 gap-2">
            <div>
                <span class="font-semibold text-gray-700">Total general:</span>
                <span class="font-semibold text-[var(--primary)]">
                    {{ $totalGeneral }}
                </span>
                personas
            </div>

            <div class="flex flex-wrap gap-2 justify-end">
                <a href="{{ route('reportes.rutas-lotes.excel', $queryExport) }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-md text-[11px] font-semibold
                          bg-green-500 text-white hover:bg-green-600 transition">
                    Exportar Excel
                </a>

                <a href="{{ route('reportes.rutas-lotes.pdf', $queryExport) }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-md text-[11px] font-semibold
                          bg-red-500 text-white hover:bg-red-600 transition">
                    Exportar PDF
                </a>
            </div>

            <div class="text-[11px] text-gray-400">
                Ruta / Lote / Comedor – matriz Horario x Área.
            </div>
        </div>
    </div>

    {{-- Info de contexto --}}
    <div class="bg-white shadow rounded-lg p-4 text-xs text-gray-600 flex flex-wrap gap-4">
        <div>
            <span class="font-semibold text-gray-700">Sucursal:</span>
            {{ $sucursalContexto->nombre ?? 'No definida' }}
        </div>
        <div>
            <span class="font-semibold text-gray-700">Fecha:</span>
            {{ \Carbon\Carbon::parse($fechaSeleccionada)->format('d/m/Y') }}
        </div>
        <div>
            <span class="font-semibold text-gray-700">Tipo:</span>
            {{ $tipoSeleccionado === 'recojo' ? 'Recojo' : 'Salida' }}
        </div>
    </div>

    {{-- Matriz Ruta / Lote / Comedor vs Horario x Área --}}
    <div class="bg-white shadow rounded-lg p-4 overflow-x-auto">
        @if($filasCollection->isEmpty() || empty($columnasArray))
            <div class="text-center text-sm text-gray-500 py-8">
                No se encontraron datos para los filtros seleccionados.
            </div>
        @else
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700">
                    Distribución de personal por
                    <span class="font-bold">Ruta / Lote / Comedor</span>
                    y combinación <span class="font-bold">Horario x Área</span>
                    ({{ $tipoSeleccionado === 'recojo' ? 'RECOJO' : 'SALIDA' }}).
                </h2>
                <span class="text-xs text-gray-500">
                    Total general:
                    <span class="font-semibold text-[var(--primary)]">
                        {{ $totalGeneral }}
                    </span>
                    personas
                </span>
            </div>

            <div class="border border-slate-200 rounded-lg overflow-x-auto">
                <table class="min-w-full text-xs border-collapse">
                    <thead>
                        {{-- Fila 1: cabecera fija + bloques por horario --}}
                        <tr class="bg-slate-900 text-white">
                            <th rowspan="2"
                                class="px-3 py-2 border-r border-slate-800 text-left text-[11px] font-semibold">
                                Ruta
                            </th>
                            <th rowspan="2"
                                class="px-3 py-2 border-r border-slate-800 text-left text-[11px] font-semibold">
                                Lote
                            </th>
                            <th rowspan="2"
                                class="px-3 py-2 border-r border-slate-800 text-left text-[11px] font-semibold">
                                Comedor
                            </th>

                            @foreach($columnasArray as $idx => $bloque)
                                @php
                                    $areasBloque    = $bloque['areas'] ?? [];
                                    $colspanBloque  = count($areasBloque) + 1; // +1 por subtotal horario
                                    // Colores alternados tipo Excel
                                    $claseColor = $idx % 3 === 0
                                        ? 'bg-slate-900'
                                        : ($idx % 3 === 1 ? 'bg-slate-800' : 'bg-slate-700');
                                @endphp
                                <th colspan="{{ $colspanBloque }}"
                                    class="px-2 py-2 border-l border-slate-800 text-center text-[11px] font-semibold {{ $claseColor }}">
                                    {{ $bloque['label'] ?? 'Horario' }}
                                </th>
                            @endforeach

                            {{-- Total fila al FINAL --}}
                            <th rowspan="2"
                                class="px-3 py-2 border-l border-slate-800 text-center text-[11px] font-semibold">
                                Total fila
                            </th>
                        </tr>

                        {{-- Fila 2: nombres de Áreas dentro de cada horario + TOTAL horario --}}
                        <tr class="bg-slate-900 text-white text-[11px]">
                            @foreach($columnasArray as $bloque)
                                @php
                                    $areasBloque = $bloque['areas'] ?? [];
                                @endphp
                                @foreach($areasBloque as $area)
                                    <th class="px-2 py-1 border-l border-slate-800 text-center whitespace-nowrap">
                                        {{ $area['nombre'] }}
                                    </th>
                                @endforeach
                                <th class="px-2 py-1 border-l border-slate-800 text-center whitespace-nowrap">
                                    TOTAL horario
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            // Para efecto "zebra", contamos filas totales (no por ruta)
                            $rowIndex = 0;
                        @endphp

                        @foreach($filasPorRuta as $rutaId => $grupoRuta)
                            @php
                                $rowspanRuta     = count($grupoRuta['filas']);
                                $esPrimeraDeRuta = true;
                            @endphp

                            @foreach($grupoRuta['filas'] as $keyFila => $fila)
                                @php $rowIndex++; @endphp
                                <tr class="{{ $rowIndex % 2 === 1 ? 'bg-white' : 'bg-slate-50' }}">

                                    {{-- ====== COLUMNA RUTA (solo una vez con rowspan) ====== --}}
                                    @if($esPrimeraDeRuta)
                                        <td rowspan="{{ $rowspanRuta }}"
                                            class="px-3 py-2 border-t border-slate-200 border-r text-xs font-semibold whitespace-nowrap align-top">
                                            {{ $grupoRuta['ruta_codigo'] }}
                                        </td>
                                        @php $esPrimeraDeRuta = false; @endphp
                                    @endif

                                    {{-- Lote --}}
                                    <td class="px-3 py-2 border-t border-slate-200 border-r text-xs whitespace-nowrap">
                                        {{ $fila['lote'] !== '' ? $fila['lote'] : '–' }}
                                    </td>

                                    {{-- Comedor --}}
                                    <td class="px-3 py-2 border-t border-slate-200 border-r text-xs whitespace-nowrap">
                                        {{ $fila['comedor'] !== '' ? $fila['comedor'] : '–' }}
                                    </td>

                                    {{-- Bloques Horario x Área --}}
                                    @foreach($columnasArray as $bloque)
                                        @php
                                            $hId              = $bloque['horario_id'];
                                            $areasBloque      = $bloque['areas'] ?? [];
                                            $totalFilaHorario = 0;
                                        @endphp

                                        @foreach($areasBloque as $area)
                                            @php
                                                $areaId   = $area['id'];
                                                $cantidad = $matriz[$keyFila][$hId][$areaId] ?? 0;
                                                $totalFilaHorario += $cantidad;
                                            @endphp
                                            <td class="px-2 py-2 border-t border-slate-200 text-center">
                                                @if($cantidad > 0)
                                                    <span class="inline-flex items-center justify-center min-w-[2rem]
                                                                 px-1 py-0.5 rounded-full bg-emerald-50 text-[11px]
                                                                 text-emerald-700 font-semibold">
                                                        {{ $cantidad }}
                                                    </span>
                                                @else
                                                    <span class="text-[11px] text-slate-300">–</span>
                                                @endif
                                            </td>
                                        @endforeach

                                        {{-- Subtotal fila para ese horario --}}
                                        <td class="px-2 py-2 border-t border-slate-200 text-center font-semibold bg-slate-50">
                                            {{ $totalFilaHorario }}
                                        </td>
                                    @endforeach

                                    {{-- Total fila al FINAL --}}
                                    <td class="px-3 py-2 border-t border-slate-200 border-l text-center font-semibold bg-slate-50">
                                        {{ $totalesFila[$keyFila] ?? 0 }}
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>

                    <tfoot>
                        {{-- Totales por columna (Horario x Área) --}}
                        <tr class="bg-slate-900 text-white">
                            {{-- Primeras 3 columnas fijas: Ruta / Lote / Comedor --}}
                            <th colspan="3"
                                class="px-3 py-2 border-r border-slate-800 text-right text-[11px] font-semibold">
                                TOTAL COLUMNA
                            </th>

                            @foreach($columnasArray as $bloque)
                                @php
                                    $hId         = $bloque['horario_id'];
                                    $areasBloque = $bloque['areas'] ?? [];
                                @endphp

                                @foreach($areasBloque as $area)
                                    @php
                                        $areaId   = $area['id'];
                                        $totalCol = $totalesColumna[$hId][$areaId] ?? 0;
                                    @endphp
                                    <th class="px-2 py-2 border-l border-slate-800 text-center text-[11px] font-semibold">
                                        {{ $totalCol }}
                                    </th>
                                @endforeach

                                {{-- Total por horario (sumando todas las áreas) --}}
                                <th class="px-2 py-2 border-l border-slate-800 text-center text-[11px] font-bold">
                                    {{ $totalesHorario[$hId] ?? 0 }}
                                </th>
                            @endforeach

                            {{-- Columna final: Total fila (total general) --}}
                            <th class="px-3 py-2 border-l border-slate-800 text-center text-[11px] font-bold">
                                {{ $totalGeneral }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-3 text-[11px] text-gray-500">
                @if($tipoSeleccionado === 'recojo')
                    <p>
                        Las cantidades representan el número de personas programadas para el
                        <strong>RECOJO</strong> en cada combinación
                        <strong>Ruta / Lote / Comedor x Horario x Área</strong>.
                    </p>
                @else
                    <p>
                        Las cantidades representan el número de personas programadas para la
                        <strong>SALIDA</strong> en cada combinación
                        <strong>Ruta / Lote / Comedor x Horario x Área</strong>.
                    </p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
