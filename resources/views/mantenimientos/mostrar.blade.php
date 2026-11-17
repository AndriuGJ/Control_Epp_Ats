@extends('layouts.app')

@section('titulo', 'Mantenimiento de '.$maquina->nombre)

@section('contenido')
    <div class="mb-7">
        <a class="inline-flex items-center gap-2 text-sm text-principal hover:underline" href="{{ route('mantenimientos.index') }}">@include('partials.icono', ['nombre' => 'volver']) Volver a mantenimiento</a>
        <h1 class="mt-4 text-2xl font-semibold">{{ $maquina->nombre }}</h1>
        <p class="mt-2 text-sm text-neutral-600">
            Uso acumulado: <strong>@include('partials.duracion-uso', ['minutos' => $resumen['minutos']])</strong>
            · Intervalo: {{ $resumen['limite'] === null ? 'sin configurar' : $maquina->intervalo_mantenimiento_horas.' h' }}
        </p>
        @if ($resumen['pendiente'])
            <span class="insignia insignia-inactiva mt-2">Mantenimiento pendiente</span>
        @endif
        @can('maquinas.gestionar')
            <a class="mt-2 inline-flex text-sm text-principal underline underline-offset-4" href="{{ route('maquinas.edit', $maquina) }}">Editar intervalo</a>
        @endcan
    </div>

    <form method="POST" action="{{ route('mantenimientos.store', $maquina) }}" class="tarjeta max-w-2xl space-y-4">
        @csrf
        <h2 class="text-lg font-semibold">Registrar mantenimiento realizado</h2>
        <div>
            <label class="etiqueta" for="fecha_mantenimiento">Fecha y hora en que terminó</label>
            <input class="campo" type="datetime-local" name="fecha_mantenimiento" id="fecha_mantenimiento" required
                value="{{ old('fecha_mantenimiento', now()->format('Y-m-d\TH:i')) }}" aria-describedby="ayuda-fecha-mantenimiento">
            <p id="ayuda-fecha-mantenimiento" class="mt-2 text-xs text-neutral-500">Hora de Perú. El nuevo conteo empieza desde la fecha y hora que indiques.</p>
        </div>
        <div>
            <label class="etiqueta" for="observaciones">Observaciones (opcional)</label>
            <textarea class="campo" name="observaciones" id="observaciones" rows="3" maxlength="3000">{{ old('observaciones') }}</textarea>
        </div>
        <button class="boton" type="submit">@include('partials.icono', ['nombre' => 'guardar']) Registrar mantenimiento</button>
    </form>

    <h2 class="mb-4 mt-8 text-lg font-semibold">Historial de mantenimiento</h2>
    <div class="overflow-x-auto rounded-md border border-neutral-200 bg-white">
        <table class="tabla">
            <thead><tr><th>Fecha</th><th>Uso del ciclo</th><th>Registrado por</th><th>Observaciones</th></tr></thead>
            <tbody>
                @forelse ($historial as $mantenimiento)
                    <tr>
                        <td class="whitespace-nowrap">{{ $mantenimiento->fecha_mantenimiento->format('d/m/Y H:i') }}</td>
                        <td class="whitespace-nowrap">@include('partials.duracion-uso', ['minutos' => $mantenimiento->minutos_uso_acumulados])</td>
                        <td>{{ $mantenimiento->usuario->nombres }} {{ $mantenimiento->usuario->apellidos }}</td>
                        <td class="whitespace-pre-line">{{ $mantenimiento->observaciones ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-10 text-center text-neutral-500">Esta máquina aún no tiene mantenimientos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $historial->links() }}</div>
@endsection
