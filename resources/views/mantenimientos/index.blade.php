@extends('layouts.app')

@section('titulo', 'Mantenimiento')

@section('contenido')
    <div class="mb-7">
        <h1 class="text-2xl font-semibold">Mantenimiento</h1>
        <p class="mt-2 text-sm text-neutral-500">Horas de uso por máquina, calculadas con los ATS finalizados desde el último mantenimiento.</p>
    </div>

    <div class="overflow-x-auto rounded-md border border-neutral-200 bg-white">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Máquina</th>
                    <th>Uso acumulado</th>
                    <th>Intervalo</th>
                    <th>Tiempo restante</th>
                    <th>Estado</th>
                    <th><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($maquinas as $maquina)
                    @php($uso = $resumenes[$maquina->getKey()])
                    <tr>
                        <td class="font-medium">
                            {{ $maquina->nombre }}
                            @unless ($maquina->estado)
                                <span class="mt-1 block text-xs text-neutral-500">Inactiva</span>
                            @endunless
                        </td>
                        <td class="whitespace-nowrap">@include('partials.duracion-uso', ['minutos' => $uso['minutos']])</td>
                        <td class="whitespace-nowrap">{{ $uso['limite'] === null ? '—' : $maquina->intervalo_mantenimiento_horas.' h' }}</td>
                        <td class="whitespace-nowrap">
                            @if ($uso['restantes'] !== null)
                                @include('partials.duracion-uso', ['minutos' => $uso['restantes']])
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($uso['limite'] === null)
                                <span class="text-neutral-500">Sin intervalo</span>
                            @else
                                <span class="insignia {{ $uso['pendiente'] ? 'insignia-inactiva' : 'insignia-activa' }}">
                                    {{ $uso['pendiente'] ? 'Mantenimiento pendiente' : 'Al día' }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <a class="font-medium text-principal underline underline-offset-4" href="{{ route('mantenimientos.show', $maquina) }}">Ver y registrar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-neutral-500">Aún no hay máquinas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $maquinas->links() }}</div>
@endsection
