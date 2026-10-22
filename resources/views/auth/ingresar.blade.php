<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="grid min-h-screen lg:grid-cols-2">
        <section class="relative flex flex-col justify-between overflow-hidden bg-slate-950 p-8 text-white lg:p-14">
            <div class="pointer-events-none absolute -right-32 top-1/4 size-96 rounded-full bg-teal-600/15 blur-3xl"></div>
            <div class="relative flex items-center gap-3"><span class="grid size-11 place-items-center rounded-xl bg-teal-600 text-sm font-bold">E/A</span><span class="font-semibold">Control EPP y ATS</span></div>
            <div class="relative max-w-lg py-10 lg:py-16"><p class="mb-6 text-xs font-semibold uppercase tracking-[0.25em] text-teal-300">Logística · Makro</p><h1 class="text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">Cada labor,<br>un registro seguro.</h1><p class="mt-6 max-w-sm text-base leading-7 text-slate-400">Organiza tus controles de EPP y ATS en un solo lugar, desde el inicio hasta el cierre de la labor.</p></div>
            <div class="relative flex flex-wrap gap-6 border-t border-white/10 pt-6 text-xs text-slate-400"><span>01 · Identificación</span><span>02 · Registro</span><span>03 · Revisión</span></div>
        </section>
        <section class="flex items-center justify-center bg-white p-7 sm:p-12">
            <div class="w-full max-w-sm"><p class="text-xs font-semibold uppercase tracking-widest text-teal-700">Bienvenido</p><h2 class="mt-3 text-3xl font-semibold tracking-tight">Inicia sesión</h2><p class="mb-8 mt-3 text-sm leading-6 text-slate-500">Ingresa con la cuenta proporcionada por el administrador.</p>
                <x-mensajes />
                <form method="POST" action="{{ route('sesion.store') }}" class="space-y-5">
                    @csrf
                    <div><label for="correo" class="etiqueta">Correo electrónico</label><input class="campo" id="correo" name="correo" type="email" autocomplete="username" value="{{ old('correo') }}" required autofocus maxlength="255" placeholder="nombre@empresa.com"></div>
                    <div><label for="contrasena" class="etiqueta">Contraseña</label><input class="campo" id="contrasena" name="contrasena" type="password" autocomplete="current-password" required></div>
                    <label class="flex cursor-pointer items-center gap-3 text-sm text-slate-600"><input type="checkbox" name="recordarme" value="1" class="size-4 accent-teal-700" @checked(old('recordarme'))>Mantener mi sesión iniciada</label>
                    <button type="submit" class="boton w-full">Ingresar al sistema <span aria-hidden="true">→</span></button>
                </form>
                <p class="mt-8 text-center text-xs leading-5 text-slate-400">Si no puedes ingresar, comunícate con el administrador.</p>
            </div>
        </section>
    </main>
</body>
</html>
