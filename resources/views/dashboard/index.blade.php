@extends('layouts.app')

@section('title', 'Dashboard de Transporte')
@section('header', 'Dashboard de Transporte')

@section('content')
@php
    use Carbon\Carbon;

    // Fecha seleccionada: viene del controlador como $fecha
    $fechaSeleccionada = $fecha ?? request('fecha', now()->toDateString());
    $fechaCarbon       = Carbon::parse($fechaSeleccionada);
    $fechaTexto        = $fechaCarbon->format('d/m/Y');

    // Métricas por defecto para evitar errores si no vienen del controlador
    $metricasSalida = $metricasSalida ?? [];
    $metricasRecojo = $metricasRecojo ?? [];

    $salidaProgramaciones = (int) ($metricasSalida['programaciones'] ?? 0);
    $salidaTotalPersonas  = (int) ($metricasSalida['total_personas'] ?? 0);
    $salidaPorArea        = $metricasSalida['por_area'] ?? [];

    $recojoProgramaciones = (int) ($metricasRecojo['programaciones'] ?? 0);
    $recojoTotalPersonas  = (int) ($metricasRecojo['total_personas'] ?? 0);
    $recojoPorArea        = $metricasRecojo['por_area'] ?? [];

    // Datos para el gráfico comparativo (misma fecha)
    $totalSalidaGrafico = $salidaTotalPersonas;
    $totalRecojoGrafico = $recojoTotalPersonas;
@endphp

