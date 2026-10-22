@extends('layouts.app')

@section('titulo', 'Máquinas')

@section('contenido')
    <div class="mb-7 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Máquinas</h1>
            <p class="mt-2 text-sm text-neutral-500">Catálogo de equipos del área de Logística.</p>
        </div>
        <a href="{{ route('maquinas.create') }}" class="boton">@include('partials.icono', ['nombre' => 'agregar']) Registrar máquina</a>
    </div>

    <div class="overflow-x-auto rounded-md border border-neutral-200 bg-white">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Fotografía</th>
                    <th>Máquina</th>
                    <th>Operadores autorizados</th>
                    <th>Estado</th>
                    <th><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($maquinas as $maquina)
                    <tr>
                        <td>
                            @if ($maquina->fotografia)
                                <img src="{{ route('maquinas.fotografia', $maquina) }}"
                                    alt="Fotografía de {{ $maquina->nombre }}" loading="lazy"
                                    class="size-14 rounded-md border border-neutral-200 bg-neutral-50 object-contain">
                            @else
                                <span class="text-xs text-neutral-500">Sin fotografía</span>
                            @endif
                        </td>
                        <td>
                            <p class="font-medium">{{ $maquina->nombre }}</p>
                            <p class="mt-1 text-xs text-neutral-500">{{ $maquina->descripcion }}</p>
                        </td>
                        <td>{{ $maquina->operadores_count }}</td>
                        <td><span class="insignia">{{ $maquina->estado ? 'Activa' : 'Inactiva' }}</span></td>
                        <td><a class="inline-flex items-center gap-2 font-medium text-principal hover:underline" href="{{ route('maquinas.edit', $maquina) }}">@include('partials.icono', ['nombre' => 'editar']) Editar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-neutral-500">Aún no hay máquinas. Registra el primer equipo para comenzar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $maquinas->links() }}</div>
@endsection
