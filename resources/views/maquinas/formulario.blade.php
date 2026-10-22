@extends('layouts.app')

@section('titulo', $maquina->exists ? 'Editar máquina' : 'Registrar máquina')

@section('contenido')
    <div class="mb-7">
        <a class="inline-flex items-center gap-2 text-sm text-principal hover:underline" href="{{ route('maquinas.index') }}">@include('partials.icono', ['nombre' => 'volver']) Volver a máquinas</a>
        <h1 class="mt-4 text-2xl font-semibold">{{ $maquina->exists ? 'Editar máquina' : 'Registrar máquina' }}</h1>
    </div>

    <form method="POST" enctype="multipart/form-data"
        action="{{ $maquina->exists ? route('maquinas.update', $maquina) : route('maquinas.store') }}"
        class="tarjeta max-w-2xl space-y-5">
        @csrf
        @if ($maquina->exists)
            @method('PUT')
        @endif

        <div>
            <label class="etiqueta" for="nombre">Nombre de la máquina</label>
            <input class="campo" name="nombre" id="nombre" value="{{ old('nombre', $maquina->nombre) }}"
                required maxlength="100" placeholder="Ejemplo: Transpaleta eléctrica 01">
        </div>

        <div>
            <label class="etiqueta" for="descripcion">Descripción</label>
            <textarea class="campo" name="descripcion" id="descripcion" rows="4" maxlength="3000">{{ old('descripcion', $maquina->descripcion) }}</textarea>
        </div>

        <div>
            <label class="etiqueta" for="fotografia">Fotografía de la máquina (opcional)</label>
            @if ($maquina->fotografia)
                <img src="{{ route('maquinas.fotografia', $maquina) }}" alt="Fotografía de {{ $maquina->nombre }}"
                    class="mb-3 h-40 w-full rounded-md border border-neutral-200 bg-neutral-50 object-contain">
                <p class="mb-3 text-xs text-neutral-500">Selecciona otra imagen para reemplazar la fotografía actual.</p>
            @endif
            <input class="campo file:mr-3 file:cursor-pointer file:border-0 file:bg-neutral-100 file:px-3 file:py-1 file:text-sm file:text-neutral-700"
                name="fotografia" id="fotografia" type="file" accept="image/jpeg,image/png,image/webp"
                aria-describedby="ayuda-fotografia">
            <p id="ayuda-fotografia" class="mt-2 text-xs text-neutral-500">JPG, PNG o WEBP. Máximo 4 MB.</p>
            @error('fotografia')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="etiqueta" for="estado">Estado</label>
            <select class="campo" name="estado" id="estado">
                <option value="1" @selected((string) old('estado', (int) $maquina->estado) === '1')>Activa</option>
                <option value="0" @selected((string) old('estado', (int) $maquina->estado) === '0')>Inactiva</option>
            </select>
            <p class="mt-2 text-xs text-neutral-500">Una máquina inactiva no aparecerá entre los equipos disponibles para el operador.</p>
        </div>

        <div class="flex flex-wrap gap-3 pt-3">
            <button class="boton" type="submit">@include('partials.icono', ['nombre' => 'guardar']) Guardar máquina</button>
            <a class="boton-secundario" href="{{ route('maquinas.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
