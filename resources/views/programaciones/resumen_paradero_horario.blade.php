{{-- resources/views/programaciones/resumen_paradero_horario.blade.php --}}

@extends('layouts.app')

@section('title', 'Resumen Paradero x Horario')
@section('header', 'Resumen Paradero x Horario')

@section('content')
@php
    use App\Models\Sucursal;
    use Illuminate\Support\Collection;

    /** @var \App\Models\Sucursal|null $sucursalContexto */
    $sucursalContexto = $sucursalActual
        ?? (auth()->user()->sucursal ?? null);

    /** @var \Illuminate\Support\Collection $paraderos */
    $paraderos = $paraderos instanceof Collection ? $paraderos : collect($paraderos ?? []);

    /** @var \Illuminate\Support\Collection $horarios */
    $horarios = $horarios instanceof Collection ? $horarios : collect($horarios ?? []);

    // Tipo seleccionado (recojo / salida)
    $tipoSeleccionado = request('tipo', $tipo ?? 'recojo');
    if (! in_array($tipoSeleccionado, ['recojo', 'salida'], true)) {
        $tipoSeleccionado = 'recojo';
    }

    // AGRUPAR PARADEROS POR LUGAR (Barranca, Supe, etc.)
    $paraderosPorLugar = $paraderos
        ->sortBy(function (\App\Models\Paradero $p) {
            $lugar = $p->lugar?->nombre ?? 'SIN LUGAR';
            return sprintf('%s|%s', $lugar, $p->nombre);
        })
        ->groupBy(function (\App\Models\Paradero $p) {
            return $p->lugar?->nombre ?? 'SIN LUGAR';
        });

    // Horarios a mostrar
    $horariosFiltrados = $horarios->values();

    // Matriz y totales con valores por defecto
    $matriz          = $matriz          ?? [];
    $totalesParadero = $totalesParadero ?? [];
    $totalesHorario  = $totalesHorario  ?? [];
    $totalGeneral    = $totalGeneral    ?? 0;
@endphp

<div class="flex flex-col gap-4">

    {{-- Barra de filtros / acciones --}}
    <div class="bg-white shadow rounded-lg p-4 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <form method="GET"
              action="{{ route('programaciones.resumen.paradero-horario') }}"
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

                <a href="{{ route('programaciones.resumen.paradero-horario', ['tipo' => $tipoSeleccionado]) }}"
                   class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold
                          border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
                    Limpiar
                </a>
            </div>
        </form>

        {{-- Exportaciones --}}
        <div class="flex flex-wrap gap-2 justify-start md:justify-end">
            @php
                $query = [
                    'fecha' => request('fecha', $fecha ?? now()->toDateString()),
                    'tipo'  => $tipoSeleccionado,
                ];
            @endphp

            <a href="{{ route('reportes.resumen.paradero-horario.excel', $query) }}"
               class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold
                      bg-green-500 text-white hover:bg-green-600 transition">
                Exportar Excel
            </a>

            <a href="{{ route('reportes.resumen.paradero-horario.pdf', $query) }}"
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

    {{-- Matriz Horario x Paradero, agrupada por LUGAR --}}
    <div class="bg-white shadow rounded-lg p-4 overflow-x-auto">
        @if($paraderosPorLugar->isEmpty() || $horariosFiltrados->isEmpty())
            <div class="text-center text-sm text-gray-500 py-8">
                No se encontraron datos para los filtros seleccionados.
            </div>
        @else
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700">
                    @if($tipoSeleccionado === 'recojo')
                        Distribución de personal para el <span class="font-bold">RECOJO</span> por Horario y Paradero
                    @else
                        Distribución de personal para la <span class="font-bold">SALIDA</span> por Horario y Paradero
                    @endif
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

                            @foreach($paraderosPorLugar as $nombreLugar => $grupo)
                                <th colspan="{{ $grupo->count() }}"
                                    class="px-2 py-2 border-l border-slate-800 text-center text-[11px] font-semibold">
                                    {{ $nombreLugar === 'SIN LUGAR' ? 'SIN LUGAR' : strtoupper($nombreLugar) }}
                                </th>
                            @endforeach

                            <th rowspan="2"
                                class="px-3 py-2 border-l border-slate-800 text-center text-[11px] font-semibold">
                                TOTAL
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
                        @foreach($horariosFiltrados as $horario)
                            @php
                                $hid       = $horario->id;
                                $totalFila = (int) ($totalesHorario[$hid] ?? 0);
                            @endphp
                            <tr class="{{ $loop->odd ? 'bg-white' : 'bg-slate-50' }}">
                                {{-- Columna HORARIO --}}
                                <td class="px-3 py-2 border-t border-slate-200 border-r text-xs font-semibold whitespace-nowrap">
                                    @if(!empty($horario->descripcion_rango))
                                        {{ $horario->descripcion_rango }}
                                    @else
                                        {{ $horario->nombre ?? 'Horario' }}
                                    @endif
                                </td>

                                {{-- Celdas por paradero --}}
                                @foreach($paraderosPorLugar as $grupo)
                                    @foreach($grupo as $paradero)
                                        @php
                                            $pid      = $paradero->id;
                                            $cantidad = (int) ($matriz[$pid][$hid] ?? 0);
                                        @endphp
                                        <td class="px-2 py-2 border-t border-slate-200 text-center">
                                            @if($cantidad > 0)
                                                <span class="inline-flex items-center justify-center min-w-[2rem]
                                                             px-1 py-0.5 rounded-full bg-emerald-50 text-[11px]
                                                             text-emerald-700 font-semibold">
                                                    {{ $cantidad }}
                                                </span>
                                            @else
                                                &nbsp;
                                            @endif
                                        </td>
                                    @endforeach
                                @endforeach

                                {{-- Total por HORARIO --}}
                                <td class="px-3 py-2 border-t border-slate-200 border-l text-center font-semibold bg-slate-50">
                                    {{ $totalFila > 0 ? $totalFila : '' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="bg-slate-900 text-white">
                            <th class="px-3 py-2 border-r border-slate-800 text-right text-[11px] font-semibold">
                                TOTAL PARADERO
                            </th>

                            @foreach($paraderosPorLugar as $grupo)
                                @foreach($grupo as $paradero)
                                    @php
                                        $pid  = $paradero->id;
                                        $tPar = (int) ($totalesParadero[$pid] ?? 0);
                                    @endphp
                                    <th class="px-2 py-2 border-l border-slate-800 text-center text-[11px] font-semibold">
                                        {{ $tPar > 0 ? $tPar : '' }}
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
                @if($tipoSeleccionado === 'recojo')
                    <p>
                        Las cantidades representan el número de personas programadas para el
                        <strong>RECOJO</strong> en cada combinación
                        <strong>Horario x Paradero</strong>,
                        agrupadas por <strong>Lugar</strong> (Barranca, Supe, Paramonga, etc.).
                    </p>
                @else
                    <p>
                        Las cantidades representan el número de personas programadas para la
                        <strong>SALIDA</strong> en cada combinación
                        <strong>Horario x Paradero</strong>,
                        agrupadas por <strong>Lugar</strong> (Barranca, Supe, Paramonga, etc.).
                    </p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
