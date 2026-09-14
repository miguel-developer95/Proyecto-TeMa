const POLL_INTERVAL_MS = 15000;
const AVISO_SEGUNDOS = 50;

let countdownInterval = null;
let modalVisible = false;

function urlAjax(nombre) {
  return (window.BASE_URL || "") + "/ajax/" + nombre;
}

function verificarSesion() {
  fetch(urlAjax("verificar_sesion.php"))
    .then((res) => res.json())
    .then((data) => {
      if (!data.activa) {
        window.location.href =
          (window.BASE_URL || "") + "/view/login.php?error=expired";
        return;
      }
      if (data.segundos_restantes <= AVISO_SEGUNDOS) {
        mostrarModal(data.segundos_restantes);
      } else {
        ocultarModal();
      }
    })
    .catch((err) => console.error("Error verificando sesión:", err));
}

function mostrarModal(segundosRestantes) {
  const modal = document.getElementById("session-timeout-modal");
  if (!modal) return;
  modal.style.display = "block";

  if (modalVisible) return; // evita reiniciar el contador si ya está visible
  modalVisible = true;

  let restante = segundosRestantes;
  const contador = document.getElementById("session-timeout-countdown");
  if (contador) contador.textContent = restante;

  countdownInterval = setInterval(() => {
    restante--;
    if (contador) contador.textContent = restante;
    if (restante <= 0) {
      clearInterval(countdownInterval);
      window.location.href =
        (window.BASE_URL || "") + "/view/login.php?error=expired";
    }
  }, 1000);
}

function ocultarModal() {
  const modal = document.getElementById("session-timeout-modal");
  if (modal) modal.style.display = "none";
  modalVisible = false;
  if (countdownInterval) clearInterval(countdownInterval);
}

function renovarActividad() {
  fetch(urlAjax("renovar_actividad.php"))
    .then((res) => res.json())
    .then((data) => {
      if (data.ok) ocultarModal();
    });
}

document.addEventListener("DOMContentLoaded", () => {
  const btnContinuar = document.getElementById("btn-continuar-sesion");
  if (btnContinuar) {
    btnContinuar.addEventListener("click", renovarActividad);
  }
  setInterval(verificarSesion, POLL_INTERVAL_MS);
});
