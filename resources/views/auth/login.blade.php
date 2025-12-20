{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Login – AGROKASA Transporte</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- CSRF para cualquier petición JS (por si luego usas AJAX) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Tailwind por CDN (sin Vite) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary: #007037;
            --primary-dark: #00552a;
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-100 flex items-center justify-center">

<div class="max-w-5xl w-full mx-3 grid grid-cols-1 lg:grid-cols-2 bg-white rounded-2xl shadow-2xl overflow-hidden">

    {{-- Panel izquierdo: branding --}}
    <div class="hidden lg:flex flex-col justify-between p-8 bg-[var(--primary)] text-white relative">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-lg font-bold">
                    AK
                </div>
                <div>
                    <div class="font-semibold text-lg">AGROKASA</div>
                    <div class="text-sm text-emerald-100">Transporte de personal</div>
                </div>
            </div>

            <div class="mt-10 space-y-4">
                <h1 class="text-2xl font-semibold leading-tight">
                    Sistema de gestión<br>de transporte y paraderos
                </h1>
                <p class="text-sm text-emerald-100">
                    Centraliza la programación de salidas, recojo de personal
                </p>
            </div>
        </div>

        <div class="text-xs text-emerald-100/80">
            &copy; {{ date('Y') }} AGROKASA
        </div>
    </div>

    {{-- Panel derecho: formulario --}}
    <div class="p-6 sm:p-8 md:p-10">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Ingreso al sistema</h2>
                <p class="text-xs text-gray-500 mt-1">
                    Usa tu <span class="font-semibold">Utiliza tu Usuario</span> y contraseña.
                </p>
            </div>
        </div>

        {{-- Errores --}}
        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm px-3 py-2 rounded">
                <div class="font-semibold mb-1">No se pudo iniciar sesión</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 
            IMPORTANTE:
            - Usa la ruta que tengas definida para el POST de login.
            - Si en routes/web.php tu ruta es ->name('login.attempt'), deja login.attempt.
            - Si le pusiste ->name('login.web'), cámbialo aquí por 'login.web'.
        --}}
        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
            @csrf

            <div class="space-y-1 text-sm">
                <label for="codigo" class="block text-gray-700 font-medium">
                    Código de Usuario
                </label>
                <input id="codigo"
                       type="text"
                       name="codigo"
                       value="{{ old('codigo') }}"
                       required
                       autofocus
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-[var(--primary)] focus:border-transparent"
                       placeholder="Escribe tu usuario">
            </div>

            <div class="space-y-1 text-sm">
                <label for="password" class="block text-gray-700 font-medium">
                    Contraseña
                </label>
                <input id="password"
                       type="password"
                       name="password"
                       required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-[var(--primary)] focus:border-transparent">
            </div>

            <div class="flex items-center justify-between text-xs text-gray-500">
                <div class="flex items-center gap-2">
                    <input id="remember" name="remember" type="checkbox"
                           class="rounded border-gray-300 text-[var(--primary)] focus:ring-[var(--primary)]">
                    <label for="remember">Recordarme en este equipo</label>
                </div>
                <div>
                    <span class="italic">¿Olvidaste tu contraseña? Contacta TI.</span>
                    {{-- Si luego habilitas una vista de info de recuperación:
                    <a href="{{ route('password.forgot.info') }}" class="text-[var(--primary)] hover:underline">
                        ¿Olvidaste tu contraseña?
                    </a>
                    --}}
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2
                               bg-[var(--primary)] hover:bg-[var(--primary-dark)]
                               text-white text-sm font-medium px-4 py-2.5 rounded-lg shadow-sm transition">
                    <span>Ingresar</span>
                    <svg viewBox="0 0 24 24" class="w-4 h-4">
                        <path fill="currentColor" d="M13 5l7 7-7 7v-4H4v-6h9V5z"/>
                    </svg>
                </button>
            </div>
        </form>

        <div class="mt-6 text-[11px] text-gray-400">
            Este sistema registra auditoría de accesos y cambios.
        </div>
    </div>
</div>

</body>
</html>
