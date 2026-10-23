@extends('layouts.app')

@section('titulo', 'Panel de control')

@section('contenido')
    <div class="mb-8"><p class="text-xs font-semibold uppercase tracking-widest text-principal">Tu espacio de trabajo</p><h1 class="mt-3 text-3xl font-semibold tracking-tight">Hola, {{ auth()->user()->nombres }}.</h1><p class="mt-2 text-sm text-neutral-500">Consulta el estado de los registros de EPP y ATS.</p></div>
    @can('registros.crear')
        <div class="mb-6"><a class="boton" href="{{ route('registros.create') }}">@include('partials.icono', ['nombre' => 'agregar']) Iniciar registro ATS/EPP</a></div>
    @endcan
    <div class="grid gap-5 sm:grid-cols-3">
        @foreach (['En curso' => $enCurso, 'Finalizados' => $finalizados, 'Pendientes de revisión' => $pendientesRevision] as $etiqueta => $cantidad)
            <div class="tarjeta"><p class="text-sm text-neutral-500">{{ $etiqueta }}</p><p class="mt-4 text-4xl font-semibold tracking-tight">{{ $cantidad }}</p></div>
        @endforeach
    </div>
    @can('usuarios.gestionar')
        <div class="mt-6 grid gap-5 sm:grid-cols-2"><a class="tarjeta transition hover:border-neutral-400" href="{{ route('usuarios.index') }}"><h2 class="font-semibold">Usuarios y autorizaciones <span aria-hidden="true">↗</span></h2><p class="mt-2 text-sm leading-6 text-neutral-500">Administra las cuentas, sus roles y las máquinas autorizadas para cada operador.</p></a><a class="tarjeta transition hover:border-neutral-400" href="{{ route('maquinas.index') }}"><h2 class="font-semibold">Máquinas <span aria-hidden="true">↗</span></h2><p class="mt-2 text-sm leading-6 text-neutral-500">Mantén actualizado el catálogo de equipos y su estado.</p></a></div>
    @endcan
    @if (auth()->user()->hasRole('operador'))
        <section class="tarjeta mt-6"><h2 class="font-semibold">Mis máquinas autorizadas</h2><div class="mt-4 grid gap-3 sm:grid-cols-2">@forelse ($maquinasDisponibles as $maquina)<div class="rounded-md border border-neutral-200 p-4"><p class="font-medium">{{ $maquina->nombre }}</p><p class="mt-1 text-sm text-neutral-500">{{ $maquina->descripcion }}</p></div>@empty<p class="text-sm text-neutral-500">Aún no tienes máquinas activas autorizadas. Comunícate con el administrador.</p>@endforelse</div></section>
    @endif
    <section class="mt-8"><h2 class="mb-4 text-lg font-semibold">Últimos registros</h2><div class="overflow-x-auto rounded-md border border-neutral-200 bg-white"><table class="tabla"><thead><tr><th>Registro</th><th>Operador</th><th>Máquina</th><th>Inicio</th><th>Estado</th></tr></thead><tbody>@forelse ($ultimosRegistros as $registro)<tr><td><a class="text-principal hover:underline" href="{{ route('registros.show', $registro) }}">#{{ $registro->id_registro }}</a></td><td>{{ $registro->operador->nombres }} {{ $registro->operador->apellidos }}</td><td>{{ $registro->maquina->nombre }}</td><td>{{ $registro->fecha_inicio->format('d/m/Y H:i') }}</td><td>@include('partials.estado-registro', ['registro' => $registro])</td></tr>@empty<tr><td colspan="5" class="py-12 text-center text-neutral-500">Todavía no hay registros para mostrar.</td></tr>@endforelse</tbody></table></div></section>
@endsection
