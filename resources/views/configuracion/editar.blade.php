@extends('layouts.app')

@section('titulo', 'Configuración')

@section('contenido')
    <h1 class="mb-2 text-2xl font-semibold">Configuración</h1>
    <p class="mb-6 text-sm text-neutral-600">Edita la identidad de la empresa y los avisos de validación de EPP.</p>

    <form class="max-w-3xl space-y-5" method="POST" action="{{ route('configuracion.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <section class="tarjeta space-y-4" aria-labelledby="datos-empresa">
            <h2 id="datos-empresa" class="font-semibold">Identidad de la empresa</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="etiqueta" for="nombre_empresa">Nombre de la empresa</label>
                    <input class="campo" id="nombre_empresa" name="nombre_empresa" maxlength="120" required
                        value="{{ old('nombre_empresa', $configuracion->nombre_empresa) }}">
                </div>
                <div>
                    <label class="etiqueta" for="area">Área</label>
                    <input class="campo" id="area" name="area" maxlength="100" required
                        value="{{ old('area', $configuracion->area) }}">
                </div>
            </div>
            <div>
                <label class="etiqueta" for="logo">Logo</label>
                @if ($configuracion->logo)
                    <img class="mb-3 h-12 max-w-40 object-contain" src="{{ route('marca.logo', ['version' => basename($configuracion->logo)]) }}" alt="Logo actual">
                @endif
                <input class="campo" id="logo" name="logo" type="file" accept="image/png,image/jpeg" aria-describedby="ayuda-logo">
                <p id="ayuda-logo" class="mt-1 text-xs text-neutral-500">PNG o JPG, hasta 2 MB. Se muestra en el acceso, las pestañas, el pie de página y los reportes.</p>
                @if ($configuracion->logo)
                    <label class="mt-3 flex items-center gap-2 text-sm">
                        <input class="accent-acento" type="checkbox" name="retirar_logo" value="1" @checked(old('retirar_logo'))>
                        Quitar el logo actual
                    </label>
                @endif
            </div>
        </section>

        <section class="tarjeta space-y-4" aria-labelledby="datos-correo">
            <h2 id="datos-correo" class="font-semibold">Correo SMTP</h2>
            <input type="hidden" name="avisos_epp" value="0">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input class="accent-acento" type="checkbox" name="avisos_epp" value="1" @checked(old('avisos_epp', $configuracion->avisos_epp))>
                Enviar avisos de validación de EPP
            </label>
            <p class="text-sm text-neutral-600">Los avisos llegan a los supervisores y administradores activos que pueden revisar las solicitudes.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="etiqueta" for="servidor_smtp">Servidor SMTP</label>
                    <select class="campo" id="servidor_smtp" name="servidor_smtp" required>
                        @foreach (config('epp.servidores_smtp') as $servidor)
                            <option value="{{ $servidor }}" @selected(old('servidor_smtp', $configuracion->servidor_smtp) === $servidor)>{{ $servidor }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="etiqueta" for="puerto_smtp">Puerto</label>
                    <select class="campo" id="puerto_smtp" name="puerto_smtp" required>
                        <option value="465" @selected((int) old('puerto_smtp', $configuracion->puerto_smtp) === 465)>465 (SSL/TLS)</option>
                        <option value="587" @selected((int) old('puerto_smtp', $configuracion->puerto_smtp) === 587)>587 (STARTTLS)</option>
                    </select>
                </div>
                <div>
                    <label class="etiqueta" for="usuario_smtp">Correo remitente</label>
                    <input class="campo" id="usuario_smtp" name="usuario_smtp" type="email" maxlength="254"
                        value="{{ old('usuario_smtp', $configuracion->usuario_smtp) }}" placeholder="nombre@gmail.com">
                </div>
                <div>
                    <label class="etiqueta" for="contrasena_smtp">Contraseña SMTP</label>
                    <input class="campo" id="contrasena_smtp" name="contrasena_smtp" type="password" maxlength="255"
                        autocomplete="new-password" aria-describedby="ayuda-contrasena-smtp">
                    <p id="ayuda-contrasena-smtp" class="mt-1 text-xs text-neutral-500">
                        @if ($configuracion->tieneContrasenaSmtp())
                            Deja este campo vacío para conservar la contraseña guardada.
                        @else
                            En Gmail, ingresa la contraseña de aplicación sin espacios.
                        @endif
                    </p>
                </div>
            </div>
        </section>

        <button class="boton" type="submit">@include('partials.icono', ['nombre' => 'guardar']) Guardar configuración</button>
    </form>
@endsection
