@extends('layouts.app')

@section('titulo', 'Preguntas del ATS')

@section('contenido')
    <div class="mb-7 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Preguntas del ATS</h1>
            <p class="mt-2 text-sm text-slate-500">Configura las preguntas del formato de tu empresa, su etapa y su orden.</p>
        </div>
        <a href="{{ route('preguntas.create') }}" class="boton">@include('partials.icono', ['nombre' => 'agregar']) Registrar pregunta</a>
    </div>
    <div class="overflow-x-auto rounded-md border border-slate-200 bg-white">
        <table class="tabla">
            <thead><tr><th>Pregunta</th><th>Etapa</th><th>Posición</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                @forelse ($preguntas as $pregunta)
                    <tr>
                        <td>{{ $pregunta->pregunta }}</td>
                        <td>{{ $pregunta->etapa }}</td>
                        <td>{{ $pregunta->posicion }}</td>
                        <td><span class="insignia">{{ $pregunta->estado ? 'Activa' : 'Inactiva' }}</span></td>
                        <td><a class="inline-flex items-center gap-2 text-teal-700 hover:underline" href="{{ route('preguntas.edit', $pregunta) }}">@include('partials.icono', ['nombre' => 'editar']) Editar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-slate-500">Aún no hay preguntas. Registra las de tu formato ATS para habilitar el inicio de los controles.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $preguntas->links() }}</div>
@endsection
