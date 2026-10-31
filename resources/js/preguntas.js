const formulario = document.querySelector('[data-formulario-pregunta]');

if (formulario) {
    const etapa = formulario.querySelector('[name="etapa"]');
    const posicion = formulario.querySelector('[name="posicion"]');
    const siguientes = JSON.parse(formulario.dataset.siguientesPosiciones);

    etapa.addEventListener('change', () => {
        posicion.value = etapa.value === formulario.dataset.etapaOriginal
            ? formulario.dataset.posicionOriginal
            : siguientes[etapa.value];
    });
}
