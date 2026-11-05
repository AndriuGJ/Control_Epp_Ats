document.querySelectorAll('[data-firma]').forEach((recuadro) => {
    const lienzo = recuadro.querySelector('[data-lienzo-firma]');
    const valor = recuadro.querySelector('[data-valor-firma]');
    const limpiar = recuadro.querySelector('[data-limpiar-firma]');
    const error = recuadro.querySelector('[data-error-firma]');
    const formulario = recuadro.closest('form');
    const contexto = lienzo.getContext('2d');
    let dibujando = false;
    let puntero = null;

    contexto.strokeStyle = '#0f172a';
    contexto.fillStyle = '#0f172a';
    contexto.lineWidth = 3;
    contexto.lineCap = 'round';
    contexto.lineJoin = 'round';

    const posicion = (evento) => {
        const limites = lienzo.getBoundingClientRect();
        return {
            x: (evento.clientX - limites.left) * lienzo.width / limites.width,
            y: (evento.clientY - limites.top) * lienzo.height / limites.height,
        };
    };

    lienzo.addEventListener('pointerdown', (evento) => {
        if (dibujando || (evento.pointerType === 'mouse' && evento.button !== 0)) return;
        evento.preventDefault();
        const punto = posicion(evento);
        dibujando = true;
        puntero = evento.pointerId;
        lienzo.setPointerCapture(puntero);
        contexto.beginPath();
        contexto.arc(punto.x, punto.y, 1.5, 0, Math.PI * 2);
        contexto.fill();
        contexto.beginPath();
        contexto.moveTo(punto.x, punto.y);
        error.hidden = true;
    });

    lienzo.addEventListener('pointermove', (evento) => {
        if (!dibujando || evento.pointerId !== puntero) return;
        const punto = posicion(evento);
        contexto.lineTo(punto.x, punto.y);
        contexto.stroke();
    });

    const terminar = (evento) => {
        if (!dibujando || evento.pointerId !== puntero) return;
        dibujando = false;
        puntero = null;
        valor.value = lienzo.toDataURL('image/png');
    };
    lienzo.addEventListener('pointerup', terminar);
    lienzo.addEventListener('pointercancel', terminar);
    lienzo.addEventListener('lostpointercapture', terminar);

    limpiar.addEventListener('click', () => {
        contexto.clearRect(0, 0, lienzo.width, lienzo.height);
        valor.value = '';
        dibujando = false;
        puntero = null;
        error.hidden = true;
    });
    formulario.addEventListener('submit', (evento) => {
        if (!valor.value) {
            evento.preventDefault();
            error.hidden = false;
            lienzo.focus();
        }
    });
});
