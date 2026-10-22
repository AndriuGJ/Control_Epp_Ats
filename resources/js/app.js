import './firma';

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

const menuLateral = document.getElementById('menu-lateral');
const abrirMenu = document.querySelector('[data-abrir-menu]');
const fondoMenu = document.querySelector('[data-fondo-menu]');
const contenidoApp = document.querySelector('[data-contenido-app]');

if (menuLateral && abrirMenu && fondoMenu && contenidoApp) {
    const escritorio = window.matchMedia('(min-width: 64rem)');
    let menuAbierto = false;

    const actualizarMenu = (abrir) => {
        const estabaAbierto = menuAbierto;
        menuAbierto = abrir && !escritorio.matches;
        const visible = escritorio.matches || menuAbierto;

        menuLateral.classList.toggle('-translate-x-full', !visible);
        menuLateral.inert = !visible;
        menuLateral.setAttribute('aria-hidden', String(!visible));
        abrirMenu.setAttribute('aria-expanded', String(menuAbierto));
        fondoMenu.hidden = !menuAbierto;
        contenidoApp.inert = menuAbierto;
        document.body.classList.toggle('overflow-hidden', menuAbierto);

        if (menuAbierto) {
            menuLateral.setAttribute('role', 'dialog');
            menuLateral.setAttribute('aria-modal', 'true');
            menuLateral.querySelector('[data-cerrar-menu]').focus();
        } else {
            menuLateral.removeAttribute('role');
            menuLateral.removeAttribute('aria-modal');
            if (estabaAbierto && !escritorio.matches) {
                abrirMenu.focus();
            }
        }
    };

    abrirMenu.addEventListener('click', () => actualizarMenu(true));
    document.querySelectorAll('[data-cerrar-menu]').forEach((boton) => {
        boton.addEventListener('click', () => actualizarMenu(false));
    });
    menuLateral.querySelectorAll('a').forEach((enlace) => {
        enlace.addEventListener('click', () => actualizarMenu(false));
    });
    escritorio.addEventListener('change', () => actualizarMenu(false));

    document.addEventListener('keydown', (evento) => {
        if (!menuAbierto) return;
        if (evento.key === 'Escape') {
            actualizarMenu(false);
        }
        if (evento.key === 'Tab') {
            const elementos = [...menuLateral.querySelectorAll('a[href], button:not([disabled])')]
                .filter((elemento) => elemento.getClientRects().length > 0);
            const primero = elementos[0];
            const ultimo = elementos[elementos.length - 1];
            if (evento.shiftKey && document.activeElement === primero) {
                evento.preventDefault();
                ultimo.focus();
            } else if (!evento.shiftKey && document.activeElement === ultimo) {
                evento.preventDefault();
                primero.focus();
            }
        }
    });

    actualizarMenu(false);
}
