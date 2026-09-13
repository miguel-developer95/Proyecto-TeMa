<?php if (isset($_GET['sesion_expirada'])): ?>
    <div class="alerta alerta-info">
        Tu sesión se cerró automáticamente por inactividad. Por favor, inicia sesión nuevamente.
    </div>
<?php endif; ?>

<?php
date_default_timezone_set('America/Bogota');
// Deshabilitar la memoria caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

if (session_status() === PHP_SESSION_NONE) session_start();

// SI YA TIENE SESIÓN ACTIVA, REDIRIGIR AL DASHBOARD
if (isset($_SESSION['user'])) {
    header("Location: /Proyecto-TeMa/view/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentaciones Marlly - Iniciar Sesión</title>
    <link rel="stylesheet" href="/Proyecto-TeMa/public/styles/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

    <div class="login-wrapper">
        <div class="login-card">

            <img src="/Proyecto-TeMa/public/logo.png" alt="Logo Tentaciones Marlly" class="shop-logo">

            <p class="tagline">MINI TIENDA DE CONSUMO DIARIO</p>

            <h2 class="welcome">Bienvenido</h2>
            <p class="subtitle">Ingresa para continuar</p>

            <!-- Alertas dinámicas -->
            <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_credentials'): ?>
                <p
                    style="color: #c62828; background-color: #ffebee; padding: 10px; border-radius: 5px; font-size: 14px; text-align: center;">
                    Usuario o contraseña incorrectos.
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'inactive'): ?>
                <p
                    style="color: #e65100; background-color: #fff3e0; padding: 10px; border-radius: 5px; font-size: 14px; text-align: center;">
                    Tu cuenta está inactiva. Contacta al administrador para más información.
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'locked'): ?>
                <p id="alertaBloqueo" data-segundos="<?= (int)($_GET['segundos'] ?? 60) ?>"
                    style="color: #e65100; background-color: #fff3e0; padding: 10px; border-radius: 5px; font-size: 14px; text-align: center;">
                    Cuenta bloqueada temporalmente. Intenta de nuevo en <span id="segundosRestantes"><?= (int)($_GET['segundos'] ?? 60) ?></span> segundos.
                </p>
            <?php endif; ?>

            <!-- Formulario configurado para el controlador MVC -->
            <form action="/Proyecto-TeMa/index.php" method="POST">
                <input type="hidden" name="action" value="login">

                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Usuario o Correo" required>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Contraseña" required>
                    <i class="fa-solid fa-eye toggle-eye" id="toggleEye"></i>
                </div>

                <a href="recuperar_contra.php" class="forgot-link">¿Olvidaste tu contraseña?</a>

                <button type="submit">Iniciar Sesión</button>
            </form>

            <p class="register-link" style="margin-top: 15px; font-size: 14px; color: #888;">
                ¿No tienes cuenta? <a href="/Proyecto-TeMa/view/register.php"
                    style="color: #e63c82; text-decoration: none; font-weight: 600;">Regístrate</a>
            </p>

        </div>
    </div>

    <script>
        const alertaBloqueo = document.getElementById('alertaBloqueo');

        if (alertaBloqueo) {
            let segundos = parseInt(alertaBloqueo.dataset.segundos, 10);
            const spanSegundos = document.getElementById('segundosRestantes');
            const boton = document.querySelector('form button[type="submit"]');
            const inputs = document.querySelectorAll('form input');

            // Deshabilitar el formulario mientras dura el bloqueo
            if (boton) boton.disabled = true;
            inputs.forEach(input => input.disabled = true);

            const intervalo = setInterval(() => {
                segundos--;

                if (segundos <= 0) {
                    clearInterval(intervalo);
                    // Recarga la página para limpiar el error y reactivar el formulario
                    window.location.href = '/Proyecto-TeMa/view/login.php';
                    return;
                }

                spanSegundos.textContent = segundos;
            }, 1000);
        }

        const toggleEye = document.getElementById('toggleEye');
        const password = document.getElementById('password');

        toggleEye.addEventListener('click', () => {
            const isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            toggleEye.classList.toggle('fa-eye');
            toggleEye.classList.toggle('fa-eye-slash');
        });
    </script>

</body>

</html>