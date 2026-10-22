@extends('layouts.app')

@section('titulo', $maquina->exists ? 'Editar máquina' : 'Registrar máquina')

@section('contenido')
    <div class="mb-7"><a class="text-sm text-teal-700 hover:underline" href="{{ route('maquinas.index') }}">← Volver a máquinas</a><h1 class="mt-4 text-2xl font-semibold">{{ $maquina->exists ? 'Editar máquina' : 'Registrar máquina' }}</h1></div>
    <form method="POST" action="{{ $maquina->exists ? route('maquinas.update', $maquina) : route('maquinas.store') }}" class="tarjeta max-w-2xl space-y-5">
        @csrf @if ($maquina->exists) @method('PUT') @endif
        <div><label class="etiqueta" for="nombre">Nombre de la máquina</label><input class="campo" name="nombre" id="nombre" value="{{ old('nombre', $maquina->nombre) }}" required maxlength="100" placeholder="Ejemplo: Transpaleta eléctrica 01"></div>
        <div><label class="etiqueta" for="descripcion">Descripción</label><textarea class="campo" name="descripcion" id="descripcion" rows="4" maxlength="3000">{{ old('descripcion', $maquina->descripcion) }}</textarea></div>
        <div><label class="etiqueta" for="estado">Estado</label><select class="campo" name="estado" id="estado"><option value="1" @selected((string) old('estado', (int) $maquina->estado) === '1')>Activa</option><option value="0" @selected((string) old('estado', (int) $maquina->estado) === '0')>Inactiva</option></select><p class="mt-2 text-xs text-slate-500">Una máquina inactiva no aparecerá entre los equipos disponibles para el operador.</p></div>
        <div class="flex flex-wrap gap-3 pt-3"><button class="boton" type="submit">Guardar máquina</button><a class="boton-secundario" href="{{ route('maquinas.index') }}">Cancelar</a></div>
    </form>
@endsection
