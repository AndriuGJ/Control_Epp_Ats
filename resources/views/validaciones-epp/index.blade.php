@extends('layouts.app')

@section('titulo', 'Validación de EPP')

@section('contenido')
    <div class="mb-7 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Validación de EPP</h1>
            <p class="mt-2 text-sm text-neutral-500">Revisa el equipo requerido para la máquina antes de habilitar el ATS inicial. Si falta un elemento o no se distingue, pide otra foto e indica por qué.</p>
        </div>
        <a class="boton-secundario" href="{{ route('validaciones-epp.index') }}">Actualizar pendientes</a>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        @forelse ($pendientes as $validacion)
            <article class="tarjeta space-y-4">
                <div>
                    <h2 class="font-semibold">{{ $validacion->operador->nombres }} {{ $validacion->operador->apellidos }}</h2>
                    <p class="text-sm text-neutral-600">{{ $validacion->maquina->nombre }} · {{ $validacion->fecha_fotografia->format('d/m/Y H:i:s') }}</p>
                </div>
                <a href="{{ route('validaciones-epp.fotografia', $validacion) }}" target="_blank" rel="noopener">
                    <img class="max-h-80 w-full rounded-md border border-neutral-200 object-contain" loading="lazy"
                        src="{{ route('validaciones-epp.fotografia', $validacion) }}" alt="Foto del operador con su equipo de protección">
                </a>
                <form method="POST" action="{{ route('validaciones-epp.resolver', $validacion) }}" class="space-y-3">
                    @csrf
                    <label class="etiqueta" for="motivo-{{ $validacion->getKey() }}">Motivo (obligatorio si pides otra foto)</label>
                    <textarea class="campo" id="motivo-{{ $validacion->getKey() }}" name="motivo_rechazo" maxlength="500" rows="2" placeholder="Ejemplo: no se distinguen los guantes"></textarea>
                    <div class="flex flex-wrap gap-3">
                        <button class="boton" type="submit" name="decision" value="aprobar">Aprobar EPP</button>
                        <button class="boton-secundario" type="submit" name="decision" value="rechazar">Pedir otra foto</button>
                    </div>
                </form>
            </article>
        @empty
            <p class="tarjeta text-sm text-neutral-600">No hay fotos pendientes de revisión.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $pendientes->links() }}</div>
@endsection
