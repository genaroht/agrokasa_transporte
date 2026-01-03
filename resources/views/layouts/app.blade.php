<!DOCTYPE html>
<html lang="es"
      x-data="layoutState()"
      x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title','AGROKASA Transporte')</title>

    {{-- CSRF para formularios web y JS --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Forzar esquema de color claro para navegadores --}}
    <meta name="color-scheme" content="light">

    {{-- Config Tailwind: solo modo oscuro por clase (no por preferencia del sistema) --}}
    <script>
        tailwind.config = {
            darkMode: 'class',
        };
    </script>
    {{-- TailwindCSS por CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Alpine.js para interactividad ligera --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --primary: #007037;
            --primary-dark: #00592c;
            --primary-soft: #e1f3e8;
        }
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

@php
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    $tz   = config('app.timezone');

    /** @var \App\Models\Sucursal|null $sucursalActual */
    $sucursalActual = null;

    if ($user) {
        if (method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral()) {
            $sucursalActualId = session('sucursal_actual_id', $user->sucursal_id);
        } else {
            $sucursalActualId = $user->sucursal_id;
        }

        if ($sucursalActualId) {
            $sucursalActual = \App\Models\Sucursal::find($sucursalActualId);
        }

        if ($sucursalActual && !empty($sucursalActual->timezone)) {
            $tz = $sucursalActual->timezone;
        }
    }

    $now         = \Carbon\Carbon::now($tz);
    $mainRole    = $user?->rol_principal ?? 'sin rol';
    $isAdminGral = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

    // ====== QUIÉN PUEDE VER "HORARIOS" EN EL MENÚ ======
    $puedeVerHorarios = false;
    if ($user) {
        if ($isAdminGral) {
            $puedeVerHorarios = true;
        }
        if (method_exists($user, 'hasRole') && $user->hasRole('admin_sucursal')) {
            $puedeVerHorarios = true;
        }
        if (method_exists($user, 'can') && $user->can('gestionar_catalogos')) {
            $puedeVerHorarios = true;
        }
    }
@endphp

<body class="min-h-screen bg-gray-50 text-gray-900">

@if(!$user)
    {{-- =========================================================
       LAYOUT PARA INVITADOS (LOGIN)
       ========================================================= --}}
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-md bg-white shadow-lg rounded-xl p-6 space-y-4">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-full bg-[var(--primary)] flex items-center justify-center text-white font-bold">
                    AK
                </div>
                <div>
                    <div class="text-sm font-semibold">AGROKASA</div>
                    <div class="text-[11px] text-gray-500">Transporte de personal</div>
                </div>
            </div>

            @yield('content')
        </div>
    </div>

@else
    {{-- =========================================================
       LAYOUT COMPLETO PARA USUARIOS AUTENTICADOS
       ========================================================= --}}
    <div class="flex h-screen">

        {{-- SIDEBAR ESCRITORIO --}}
        <aside class="hidden md:flex md:flex-col w-60 bg-white border-r border-gray-200">
            <div class="h-16 flex items-center px-4 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-[var(--primary)] flex items-center justify-center text-white font-bold text-sm">
                        AK
                    </div>
                    <div class="leading-tight">
                        <div class="font-semibold text-sm">AGROKASA</div>
                        <div class="text-[11px] text-gray-500">Transporte de personal</div>
                    </div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto py-3 text-sm space-y-1">
                {{-- DASHBOARD --}}
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                   @if(request()->routeIs('dashboard')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                    <span class="w-4 h-4">
                        <svg viewBox="0 0 24 24" class="w-4 h-4">
                            <path fill="currentColor" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                        </svg>
                    </span>
                    <span>Dashboard</span>
                </a>

                {{-- PROGRAMACIONES --}}
                <div class="mt-2 px-4 text-[11px] uppercase tracking-wide text-gray-400">
                    Programaciones
                </div>

                <a href="{{ route('programaciones.index') }}"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                   @if(request()->routeIs('programaciones.index')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                    <span class="w-4 h-4">
                        <svg viewBox="0 0 24 24" class="w-4 h-4">
                            <path fill="currentColor"
                                  d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 
                                     2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 
                                     2-2V6c0-1.1-.9-2-2-2zm0 14H5V9h14v9z"/>
                        </svg>
                    </span>
                    <span>Programaciones</span>
                </a>

                <a href="{{ route('programaciones.resumen.paradero-horario') }}"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                   @if(request()->routeIs('programaciones.resumen.paradero-horario')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                    <span class="w-4 h-4">
                        <svg viewBox="0 0 24 24" class="w-4 h-4">
                            <path fill="currentColor" d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/>
                        </svg>
                    </span>
                    <span>Resumen Paradero x Horario</span>
                </a>

                <a href="{{ route('programaciones.resumen.ruta-paradero') }}"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                   @if(request()->routeIs('programaciones.resumen.ruta-paradero')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                    <span class="w-4 h-4">
                        <svg viewBox="0 0 24 24" class="w-4 h-4">
                            <path fill="currentColor"
                                  d="M4 4h4v4H4V4zm12 0h4v4h-4V4zM4 16h4v4H4v-4zm12 
                                     0h4v4h-4v-4zM9 6h6v2H9V6zm0 10h6v2H9v-2zM6 
                                     9h2v6H6V9zm10 0h2v6h-2V9z"/>
                        </svg>
                    </span>
                    <span>Resumen Ruta x Paradero</span>
                </a>

                @if(\Illuminate\Support\Facades\Route::has('programaciones.reporte_ruta_lote_com'))
                    <a href="{{ route('programaciones.reporte_ruta_lote_com') }}"
                       class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                       @if(request()->routeIs('programaciones.reporte_ruta_lote_com')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                        <span class="w-4 h-4">
                            <svg viewBox="0 0 24 24" class="w-4 h-4">
                                <path fill="currentColor"
                                      d="M3 5h18v2H3V5zm0 4h18v2H3V9zm0 4h18v2H3v-2zm0 4h18v2H3v-2z"/>
                            </svg>
                        </span>
                        <span>Ruta / Lote / Comedor</span>
                    </a>
                @endif

                {{-- CATÁLOGOS --}}
                <div class="mt-3 px-4 text-[11px] uppercase tracking-wide text-gray-400">
                    Catálogos
                </div>

                @if($puedeVerHorarios)
                    <a href="{{ route('catalogos.horarios.index') }}"
                       class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                       @if(request()->routeIs('catalogos.horarios.*')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                        <span class="w-4 h-4">
                            <svg viewBox="0 0 24 24" class="w-4 h-4">
                                <path fill="currentColor"
                                      d="M12 2a10 10 0 1010 10A10.011 10.011 0 0012 2zm1 10.59V7h-2v6h.01L13 14.59z"/>
                            </svg>
                        </span>
                        <span>Horarios</span>
                    </a>
                @endif

                <a href="{{ route('catalogos.paraderos.index') }}"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                   @if(request()->routeIs('catalogos.paraderos.*')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                    <span class="w-4 h-4">
                        <svg viewBox="0 0 24 24" class="w-4 h-4">
                            <path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/>
                        </svg>
                    </span>
                    <span>Paraderos</span>
                </a>

                <a href="{{ route('catalogos.rutas.index') }}"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                   @if(request()->routeIs('catalogos.rutas.*')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                    <span class="w-4 h-4">
                        <svg viewBox="0 0 24 24" class="w-4 h-4">
                            <path fill="currentColor" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zm0 9h7v7h-7v-7zM4 13h7v7H4v-7z"/>
                        </svg>
                    </span>
                    <span>Rutas</span>
                </a>

                <a href="{{ route('catalogos.lugares.index') }}"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                   @if(request()->routeIs('catalogos.lugares.*')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                    <span class="w-4 h-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M12 21s-6-4.35-6-10a6 6 0 0112 0c0 5.65-6 10-6 10z" />
                            <circle cx="12" cy="11" r="2.5" />
                        </svg>
                    </span>
                    <span>Lugares</span>
                </a>

                <a href="{{ route('catalogos.areas.index') }}"
                   class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                   @if(request()->routeIs('catalogos.areas.*')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                    <span class="w-4 h-4">
                        <svg viewBox="0 0 24 24" class="w-4 h-4">
                            <path fill="currentColor"
                                  d="M4 4h7v7H4V4zm9 0h7v7h-7V4zm0 9h7v7h-7v-7zM4 13h7v7H4v-7z"/>
                        </svg>
                    </span>
                    <span>Áreas</span>
                </a>

                {{-- SEGURIDAD / SUCURSALES / AUDITORÍA (solo Admin General) --}}
                @if($isAdminGral)
                    <div class="mt-3 px-4 text-[11px] uppercase tracking-wide text-gray-400">
                        Seguridad
                    </div>

                    <a href="{{ route('usuarios.index') }}"
                       class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                       @if(request()->routeIs('usuarios.*')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                        <span class="w-4 h-4">
                            <svg viewBox="0 0 24 24" class="w-4 h-4">
                                <path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4S8 5.79 8 8s1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4
                                v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </span>
                        <span>Usuarios</span>
                    </a>

                    <a href="{{ route('roles.index') }}"
                       class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                       @if(request()->routeIs('roles.*')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                        <span class="w-4 h-4">
                            <svg viewBox="0 0 24 24" class="w-4 h-4">
                                <path fill="currentColor" d="M3 5h18v2H3V5zm0 4h18v2H3V9zm0 4h12v2H3v-2zm0 4h8v2H3v-2z"/>
                            </svg>
                        </span>
                        <span>Roles</span>
                    </a>

                    <a href="{{ route('sucursales.index') }}"
                       class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                       @if(request()->routeIs('sucursales.*')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                        <span class="w-4 h-4">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor">
                                <path d="M3 7h18M6 3h12M5 7v14h4V7m6 0v14h4V7"
                                      stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>Sucursales</span>
                    </a>

                    <div class="mt-3 px-4 text-[11px] uppercase tracking-wide text-gray-400">
                        Ventanas de tiempo
                    </div>
                    <a href="{{ route('timewindows.index') }}"
                       class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                       @if(request()->routeIs('timewindows.*')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                        <span class="w-4 h-4">
                            <svg viewBox="0 0 24 24" class="w-4 h-4">
                                <path fill="currentColor" d="M12 1a11 11 0 1011 11A11.013 11.013 0 0012 1zm1 11h5v2h-7V6h2z"/>
                            </svg>
                        </span>
                        <span>Ventanas de tiempo</span>
                    </a>

                    <div class="mt-3 px-4 text-[11px] uppercase tracking-wide text-gray-400">
                        Auditoría
                    </div>
                    <a href="{{ route('audit.index') }}"
                       class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 rounded-r-full transition
                       @if(request()->routeIs('audit.*')) bg-[var(--primary-soft)] text-[var(--primary)] font-semibold @endif">
                        <span class="w-4 h-4">
                            <svg viewBox="0 0 24 24" class="w-4 h-4">
                                <path fill="currentColor" d="M3 5h18v2H3V5zm2 4h14v2H5V9zm-2 4h18v2H3v-2zm2 4h14v2H5v-2z"/>
                            </svg>
                        </span>
                        <span>Auditoría</span>
                    </a>
                @endif
            </nav>

            <div class="border-t border-gray-200 p-3 text-[11px] text-gray-400">
                &copy; {{ date('Y') }} AGROKASA
            </div>
        </aside>

        {{-- CONTENIDO PRINCIPAL --}}
        <div class="flex-1 flex flex-col">
            {{-- TOPBAR --}}
            <header class="h-16 flex items-center justify-between px-3 md:px-6 bg-white/90 backdrop-blur border-b border-gray-200">
                <div class="flex items-center gap-2 md:gap-4">
                    <button class="md:hidden p-2 rounded-md hover:bg-gray-100"
                            @click="mobileSidebarOpen = !mobileSidebarOpen">
                        <svg viewBox="0 0 24 24" class="w-5 h-5">
                            <path fill="currentColor" d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/>
                        </svg>
                    </button>

                    <div>
                        <div class="font-semibold text-sm md:text-base">@yield('header','Panel de transporte')</div>
                        <div class="text-[11px] text-gray-500 hidden sm:block">
                            Gestión de transporte de personal y paraderos
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 text-[11px] md:text-xs">
                    {{-- HORA SERVIDOR --}}
                    <div class="hidden sm:flex flex-col items-end">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">
                            Hora servidor ({{ $tz }})
                        </div>
                        <div class="font-mono text-[11px]" x-text="nowFormatted"></div>
                    </div>

                    {{-- SUCURSAL --}}
                    <div class="flex flex-col items-end">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">
                            Sucursal
                        </div>

                        @if($isAdminGral)
                            @php
                                $sucursalesTop = \App\Models\Sucursal::where('activo', true)->orderBy('nombre')->get();
                            @endphp

                            <form method="POST" action="{{ route('sucursales.cambiar') }}">
                                @csrf
                                <select name="sucursal_id"
                                        class="border border-gray-200 bg-gray-50 rounded px-2 py-1 text-[11px]"
                                        onchange="this.form.submit()">
                                    @foreach($sucursalesTop as $s)
                                        <option value="{{ $s->id }}"
                                            @selected(($sucursalActual->id ?? null) == $s->id)>
                                            {{ $s->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <div class="font-medium text-[11px]">
                                {{ $sucursalActual->nombre ?? 'Sin sucursal' }}
                            </div>
                        @endif
                    </div>

                    {{-- PERFIL / "MODO" / LOGOUT --}}
                    <div class="flex items-center gap-3">
                        {{-- Botón que antes cambiaba modo: ahora solo asegura claro --}}
                        <button type="button"
                                class="p-2 rounded-full border border-gray-200 hover:bg-gray-100 transition"
                                @click="toggleDarkMode()"
                                title="Modo claro fijo">
                            {{-- Ícono de sol fijo (solo decorativo) --}}
                            <svg viewBox="0 0 24 24" class="w-4 h-4">
                                <path fill="currentColor"
                                    d="M12 4a1 1 0 011 1v1a1 1 0 01-2 0V5a1 1 0 011-1zm0 9a3 3 0 110-6 3 3 0 010 6zm7-3a1 1 0 011 1 1 1 0 01-1 1h-1a1 1 0 010-2h1zM6 11a1 1 0 000 2H5a1 1 0 010-2h1zm10.95 5.536a1 1 0 011.414 0l.707.707a1 1 0 01-1.414 1.414l-.707-.707a1 1 0 010-1.414zM5.636 6.05a1 1 0 011.414 0l.707.708A1 1 0 016.343 8.17l-.707-.707a1 1 0 010-1.414zm0 11.314a1 1 0 010-1.414l.707-.707A1 1 0 017.757 16.96l-.707.707a1 1 0 01-1.414 0zM17.657 6.05a1 1 0 010 1.414l-.707.707A1 1 0 0115.536 6.76l.707-.708a1 1 0 011.414 0zM12 18a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1z"/>
                            </svg>
                        </button>

                        <div class="flex items-center gap-2">
                            <div class="hidden sm:flex flex-col items-end">
                                <div class="text-[11px] font-semibold">
                                    {{ $user->nombre_completo ?? $user->codigo }}
                                </div>
                                <div class="text-[10px] text-gray-500">
                                    {{ $mainRole }} • {{ $user->codigo }}
                                </div>
                            </div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="text-[11px] px-2 py-1 rounded border border-gray-200 hover:bg-red-50 hover:text-red-700 transition">
                                    Salir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- CONTENIDO --}}
            <main class="flex-1 overflow-y-auto bg-gray-50/60">
                <div class="max-w-7xl mx-auto p-3 md:p-6 space-y-3 md:space-y-4">
                    @if(session('status'))
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-3 py-2 rounded flex items-center gap-2">
                            <span class="w-4 h-4">
                                <svg viewBox="0 0 24 24" class="w-4 h-4">
                                    <path fill="currentColor" d="M12 2a10 10 0 1010 10A10.011 10.011 0 0012 2zm1 15h-2v-2h2zm0-4h-2V7h2z"/>
                                </svg>
                            </span>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-800 text-sm px-3 py-2 rounded">
                            <div class="font-semibold mb-1">Se encontraron errores:</div>
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    {{-- PANEL LATERAL MÓVIL --}}
    <div
        x-cloak
        x-show="mobileSidebarOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 flex md:hidden"
        role="dialog"
        aria-modal="true"
    >
        {{-- Fondo oscuro --}}
        <div class="fixed inset-0 bg-black/40" @click="mobileSidebarOpen = false"></div>

        {{-- Panel lateral --}}
        <div class="relative flex w-full max-w-xs">
            <div class="flex flex-col w-full h-full bg-white shadow-xl">
                {{-- Header del menú móvil --}}
                <div class="flex items-center justify-between h-14 px-4 border-b border-slate-200">
                    <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        Menú
                    </p>
                    <button
                        type="button"
                        class="p-2 rounded-md text-slate-500 hover:bg-slate-100"
                        @click="mobileSidebarOpen = false"
                    >
                        Cerrar
                    </button>
                </div>

                {{-- Contenido del menú móvil --}}
                <div class="flex-1 overflow-y-auto py-4 text-sm">

                    {{-- DASHBOARD --}}
                    <div class="px-4 mb-3">
                        <a
                            href="{{ route('dashboard') }}"
                            class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                   {{ request()->routeIs('dashboard') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                            @click="mobileSidebarOpen = false"
                        >
                            Dashboard
                        </a>
                    </div>

                    {{-- PROGRAMACIONES --}}
                    <div class="mt-4">
                        <p class="px-7 mb-1 text-[11px] font-semibold tracking-wide text-slate-400 uppercase">
                            Programaciones
                        </p>
                        <div class="space-y-1 px-4">
                            <a
                                href="{{ route('programaciones.index') }}"
                                class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                       {{ request()->routeIs('programaciones.index') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                @click="mobileSidebarOpen = false"
                            >
                                Programaciones
                            </a>

                            <a
                                href="{{ route('programaciones.resumen.paradero-horario') }}"
                                class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                       {{ request()->routeIs('programaciones.resumen.paradero-horario') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                @click="mobileSidebarOpen = false"
                            >
                                Resumen Paradero x Horario
                            </a>

                            <a
                                href="{{ route('programaciones.resumen.ruta-paradero') }}"
                                class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                       {{ request()->routeIs('programaciones.resumen.ruta-paradero') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                @click="mobileSidebarOpen = false"
                            >
                                Resumen Ruta x Paradero
                            </a>

                            <a
                                href="{{ route('programaciones.reporte_ruta_lote_com') }}"
                                class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                       {{ request()->routeIs('programaciones.reporte_ruta_lote_com') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                @click="mobileSidebarOpen = false"
                            >
                                Ruta / Lote / Comedor
                            </a>
                        </div>
                    </div>

                    {{-- CATÁLOGOS --}}
                    <div class="mt-6">
                        <p class="px-7 mb-1 text-[11px] font-semibold tracking-wide text-slate-400 uppercase">
                            Catálogos
                        </p>
                        <div class="space-y-1 px-4">
                            @if($puedeVerHorarios)
                                <a
                                    href="{{ route('catalogos.horarios.index') }}"
                                    class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                           {{ request()->routeIs('catalogos.horarios.*') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                    @click="mobileSidebarOpen = false"
                                >
                                    Horarios
                                </a>
                            @endif

                            <a
                                href="{{ route('catalogos.paraderos.index') }}"
                                class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                       {{ request()->routeIs('catalogos.paraderos.*') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                @click="mobileSidebarOpen = false"
                            >
                                Paraderos
                            </a>

                            <a
                                href="{{ route('catalogos.rutas.index') }}"
                                class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                       {{ request()->routeIs('catalogos.rutas.*') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                @click="mobileSidebarOpen = false"
                            >
                                Rutas
                            </a>

                            <a
                                href="{{ route('catalogos.lugares.index') }}"
                                class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                       {{ request()->routeIs('catalogos.lugares.*') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                @click="mobileSidebarOpen = false"
                            >
                                Lugares
                            </a>

                            <a
                                href="{{ route('catalogos.areas.index') }}"
                                class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                       {{ request()->routeIs('catalogos.areas.*') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                @click="mobileSidebarOpen = false"
                            >
                                Áreas
                            </a>
                        </div>
                    </div>

                    {{-- SEGURIDAD / AUDITORÍA (móvil) --}}
                    @if($isAdminGral)
                        <div class="mt-6 mb-4">
                            <p class="px-7 mb-1 text-[11px] font-semibold tracking-wide text-slate-400 uppercase">
                                Seguridad
                            </p>
                            <div class="space-y-1 px-4">
                                <a
                                    href="{{ route('usuarios.index') }}"
                                    class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                           {{ request()->routeIs('usuarios.*') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                    @click="mobileSidebarOpen = false"
                                >
                                    Usuarios
                                </a>

                                <a
                                    href="{{ route('roles.index') }}"
                                    class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                           {{ request()->routeIs('roles.*') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                    @click="mobileSidebarOpen = false"
                                >
                                    Roles
                                </a>

                                <a
                                    href="{{ route('sucursales.index') }}"
                                    class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                           {{ request()->routeIs('sucursales.*') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                    @click="mobileSidebarOpen = false"
                                >
                                    Sucursales
                                </a>

                                <a
                                    href="{{ route('timewindows.index') }}"
                                    class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                           {{ request()->routeIs('timewindows.*') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                    @click="mobileSidebarOpen = false"
                                >
                                    Ventanas de tiempo
                                </a>

                                <a
                                    href="{{ route('audit.index') }}"
                                    class="block rounded-md px-3 py-2 text-[13px] font-medium transition-colors
                                           {{ request()->routeIs('audit.*') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-800 hover:bg-slate-100 hover:text-emerald-700' }}"
                                    @click="mobileSidebarOpen = false"
                                >
                                    Auditoría
                                </a>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
    {{-- FIN PANEL LATERAL MÓVIL --}}
@endif

<script>
    function layoutState() {
        return {
            // Sólo reloj y menú móvil
            mobileSidebarOpen: false,
            now: new Date('{{ $now->format('Y-m-d H:i:s') }}'.replace(' ', 'T')),

            init() {
                // Reloj del header
                setInterval(() => {
                    this.now = new Date(this.now.getTime() + 1000);
                }, 1000);

                // Garantizar que NUNCA se aplique modo oscuro
                localStorage.removeItem('ak_dark');
                document.documentElement.classList.remove('dark');
            },

            get nowFormatted() {
                const pad = (n) => n.toString().padStart(2, '0');
                const d = this.now;
                const yyyy = d.getFullYear();
                const mm = pad(d.getMonth() + 1);
                const dd = pad(d.getDate());
                const hh = pad(d.getHours());
                const mi = pad(d.getMinutes());
                const ss = pad(d.getSeconds());
                return `${dd}/${mm}/${yyyy} ${hh}:${mi}:${ss}`;
            },

            toggleDarkMode() {
                // Botón decorativo: por si alguien hace clic, limpiamos cualquier rastro viejo
                localStorage.removeItem('ak_dark');
                document.documentElement.classList.remove('dark');
            }
        }
    }
</script>

</body>
</html>
