@extends('layouts.app')

@section('titulo', 'Registros ATS/EPP')

@section('contenido')
    <div class="mb-7 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Registros ATS/EPP</h1>
            <p class="mt-2 text-sm text-neutral-500">Consulta el inicio, el cierre y la revisión de cada labor.</p>
        </div>
        @can('registros.crear')
            <a class="boton" href="{{ route('registros.create') }}">@include('partials.icono', ['nombre' => 'agregar']) Iniciar registro</a>
        @endcan
    </div>
    @include('partials.filtros-registros', ['rutaFiltros' => route('registros.index')])
    <p class="mb-4 text-sm text-neutral-500">Registros encontrados: {{ $registros->total() }}</p>
    <div class="overflow-x-auto rounded-md border border-neutral-200 bg-white">
        <table class="tabla">
            <thead><tr><th>Registro</th><th>Trabajador</th><th>Máquina</th><th>Inicio</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                @forelse ($registros as $registro)
                    <tr>
                        <td>#{{ $registro->id_registro }}</td>
                        <td>{{ $registro->operador->nombres }} {{ $registro->operador->apellidos }}</td>
                        <td>{{ $registro->maquina->nombre }}</td>
                        <td>{{ $registro->fecha_inicio->format('d/m/Y H:i') }}</td>
                        <td>@include('partials.estado-registro', ['registro' => $registro])</td>
                        <td><a class="text-principal hover:underline" href="{{ route('registros.show', $registro) }}">Ver registro</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-neutral-500">No se encontraron registros. Prueba con otros filtros o límpialos para consultar todos los registros disponibles.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $registros->links() }}</div>
@endsection
