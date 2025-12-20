{{-- resources/views/reportes/resumen_ruta_paradero_export.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resumen Ruta x Paradero</title>

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

        table.report thead tr.header-paraderos th {
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
    // Cálculo de TOTALES
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

    // Fecha a mostrar
    $fechaBase   = request('fecha', $fecha ?? now()->toDateString());
    $fechaTexto  = \Carbon\Carbon::parse($fechaBase)->format('d/m/Y');
@endphp

<div class="page-title">
    Resumen Ruta x Paradero ({{ $tipoSeleccionado === 'recojo' ? 'Recojo' : 'Salida' }})
</div>
<div class="page-subtitle">
    Distribución de personal por Horario / Responsable / Área y paraderos, agrupados por Lugar.
</div>

<div class="meta">
    <span><strong>Sucursal:</strong> {{ $sucursalContexto->nombre ?? 'No definida' }}</span>
    <span><strong>Fecha:</strong> {{ $fechaTexto }}</span>
    <span><strong>Tipo:</strong> {{ $tipoSeleccionado === 'recojo' ? 'Recojo' : 'Salida' }}</span>
    <span><strong>Total general:</strong> {{ $totalGeneral }} personas</span>
</div>

@if($filasPorHorario->isEmpty() || $paraderosPorLugar->isEmpty())
    <div class="no-data">
        No se encontraron datos para los filtros seleccionados.
    </div>
@else
    <table class="report">
        <thead>
        {{-- Fila 1: cabecera por LUGAR --}}
        <tr>
            <th rowspan="2" style="text-align:left;">HORARIO</th>
            <th rowspan="2" style="text-align:left;">RESPONSABLE</th>
            <th rowspan="2" style="text-align:left;">ÁREA</th>

            @foreach($paraderosPorLugar as $nombreLugar => $grupo)
                <th colspan="{{ $grupo->count() }}">
                    {{ $nombreLugar === 'SIN LUGAR' ? 'SIN LUGAR' : strtoupper($nombreLugar) }}
                </th>
            @endforeach

            <th rowspan="2">TOTAL fila</th>
        </tr>

        {{-- Fila 2: nombres de PARADEROS --}}
        <tr class="header-paraderos">
            @foreach($paraderosPorLugar as $grupo)
                @foreach($grupo as $paradero)
                    <th>
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
                <tr>
                    {{-- Columna HORARIO (rowspan) --}}
                    @if($primeraFilaHor)
                        <td rowspan="{{ $rowspanHorario }}"
                            style="font-weight:600; white-space:nowrap; background:#e0f2fe; vertical-align:top;">
                            {{ $etqHorario }}
                        </td>
                        @php $primeraFilaHor = false; @endphp
                    @endif

                    {{-- Responsable --}}
                    <td style="white-space:nowrap;">
                        {{ $responsableNombre }}
                    </td>

                    {{-- Área --}}
                    <td style="white-space:nowrap;">
                        {{ $areaNombre }}
                    </td>

                    {{-- Celdas por paradero --}}
                    @foreach($paraderosPorLugar as $grupo)
                        @foreach($grupo as $paradero)
                            @php
                                $pid      = $paradero->id;
                                $cantidad = $matriz[$hid][$aid][$pid] ?? 0;
                            @endphp
                            <td class="text-center">
                                @if($cantidad > 0)
                                    <span class="badge">{{ $cantidad }}</span>
                                @else
                                    <span class="muted">–</span>
                                @endif
                            </td>
                        @endforeach
                    @endforeach

                    {{-- Total por fila --}}
                    <td class="text-center" style="font-weight:600; background:#f8fafc;">
                        {{ $totalesFila[$hid][$aid] ?? 0 }}
                    </td>
                </tr>
            @endforeach

            {{-- Fila de SUBTOTAL por HORARIO --}}
            <tr style="background:#cffafe; font-size:11px; font-weight:600;">
                <td colspan="2" class="text-right">
                    Sub total {{ $etqHorario }}
                </td>

                @foreach($paraderosPorLugar as $grupo)
                    @foreach($grupo as $paradero)
                        @php
                            $pid      = $paradero->id;
                            $subtotal = $subtotalesHorario[$hid][$pid] ?? 0;
                        @endphp
                        <td class="text-center">
                            {{ $subtotal > 0 ? $subtotal : '' }}
                        </td>
                    @endforeach
                @endforeach

                <td class="text-center">
                    {{ $subtotalesHorarioTot[$hid] ?? 0 }}
                </td>
            </tr>
        @endforeach
        </tbody>

        <tfoot>
        <tr>
            <th colspan="3" class="text-right">
                TOTAL PARADERO
            </th>

            @foreach($paraderosPorLugar as $grupo)
                @foreach($grupo as $paradero)
                    @php
                        $pid  = $paradero->id;
                        $tPar = $totalesParadero[$pid] ?? 0;
                    @endphp
                    <th class="text-center">
                        {{ $tPar }}
                    </th>
                @endforeach
            @endforeach

            <th class="text-center">
                {{ $totalGeneral }}
            </th>
        </tr>
        </tfoot>
    </table>

    <div style="margin-top:10px; font-size:10px; color:#6b7280;">
        Las cantidades representan el número de personas programadas en cada combinación
        <strong>Horario / Responsable / Área x Paradero</strong>, agrupadas por
        <strong>Lugar</strong>, para el tipo
        <strong>{{ $tipoSeleccionado === 'recojo' ? 'recojo' : 'salida' }}</strong>.
    </div>
@endif

@if($print)
<script>
    window.print();
</script>
@endif
</body>
</html>
