@extends('layouts.app')

@section('titulo', 'Reportes')

@section('contenido')
    <div class="mb-7 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Reportes ATS/EPP</h1>
            <p class="mt-2 text-sm text-neutral-500">Consulta los registros y sus fotografías del EPP. El PDF se abrirá en otra pestaña.</p>
        </div>
        <a class="boton" href="{{ route('reportes.pdf', $filtros) }}" target="_blank" rel="noopener noreferrer" title="Abrir PDF en otra pestaña">@include('partials.icono', ['nombre' => 'descargar']) Descargar PDF</a>
    </div>
    @include('partials.filtros-registros', ['rutaFiltros' => route('reportes.index')])
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-sm text-neutral-500">
        <p>Registros encontrados: {{ $registros->total() }}</p>
        <p>El PDF incluye todos los resultados filtrados, hasta {{ $maximoRegistros }} registros por descarga.</p>
    </div>
    @if ($registros->total() > $maximoRegistros)
        <p class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">Reduce el intervalo de fechas o aplica otros filtros antes de descargar el PDF.</p>
    @endif
    <div class="overflow-x-auto rounded-md border border-neutral-200 bg-white">
        <table class="tabla">
            <thead><tr><th>Registro</th><th>Trabajador / DNI</th><th>Máquina</th><th>Inicio</th><th>Finalización</th><th>Estado</th><th>Revisor</th><th>Fecha de revisión</th></tr></thead>
            <tbody>
                @forelse ($registros as $registro)
                    <tr>
                        <td><a class="text-principal hover:underline" href="{{ route('registros.show', $registro) }}">#{{ $registro->id_registro }}</a></td>
                        <td><p>{{ $registro->operador->nombres }} {{ $registro->operador->apellidos }}</p><p class="mt-1 text-xs text-neutral-500">{{ $registro->operador->dni }}</p></td>
                        <td>{{ $registro->maquina->nombre }}</td>
                        <td>{{ $registro->fecha_inicio->format('d/m/Y H:i') }}</td>
                        <td>{{ $registro->fecha_finalizacion?->format('d/m/Y H:i') ?? 'Sin finalizar' }}</td>
                        <td>@include('partials.estado-registro', ['registro' => $registro])</td>
                        <td>{{ $registro->revisor ? $registro->revisor->nombres.' '.$registro->revisor->apellidos : 'Sin revisión' }}</td>
                        <td>{{ $registro->fecha_revision?->format('d/m/Y H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-neutral-500">No se encontraron registros con los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $registros->links() }}</div>
@endsection
