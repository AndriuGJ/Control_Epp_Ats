const validacion = document.querySelector('[data-estado-epp]');

// Consultar solo en espera: recargar un ATS abierto podría borrar respuestas y firma.
if (validacion?.dataset.estadoEpp === 'Pendiente') {
    const consultar = async () => {
        try {
            const respuesta = await fetch(validacion.dataset.urlEstadoEpp, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });
            if (respuesta.ok && (await respuesta.json()).estado !== 'Pendiente') {
                window.location.reload();
                return;
            }
        } catch {
            // El operador también puede pulsar «Actualizar estado».
        }
        window.setTimeout(consultar, 8000);
    };
    window.setTimeout(consultar, 8000);
}
