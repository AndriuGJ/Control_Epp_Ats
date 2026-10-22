@extends('layouts.app')

@section('titulo', 'Iniciar registro ATS/EPP')

@section('contenido')
    <div class="mb-7">
        <a class="inline-flex items-center gap-2 text-sm text-teal-700 hover:underline" href="{{ route('registros.index') }}">@include('partials.icono', ['nombre' => 'volver']) Volver a registros</a>
        <h1 class="mt-4 text-2xl font-semibold">Iniciar registro ATS/EPP</h1>
        <p class="mt-2 text-sm text-slate-500">Selecciona tu máquina, adjunta tu fotografía con EPP, responde las preguntas y firma.</p>
    </div>

    @if ($maquinas->isEmpty() || $preguntas->isEmpty())
        <div class="tarjeta max-w-3xl space-y-3">
            @if ($maquinas->isEmpty())
                <p class="text-sm text-slate-600">No tienes máquinas activas autorizadas. Comunícate con el administrador.</p>
            @endif
            @if ($preguntas->isEmpty())
                <p class="text-sm text-slate-600">El administrador debe registrar las preguntas iniciales antes de iniciar un ATS.</p>
            @endif
        </div>
    @else
        <form method="POST" enctype="multipart/form-data" action="{{ route('registros.store') }}" class="max-w-3xl space-y-6" data-formulario-ats>
            @csrf
            <input type="hidden" name="version_preguntas" value="{{ $versionPreguntas }}">
            <section class="tarjeta space-y-5">
                <h2 class="font-semibold">Identificación y EPP</h2>
                <p class="text-sm text-slate-600">Trabajador: {{ auth()->user()->nombres }} {{ auth()->user()->apellidos }}</p>
                <div>
                    <label class="etiqueta" for="id_maquina">Máquina autorizada</label>
                    <select class="campo" id="id_maquina" name="id_maquina" required>
                        <option value="">Selecciona una máquina</option>
                        @foreach ($maquinas as $maquina)
                            <option value="{{ $maquina->getKey() }}" @selected((string) old('id_maquina') === (string) $maquina->getKey())>{{ $maquina->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="etiqueta" for="fotografia_epp">Fotografía usando tu EPP</label>
                    <input class="campo" id="fotografia_epp" name="fotografia_epp" type="file" accept="image/jpeg,image/png,image/webp" capture="user" required>
                    <p class="mt-2 text-xs text-slate-500">JPG, PNG o WEBP. Máximo 4 MB. La fecha de recepción se registra al guardar.</p>
                </div>
            </section>
            <section class="tarjeta space-y-5">
                <h2 class="font-semibold">ATS inicial</h2>
                <p class="text-sm text-slate-500">Responde cada pregunta de acuerdo con el formato de tu empresa.</p>
                @foreach ($preguntas as $pregunta)
                    <div>
                        <label class="etiqueta" for="respuesta_{{ $pregunta->getKey() }}">{{ $loop->iteration }}. {{ $pregunta->pregunta }}</label>
                        <textarea class="campo" id="respuesta_{{ $pregunta->getKey() }}" name="respuestas[{{ $pregunta->getKey() }}]" rows="2" maxlength="2000" required>{{ old('respuestas.'.$pregunta->getKey()) }}</textarea>
                    </div>
                @endforeach
            </section>
            <section class="tarjeta space-y-4" data-firma>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-semibold">Firma del trabajador</h2>
                    <button type="button" class="boton-secundario" data-limpiar-firma>Limpiar firma</button>
                </div>
                <p class="text-sm text-slate-500">Firma dentro del recuadro usando el dedo o el mouse.</p>
                <canvas width="800" height="240" class="block w-full touch-none rounded-md border border-slate-300 bg-white" tabindex="0" aria-label="Recuadro para dibujar tu firma" data-lienzo-firma></canvas>
                <input type="hidden" name="firma_inicial" data-valor-firma>
                <p class="text-sm text-red-700" role="alert" data-error-firma hidden>Dibuja tu firma antes de iniciar el registro.</p>
            </section>
            <button class="boton" type="submit">@include('partials.icono', ['nombre' => 'guardar']) Guardar e iniciar registro</button>
        </form>
    @endif
@endsection
