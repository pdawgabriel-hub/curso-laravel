@props(['titulo' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ? $titulo . ' · ' : '' }}{{ config('gestionobras.empresa.nombre') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    @php
        // Solo se muestran las entradas cuya ruta ya existe: el menú crece con el proyecto.
        $menu = [
            'clientes.index' => 'Clientes',
            'presupuestos.index' => 'Presupuestos',
            'obras.index' => 'Obras',
            'trabajadores.index' => 'Trabajadores',
            'proveedores.index' => 'Proveedores',
            'especialistas.index' => 'Especialistas',
            'gastos.index' => 'Gastos',
            'faltas.index' => 'Faltas',
        ];
    @endphp

    <header class="bg-slate-800 text-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-6 px-4 py-3">
            <a href="{{ url('/') }}" class="font-semibold">{{ config('gestionobras.empresa.nombre') }}</a>
            <nav class="flex flex-wrap gap-4 text-sm">
                @foreach ($menu as $ruta => $texto)
                    @if (Route::has($ruta))
                        <a href="{{ route($ruta) }}" @class(['underline' => request()->routeIs(str_replace('.index', '.*', $ruta))])>{{ $texto }}</a>
                    @endif
                @endforeach
            </nav>
            <div class="ms-auto text-sm">
                @auth
                    <span>{{ auth()->user()->name }}</span>
                    @if (Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="ms-3 underline">Cerrar sesión</button>
                        </form>
                    @endif
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="underline">Iniciar sesión</a>
                    @endif
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
        @if (session('mensaje'))
            <div class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">{{ session('mensaje') }}</div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
