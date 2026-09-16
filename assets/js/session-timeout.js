(function () {
    const URL_VERIFICAR = '/Proyecto-TeMa/ajax/verificar_sesion.php';
    const URL_RENOVAR   = '/Proyecto-TeMa/ajax/renovar_actividad.php';
    const URL_LOGOUT    = '/Proyecto-TeMa/index.php?action=logout&sesion_expirada=1';

    let tiempoMaximoSegundos = 1800; // 30 minutos
    let umbralAvisoSegundos = 120;    // Avisar cuando falte 20 segundos
    let throttleHeartbeatMs = 15000; // Throttle de heartbeat a 5s
    let ultimoHeartbeat = Date.now();
    let ultimaActividadUsuario = Date.now();
    let modalVisible = false;
    let countdownInterval = null;
    let checkInterval = null;
    let isTerminating = false; 

    function crearModal() {
        let modal = document.getElementById('modal-sesion-expira');
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = 'modal-sesion-expira';
        modal.style.cssText = `
            position: fixed; inset: 0; background: rgba(0,0,0,0.55);
            display: none; align-items: center; justify-content: center; z-index: 999999;
            backdrop-filter: blur(2px);
        `;
        modal.innerHTML = `
            <div style="background:#ffffff; padding:28px 24px; border-radius:18px; max-width:400px; width:90%; text-align:center; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; box-shadow:0 12px 35px rgba(0,0,0,.25); border:1.5px solid #fce4ec;">
                <div style="width:54px; height:54px; border-radius:50%; background:#fff0f6; color:#e63c82; display:inline-flex; align-items:center; justify-content:center; font-size:24px; margin-bottom:14px; box-shadow:0 3px 10px rgba(230,60,130,0.15);">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <h3 style="margin:0 0 8px; color:#2b3a55; font-size:18px; font-weight:700;">Tu sesión está por expirar</h3>
                <p style="color:#666666; font-size:14px; margin:0 0 16px; line-height:1.5;">
                    Por inactividad, el sistema cerrará tu sesión automáticamente en:
                </p>
                <div style="margin-bottom:20px;">
                    <span id="contador-sesion" style="font-size:32px; font-weight:800; color:#e63c82; font-variant-numeric:tabular-nums;">--</span>
                    <span style="font-size:14px; font-weight:600; color:#888888;"> segundos</span>
                </div>
                <button id="btn-continuar-sesion" style="padding:10px 24px; background:#e63c82; color:#ffffff; border:none; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; box-shadow:0 4px 12px rgba(230,60,130,0.3); transition:all 0.2s ease;">
                    <i class="fa-solid fa-rotate-right" style="margin-right:6px;"></i> Continuar Sesión
                </button>
            </div>
        `;
        document.body.appendChild(modal);

        document.getElementById('btn-continuar-sesion').addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            renovarActividad(true);
            ocultarModal();
        });

        return modal;
    }

    function cerrarSesion() {
        if (isTerminating) return;
        isTerminating = true;
        if (countdownInterval) clearInterval(countdownInterval);
        if (checkInterval) clearInterval(checkInterval);
        window.location.href = URL_LOGOUT;
    }

    function mostrarModal(segundosRestantes) {
        if (isTerminating) return;
        const modal = crearModal();
        modal.style.display = 'flex';

        if (modalVisible) {
            return;
        }

        modalVisible = true;
        let restante = Math.max(1, Math.round(segundosRestantes));
        actualizarContador(restante);

        if (countdownInterval) clearInterval(countdownInterval);
        countdownInterval = setInterval(() => {
            restante--;
            actualizarContador(restante);
            if (restante <= 0) {
                clearInterval(countdownInterval);
                cerrarSesion();
            }
        }, 1000);
    }

    function actualizarContador(segundos) {
        const span = document.getElementById('contador-sesion');
        if (span) span.textContent = Math.max(0, segundos);
    }

    function ocultarModal() {
        modalVisible = false;
        if (countdownInterval) clearInterval(countdownInterval);
        const modal = document.getElementById('modal-sesion-expira');
        if (modal) modal.style.display = 'none';
    }

    function renovarActividad(forzado = false) {
        if (isTerminating) return;
        const ahora = Date.now();
        if (!forzado && (ahora - ultimoHeartbeat) < throttleHeartbeatMs) return;
        ultimoHeartbeat = ahora;

        fetch(URL_RENOVAR, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(res => {
            if (res && res.ok) {
                if (modalVisible && forzado) {
                    ocultarModal();
                }
            } else if (res && res.expirada) {
                cerrarSesion();
            }
        })
        .catch(err => console.error('Error al renovar actividad:', err));
    }

    function verificarSesion() {
        if (isTerminating) return;

        fetch(URL_VERIFICAR, {
            credentials: 'same-origin',
            cache: 'no-store'
        })
        .then(res => res.json())
        .then(data => {
            if (!data.activa) {
                cerrarSesion();
                return;
            }

            if (data.tiempoMaximo) {
                tiempoMaximoSegundos = Number(data.tiempoMaximo);
                throttleHeartbeatMs = Math.max(2000, Math.min(15000, Math.floor(tiempoMaximoSegundos * 1000 / 4)));
            }
            if (data.umbralAviso) {
                umbralAvisoSegundos = Number(data.umbralAviso);
            }

            const restantes = Number(data.segundosRestantes);

            // Solo mostrar modal si el tiempo restante es positivo y está dentro del umbral de aviso
            if (restantes > 0 && restantes <= umbralAvisoSegundos) {
                // Si el usuario interactuó localmente en los últimos 5 segundos, renovar de inmediato
                const segundosInactivoLocal = Math.floor((Date.now() - ultimaActividadUsuario) / 1000);
                if (segundosInactivoLocal < 5) {
                    renovarActividad(true);
                    if (modalVisible) ocultarModal();
                    return;
                }
                mostrarModal(restantes);
            } else if (modalVisible && restantes > umbralAvisoSegundos) {
                ocultarModal();
            }
        })
        .catch(err => console.error('Error al verificar sesión:', err));
    }

    // Escuchar actividad real del usuario
    ['click', 'mousemove', 'keydown', 'scroll', 'touchstart'].forEach(evento => {
        document.addEventListener(evento, () => {
            ultimaActividadUsuario = Date.now();
            if (!modalVisible) {
                renovarActividad(false);
            }
        }, { passive: true });
    });

    const INTERVALO_VERIFICACION_MS = 4000;
    checkInterval = setInterval(verificarSesion, INTERVALO_VERIFICACION_MS);
    verificarSesion();
})();