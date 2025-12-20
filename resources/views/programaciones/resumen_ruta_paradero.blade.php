{{-- resources/views/programaciones/resumen_ruta_paradero.blade.php --}}

@extends('layouts.app')

@section('title', 'Resumen Ruta x Paradero')
@section('header', 'Resumen Ruta x Paradero')

@section('content')
@php
    use App\Models\Sucursal;
    use Illuminate\Support\Collection;

    /** @var \App\Models\Sucursal|null $sucursalContexto */
    $sucursalContexto = $sucursalActual
        ?? (auth()->user()->sucursal ?? null);

    // Normalizar colecciones
    /** @var \Illuminate\Support\Collection $paraderos */
    $paraderos = $paraderos instanceof Collection ? $paraderos : collect($paraderos ?? []);

    // $filas viene del controlador como: [horario_id => [ ['horario_id'=>..,'area_id'=>..], ... ]]
    $filasPorHorario = is_array($filas)
        ? collect($filas)
        : ($filas instanceof Collection ? $filas : collect($filas ?? []));

    /** @var \Illuminate\Support\Collection $horarios */
    $horarios = $horarios instanceof Collection ? $horarios : collect($horarios ?? []);
    /** @var \Illuminate\Support\Collection $areas */
    $areas    = $areas instanceof Collection    ? $areas    : collect($areas ?? []);

    // Tipo seleccionado: solo 'recojo' o 'salida'
    $tipoSeleccionado = request('tipo', $tipo ?? 'recojo');
    if (! in_array($tipoSeleccionado, ['recojo', 'salida'], true)) {
        $tipoSeleccionado = 'recojo';
    }

    // Aseguramos lugares en paraderos y agrupamos por LUGAR
    $paraderos->loadMissing('lugar');

    $paraderosPorLugar = $paraderos
        ->sortBy(function (\App\Models\Paradero $p) {
            $lugar = $p->lugar?->nombre ?? 'SIN LUGAR';
            return sprintf('%s|%s', $lugar, $p->nombre);
        })
        ->groupBy(function (\App\Models\Paradero $p) {
            return $p->lugar?->nombre ?? 'SIN LUGAR';
        });

    // Índices rápidos
    $horariosIndex = $horarios->keyBy('id');
    $areasIndex    = $areas->keyBy('id');

    // Helper etiqueta de horario
    $getEtiquetaHorario = function (int $horarioId) use ($horariosIndex): string {
        $horario = $horariosIndex->get($horarioId);
        if (!$horario) {
            return 'Horario ' . $horarioId;
        }

        if (!empty($horario->descripcion_rango)) {
            return $horario->descripcion_rango;
        }

        if (!empty($horario->nombre)) {
            return $horario->nombre;
        }

        return 'Horario ' . $horario->id;
    };

    // ============================
    // Cálculo de TOTALES en base a:
    // $matriz[horario_id][area_id][paradero_id]
    // y $filasPorHorario
    // ============================

    $totalesParadero       = []; // [paradero_id] => total (todas las filas)
    $totalesFila           = []; // [horario_id][area_id] => total fila
    $subtotalesHorario     = []; // [horario_id][paradero_id] => subtotal por horario
    $subtotalesHorarioTot  = []; // [horario_id] => subtotal total del horario
    $totalGeneral          = 0;

    foreach ($filasPorHorario as $horarioId => $filasDeHorario) {
        foreach ($filasDeHorario as $fila) {
            $hid = (int) ($fila['horario_id'] ?? $horarioId);
            $aid = (int) ($fila['area_id'] ?? 0);

            foreach ($paraderos as $paradero) {
                $pid = $paradero->id;
                $val = $matriz[$hid][$aid][$pid] ?? 0;

                if ($val <= 0) {
                    continue;
                }

                // Total por paradero (todas las filas)
                $totalesParadero[$pid] = ($totalesParadero[$pid] ?? 0) + $val;

                // Total por fila (horario + área)
                $totalesFila[$hid][$aid] = ($totalesFila[$hid][$aid] ?? 0) + $val;

                // Subtotal por horario y paradero
                $subtotalesHorario[$hid][$pid] = ($subtotalesHorario[$hid][$pid] ?? 0) + $val;

                // Subtotal total por horario
                $subtotalesHorarioTot[$hid] = ($subtotalesHorarioTot[$hid] ?? 0) + $val;

                // Total general
                $totalGeneral += $val;
            }
        }
    }
