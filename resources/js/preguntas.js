const formulario = document.querySelector('[data-orden-preguntas]');

if (formulario) {
    const lista = formulario.querySelector('[data-lista-orden]');
    const guardar = formulario.querySelector('[data-guardar-orden]');
    const estado = formulario.querySelector('[data-estado-orden]');
    const selector = document.querySelector('[data-selector-etapa]');
    const etapa = selector.querySelector('[name="etapa"]');
    const etapaOriginal = etapa.value;
    const filas = () => [...lista.querySelectorAll('[data-pregunta-orden]')];
    const ordenActual = () => filas().map((fila) => fila.querySelector('[name="orden[]"]').value).join(',');
    const ordenOriginal = ordenActual();
    let arrastre = null;

    const actualizar = () => {
        const preguntas = filas();
        preguntas.forEach((fila, indice) => {
            fila.querySelector('[data-numero-orden]').textContent = indice + 1;
            fila.querySelector('[data-subir]').disabled = indice === 0;
            fila.querySelector('[data-bajar]').disabled = indice === preguntas.length - 1;
            fila.querySelector('[data-arrastrar]').disabled = preguntas.length < 2;
        });
        if (guardar) {
            const modificado = ordenActual() !== ordenOriginal;
            guardar.disabled = !modificado;
            estado.textContent = modificado
                ? 'Orden modificado. Pulsa Guardar orden para aplicar los cambios.'
                : 'El orden guardado se muestra en la lista.';
        }
    };

    lista.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-subir], [data-bajar]');
        if (!boton || boton.disabled) return;
        const fila = boton.closest('[data-pregunta-orden]');
        if (boton.hasAttribute('data-subir')) {
            fila.previousElementSibling.before(fila);
        } else {
            fila.nextElementSibling.after(fila);
        }
        actualizar();
        const opuesto = boton.hasAttribute('data-subir') ? '[data-bajar]' : '[data-subir]';
        (boton.disabled ? fila.querySelector(opuesto) : boton).focus();
    });

    lista.addEventListener('pointerdown', (evento) => {
        const boton = evento.target.closest('[data-arrastrar]');
        if (!boton || boton.disabled || !evento.isPrimary || evento.button !== 0) return;
        evento.preventDefault();
        const fila = boton.closest('[data-pregunta-orden]');
        arrastre = { fila, boton, siguiente: fila.nextElementSibling, puntero: evento.pointerId };
        lista.setPointerCapture(evento.pointerId);
        fila.classList.add('border-acento', 'bg-blue-50', 'select-none');
        boton.focus();
    });

    lista.addEventListener('pointermove', (evento) => {
        if (!arrastre || evento.pointerId !== arrastre.puntero) return;
        const destino = filas().find((fila) => fila !== arrastre.fila
            && evento.clientY < fila.getBoundingClientRect().top + fila.offsetHeight / 2);
        lista.insertBefore(arrastre.fila, destino ?? null);
        if (evento.clientY < 80) window.scrollBy(0, -16);
        if (evento.clientY > window.innerHeight - 80) window.scrollBy(0, 16);
    });

    const terminarArrastre = (evento) => {
        if (!arrastre || evento.pointerId !== arrastre.puntero) return;
        const { fila, boton, siguiente, puntero } = arrastre;
        arrastre = null;
        if (evento.type === 'pointercancel') lista.insertBefore(fila, siguiente);
        fila.classList.remove('border-acento', 'bg-blue-50', 'select-none');
        if (lista.hasPointerCapture(puntero)) lista.releasePointerCapture(puntero);
        actualizar();
        boton.focus();
    };
    lista.addEventListener('pointerup', terminarArrastre);
    lista.addEventListener('pointercancel', terminarArrastre);
    lista.addEventListener('lostpointercapture', terminarArrastre);

    selector.addEventListener('submit', (evento) => {
        if (ordenActual() !== ordenOriginal && !window.confirm('Tienes cambios sin guardar. ¿Quieres cambiar de etapa y descartarlos?')) {
            evento.preventDefault();
            etapa.value = etapaOriginal;
        }
    });
    etapa.addEventListener('change', () => selector.requestSubmit());
    actualizar();
}
