{{-- resources/views/reportes/resumen_paradero_horario_export.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resumen Paradero x Horario</title>

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

    // Fecha a mostrar
    $fechaBase   = request('fecha', $fecha ?? now()->toDateString());
    $fechaTexto  = \Carbon\Carbon::parse($fechaBase)->format('d/m/Y');
@endphp

<div class="page-title">
    Resumen Paradero x Horario ({{ $tipoSeleccionado === 'recojo' ? 'Recojo' : 'Salida' }})
</div>
<div class="page-subtitle">
    Distribución de personal por Horario y Paradero, agrupada por Lugar.
</div>

<div class="meta">
    <span><strong>Sucursal:</strong> {{ $sucursalContexto->nombre ?? 'No definida' }}</span>
    <span><strong>Fecha:</strong> {{ $fechaTexto }}</span>
    <span><strong>Tipo:</strong> {{ $tipoSeleccionado === 'recojo' ? 'Recojo' : 'Salida' }}</span>
    <span><strong>Total general:</strong> {{ $totalGeneral }} personas</span>
</div>

@if($paraderosPorLugar->isEmpty() || $horariosFiltrados->isEmpty())
    <div class="no-data">
        No se encontraron datos para los filtros seleccionados.
    </div>
@else
    <table class="report">
        <thead>
        {{-- Fila 1: cabecera por LUGAR --}}
        <tr>
            <th rowspan="2" style="text-align:left;">
                HORARIO
            </th>

            @foreach($paraderosPorLugar as $nombreLugar => $grupo)
                <th colspan="{{ $grupo->count() }}">
                    {{ $nombreLugar === 'SIN LUGAR' ? 'SIN LUGAR' : strtoupper($nombreLugar) }}
                </th>
            @endforeach

            <th rowspan="2">
                TOTAL
            </th>
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
        @foreach($horariosFiltrados as $horario)
            @php
                $hid       = $horario->id;
                $totalFila = (int) ($totalesHorario[$hid] ?? 0);
            @endphp
            <tr>
                {{-- Columna HORARIO --}}
                <td style="font-weight:600; white-space:nowrap;">
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
                        <td class="text-center">
                            @if($cantidad > 0)
                                <span class="badge">{{ $cantidad }}</span>
                            @else
                                &nbsp;
                            @endif
                        </td>
                    @endforeach
                @endforeach

                {{-- Total por HORARIO --}}
                <td class="text-center" style="font-weight:600; background:#f8fafc;">
                    {{ $totalFila > 0 ? $totalFila : '' }}
                </td>
            </tr>
        @endforeach
        </tbody>

        <tfoot>
        <tr>
            <th class="text-right">
                TOTAL PARADERO
            </th>

            @foreach($paraderosPorLugar as $grupo)
                @foreach($grupo as $paradero)
                    @php
                        $pid  = $paradero->id;
                        $tPar = (int) ($totalesParadero[$pid] ?? 0);
                    @endphp
                    <th class="text-center">
                        {{ $tPar > 0 ? $tPar : '' }}
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
        @if($tipoSeleccionado === 'recojo')
            Las cantidades representan el número de personas programadas para el
            <strong>RECOJO</strong> en cada combinación
            <strong>Horario x Paradero</strong>, agrupadas por <strong>Lugar</strong>.
        @else
            Las cantidades representan el número de personas programadas para la
            <strong>SALIDA</strong> en cada combinación
            <strong>Horario x Paradero</strong>, agrupadas por <strong>Lugar</strong>.
        @endif
    </div>
@endif

@if($print)
<script>
    // Para la opción PDF: abre el diálogo de impresión
    window.print();
</script>
@endif
</body>
</html>
