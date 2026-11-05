@extends('layouts.app')

@section('titulo', 'Orden de preguntas ATS')

@section('contenido')
    <div class="mb-7">
        <a class="inline-flex items-center gap-2 text-sm text-principal hover:underline" href="{{ route('preguntas.index') }}">@include('partials.icono', ['nombre' => 'volver']) Volver a preguntas</a>
        <h1 class="mt-4 text-2xl font-semibold">Orden de preguntas</h1>
        <p class="mt-2 text-sm text-neutral-500">Selecciona la etapa y organiza sus preguntas. Arrastra desde los puntos o usa las flechas para subir y bajar.</p>
    </div>

    <div class="max-w-4xl space-y-5">
        <form method="GET" action="{{ route('preguntas.orden') }}" class="tarjeta flex flex-wrap items-end gap-4" data-selector-etapa>
            <div class="w-full sm:w-56">
                <label class="etiqueta" for="etapa">Etapa</label>
                <select class="campo" name="etapa" id="etapa">
                    @foreach (['Inicial', 'Final'] as $opcion)
                        <option value="{{ $opcion }}" @selected($etapa === $opcion)>{{ $opcion }}</option>
                    @endforeach
                </select>
            </div>
            <button class="boton-secundario" type="submit">Ver preguntas</button>
        </form>

        <form method="POST" action="{{ route('preguntas.orden.guardar', ['etapa' => $etapa]) }}" class="space-y-5" data-orden-preguntas>
            @csrf
            @method('PUT')
            <input type="hidden" name="etapa" value="{{ $etapa }}">
            <input type="hidden" name="version_orden" value="{{ $versionOrden }}">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold">Preguntas de la etapa {{ mb_strtolower($etapa) }}</h2>
                <span class="text-sm text-neutral-500">{{ $preguntas->count() }} preguntas</span>
            </div>
            <ol class="space-y-3" data-lista-orden aria-label="Preguntas de la etapa {{ $etapa }}">
                @forelse ($preguntas as $pregunta)
                    <li class="flex items-center gap-3 rounded-md border border-neutral-200 bg-white p-3 sm:gap-4 sm:p-4" data-pregunta-orden>
                        <button type="button" class="shrink-0 touch-none cursor-grab rounded-md p-2 text-neutral-400 hover:bg-neutral-100 hover:text-principal active:cursor-grabbing disabled:cursor-default disabled:opacity-40" data-arrastrar aria-label="Arrastrar pregunta: {{ $pregunta->pregunta }}" title="Arrastrar pregunta">
                            @include('partials.icono', ['nombre' => 'arrastrar'])
                        </button>
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-neutral-100 text-sm font-semibold" data-numero-orden>{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm wrap-break-word">{{ $pregunta->pregunta }}</p>
                            <span class="insignia mt-2 {{ $pregunta->estado ? 'insignia-activa' : 'insignia-inactiva' }}">{{ $pregunta->estado ? 'Activa' : 'Inactiva' }}</span>
                            <input type="hidden" name="orden[]" value="{{ $pregunta->getKey() }}">
                        </div>
                        <div class="flex shrink-0 flex-col gap-1 sm:flex-row">
                            <button type="button" class="cursor-pointer rounded-md border border-neutral-200 p-2 text-neutral-600 hover:bg-neutral-100 disabled:cursor-default disabled:opacity-30" data-subir aria-label="Subir pregunta: {{ $pregunta->pregunta }}" title="Subir" @disabled($loop->first)>@include('partials.icono', ['nombre' => 'subir'])</button>
                            <button type="button" class="cursor-pointer rounded-md border border-neutral-200 p-2 text-neutral-600 hover:bg-neutral-100 disabled:cursor-default disabled:opacity-30" data-bajar aria-label="Bajar pregunta: {{ $pregunta->pregunta }}" title="Bajar" @disabled($loop->last)>@include('partials.icono', ['nombre' => 'bajar'])</button>
                        </div>
                    </li>
                @empty
                    <li class="tarjeta text-center text-sm text-neutral-500">No hay preguntas en esta etapa. Registra una pregunta para comenzar.</li>
                @endforelse
            </ol>
            @if ($preguntas->isNotEmpty())
                <p class="text-sm text-neutral-500">Solo las preguntas activas se mostrarán en el ATS, respetando este orden.</p>
                <p class="text-sm text-acento" role="status" aria-live="polite" data-estado-orden>El orden guardado se muestra en la lista.</p>
                <div class="flex flex-wrap gap-3">
                    <button class="boton disabled:cursor-default disabled:opacity-50" type="submit" data-guardar-orden>@include('partials.icono', ['nombre' => 'guardar']) Guardar orden</button>
                    <a class="boton-secundario" href="{{ route('preguntas.index') }}">Volver a preguntas</a>
                </div>
            @endif
        </form>
    </div>
@endsection
