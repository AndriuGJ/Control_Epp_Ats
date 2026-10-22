@extends('layouts.app')

@section('titulo', 'Registro ATS/EPP #'.$registro->id_registro)

@section('contenido')
    <div class="mb-7">
        <a class="inline-flex items-center gap-2 text-sm text-principal hover:underline" href="{{ route('registros.index') }}">@include('partials.icono', ['nombre' => 'volver']) Volver a registros</a>
        <h1 class="mt-4 text-2xl font-semibold">Registro ATS/EPP #{{ $registro->id_registro }}</h1>
    </div>
    <div class="max-w-4xl space-y-6">
        <section class="tarjeta">
            <dl class="grid gap-5 sm:grid-cols-2">
                <div><dt class="text-sm text-neutral-500">Trabajador</dt><dd class="mt-1 font-medium">{{ $registro->operador->nombres }} {{ $registro->operador->apellidos }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Máquina</dt><dd class="mt-1 font-medium">{{ $registro->maquina->nombre }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Inicio de la labor</dt><dd class="mt-1">{{ $registro->fecha_inicio->format('d/m/Y H:i') }}</dd></div>
                <div><dt class="text-sm text-neutral-500">Estado</dt><dd class="mt-1"><span class="insignia">{{ $registro->estado }}</span></dd></div>
            </dl>
        </section>
        <section class="tarjeta">
            <h2 class="mb-4 font-semibold">Fotografía del EPP</h2>
            <figure class="relative"><img class="max-h-96 w-full rounded-md border border-neutral-200 bg-neutral-50 object-contain" src="{{ route('registros.fotografia', $registro) }}" alt="Fotografía del EPP del trabajador"><figcaption class="absolute bottom-2 right-2 rounded-md bg-neutral-950/80 px-3 py-2 text-xs text-white">{{ $registro->fecha_fotografia->format('d/m/Y H:i:s') }} · Perú</figcaption></figure>
            <p class="mt-2 text-xs text-neutral-500">Capturada: {{ $registro->fecha_fotografia->format('d/m/Y H:i') }}</p>
        </section>
        <section class="tarjeta">
            <h2 class="mb-4 font-semibold">Respuestas del ATS</h2>
            <dl class="space-y-5">
                @foreach ($registro->respuestas as $respuesta)
                    <div><dt class="text-sm font-medium">{{ $respuesta->pregunta->pregunta }} <span class="insignia">{{ $respuesta->pregunta->etapa }}</span></dt><dd class="mt-2 whitespace-pre-line text-sm text-neutral-600">{{ $respuesta->respuesta }}</dd></div>
                @endforeach
            </dl>
        </section>
        <section class="tarjeta">
            <h2 class="mb-4 font-semibold">Firma inicial del trabajador</h2>
            <img class="max-h-40 w-full rounded-md border border-neutral-200 bg-white object-contain" src="{{ route('registros.firma', $registro) }}" alt="Firma inicial del trabajador">
        </section>
        @if ($registro->firma_final)
            <section class="tarjeta">
                <h2 class="mb-4 font-semibold">Firma final del trabajador</h2>
                <img class="max-h-40 w-full rounded-md border border-neutral-200 bg-white object-contain" src="{{ route('registros.firma-final', $registro) }}" alt="Firma final del trabajador">
                <p class="mt-3 text-sm text-neutral-600">Labor finalizada: {{ $registro->fecha_finalizacion?->format('d/m/Y H:i:s') }}</p>
            </section>
        @endif
        @if ($registro->estado === 'En curso' && $registro->id_operador === auth()->id())
            @can('registros.crear')
                <a class="boton" href="{{ route('registros.finalizar', $registro) }}">Continuar y finalizar labor</a>
            @endcan
        @endif
    </div>
@endsection