<div class="flex flex-col gap-6">

    {{-- Filtros / Contexto --}}
    <div class="bg-white shadow rounded-lg p-4 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <form method="GET"
              action="{{ url()->current() }}"
              class="flex flex-wrap gap-4 items-end">

            {{-- Fecha del dashboard --}}
            <div>
                <label for="fecha" class="block text-xs font-semibold text-gray-600 mb-1">
                    Fecha del dashboard
                </label>
                <input
                    type="date"
                    id="fecha"
                    name="fecha"
                    value="{{ $fechaSeleccionada }}"
                    class="border rounded-md px-3 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-[var(--primary)]
                           focus:border-[var(--primary)]"
                >
                <p class="mt-1 text-[11px] text-gray-500">
                    Por defecto se muestra el día de hoy. Puedes cambiarla para ver otro día.
                </p>
            </div>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold
                           bg-[var(--primary)] text-white hover:bg-emerald-700 transition"
                >
                    Actualizar dashboard
                </button>

                <a href="{{ url()->current() }}"
                   class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold
                          border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
                    Hoy
                </a>
            </div>
        </form>

        <div class="text-xs text-gray-600 space-y-1">
            <p>
                <span class="font-semibold text-gray-700">Fecha seleccionada:</span>
                {{ $fechaTexto }}
            </p>
            <p>
                <span class="font-semibold text-gray-700">Comparación:</span>
                salida y recojo correspondientes al día
                <strong>{{ $fechaTexto }}</strong>.
            </p>
        </div>
    </div>

    {{-- Secciones: Salida y Recojo --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- Sección SALIDA --}}
        <section class="bg-white shadow rounded-lg p-4 flex flex-col gap-4">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">
                        Salida – Programación del día {{ $fechaTexto }}
                    </h2>
                    <p class="text-[11px] text-gray-500">
                        Programaciones y personal que <strong>salen</strong> en el día seleccionado.
                    </p>
                </div>
            </div>

            {{-- KPIs principales --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {{-- Programaciones del día (Salida) --}}
                <div class="border rounded-lg px-4 py-3 bg-slate-50">
                    <p class="text-[11px] text-gray-500 mb-1">
                        Programaciones de salida del día
                    </p>
                    <p class="text-2xl font-semibold text-slate-800">
                        {{ $salidaProgramaciones }}
                    </p>
                    <p class="text-[11px] text-gray-400">
                        Cantidad de registros de programación tipo <strong>salida</strong>.
                    </p>
                </div>

                {{-- Total personal programado hoy (Salida) --}}
                <div class="border rounded-lg px-4 py-3 bg-slate-50">
                    <p class="text-[11px] text-gray-500 mb-1">
                        Total personal programado (Salida)
                    </p>
                    <p class="text-2xl font-semibold text-emerald-700">
                        {{ $salidaTotalPersonas }}
                    </p>
                    <p class="text-[11px] text-gray-400">
                        Personas asociadas a programaciones de salida para {{ $fechaTexto }}.
                    </p>
                </div>
            </div>

            {{-- Tarjetas por área (Salida) --}}
            <div>
                <h3 class="text-[12px] font-semibold text-gray-700 mb-2">
                    Personas programadas por área – Salida
                </h3>

                @if(empty($salidaPorArea) || count($salidaPorArea) === 0)
                    <p class="text-[11px] text-gray-400">
                        No hay datos de salida por área para esta fecha.
                    </p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($salidaPorArea as $item)
                            @php
                                if (is_array($item)) {
                                    $areaNombre = $item['nombre'] ?? $item['area'] ?? 'Área';
                                    $areaTotal  = (int) ($item['total'] ?? 0);
                                } else {
                                    $areaNombre = $item->nombre ?? $item->area ?? 'Área';
                                    $areaTotal  = (int) ($item->total ?? 0);
                                }
                            @endphp
                            <div class="border rounded-lg px-3 py-2 bg-white">
                                <p class="text-[11px] text-gray-500 truncate mb-1">
                                    {{ $areaNombre }}
                                </p>
                                <p class="text-xl font-semibold text-slate-800">
                                    {{ $areaTotal }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- Sección RECOJO --}}
        <section class="bg-white shadow rounded-lg p-4 flex flex-col gap-4">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">
                        Recojo – Programación del día {{ $fechaTexto }}
                    </h2>
                    <p class="text-[11px] text-gray-500">
                        Programaciones de <strong>recojo</strong> correspondientes al mismo día del dashboard.
                    </p>
                </div>
            </div>

            {{-- KPIs principales --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {{-- Programaciones del día (Recojo) --}}
                <div class="border rounded-lg px-4 py-3 bg-slate-50">
                    <p class="text-[11px] text-gray-500 mb-1">
                        Programaciones de recojo del día
                    </p>
                    <p class="text-2xl font-semibold text-slate-800">
                        {{ $recojoProgramaciones }}
                    </p>
                    <p class="text-[11px] text-gray-400">
                        Registros tipo <strong>recojo</strong> en {{ $fechaTexto }}.
                    </p>
                </div>

                {{-- Total personal programado (Recojo) --}}
                <div class="border rounded-lg px-4 py-3 bg-slate-50">
                    <p class="text-[11px] text-gray-500 mb-1">
                        Total personal programado (Recojo)
                    </p>
                    <p class="text-2xl font-semibold text-blue-700">
                        {{ $recojoTotalPersonas }}
                    </p>
                    <p class="text-[11px] text-gray-400">
                        Personas programadas para el recojo en {{ $fechaTexto }}.
                    </p>
                </div>
            </div>

            {{-- Tarjetas por área (Recojo) --}}
            <div>
                <h3 class="text-[12px] font-semibold text-gray-700 mb-2">
                    Personas programadas por área – Recojo
                </h3>

                @if(empty($recojoPorArea) || count($recojoPorArea) === 0)
                    <p class="text-[11px] text-gray-400">
                        No hay datos de recojo por área para esta fecha.
                    </p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($recojoPorArea as $item)
                            @php
                                if (is_array($item)) {
                                    $areaNombre = $item['nombre'] ?? $item['area'] ?? 'Área';
                                    $areaTotal  = (int) ($item['total'] ?? 0);
                                } else {
                                    $areaNombre = $item->nombre ?? $item->area ?? 'Área';
                                    $areaTotal  = (int) ($item->total ?? 0);
                                }
                            @endphp
                            <div class="border rounded-lg px-3 py-2 bg-white">
                                <p class="text-[11px] text-gray-500 truncate mb-1">
                                    {{ $areaNombre }}
                                </p>
                                <p class="text-xl font-semibold text-slate-800">
                                    {{ $areaTotal }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>

    {{-- Gráfico comparativo Recojo vs Salida --}}
    <section class="bg-white shadow rounded-lg p-4">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-800">
                    Comparativo Recojo vs Salida – {{ $fechaTexto }}
                </h2>
                <p class="text-[11px] text-gray-500">
                    Personas programadas de <strong>salida</strong> y <strong>recojo</strong> en la misma fecha.
                </p>
            </div>
        </div>

        <div class="h-72">
            <canvas id="chartRecojoSalida"></canvas>
        </div>
    </section>
</div>

{{-- Scripts del gráfico --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const ctx = document.getElementById('chartRecojoSalida');

        if (!ctx) return;

        const totalRecojo = {{ $totalRecojoGrafico }};
        const totalSalida = {{ $totalSalidaGrafico }};

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    'Recojo',
                    'Salida'
                ],
                datasets: [{
                    label: 'Personas programadas ({{ $fechaTexto }})',
                    data: [totalRecojo, totalSalida],
                    backgroundColor: ['#3b82f6', '#10b981'],
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const value = context.parsed.y || 0;
                                return value + ' personas';
                            }
                        }
                    }
                }
            }
        });
    })();
</script>
@endsection
