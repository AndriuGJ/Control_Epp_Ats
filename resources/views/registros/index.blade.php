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
    <form method="GET" action="{{ route('registros.index') }}" class="tarjeta mb-6 space-y-5" aria-label="Buscar registros">
        <div>
            <h2 class="font-semibold">Buscar registros</h2>
            <p class="mt-1 text-sm text-neutral-500">Puedes combinar los filtros. Las fechas corresponden al inicio de la labor.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="etiqueta" for="trabajador">Trabajador</label>
                <input class="campo" id="trabajador" name="trabajador" type="search" maxlength="100" placeholder="Nombres, apellidos o DNI" value="{{ $filtros['trabajador'] ?? '' }}">
            </div>
            <div>
                <label class="etiqueta" for="fecha_desde">Desde</label>
                <input class="campo" id="fecha_desde" name="fecha_desde" type="date" value="{{ $filtros['fecha_desde'] ?? '' }}">
            </div>
            <div>
                <label class="etiqueta" for="fecha_hasta">Hasta</label>
                <input class="campo" id="fecha_hasta" name="fecha_hasta" type="date" value="{{ $filtros['fecha_hasta'] ?? '' }}">
            </div>
            <div>
                <label class="etiqueta" for="id_maquina">Máquina</label>
                <select class="campo" id="id_maquina" name="id_maquina">
                    <option value="">Todas las máquinas</option>
                    @foreach ($maquinas as $maquina)
                        <option value="{{ $maquina->id_maquina }}" @selected((string) ($filtros['id_maquina'] ?? '') === (string) $maquina->id_maquina)>{{ $maquina->nombre }}{{ $maquina->estado ? '' : ' (inactiva)' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="etiqueta" for="estado">Estado de la labor</label>
                <select class="campo" id="estado" name="estado">
                    <option value="">Todos los estados</option>
                    <option value="En curso" @selected(($filtros['estado'] ?? '') === 'En curso')>En curso</option>
                    <option value="Finalizado" @selected(($filtros['estado'] ?? '') === 'Finalizado')>Finalizado</option>
                </select>
            </div>
            <div>
                <label class="etiqueta" for="revision">Revisión del supervisor</label>
                <select class="campo" id="revision" name="revision">
                    <option value="">Todas las revisiones</option>
                    <option value="Pendiente" @selected(($filtros['revision'] ?? '') === 'Pendiente')>Pendiente de revisión</option>
                    <option value="Revisado" @selected(($filtros['revision'] ?? '') === 'Revisado')>Revisado</option>
                </select>
                <p class="mt-1 text-xs text-neutral-500">Este filtro consulta únicamente labores finalizadas.</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <button class="boton" type="submit">@include('partials.icono', ['nombre' => 'buscar']) Buscar</button>
            <a class="boton-secundario gap-2" href="{{ route('registros.index') }}">@include('partials.icono', ['nombre' => 'cerrar']) Limpiar filtros</a>
        </div>
    </form>
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
