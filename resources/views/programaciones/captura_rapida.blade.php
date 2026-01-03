{{-- resources/views/programaciones/captura_rapida.blade.php --}}

@extends('layouts.app')

@section('title', 'Programación rápida por ruta / paradero')
@section('header', 'Programación rápida (Ruta / Lote / Comedor x Paradero)')

@section('content')
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();

    use App\Models\Sucursal;

    $fecha  = $fecha ?? now()->toDateString();
    $tipo   = $tipo ?? \App\Models\Programacion::TIPO_RECOJO;

    $sucursalContexto = request()->attributes->get('sucursalActual')
        ?? ($user->sucursal ?? null);

    $esAdminGral = $esAdminGral ?? false;
    $esAdmin     = $esAdmin ?? false;

    // Si NO es admin_general ni admin, su área queda fija
    $soloAreaFija = !($esAdminGral || $esAdmin);
@endphp

@if(session('status'))
    <div class="mb-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-3 py-2 rounded">
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-3 bg-red-50 border border-red-200 text-red-800 text-xs px-3 py-2 rounded">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="flex flex-col gap-4">

    {{-- BARRA SUPERIOR: CONTEXTO Y FILTROS --}}
    <div class="bg-white rounded-lg shadow p-4 text-xs flex flex-col gap-3">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <div class="font-semibold text-sm">
                    Programación rápida por Ruta / Lote / Comedor y Paradero
                </div>
                <div class="text-[11px] text-gray-500">
                    El sistema fija tu Área y Horario según tus permisos.
                    Solo digitas la cantidad en cada paradero para cada Ruta / Lote / Comedor.
                </div>
            </div>

            <div class="text-[11px] text-gray-600">
                <div><span class="font-semibold">Sucursal:</span> {{ $sucursalContexto->nombre ?? 'No definida' }}</div>
            </div>
        </div>

        {{-- FORM DE CONTEXTO (GET) --}}
        <form method="GET" action="{{ route('programaciones.captura-rapida') }}"
              class="grid grid-cols-2 md:grid-cols-5 gap-2 md:gap-3 items-end mt-2">

            {{-- Fecha --}}
            <div>
                <label class="block text-[11px] text-gray-500 mb-1">Fecha</label>
                <input type="date"
                       name="fecha"
                       value="{{ $fecha }}"
                       class="w-full border border-gray-300 rounded px-2 py-1 text-xs bg-white">
            </div>

            {{-- Tipo --}}
            <div>
                <label class="block text-[11px] text-gray-500 mb-1">Tipo</label>
                <select name="tipo"
                        class="w-full border border-gray-300 rounded px-2 py-1 text-xs bg-white">
                    <option value="recojo" @selected($tipo === 'recojo')>Recojo</option>
                    <option value="salida" @selected($tipo === 'salida')>Salida</option>
                </select>
            </div>

            {{-- Área --}}
            <div>
                <label class="block text-[11px] text-gray-500 mb-1">Área</label>

                <select name="area_id"
                        @if($soloAreaFija) disabled @endif
                        class="w-full border border-gray-300 rounded px-2 py-1 text-xs bg-white">
                    @foreach($areas as $a)
                        <option value="{{ $a->id }}" @selected($areaIdSeleccionado == $a->id)>
                            {{ $a->nombre }}
                        </option>
                    @endforeach
                </select>

                @if($soloAreaFija)
                    <div class="mt-1 text-[10px] text-gray-400">
                        (Solo puede registrar en su propia área)
                    </div>
                @endif
            </div>

            {{-- Horario --}}
            <div>
                <label class="block text-[11px] text-gray-500 mb-1">Horario</label>
                <select name="horario_id"
                        class="w-full border border-gray-300 rounded px-2 py-1 text-xs bg-white">
                    @forelse($horarios as $h)
                        <option value="{{ $h->id }}" @selected($horarioIdSeleccionado == $h->id)>
                            {{ $h->nombre }}
                            @if(!empty($h->hora))
                                ({{ \Carbon\Carbon::parse($h->hora)->format('H:i') }})
                            @endif
                        </option>
                    @empty
                        <option value="">Sin horarios disponibles</option>
                    @endforelse
                </select>
            </div>

            <div class="flex justify-end">
                <button class="inline-flex items-center gap-1 px-3 py-1.5 rounded bg-gray-100 text-xs">
                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                        <path fill="currentColor" d="M3 4h18v2H3V4zm4 4h10v2H7V8zm-4 4h18v2H3v-2zm4 4h10v2H7v-2z"/>
                    </svg>
                    <span>Aplicar</span>
                </button>
            </div>
        </form>
    </div>

    {{-- FORM PRINCIPAL DE MATRIZ RUTA/LOTE/COM x PARADERO --}}
    @if($areas->isEmpty() || $horarios->isEmpty())
        <div class="bg-white rounded-lg shadow p-4 text-xs text-center text-gray-500">
            No hay Área u Horario configurado para capturar.
        </div>
    @elseif($paraderosPorLugar->isEmpty())
        <div class="bg-white rounded-lg shadow p-4 text-xs text-center text-gray-500">
            No hay paraderos activos configurados para esta sucursal.
        </div>
    @elseif(empty($filas))
        <div class="bg-white rounded-lg shadow p-4 text-xs text-center text-gray-500">
            No hay combinaciones de Ruta / Lote / Comedor configuradas.
        </div>
    @else
        @php
            // Para el wizard por Lugar
            $lugaresKeys   = $paraderosPorLugar->keys()->values();
            $totalLugares  = $lugaresKeys->count();
        @endphp

        <form method="POST" action="{{ route('programaciones.captura-rapida.guardar') }}"
              class="flex flex-col gap-4">
            @csrf

            {{-- Hidden con el contexto que se guarda --}}
            <input type="hidden" name="fecha" value="{{ $fecha }}">
            <input type="hidden" name="tipo" value="{{ $tipo }}">
            <input type="hidden" name="area_id" value="{{ $areaIdSeleccionado }}">
            <input type="hidden" name="horario_id" value="{{ $horarioIdSeleccionado }}">

            <div class="bg-white rounded-lg shadow p-4 text-xs flex flex-col gap-3">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-2">
                    <div>
                        <div class="font-semibold text-sm">
                            Matriz de captura por Lugar (paso a paso)
                        </div>
                        <div class="text-[11px] text-gray-500">
                            Se muestra un Lugar a la vez. Usa los botones de Lugar o
                            <strong>Anterior / Siguiente</strong> para moverte entre lugares.
                            La información se guarda cuando pulses
                            <strong>Guardar programación rápida</strong>.
                        </div>
                    </div>
                    <div class="text-[11px] text-gray-600 text-right">
                        @if($programacion)
                            <span class="font-semibold">Programación actual:</span>
                            {{ $programacion->fecha->format('d/m/Y') }}
                            – Área: {{ $programacion->area->nombre ?? '' }}
                            – Horario: {{ $programacion->horario->nombre ?? '' }}
                        @else
                            <span class="italic text-gray-400">
                                Aún no existe programación; se creará al guardar.
                            </span>
                        @endif
                    </div>
                </div>

                {{-- NAV DEL WIZARD POR LUGAR --}}
                <div class="mb-3 flex flex-col gap-2">
                    {{-- Fila 1: botones de Lugar --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] text-gray-500">
                            Ir a lugar:
                        </span>

                        <div id="contenedor-botones-lugares"
                             class="flex flex-wrap gap-1">
                            @foreach($lugaresKeys as $idx => $nombreLugar)
                                @php
                                    $tituloLugar = $nombreLugar === 'SIN LUGAR'
                                        ? 'SIN LUGAR'
                                        : strtoupper($nombreLugar);
                                @endphp
                                <button type="button"
                                        class="btn-lugar px-2 py-1 rounded border border-gray-300
                                               text-[11px] text-gray-700
                                               bg-white
                                               hover:bg-gray-100 whitespace-nowrap"
                                        data-index="{{ $idx }}">
                                    {{ $tituloLugar }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Fila 2: "Lugar X de Y: NOMBRE" + Anterior/Siguiente --}}
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="text-[11px] text-gray-600">
                            Lugar <span id="lugar-actual">1</span> de {{ $totalLugares }}:
                            <span id="label-lugar-actual" class="font-semibold"></span>
                        </div>

                        <div class="flex gap-1">
                            <button type="button"
                                    id="btn-prev-lugar"
                                    class="px-2 py-1 rounded border border-gray-300 text-[11px] text-gray-700 hover:bg-gray-50 disabled:opacity-40">
                                ◀ Anterior
                            </button>
                            <button type="button"
                                    id="btn-next-lugar"
                                    class="px-2 py-1 rounded border border-gray-300 text-[11px] text-gray-700 hover:bg-gray-50 disabled:opacity-40">
                                Siguiente ▶
                            </button>
                        </div>
                    </div>
                </div>

                {{-- BLOQUES POR LUGAR --}}
                <div class="flex flex-col gap-4">
                    @foreach($paraderosPorLugar as $nombreLugar => $grupo)
                        @php
                            $tituloLugar = $nombreLugar === 'SIN LUGAR'
                                ? 'SIN LUGAR'
                                : strtoupper($nombreLugar);
                        @endphp

                        <div class="lugar-block border border-gray-200 rounded-lg overflow-hidden @if(!$loop->first) hidden @endif"
                             data-lugar-index="{{ $loop->index }}"
                             data-lugar-nombre="{{ $tituloLugar }}">
                            {{-- Cabecera del bloque --}}
                            <div class="bg-[var(--primary)] text-white px-3 py-2 text-[11px] font-semibold flex justify-between">
                                <span>
                                    Lugar: {{ $tituloLugar }}
                                </span>
                                <span class="font-normal text-emerald-100">
                                    Paraderos: {{ $grupo->count() }}
                                </span>
                            </div>

                            {{-- Tabla de la matriz solo con los paraderos de este Lugar --}}
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-[11px] border-collapse">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="border border-gray-200 px-2 py-1 text-left">
                                                Ruta
                                            </th>
                                            <th class="border border-gray-200 px-2 py-1 text-left">
                                                Lote
                                            </th>
                                            <th class="border border-gray-200 px-2 py-1 text-left">
                                                Comedor
                                            </th>

                                            @foreach($grupo as $paradero)
                                                <th class="border border-gray-200 px-2 py-1 text-center whitespace-nowrap">
                                                    {{ $paradero->nombre }}
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($filas as $keyFila => $fila)
                                            @php
                                                $rutaCodigo = $fila['ruta_codigo'] ?? '';
                                                $lote       = $fila['lote'] ?? '';
                                                $comedor    = $fila['comedor'] ?? '';
                                            @endphp
                                            <tr class="{{ $loop->odd ? 'bg-white' : 'bg-gray-50' }}">
                                                <td class="border border-gray-200 px-2 py-1 whitespace-nowrap">
                                                    {{ $rutaCodigo }}
                                                </td>
                                                <td class="border border-gray-200 px-2 py-1 whitespace-nowrap">
                                                    {{ $lote !== '' ? $lote : '–' }}
                                                </td>
                                                <td class="border border-gray-200 px-2 py-1 whitespace-nowrap">
                                                    {{ $comedor !== '' ? $comedor : '–' }}
                                                </td>

                                                @foreach($grupo as $paradero)
                                                    @php
                                                        $pid   = $paradero->id;
                                                        $valor = $matrix[$keyFila][$pid] ?? null;
                                                    @endphp
                                                    <td class="border border-gray-200 px-1.5 py-0.5 text-right align-middle">
                                                        <input
                                                            type="number"
                                                            name="matrix[{{ $keyFila }}][{{ $pid }}]"
                                                            value="{{ $valor !== null ? $valor : '' }}"
                                                            min="0"
                                                            step="1"
                                                            class="w-16 md:w-20 text-right border border-gray-300 rounded px-1 py-0.5 text-[11px] bg-white"
                                                            placeholder="0"
                                                        >
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- BOTONES FINALES --}}
            <div class="flex justify-between items-center mt-2">
                <div class="text-[10px] text-gray-500">
                    Al guardar, se reemplazará la programación de este
                    <strong>Área / Horario / Tipo / Fecha</strong>
                    por las cantidades de esta matriz (Ruta / Lote / Comedor x Paradero).
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('programaciones.index') }}"
                       class="px-3 py-1.5 rounded border border-gray-300 text-xs text-gray-700 hover:bg-gray-50">
                        Volver a Programaciones
                    </a>

                    <button type="submit"
                            class="px-4 py-1.5 rounded bg-[var(--primary)] text-white text-xs font-semibold hover:bg-emerald-700">
                        Guardar programación rápida
                    </button>
                </div>
            </div>
        </form>

        {{-- JS DEL WIZARD POR LUGAR --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const blocks         = Array.from(document.querySelectorAll('.lugar-block'));
                const total          = blocks.length;
                if (!total) return;

                let currentIndex     = 0;

                const spanActual     = document.getElementById('lugar-actual');
                const labelLugar     = document.getElementById('label-lugar-actual');
                const btnPrev        = document.getElementById('btn-prev-lugar');
                const btnNext        = document.getElementById('btn-next-lugar');
                const botonesLugares = Array.from(document.querySelectorAll('.btn-lugar'));

                function marcarBotonActivo(index) {
                    botonesLugares.forEach(btn => {
                        const idx = parseInt(btn.dataset.index, 10) || 0;
                        if (idx === index) {
                            btn.classList.add('bg-[var(--primary)]', 'text-white', 'border-[var(--primary)]');
                            btn.classList.remove('bg-white', 'text-gray-700');
                        } else {
                            btn.classList.remove('bg-[var(--primary)]', 'text-white', 'border-[var(--primary)]');
                            btn.classList.add('bg-white', 'text-gray-700');
                        }
                    });
                }

                function showIndex(i) {
                    if (i < 0 || i >= total) return;

                    blocks.forEach((b, idx) => {
                        if (idx === i) {
                            b.classList.remove('hidden');
                        } else {
                            b.classList.add('hidden');
                        }
                    });

                    currentIndex = i;

                    if (spanActual) {
                        spanActual.textContent = (i + 1).toString();
                    }

                    if (labelLugar) {
                        const nombre = blocks[i].dataset.lugarNombre || '';
                        labelLugar.textContent = nombre;
                    }

                    if (btnPrev) {
                        btnPrev.disabled = (i === 0);
                    }
                    if (btnNext) {
                        btnNext.disabled = (i === total - 1);
                    }

                    marcarBotonActivo(i);

                    // Subir pantalla para ver siempre el inicio del bloque
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }

                // Eventos de botones de lugar
                botonesLugares.forEach(btn => {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        const idx = parseInt(this.dataset.index, 10);
                        if (!Number.isNaN(idx)) {
                            showIndex(idx);
                        }
                    });
                });

                // Anterior / siguiente
                btnPrev && btnPrev.addEventListener('click', function (e) {
                    e.preventDefault();
                    showIndex(currentIndex - 1);
                });

                btnNext && btnNext.addEventListener('click', function (e) {
                    e.preventDefault();
                    showIndex(currentIndex + 1);
                });

                // Mostrar primer lugar al cargar
                showIndex(0);
            });
        </script>
    @endif
</div>
@endsection
