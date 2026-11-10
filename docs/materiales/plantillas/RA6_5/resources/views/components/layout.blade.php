@props(['titulo' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ? $titulo . ' · ' : '' }}{{ config('gestionobras.empresa.nombre') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-800 antialiased">
    @php
        // Solo se muestran las entradas cuya ruta ya existe: el menú crece con el proyecto.
        $menu = [
            'Análisis' => ['dashboard' => 'Panel', 'calendario.index' => 'Calendario'],
            'Comercial' => ['clientes.index' => 'Clientes', 'presupuestos.index' => 'Presupuestos'],
            'Obras' => ['obras.index' => 'Obras', 'gastos.index' => 'Seguimiento de gastos'],
            'Recursos' => [
                'trabajadores.index' => 'Trabajadores',
                'faltas.index' => 'Faltas',
                'proveedores.index' => 'Proveedores',
                'especialistas.index' => 'Especialistas',
            ],
        ];
    @endphp

    <div class="min-h-full lg:flex">
        <aside class="bg-slate-900 text-slate-300 lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
            <a href="{{ url('/') }}" class="flex h-16 items-center gap-3 border-b border-slate-800 px-6">
                <span class="grid size-8 place-items-center rounded-lg bg-amber-500 text-sm font-bold text-slate-900">GO</span>
                <span class="font-semibold text-white">{{ config('gestionobras.empresa.nombre') }}</span>
            </a>

            <nav aria-label="Principal" class="flex gap-6 overflow-x-auto px-3 py-4 lg:flex-1 lg:flex-col lg:overflow-y-auto">
                @foreach ($menu as $grupo => $entradas)
                    @php $entradas = array_filter($entradas, fn ($texto, $ruta) => Route::has($ruta), ARRAY_FILTER_USE_BOTH); @endphp
                    @if ($entradas)
                        <div class="min-w-max">
                            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $grupo }}</p>
                            <ul class="mt-2 space-y-1">
                                @foreach ($entradas as $ruta => $texto)
                                    @php $activo = request()->routeIs(str_replace('.index', '.*', $ruta)); @endphp
                                    <li>
                                        <a href="{{ route($ruta) }}" @if ($activo) aria-current="page" @endif
                                           @class([
                                               'block rounded-md border-l-[3px] px-3 py-2 text-sm font-medium transition',
                                               'border-amber-400 bg-slate-800 text-white' => $activo,
                                               'border-transparent hover:bg-slate-800/60 hover:text-white' => ! $activo,
                                           ])>
                                            {{ $texto }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </nav>

            <div class="border-t border-slate-800 px-6 py-4 text-sm">
                @auth
                    <p class="font-medium text-white">{{ auth()->user()->name }}</p>
                    @if (auth()->user()->rol)
                        <p class="text-xs text-slate-400">{{ ucfirst(auth()->user()->rol) }}</p>
                    @endif
                    @if (Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}" class="mt-3">
                            @csrf
                            <button type="submit" class="text-slate-400 hover:text-white">Cerrar sesión</button>
                        </form>
                    @endif
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="font-medium text-white hover:text-amber-400">Iniciar sesión</a>
                    @endif
                @endauth
            </div>
        </aside>

        <div class="flex-1 lg:pl-64">
            <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                @if (session('mensaje'))
                    <x-alerta tipo="exito">{{ session('mensaje') }}</x-alerta>
                @endif
                @if (session('error'))
                    <x-alerta tipo="error">{{ session('error') }}</x-alerta>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
