@extends('layouts.app')

@section('titulo', $usuario->exists ? 'Editar usuario' : 'Registrar usuario')

@section('contenido')
    <div class="mb-7"><a class="text-sm text-teal-700 hover:underline" href="{{ route('usuarios.index') }}">← Volver a usuarios</a><h1 class="mt-4 text-2xl font-semibold">{{ $usuario->exists ? 'Editar usuario' : 'Registrar usuario' }}</h1></div>
    <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="tarjeta max-w-4xl">
        @csrf @if ($usuario->exists) @method('PUT') @endif
        <h2 class="mb-5 font-semibold">Identificación y acceso</h2>
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach (['nombres' => 'Nombres', 'apellidos' => 'Apellidos', 'dni' => 'DNI', 'correo' => 'Correo electrónico'] as $campo => $etiqueta)
                <div><label class="etiqueta" for="{{ $campo }}">{{ $etiqueta }}</label><input class="campo" id="{{ $campo }}" name="{{ $campo }}" value="{{ old($campo, $usuario->{$campo}) }}" type="{{ $campo === 'correo' ? 'email' : 'text' }}" maxlength="{{ $campo === 'dni' ? 8 : ($campo === 'correo' ? 255 : 100) }}" @if ($campo === 'dni') inputmode="numeric" pattern="[0-9]{8}" @endif required></div>
            @endforeach
            <div><label class="etiqueta" for="contrasena">{{ $usuario->exists ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</label><input class="campo" id="contrasena" name="contrasena" type="password" autocomplete="new-password" minlength="12" @required(! $usuario->exists)><p class="mt-2 text-xs text-slate-500">Mínimo 12 caracteres, con letras y números.</p></div>
            <div><label class="etiqueta" for="contrasena_confirmation">Confirmar contraseña</label><input class="campo" id="contrasena_confirmation" name="contrasena_confirmation" type="password" autocomplete="new-password" @required(! $usuario->exists)></div>
            <div><label class="etiqueta" for="rol">Rol</label><select class="campo" name="rol" id="rol" data-selector-rol>@foreach (['operador', 'supervisor', 'administrador'] as $rol)<option value="{{ $rol }}" @selected(old('rol', $usuario->roles->first()?->name ?? 'operador') === $rol)>{{ ucfirst($rol) }}</option>@endforeach</select></div>
            <div><label class="etiqueta" for="estado">Estado</label><select class="campo" name="estado" id="estado"><option value="1" @selected((string) old('estado', (int) $usuario->estado) === '1')>Activo</option><option value="0" @selected((string) old('estado', (int) $usuario->estado) === '0')>Inactivo</option></select></div>
        </div>
        <fieldset class="mt-8 border-t border-slate-200 pt-6" data-autorizaciones><legend class="px-1 font-semibold">Máquinas autorizadas</legend><p class="mb-4 mt-2 text-sm text-slate-500">Selecciona los equipos que podrá utilizar el operador.</p><div class="grid gap-3 sm:grid-cols-2">@forelse ($maquinas as $maquina)<label class="flex cursor-pointer items-start gap-3 rounded-md border border-slate-200 p-4"><input class="mt-1 size-4 accent-teal-700" type="checkbox" name="maquinas[]" value="{{ $maquina->id_maquina }}" @checked(in_array($maquina->id_maquina, old('maquinas', $usuario->maquinas->modelKeys())))><span class="text-sm">{{ $maquina->nombre }}@if (! $maquina->estado)<span class="block text-xs text-slate-500">Inactiva</span>@endif</span></label>@empty<p class="text-sm text-slate-500">Registra las máquinas antes de asignar autorizaciones.</p>@endforelse</div></fieldset>
        <div class="mt-8 flex flex-wrap gap-3"><button class="boton" type="submit">Guardar usuario</button><a class="boton-secundario" href="{{ route('usuarios.index') }}">Cancelar</a></div>
    </form>
@endsection
