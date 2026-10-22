<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel de control') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <button type="button" data-fondo-menu data-cerrar-menu hidden tabindex="-1"
        class="fixed inset-0 z-40 cursor-pointer bg-slate-950/50 lg:hidden"
        aria-label="Cerrar menú"></button>

    <aside id="menu-lateral" aria-label="Menú principal"
        class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[calc(100%-3rem)] -translate-x-full flex-col overflow-y-auto bg-slate-950 p-5 transition-transform motion-reduce:transition-none lg:w-64 lg:translate-x-0 lg:p-7">
        <div class="flex items-center justify-between gap-2">
            <a href="{{ route('panel') }}" class="flex min-w-0 items-center gap-3 text-white">
                <span class="grid size-10 shrink-0 place-items-center rounded-md bg-teal-600 text-sm font-bold">E/A</span>
                <span class="text-sm font-semibold">
                    Control EPP y ATS
                    <span class="mt-0.5 block text-xs font-normal text-slate-400">Logística · Makro</span>
                </span>
            </a>
            <button type="button" data-cerrar-menu aria-label="Cerrar menú"
                class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded-md p-2 text-slate-300 hover:bg-white/10 hover:text-white lg:hidden">
                @include('partials.icono', ['nombre' => 'cerrar'])
            </button>
        </div>

        <p class="mt-9 text-xs font-semibold uppercase tracking-widest text-slate-500">Espacio de trabajo</p>
        <nav aria-label="Navegación principal" class="mt-5 flex flex-col gap-2">
            <a class="enlace-menu {{ request()->routeIs('panel') ? 'enlace-menu-activo' : '' }}"
                href="{{ route('panel') }}">
                @include('partials.icono', ['nombre' => 'panel'])
                <span>Panel de control</span>
            </a>
            @can('registros.consultar')
                <a class="enlace-menu {{ request()->routeIs('registros.*') ? 'enlace-menu-activo' : '' }}" href="{{ route('registros.index') }}">
                    @include('partials.icono', ['nombre' => 'reportes'])
                    <span>Registros ATS/EPP</span>
                </a>
            @endcan
            @can('preguntas.gestionar')
                <a class="enlace-menu {{ request()->routeIs('preguntas.*') ? 'enlace-menu-activo' : '' }}" href="{{ route('preguntas.index') }}">
                    @include('partials.icono', ['nombre' => 'editar'])
                    <span>Preguntas del ATS</span>
                </a>
            @endcan
            @can('usuarios.gestionar')
                <a class="enlace-menu {{ request()->routeIs('usuarios.*') ? 'enlace-menu-activo' : '' }}"
                    href="{{ route('usuarios.index') }}">
                    @include('partials.icono', ['nombre' => 'usuarios'])
                    <span>Usuarios y autorizaciones</span>
                </a>
            @endcan
            @can('maquinas.gestionar')
                <a class="enlace-menu {{ request()->routeIs('maquinas.*') ? 'enlace-menu-activo' : '' }}"
                    href="{{ route('maquinas.index') }}">
                    @include('partials.icono', ['nombre' => 'maquinas'])
                    <span>Máquinas</span>
                </a>
            @endcan
            @can('reportes.generar')
                <a class="enlace-menu {{ request()->routeIs('reportes.*') ? 'enlace-menu-activo' : '' }}"
                    href="{{ route('reportes.index') }}">
                    @include('partials.icono', ['nombre' => 'reportes'])
                    <span>Reportes</span>
                </a>
            @endcan
        </nav>
        <p class="mt-auto pt-8 text-xs leading-5 text-slate-500">
            Registro individual.<br>Información organizada.
        </p>
    </aside>

    <div data-contenido-app class="lg:pl-64">
        <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-10">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button" data-abrir-menu aria-controls="menu-lateral"
                    aria-expanded="false" aria-label="Abrir menú"
                    class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded-md border border-slate-300 bg-white p-2 text-slate-700 hover:bg-slate-100 lg:hidden">
                    @include('partials.icono', ['nombre' => 'menu'])
                </button>
                <div class="min-w-0">
                    <p class="hidden text-xs uppercase tracking-widest text-slate-400 sm:block">Control de seguridad</p>
                    <p class="truncate text-sm font-medium sm:mt-1">@yield('titulo', 'Panel de control')</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-4">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold">{{ auth()->user()->nombres }} {{ auth()->user()->apellidos }}</p>
                    <p class="mt-0.5 text-xs capitalize text-slate-500">{{ auth()->user()->getRoleNames()->implode(', ') }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="boton-secundario gap-2" aria-label="Cerrar sesión">
                        @include('partials.icono', ['nombre' => 'salir'])
                        <span class="hidden sm:inline">Salir</span>
                    </button>
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
