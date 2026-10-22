@extends('layouts.app')

@section('titulo', 'Finalizar registro ATS/EPP #'.$registro->id_registro)

@section('contenido')
    <div class="mb-7">
        <a class="inline-flex items-center gap-2 text-sm text-teal-700 hover:underline" href="{{ route('registros.show', $registro) }}">@include('partials.icono', ['nombre' => 'volver']) Volver al registro</a>
        <h1 class="mt-4 text-2xl font-semibold">Finalizar registro #{{ $registro->id_registro }}</h1>
        <p class="mt-2 text-sm text-slate-500">Marca Sí o No en las preguntas finales y firma para cerrar tu labor.</p>
    </div>
    @if ($preguntas->isEmpty())
        <div class="tarjeta max-w-3xl"><p class="text-sm text-slate-600">El administrador debe registrar las preguntas finales antes de finalizar la labor.</p></div>
    @else
        <form method="POST" action="{{ route('registros.finalizar.guardar', $registro) }}" class="max-w-3xl space-y-6">
            @csrf
            <input type="hidden" name="version_preguntas" value="{{ $versionPreguntas }}">
            <section class="tarjeta space-y-5">
                <h2 class="font-semibold">ATS final</h2>
                <p class="text-sm text-slate-500">Selecciona una sola respuesta por pregunta.</p>
                @include('partials.preguntas-ats', ['preguntas' => $preguntas])
            </section>
            @include('partials.firma', ['campoFirma' => 'firma_final', 'tituloFirma' => 'Firma final del trabajador'])
            <button class="boton" type="submit">@include('partials.icono', ['nombre' => 'guardar']) Finalizar registro</button>
        </form>
    @endif
@endsection
