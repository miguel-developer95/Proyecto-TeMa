(function () {
    const URL_VERIFICAR = '/Proyecto-TeMa/ajax/verificar_sesion.php';
    const URL_RENOVAR    = '/Proyecto-TeMa/ajax/renovar_actividad.php';
    const URL_LOGIN       = '/Proyecto-TeMa/view/login.php?sesion_expirada=1';

    const INTERVALO_VERIFICACION_MS = 15000; // consulta al servidor cada 15s
    const UMBRAL_AVISO_SEG          = 60;   // avisar cuando falte 1 min
    const THROTTLE_HEARTBEAT_MS     = 20000; // no renovar más de 1 vez/20s

    let ultimoHeartbeat = 0;
    let modalVisible = false;
    let countdownInterval = null;

    function crearModal() {
        const modal = document.createElement('div');
        modal.id = 'modal-sesion-expira';
        modal.style.cssText = `
            position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            display: none; align-items: center; justify-content: center; z-index: 9999;
        `;
        modal.innerHTML = `
            <div style="background:#fff; padding:24px; border-radius:8px; max-width:380px; text-align:center; font-family:sans-serif; box-shadow:0 4px 20px rgba(0,0,0,.3);">
                <h3 style="margin-top:0;">Tu sesión está por expirar</h3>
                <p>Por inactividad, tu sesión se cerrará en
                    <strong><span id="contador-sesion">--</span></strong> segundos.
                </p>
                <button id="btn-continuar-sesion" style="padding:8px 16px; background:#2e7d32; color:#fff; border:none; border-radius:4px; cursor:pointer;">
                    Continuar sesión
                </button>
            </div>
        `;
        document.body.appendChild(modal);
        document.getElementById('btn-continuar-sesion').addEventListener('click', () => {
            renovarActividad(true);
            ocultarModal();
        });
        return modal;
    }

    function mostrarModal(segundosRestantes) {
        const modal = document.getElementById('modal-sesion-expira') || crearModal();
        modal.style.display = 'flex';

        // Si ya estaba visible, NO reiniciamos el interval — solo permitimos
        // que verificarSesion() resincronice el valor si hay mucha diferencia.
        if (modalVisible) {
            return;
        }

        modalVisible = true;
        let restante = segundosRestantes;
        actualizarContador(restante);

        clearInterval(countdownInterval);
        countdownInterval = setInterval(() => {
            restante--;
            actualizarContador(restante);
            if (restante <= 0) {
                clearInterval(countdownInterval);
                window.location.href = URL_LOGIN;
            }
        }, 1000);
    }

    function actualizarContador(segundos) {
        const span = document.getElementById('contador-sesion');
        if (span) span.textContent = Math.max(0, segundos);
    }

    function ocultarModal() {
        modalVisible = false;
        clearInterval(countdownInterval);
        const modal = document.getElementById('modal-sesion-expira');
        if (modal) modal.style.display = 'none';
    }

    function renovarActividad(forzado = false) {
        const ahora = Date.now();
        if (!forzado && (ahora - ultimoHeartbeat) < THROTTLE_HEARTBEAT_MS) return;
        ultimoHeartbeat = ahora;

        fetch(URL_RENOVAR, { method: 'POST', credentials: 'same-origin' })
            .catch(err => console.error('Error renovando actividad:', err));
    }

    function verificarSesion() {
        fetch(URL_VERIFICAR, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (!data.activa) {
                    window.location.href = URL_LOGIN;
                    return;
                }

                if (data.segundosRestantes <= UMBRAL_AVISO_SEG) {
                    if (!modalVisible) {
                        // Primera vez que entramos en zona de aviso: mostrar modal
                        mostrarModal(data.segundosRestantes);
                    } else {
                        // Ya visible: resincronizar SOLO si el desfase es grande
                        // (ej. la pestaña estuvo en segundo plano y el timer se atrasó)
                        const spanActual = parseInt(
                            document.getElementById('contador-sesion').textContent, 10
                        );
                        if (Math.abs(spanActual - data.segundosRestantes) > 5) {
                            actualizarContador(data.segundosRestantes);
                            // reiniciamos el interval con el valor correcto del servidor
                            clearInterval(countdownInterval);
                            let restante = data.segundosRestantes;
                            countdownInterval = setInterval(() => {
                                restante--;
                                actualizarContador(restante);
                                if (restante <= 0) {
                                    clearInterval(countdownInterval);
                                    window.location.href = URL_LOGIN;
                                }
                            }, 1000);
                        }
                    }
                } else if (modalVisible) {
                    ocultarModal();
                }
            })
            .catch(err => console.error('Error verificando sesión:', err));
    }

    // Actividad real del usuario: clics, mouse, teclado, scroll
    ['click', 'mousemove', 'keydown', 'scroll'].forEach(evento => {
        document.addEventListener(evento, () => renovarActividad(false), { passive: true });
    });

    setInterval(verificarSesion, INTERVALO_VERIFICACION_MS);
    verificarSesion(); // primera verificación al cargar
})();