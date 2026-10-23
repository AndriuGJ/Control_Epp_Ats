document.querySelectorAll('[data-camara]').forEach((recuadro) => {
    const video = recuadro.querySelector('[data-video]');
    const abrir = recuadro.querySelector('[data-abrir-camara]');
    const tomar = recuadro.querySelector('[data-tomar-foto]');
    const repetir = recuadro.querySelector('[data-repetir-foto]');
    const vista = recuadro.querySelector('[data-vista-captura]');
    const imagen = recuadro.querySelector('[data-imagen-captura]');
    const fecha = recuadro.querySelector('[data-fecha-captura]');
    const valor = recuadro.querySelector('[data-captura-epp]');
    const error = recuadro.querySelector('[data-error-camara]');
    const estado = recuadro.querySelector('[data-estado-camara]');
    const formulario = recuadro.closest('form');
    let flujo = null;
    let urlImagen = null;
    let procesando = false;
    let abandonada = false;

    const detener = () => {
        flujo?.getTracks().forEach((pista) => pista.stop());
        flujo = null;
        video.srcObject = null;
    };
    const mostrarError = (mensaje) => {
        error.textContent = mensaje;
        error.hidden = false;
    };
    const abrirCamara = async () => {
        if (procesando) return;
        if (!window.isSecureContext) {
            mostrarError('La cámara requiere HTTPS. Abre la web mediante una conexión segura y permite el uso de la cámara.');
            return;
        }
        if (!navigator.mediaDevices?.getUserMedia) {
            mostrarError('Este navegador no permite acceder a la cámara.');
            return;
        }
        procesando = true;
        abrir.disabled = true;
        repetir.disabled = true;
        error.hidden = true;
        estado.textContent = 'Abriendo cámara…';
        detener();
        try {
            flujo = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } }, audio: false });
            if (abandonada) { detener(); return; }
            video.srcObject = flujo;
            video.hidden = false;
            await video.play();
            valor.value = '';
            vista.hidden = true;
            if (urlImagen) URL.revokeObjectURL(urlImagen);
            urlImagen = null;
            repetir.hidden = true;
            abrir.hidden = true;
            tomar.hidden = false;
            tomar.disabled = !video.videoWidth;
            estado.textContent = 'Ubícate dentro de la imagen y toma la fotografía usando tu EPP.';
        } catch (excepcion) {
            detener();
            video.hidden = true;
            const mensajes = {
                NotAllowedError: 'Permite el acceso a la cámara desde la configuración del navegador.',
                NotFoundError: 'No se encontró una cámara disponible en este dispositivo.',
                NotReadableError: 'No se pudo abrir la cámara. Cierra otras aplicaciones que la estén usando.',
            };
            mostrarError(mensajes[excepcion.name] ?? 'No se pudo iniciar la cámara. Inténtalo nuevamente.');
            abrir.hidden = false;
            estado.textContent = '';
        } finally {
            procesando = false;
            abrir.disabled = false;
            repetir.disabled = false;
        }
    };
    video.addEventListener('loadeddata', () => { tomar.disabled = !video.videoWidth; });
    abrir.addEventListener('click', abrirCamara);
    repetir.addEventListener('click', abrirCamara);

    tomar.addEventListener('click', async () => {
        if (procesando || !flujo || !video.videoWidth) return;
        procesando = true;
        tomar.disabled = true;
        error.hidden = true;
        estado.textContent = 'Guardando captura…';
        try {
            const lienzo = document.createElement('canvas');
            const escala = Math.min(1, 1600 / Math.max(video.videoWidth, video.videoHeight));
            lienzo.width = Math.round(video.videoWidth * escala);
            lienzo.height = Math.round(video.videoHeight * escala);
            lienzo.getContext('2d').drawImage(video, 0, 0, lienzo.width, lienzo.height);
            const archivo = await new Promise((resolver) => lienzo.toBlob(resolver, 'image/jpeg', 0.85));
            if (!archivo) throw new Error('No se pudo generar la fotografía. Vuelve a tomarla.');
            const datos = new FormData();
            datos.append('fotografia', archivo, 'captura.jpg');
            const respuesta = await fetch(recuadro.dataset.urlCaptura, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': formulario.querySelector('[name="_token"]').value },
                body: datos,
            });
            if (respuesta.status === 419 || respuesta.status === 401) throw new Error('La sesión venció. Recarga la página e inicia sesión nuevamente.');
            const resultado = await respuesta.json();
            if (!respuesta.ok) throw new Error(Object.values(resultado.errors ?? {}).flat()[0] ?? 'No se pudo guardar la captura. Inténtalo nuevamente.');
            if (urlImagen) URL.revokeObjectURL(urlImagen);
            urlImagen = URL.createObjectURL(archivo);
            imagen.src = urlImagen;
            fecha.textContent = `${resultado.fecha_hora} · Perú`;
            valor.value = resultado.captura;
            vista.hidden = false;
            video.hidden = true;
            tomar.hidden = true;
            repetir.hidden = false;
            estado.textContent = 'Fotografía capturada. Puedes responder el ATS y firmar.';
            detener();
        } catch (excepcion) {
            const mensaje = ['TypeError', 'SyntaxError'].includes(excepcion.name)
                ? 'No se pudo comunicar con el servidor. Revisa tu conexión e inténtalo nuevamente.'
                : excepcion.message;
            mostrarError(mensaje || 'No se pudo capturar la fotografía. Inténtalo nuevamente.');
            estado.textContent = '';
        } finally {
            procesando = false;
            tomar.disabled = false;
        }
    });

    formulario.addEventListener('submit', (evento) => {
        if (!valor.value || procesando) {
            evento.preventDefault();
            mostrarError('Toma y guarda tu fotografía con la cámara antes de iniciar el registro.');
            abrir.focus();
        }
    });
    window.addEventListener('pagehide', () => {
        abandonada = true;
        detener();
        if (urlImagen) URL.revokeObjectURL(urlImagen);
    });
    window.addEventListener('pageshow', (evento) => {
        abandonada = false;
        if (evento.persisted) {
            valor.value = '';
            vista.hidden = true;
            video.hidden = true;
            tomar.hidden = true;
            repetir.hidden = true;
            abrir.hidden = false;
            abrir.disabled = false;
            procesando = false;
            estado.textContent = 'Abre la cámara para tomar una nueva fotografía.';
        }
    });
});
