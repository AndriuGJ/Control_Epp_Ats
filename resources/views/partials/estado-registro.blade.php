<div class="flex flex-wrap gap-2">
    <span class="insignia {{ $registro->estado === 'En curso' ? 'insignia-pendiente' : 'insignia-finalizado' }}">{{ $registro->estado }}</span>
    @if ($registro->estado === 'Finalizado')
        <span class="insignia {{ $registro->fecha_revision ? 'insignia-activa' : 'insignia-pendiente' }}">{{ $registro->fecha_revision ? 'Revisado' : 'Pendiente de revisión' }}</span>
    @endif
</div>