@endphp

<div class="flex flex-col gap-4">

    {{-- Barra de filtros / acciones --}}
    <div class="bg-white shadow rounded-lg p-4 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <form method="GET"
              action="{{ route('programaciones.resumen.ruta-paradero') }}"
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
                    value="{{ request('fecha', $fecha ?? now()->toDateString()) }}"
                    class="border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)] focus:border-[var(--primary)]"
                >
            </div>

            {{-- Tipo (solo Recojo o Salida) --}}
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

                <a href="{{ route('programaciones.resumen.ruta-paradero', ['tipo' => $tipoSeleccionado]) }}"
                   class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold
                          border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
                    Limpiar
                </a>
            </div>
        </form>

        {{-- Acciones de exportación --}}
        <div class="flex flex-wrap gap-2 justify-start md:justify-end">
            @php
                $query = [
                    'fecha' => request('fecha', $fecha ?? now()->toDateString()),
                    'tipo'  => $tipoSeleccionado,
                ];
            @endphp

            <a href="{{ route('reportes.resumen.ruta-paradero.excel', $query) }}"
               class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold
                      bg-green-500 text-white hover:bg-green-600 transition">
                Exportar Excel
            </a>

            <a href="{{ route('reportes.resumen.ruta-paradero.pdf', $query) }}"
               class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold
                      bg-red-500 text-white hover:bg-red-600 transition">
                Exportar PDF
            </a>
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
            {{ isset($fecha) ? \Carbon\Carbon::parse($fecha)->format('d/m/Y') : now()->format('d/m/Y') }}
        </div>
        <div>
            <span class="font-semibold text-gray-700">Tipo:</span>
            {{ $tipoSeleccionado === 'recojo' ? 'Recojo' : 'Salida' }}
        </div>
    </div>

    {{-- Matriz Horario / Responsable / Área vs Paradero (agrupado por LUGAR) --}}
    <div class="bg-white shadow rounded-lg p-4 overflow-x-auto">
        @if($filasPorHorario->isEmpty() || $paraderosPorLugar->isEmpty())
            <div class="text-center text-sm text-gray-500 py-8">
                No se encontraron datos para los filtros seleccionados.
            </div>
        @else
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700">
                    Distribución de personal por
                    <span class="font-bold">Horario / Responsable / Área</span>
                    y paraderos, agrupados por <span class="font-bold">Lugar</span>.
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
                        {{-- Fila 1: cabecera por LUGAR --}}
                        <tr class="bg-slate-900 text-white">
                            <th rowspan="2"
                                class="px-3 py-2 border-r border-slate-800 text-left text-[11px] font-semibold">
                                HORARIO
                            </th>
                            <th rowspan="2"
                                class="px-3 py-2 border-r border-slate-800 text-left text-[11px] font-semibold">
                                RESPONSABLE
                            </th>
                            <th rowspan="2"
                                class="px-3 py-2 border-r border-slate-800 text-left text-[11px] font-semibold">
                                ÁREA
                            </th>

                            @foreach($paraderosPorLugar as $nombreLugar => $grupo)
                                <th colspan="{{ $grupo->count() }}"
                                    class="px-2 py-2 border-l border-slate-800 text-center text-[11px] font-semibold">
                                    {{ $nombreLugar === 'SIN LUGAR' ? 'SIN LUGAR' : strtoupper($nombreLugar) }}
                                </th>
                            @endforeach

                            <th rowspan="2"
                                class="px-3 py-2 border-l border-slate-800 text-center text-[11px] font-semibold">
                                TOTAL fila
                            </th>
                        </tr>

                        {{-- Fila 2: nombres de PARADEROS --}}
                        <tr class="bg-slate-900 text-white text-[11px]">
                            @foreach($paraderosPorLugar as $grupo)
                                @foreach($grupo as $paradero)
                                    <th class="px-2 py-1 border-l border-slate-800 text-center whitespace-nowrap">
                                        {{ $paradero->nombre }}
                                    </th>
                                @endforeach
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($filasPorHorario as $horarioId => $filasDeHorario)
                            @php
                                $filasDeHorarioCol  = collect($filasDeHorario);
                                if ($filasDeHorarioCol->isEmpty()) {
                                    continue;
                                }

                                $hid              = (int) $horarioId;
                                $etqHorario       = $getEtiquetaHorario($hid);
                                $rowspanHorario   = $filasDeHorarioCol->count() + 1; // +1 por fila de subtotales
                                $primeraFilaHor   = true;
                            @endphp

                            @foreach($filasDeHorarioCol as $fila)
                                @php
                                    $aid  = (int) ($fila['area_id'] ?? 0);
                                    $area = $areasIndex->get($aid);

                                    $areaNombre        = $area?->nombre ?? '–';
                                    $responsableNombre = $area?->responsable ?? '–';
                                @endphp
                                <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50' }}">
                                    {{-- Columna HORARIO (rowspan) --}}
                                    @if($primeraFilaHor)
                                        <td rowspan="{{ $rowspanHorario }}"
                                            class="px-3 py-2 border-t border-slate-200 border-r text-xs font-semibold whitespace-nowrap bg-cyan-50 align-top">
                                            {{ $etqHorario }}
                                        </td>
                                        @php $primeraFilaHor = false; @endphp
                                    @endif

                                    {{-- Responsable --}}
                                    <td class="px-3 py-2 border-t border-slate-200 border-r text-xs whitespace-nowrap">
                                        {{ $responsableNombre }}
                                    </td>

                                    {{-- Área --}}
                                    <td class="px-3 py-2 border-t border-slate-200 border-r text-xs whitespace-nowrap">
                                        {{ $areaNombre }}
                                    </td>

                                    {{-- Celdas por paradero --}}
                                    @foreach($paraderosPorLugar as $grupo)
                                        @foreach($grupo as $paradero)
                                            @php
                                                $pid      = $paradero->id;
                                                $cantidad = $matriz[$hid][$aid][$pid] ?? 0;
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
                                    @endforeach

                                    {{-- Total por fila --}}
                                    <td class="px-3 py-2 border-t border-slate-200 border-l text-center font-semibold bg-slate-50">
                                        {{ $totalesFila[$hid][$aid] ?? 0 }}
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Fila de SUBTOTAL por HORARIO --}}
                            <tr class="bg-cyan-100 text-[11px] font-semibold">
                                {{-- ¡OJO! colspan="2": RESPONSABLE + ÁREA (la columna HORARIO ya está en rowspan) --}}
                                <td colspan="2"
                                    class="px-3 py-2 border-t border-slate-300 border-r text-right">
                                    Sub total {{ $etqHorario }}
                                </td>

                                @foreach($paraderosPorLugar as $grupo)
                                    @foreach($grupo as $paradero)
                                        @php
                                            $pid      = $paradero->id;
                                            $subtotal = $subtotalesHorario[$hid][$pid] ?? 0;
                                        @endphp
                                        <td class="px-2 py-2 border-t border-slate-300 text-center">
                                            {{ $subtotal > 0 ? $subtotal : '' }}
                                        </td>
                                    @endforeach
                                @endforeach

                                <td class="px-3 py-2 border-t border-slate-300 border-l text-center">
                                    {{ $subtotalesHorarioTot[$hid] ?? 0 }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="bg-slate-900 text-white">
                            <th colspan="3"
                                class="px-3 py-2 border-r border-slate-800 text-right text-[11px] font-semibold">
                                TOTAL PARADERO
                            </th>

                            @foreach($paraderosPorLugar as $grupo)
                                @foreach($grupo as $paradero)
                                    @php
                                        $pid  = $paradero->id;
                                        $tPar = $totalesParadero[$pid] ?? 0;
                                    @endphp
                                    <th class="px-2 py-2 border-l border-slate-800 text-center text-[11px] font-semibold">
                                        {{ $tPar }}
                                    </th>
                                @endforeach
                            @endforeach

                            <th class="px-3 py-2 border-l border-slate-800 text-center text-[11px] font-bold">
                                {{ $totalGeneral }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-3 text-[11px] text-gray-500">
                <p>
                    Las cantidades representan el número de personas programadas en cada combinación
                    <strong>Horario / Responsable / Área x Paradero</strong>,
                    agrupadas por <strong>Lugar</strong> (Barranca, Supe, Paramonga, etc.), para el tipo
                    seleccionado (<strong>{{ $tipoSeleccionado === 'recojo' ? 'recojo' : 'salida' }}</strong>).
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
