<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel de control') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <aside class="bg-slate-950 p-5 lg:fixed lg:inset-y-0 lg:w-64 lg:p-7">
        <a href="{{ route('panel') }}" class="flex items-center gap-3 text-white">
            <span class="grid size-11 place-items-center rounded-md bg-teal-600 text-sm font-bold">E/A</span>
            <span class="font-semibold">
                Control EPP y ATS
                <span class="mt-0.5 block text-xs font-normal text-slate-400">Logística · Makro</span>
            </span>
        </a>

        <p class="mt-9 hidden text-xs font-semibold uppercase tracking-widest text-slate-500 lg:block">
            Espacio de trabajo
        </p>
        <nav aria-label="Navegación principal" class="mt-5 flex flex-wrap gap-2 lg:flex-col">
            <a class="enlace-menu {{ request()->routeIs('panel') ? 'enlace-menu-activo' : '' }}"
                href="{{ route('panel') }}">Panel de control</a>
            @can('usuarios.gestionar')
                <a class="enlace-menu {{ request()->routeIs('usuarios.*') ? 'enlace-menu-activo' : '' }}"
                    href="{{ route('usuarios.index') }}">Usuarios y autorizaciones</a>
            @endcan
            @can('maquinas.gestionar')
                <a class="enlace-menu {{ request()->routeIs('maquinas.*') ? 'enlace-menu-activo' : '' }}"
                    href="{{ route('maquinas.index') }}">Máquinas</a>
            @endcan
            @can('reportes.generar')
                <a class="enlace-menu {{ request()->routeIs('reportes.*') ? 'enlace-menu-activo' : '' }}"
                    href="{{ route('reportes.index') }}">Reportes</a>
            @endcan
        </nav>
        <p class="absolute bottom-7 hidden text-xs leading-5 text-slate-500 lg:block">
            Registro individual.<br>Información organizada.
        </p>
    </aside>

    <div class="lg:pl-64">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4 lg:px-10">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-400">Control de seguridad</p>
                <p class="mt-1 text-sm font-medium">@yield('titulo', 'Panel de control')</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-semibold">{{ auth()->user()->nombres }} {{ auth()->user()->apellidos }}</p>
                    <p class="mt-0.5 text-xs capitalize text-slate-500">{{ auth()->user()->getRoleNames()->implode(', ') }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="boton-secundario">Salir</button>
                </form>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-5 py-8 sm:px-8 lg:px-10 lg:py-10">
            @include('partials.mensajes')
            @yield('contenido')
        </main>
    </div>
</body>
</html>
