@php
    // Precalcular totales por fila (paradero), por columna (ruta) y total general
    $totalesFila = [];
    $totalesCol  = [];
    $totalGeneral = 0;

    foreach ($paraderos as $paradero) {
        $pId = $paradero->id;
        $filaTotal = 0;

        foreach ($rutas as $ruta) {
            $rId = $ruta->id ?? 0;
            $v = $valores[$pId][$rId] ?? 0;
            $filaTotal += $v;
            $totalesCol[$rId] = ($totalesCol[$rId] ?? 0) + $v;
        }

        // Si hay registros sin ruta (ruta_id = 0)
        if (isset($valores[$pId][0])) {
            $v0 = $valores[$pId][0];
            $filaTotal += $v0;
            $totalesCol[0] = ($totalesCol[0] ?? 0) + $v0;
        }

        $totalesFila[$pId] = $filaTotal;
        $totalGeneral += $filaTotal;
    }
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Programación {{ $programacion->fecha->format('d/m/Y') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #999;
            padding: 3px 5px;
            text-align: center;
        }
        th {
            background-color: #e1f3e8;
        }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .header-table td {
            border: none;
            padding: 2px 4px;
            font-size: 11px;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    {{-- ENCABEZADO TIPO PLANTILLA EXCEL --}}
    <table class="header-table">
        <tr>
            <td colspan="4" class="title">AGROKASA - Transporte de personal</td>
        </tr>
        <tr>
            <td><strong>Sucursal:</strong> {{ $sucursal->nombre }}</td>
            <td><strong>Fecha:</strong> {{ $programacion->fecha->format('d/m/Y') }}</td>
            <td><strong>Área:</strong> {{ $area->nombre }}</td>
            <td>
                <strong>Horario:</strong>
                {{ $horario->nombre }} ({{ \Carbon\Carbon::parse($horario->hora)->format('H:i') }})
            </td>
        </tr>
        <tr>
            <td colspan="4"><strong>Estado:</strong> {{ ucfirst($programacion->estado) }}</td>
        </tr>
    </table>

    <br>

    {{-- MATRIZ PARADERO x RUTA --}}
    <table>
        <thead>
        <tr>
            <th class="text-left">Paradero</th>
            @foreach($rutas as $ruta)
                <th>{{ $ruta->codigo }}</th>
            @endforeach
            <th>Sin ruta</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($paraderos as $paradero)
            @php
                $pId = $paradero->id;
            @endphp
            <tr>
                <td class="text-left">{{ $paradero->nombre }}</td>

                @foreach($rutas as $ruta)
                    @php
                        $rId = $ruta->id ?? 0;
                        $v = $valores[$pId][$rId] ?? 0;
                    @endphp
                    <td>{{ $v ?: '' }}</td>
                @endforeach

                {{-- Sin ruta --}}
                @php
                    $v0 = $valores[$pId][0] ?? 0;
                @endphp
                <td>{{ $v0 ?: '' }}</td>

                {{-- Total fila --}}
                <td class="text-right"><strong>{{ $totalesFila[$pId] ?: '' }}</strong></td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th class="text-right">Total columna</th>
            @foreach($rutas as $ruta)
                @php
                    $rId = $ruta->id ?? 0;
                    $tc = $totalesCol[$rId] ?? 0;
                @endphp
                <th class="text-right">{{ $tc ?: '' }}</th>
            @endforeach

            {{-- Total sin ruta --}}
            @php
                $tc0 = $totalesCol[0] ?? 0;
            @endphp
            <th class="text-right">{{ $tc0 ?: '' }}</th>

            {{-- Total general --}}
            <th class="text-right">{{ $totalGeneral }}</th>
        </tr>
        </tfoot>
    </table>

</body>
</html>
