{{-- resources/views/reportes/reporte_ruta_lote_com.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ruta / Lote / Comedor</title>

    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 16px;
        }

        .page-title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .page-subtitle {
            font-size: 11px;
            color: #4b5563;
            margin-bottom: 12px;
        }

        .meta {
            font-size: 11px;
            margin-bottom: 10px;
        }

        .meta span {
            margin-right: 16px;
        }

        table.report {
            border-collapse: collapse;
            width: 100%;
        }

        table.report th,
        table.report td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            vertical-align: middle;
        }

        table.report thead th {
            background: #0f172a;
            color: #f9fafb;
            font-weight: 600;
            font-size: 11px;
            text-align: center;
        }

        table.report thead tr.header-areas th {
            background: #020617;
        }

        table.report tfoot th {
            background: #0f172a;
            color: #f9fafb;
            font-weight: 700;
            font-size: 11px;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            min-width: 22px;
            padding: 1px 4px;
            border-radius: 9999px;
            background: #ecfdf5;
            color: #047857;
            font-weight: 600;
            font-size: 10px;
            text-align: center;
        }

        .muted {
            color: #d1d5db;
            font-size: 10px;
        }

        .no-data {
            text-align: center;
            color: #6b7280;
            margin-top: 40px;
            font-size: 11px;
        }
    </style>
</head>
<body>
@php
    /** @var bool|null $print */
    $print = $print ?? false;

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

    $fechaTexto = \Carbon\Carbon::parse($fechaSeleccionada)->format('d/m/Y');
@endphp

<div class="page-title">
    Ruta / Lote / Comedor ({{ $tipoSeleccionado === 'recojo' ? 'Recojo' : 'Salida' }})
</div>
<div class="page-subtitle">
    Distribución de personal por Ruta / Lote / Comedor y combinación Horario x Área.
</div>

<div class="meta">
    <span><strong>Sucursal:</strong> {{ $sucursalContexto->nombre ?? 'No definida' }}</span>
    <span><strong>Fecha:</strong> {{ $fechaTexto }}</span>
    <span><strong>Tipo:</strong> {{ $tipoSeleccionado === 'recojo' ? 'Recojo' : 'Salida' }}</span>
    <span><strong>Total general:</strong> {{ $totalGeneral }} personas</span>
</div>

@if($filasCollection->isEmpty() || empty($columnasArray))
    <div class="no-data">
        No se encontraron datos para los filtros seleccionados.
    </div>
@else
    <table class="report">
        <thead>
        {{-- Fila 1: cabecera fija + bloques por horario --}}
        <tr>
            <th rowspan="2" style="text-align:left;">Ruta</th>
            <th rowspan="2" style="text-align:left;">Lote</th>
            <th rowspan="2" style="text-align:left;">Comedor</th>

            @foreach($columnasArray as $idx => $bloque)
                @php
                    $areasBloque    = $bloque['areas'] ?? [];
                    $colspanBloque  = count($areasBloque) + 1; // +1 por subtotal horario
                @endphp
                <th colspan="{{ $colspanBloque }}">
                    {{ $bloque['label'] ?? 'Horario' }}
                </th>
            @endforeach

            {{-- Total fila al FINAL --}}
            <th rowspan="2">Total fila</th>
        </tr>

        {{-- Fila 2: nombres de Áreas dentro de cada horario + TOTAL horario --}}
        <tr class="header-areas">
            @foreach($columnasArray as $bloque)
                @php
                    $areasBloque = $bloque['areas'] ?? [];
                @endphp
                @foreach($areasBloque as $area)
                    <th>
                        {{ $area['nombre'] }}
                    </th>
                @endforeach
                <th>
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
                <tr>
                    {{-- COLUMNA RUTA (solo una vez con rowspan) --}}
                    @if($esPrimeraDeRuta)
                        <td rowspan="{{ $rowspanRuta }}"
                            style="font-weight:600; white-space:nowrap; vertical-align:top;">
                            {{ $grupoRuta['ruta_codigo'] }}
                        </td>
                        @php $esPrimeraDeRuta = false; @endphp
                    @endif

                    {{-- Lote --}}
                    <td style="white-space:nowrap;">
                        {{ $fila['lote'] !== '' ? $fila['lote'] : '–' }}
                    </td>

                    {{-- Comedor --}}
                    <td style="white-space:nowrap;">
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
                            <td class="text-center">
                                @if($cantidad > 0)
                                    <span class="badge">{{ $cantidad }}</span>
                                @else
                                    <span class="muted">–</span>
                                @endif
                            </td>
                        @endforeach

                        {{-- Subtotal fila para ese horario --}}
                        <td class="text-center" style="font-weight:600; background:#f8fafc;">
                            {{ $totalFilaHorario }}
                        </td>
                    @endforeach

                    {{-- Total fila al FINAL --}}
                    <td class="text-center" style="font-weight:600; background:#e5e7eb;">
                        {{ $totalesFila[$keyFila] ?? 0 }}
                    </td>
                </tr>
            @endforeach
        @endforeach
        </tbody>

        <tfoot>
        {{-- Totales por columna (Horario x Área) --}}
        <tr>
            {{-- Primeras 3 columnas fijas: Ruta / Lote / Comedor --}}
            <th colspan="3" class="text-right">
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
                    <th class="text-center">
                        {{ $totalCol }}
                    </th>
                @endforeach

                {{-- Total por horario (sumando todas las áreas) --}}
                <th class="text-center">
                    {{ $totalesHorario[$hId] ?? 0 }}
                </th>
            @endforeach

            {{-- Columna final: Total fila (total general) --}}
            <th class="text-center">
                {{ $totalGeneral }}
            </th>
        </tr>
        </tfoot>
    </table>

    <div style="margin-top:10px; font-size:10px; color:#6b7280;">
        @if($tipoSeleccionado === 'recojo')
            Las cantidades representan el número de personas programadas para el
            <strong>RECOJO</strong> en cada combinación
            <strong>Ruta / Lote / Comedor x Horario x Área</strong>.
        @else
            Las cantidades representan el número de personas programadas para la
            <strong>SALIDA</strong> en cada combinación
            <strong>Ruta / Lote / Comedor x Horario x Área</strong>.
        @endif
    </div>
@endif

@if($print)
<script>
    window.print();
</script>
@endif
</body>
</html>
