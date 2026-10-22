@extends('layouts.app')

@section('titulo', $pregunta->exists ? 'Editar pregunta ATS' : 'Registrar pregunta ATS')

@section('contenido')
    <div class="mb-7">
        <a class="inline-flex items-center gap-2 text-sm text-principal hover:underline" href="{{ route('preguntas.index') }}">@include('partials.icono', ['nombre' => 'volver']) Volver a preguntas</a>
        <h1 class="mt-4 text-2xl font-semibold">{{ $pregunta->exists ? 'Editar pregunta ATS' : 'Registrar pregunta ATS' }}</h1>
    </div>
    <form method="POST" action="{{ $pregunta->exists ? route('preguntas.update', $pregunta) : route('preguntas.store') }}" class="tarjeta max-w-2xl space-y-5">
        @csrf
        @if ($pregunta->exists)
            @method('PUT')
        @endif
        <div>
            <label class="etiqueta" for="pregunta">Pregunta</label>
            <textarea class="campo" id="pregunta" name="pregunta" rows="3" maxlength="255" required>{{ old('pregunta', $pregunta->pregunta) }}</textarea>
            <p class="mt-2 text-xs text-neutral-500">Si ya fue respondida, conserva su texto y etapa. Puedes desactivarla y registrar una nueva.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label class="etiqueta" for="etapa">Etapa</label>
                <select class="campo" name="etapa" id="etapa">
                    @foreach (['Inicial', 'Final'] as $etapa)
                        <option value="{{ $etapa }}" @selected(old('etapa', $pregunta->etapa) === $etapa)>{{ $etapa }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="etiqueta" for="posicion">Posición</label>
                <input class="campo" name="posicion" id="posicion" type="number" min="1" max="999" value="{{ old('posicion', $pregunta->posicion) }}" required>
            </div>
            <div>
                <label class="etiqueta" for="estado">Estado</label>
                <select class="campo" name="estado" id="estado">
                    <option value="1" @selected((string) old('estado', (int) $pregunta->estado) === '1')>Activa</option>
                    <option value="0" @selected((string) old('estado', (int) $pregunta->estado) === '0')>Inactiva</option>
                </select>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <button class="boton" type="submit">@include('partials.icono', ['nombre' => 'guardar']) Guardar pregunta</button>
            <a class="boton-secundario" href="{{ route('preguntas.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
