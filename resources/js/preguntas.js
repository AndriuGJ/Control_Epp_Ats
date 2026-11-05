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

    const actualizar = () => {
        const preguntas = filas();
        preguntas.forEach((fila, indice) => {
            fila.querySelector('[data-numero-orden]').textContent = indice + 1;
            fila.querySelector('[data-subir]').disabled = indice === 0;
            fila.querySelector('[data-bajar]').disabled = indice === preguntas.length - 1;
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

    selector.addEventListener('submit', (evento) => {
        if (ordenActual() !== ordenOriginal && !window.confirm('Tienes cambios sin guardar. ¿Quieres cambiar de etapa y descartarlos?')) {
            evento.preventDefault();
            etapa.value = etapaOriginal;
        }
    });
    etapa.addEventListener('change', () => selector.requestSubmit());
    actualizar();
}
