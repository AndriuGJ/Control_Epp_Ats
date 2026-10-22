const selectorRol = document.querySelector('[data-selector-rol]');
const autorizaciones = document.querySelector('[data-autorizaciones]');
if (selectorRol && autorizaciones) {
    const actualizarAutorizaciones = () => {
        const esOperador = selectorRol.value === 'operador';
        autorizaciones.hidden = !esOperador;
        autorizaciones.querySelectorAll('input').forEach((input) => { input.disabled = !esOperador; });
    };
    selectorRol.addEventListener('change', actualizarAutorizaciones);
    actualizarAutorizaciones();
}
