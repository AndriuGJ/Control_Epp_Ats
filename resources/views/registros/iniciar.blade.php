@extends('layouts.app')

@section('titulo', 'Iniciar registro ATS/EPP')

@section('contenido')
    <div class="mb-7">
        <a class="inline-flex items-center gap-2 text-sm text-principal hover:underline" href="{{ route('registros.index') }}">@include('partials.icono', ['nombre' => 'volver']) Volver a registros</a>
        <h1 class="mt-4 text-2xl font-semibold">Iniciar registro ATS/EPP</h1>
        <p class="mt-2 text-sm text-neutral-500">Selecciona tu máquina, toma tu fotografía con EPP, responde las preguntas y firma.</p>
    </div>

    @if ($maquinas->isEmpty() || $preguntas->isEmpty())
        <div class="tarjeta max-w-3xl space-y-3">
            @if ($maquinas->isEmpty())
                <p class="text-sm text-neutral-600">No tienes máquinas activas autorizadas. Comunícate con el administrador.</p>
            @endif
            @if ($preguntas->isEmpty())
                <p class="text-sm text-neutral-600">El administrador debe registrar las preguntas iniciales antes de iniciar un ATS.</p>
            @endif
        </div>
    @else
        <form method="POST" action="{{ route('registros.store') }}" class="max-w-3xl space-y-6" data-formulario-ats>
            @csrf
            <input type="hidden" name="version_preguntas" value="{{ $versionPreguntas }}">
            <section class="tarjeta space-y-5">
                <h2 class="font-semibold">Identificación y EPP</h2>
                <p class="text-sm text-neutral-600">Trabajador: {{ auth()->user()->nombres }} {{ auth()->user()->apellidos }}</p>
                <div>
                    <label class="etiqueta" for="id_maquina">Máquina autorizada</label>
                    <select class="campo" id="id_maquina" name="id_maquina" required>
                        <option value="">Selecciona una máquina</option>
                        @foreach ($maquinas as $maquina)
                            <option value="{{ $maquina->getKey() }}" @selected((string) old('id_maquina') === (string) $maquina->getKey())>{{ $maquina->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-3" data-camara data-url-captura="{{ route('registros.captura') }}">
                    <h3 class="etiqueta">Fotografía usando tu EPP</h3>
                    <p class="text-sm text-neutral-500">Abre la cámara y toma tu foto. La fecha y hora aparecerán al capturarla.</p>
                    <video class="max-h-96 w-full rounded-md border border-neutral-300 bg-neutral-950 object-contain" data-video autoplay muted playsinline hidden></video>
                    <figure class="relative" data-vista-captura hidden>
                        <img class="max-h-96 w-full rounded-md border border-neutral-300 bg-neutral-50 object-contain" data-imagen-captura alt="Fotografía recién capturada usando el EPP">
                        <figcaption class="absolute bottom-2 right-2 rounded-md bg-neutral-950/80 px-3 py-2 text-xs text-white" data-fecha-captura></figcaption>
                    </figure>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" class="boton-secundario" data-abrir-camara>Abrir cámara</button>
                        <button type="button" class="boton" data-tomar-foto disabled hidden>Tomar foto</button>
                        <button type="button" class="boton-secundario" data-repetir-foto hidden>Volver a tomar</button>
                    </div>
                    <input type="hidden" name="captura_epp" data-captura-epp>
                    <p class="text-sm text-neutral-600" role="status" aria-live="polite" data-estado-camara></p>
                    <p class="text-sm text-red-700" role="alert" data-error-camara hidden></p>
                </div>
            </section>
            <section class="tarjeta space-y-5">
                <h2 class="font-semibold">ATS inicial</h2>
                <p class="text-sm text-neutral-500">Marca Sí o No en cada pregunta.</p>
                @include('partials.preguntas-ats', ['preguntas' => $preguntas])
            </section>
            @include('partials.firma', ['campoFirma' => 'firma_inicial', 'tituloFirma' => 'Firma inicial del trabajador'])
            <button class="boton" type="submit">@include('partials.icono', ['nombre' => 'guardar']) Guardar e iniciar registro</button>
        </form>
    @endif
@endsection
