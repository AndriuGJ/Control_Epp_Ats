@extends('layouts.acceso')

@section('titulo', 'Ingresar')

@section('contenido')
    <section class="tarjeta w-full max-w-sm sm:p-8" aria-labelledby="titulo-acceso">
        <div class="mb-7 text-center">
            <p class="text-sm font-semibold text-teal-700">Control EPP y ATS</p>
            <h1 id="titulo-acceso" class="mt-3 text-2xl font-semibold">Inicia sesión</h1>
            <p class="mt-2 text-sm text-slate-500">Logística · Makro</p>
        </div>

        @include('partials.mensajes')

        <form method="POST" action="{{ route('sesion.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="correo" class="etiqueta">Correo electrónico</label>
                <input class="campo" id="correo" name="correo" type="email"
                    autocomplete="username" value="{{ old('correo') }}" required autofocus
                    maxlength="255" placeholder="nombre@empresa.com">
            </div>

            <div>
                <label for="contrasena" class="etiqueta">Contraseña</label>
                <input class="campo" id="contrasena" name="contrasena" type="password"
                    autocomplete="current-password" required>
            </div>

            <label class="flex cursor-pointer items-center gap-3 text-sm text-slate-600">
                <input type="checkbox" name="recordarme" value="1"
                    class="size-4 accent-teal-700" @checked(old('recordarme'))>
                Mantener mi sesión iniciada
            </label>

            <button type="submit" class="boton w-full">@include('partials.icono', ['nombre' => 'ingresar']) Ingresar al sistema</button>
        </form>

        <p class="mt-6 text-center text-xs leading-5 text-slate-500">
            Si no puedes ingresar, comunícate con el administrador.
        </p>
    </section>
@endsection
