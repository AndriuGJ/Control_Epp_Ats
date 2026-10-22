<section class="tarjeta space-y-4" data-firma>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-semibold">{{ $tituloFirma }}</h2>
        <button type="button" class="boton-secundario" data-limpiar-firma>Limpiar firma</button>
    </div>
    <p class="text-sm text-slate-500">Firma dentro del recuadro usando el dedo o el mouse.</p>
    <canvas width="800" height="240" class="block w-full touch-none rounded-md border border-slate-300 bg-white" tabindex="0" aria-label="Recuadro para dibujar tu firma" data-lienzo-firma></canvas>
    <input type="hidden" name="{{ $campoFirma }}" data-valor-firma>
    <p class="text-sm text-red-700" role="alert" data-error-firma hidden>Dibuja tu firma antes de guardar.</p>
</section>
