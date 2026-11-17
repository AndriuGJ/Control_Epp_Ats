@extends('layouts.app')

@section('titulo', 'Iniciar registro ATS/EPP')

@section('contenido')
    <div class="mb-7">
        <a class="inline-flex items-center gap-2 text-sm text-principal hover:underline"
            href="{{ route('registros.index') }}">@include('partials.icono', ['nombre' => 'volver']) Volver a registros</a>
        <h1 class="mt-4 text-2xl font-semibold">Iniciar registro ATS/EPP</h1>
        <p class="mt-2 text-sm text-neutral-500">Envía una foto con tu EPP. Cuando el supervisor la apruebe, podrás completar
            y firmar el ATS inicial.</p>
    </div>

    @if ($maquinas->isEmpty() || $preguntas->isEmpty())
        <div class="tarjeta max-w-3xl space-y-3">
            @if ($maquinas->isEmpty())
                <p class="text-sm text-neutral-600">No tienes máquinas activas autorizadas. Comunícate con el administrador.
                </p>
            @endif
            @if ($preguntas->isEmpty())
                <p class="text-sm text-neutral-600">El administrador debe registrar las preguntas iniciales antes de iniciar
                    un ATS.</p>
            @endif
        </div>
    @else
        @php($estadoEpp = $validacion?->estadoActual())

        @if ($estadoEpp && $estadoEpp !== 'Utilizada')
            <section class="tarjeta mb-6 max-w-3xl space-y-4"
                @if ($estadoEpp === 'Pendiente') data-estado-epp="{{ $estadoEpp }}" data-url-estado-epp="{{ route('validaciones-epp.estado') }}" @endif>
                <h2 class="font-semibold">Validación de EPP: {{ $estadoEpp }}</h2>
                <p class="text-sm text-neutral-600">{{ $validacion->maquina->nombre }} · Foto del
                    {{ $validacion->fecha_fotografia->format('d/m/Y H:i:s') }}</p>
                <img class="max-h-64 w-full rounded-md border border-neutral-200 object-contain"
                    src="{{ route('validaciones-epp.fotografia', $validacion) }}"
                    alt="Evidencia de EPP enviada para revisión">
                @if ($estadoEpp === 'Pendiente')
                    <p class="text-sm">Espera la revisión del supervisor hasta las
                        {{ $validacion->vence_en->format('H:i:s') }}. El ATS inicial se habilitará si aprueba la foto.</p>
                    <a class="boton-secundario" href="{{ route('registros.create') }}">Actualizar estado</a>
                @elseif ($estadoEpp === 'Aprobada')
                    <p class="text-sm">Aprobada por {{ $validacion->supervisor->nombres }}
                        {{ $validacion->supervisor->apellidos }}. Inicia el ATS antes de las
                        {{ $validacion->vence_en->format('H:i:s') }}.</p>
                @elseif ($estadoEpp === 'Rechazada')
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p><strong>Motivo del rechazo:</strong> {{ $validacion->motivo_rechazo }}</p>
                        <p class="mt-1">Corrige lo indicado y envía otra foto.</p>
                    </div>
                @elseif ($validacion->estado === \App\Models\ValidacionEpp::PENDIENTE)
                    <p class="text-sm">Venció el plazo de revisión del EPP. Coordina con el supervisor y vuelve a tomar y
                        enviar una foto.</p>
                @else
                    <p class="text-sm">La aprobación venció antes de iniciar el ATS. Toma y envía una nueva foto.</p>
                @endif
            </section>
        @endif

        @if ($estadoEpp === 'Aprobada')
            <form method="POST" action="{{ route('registros.store') }}" class="max-w-3xl space-y-6" data-formulario-ats>
                @csrf
                <input type="hidden" name="version_preguntas" value="{{ $versionPreguntas }}">
                <input type="hidden" name="id_maquina" value="{{ $validacion->id_maquina }}">
                <input type="hidden" name="id_validacion_epp" value="{{ $validacion->getKey() }}">
                <section class="tarjeta space-y-5">
                    <h2 class="font-semibold">ATS inicial</h2>
                    <p class="text-sm text-neutral-500">Marca Sí o No en cada pregunta.</p>
                    @include('partials.preguntas-ats', ['preguntas' => $preguntas])
                </section>
                @include('partials.firma', [
                    'campoFirma' => 'firma_inicial',
                    'tituloFirma' => 'Firma inicial del trabajador',
                ])
                <button class="boton" type="submit">@include('partials.icono', ['nombre' => 'guardar']) Guardar e iniciar registro</button>
            </form>
        @elseif ($estadoEpp !== 'Pendiente')
            <form method="POST" action="{{ route('validaciones-epp.store') }}" class="max-w-3xl space-y-4">
                @csrf
                <section class="tarjeta space-y-5">
                    <h2 class="font-semibold">Enviar foto para revisión</h2>
                    <p class="text-sm text-neutral-600">Trabajador: {{ auth()->user()->nombres }}
                        {{ auth()->user()->apellidos }}</p>
                    <div>
                        <label class="etiqueta" for="id_maquina">Máquina autorizada</label>
                        <select class="campo" id="id_maquina" name="id_maquina" required>
                            <option value="">Selecciona una máquina</option>
                            @foreach ($maquinas as $maquina)
                                <option value="{{ $maquina->getKey() }}" @selected((string) old('id_maquina') === (string) $maquina->getKey())>
                                    {{ $maquina->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-3" data-camara data-url-captura="{{ route('registros.captura') }}">
                        <h3 class="etiqueta">Fotografía usando tu EPP</h3>
                        <p class="text-sm text-neutral-500">Abre la cámara y toma tu foto. La fecha y hora aparecerán al
                            capturarla.</p>
                        <video class="max-h-96 w-full rounded-md border border-neutral-300 bg-neutral-950 object-contain"
                            data-video autoplay muted playsinline hidden></video>
                        <figure class="relative" data-vista-captura hidden>
                            <img class="max-h-96 w-full rounded-md border border-neutral-300 bg-neutral-50 object-contain"
                                data-imagen-captura alt="Fotografía recién capturada usando el EPP">
                            <figcaption
                                class="absolute bottom-2 right-2 rounded-md bg-neutral-950/80 px-3 py-2 text-xs text-white"
                                data-fecha-captura></figcaption>
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
                <button class="boton" type="submit">Enviar foto al supervisor</button>
            </form>
        @endif
    @endif
@endsection
