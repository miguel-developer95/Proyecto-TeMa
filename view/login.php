<?php
require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    redirect_by_role();
}

$error = get('error');
$segundos = (int) (get('segundos') ?: 60);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentaciones Marlly - Iniciar Sesión</title>
    <link rel="stylesheet" href="<?= e(base_url('public/styles/app.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <img src="<?= e(base_url('public/logo.png')) ?>" alt="Logo Tentaciones Marlly" class="shop-logo">
            <p class="tagline">MINI TIENDA DE CONSUMO DIARIO</p>
            <h2 class="welcome">Bienvenido</h2>
            <p class="subtitle">Ingresa para continuar</p>

            <?php foreach (flashes() as $f): ?>
                <p class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></p>
            <?php endforeach; ?>

            <?php if ($error === 'invalid_credentials'): ?>
                <p class="flash flash-error">Usuario o contraseña incorrectos.</p>
            <?php elseif ($error === 'expired'): ?>
                <p class="flash flash-error">Tu sesión expiró por inactividad. Ingresa de nuevo.</p>
            <?php elseif ($error === 'locked'): ?>
                <p class="flash flash-warning" id="alertaBloqueo" data-segundos="<?= $segundos ?>">
                    Cuenta bloqueada temporalmente. Intenta de nuevo en
                    <span id="segundosRestantes"><?= $segundos ?></span> segundos.
                </p>
            <?php endif; ?>

            <form action="<?= e(base_url('index.php')) ?>" method="POST" id="loginForm">
                <input type="hidden" name="action" value="login">
                <?= csrf_field() ?>
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Usuario o Correo" required autocomplete="username">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Contraseña" required autocomplete="current-password">
                    <i class="fa-solid fa-eye toggle-eye" id="toggleEye"></i>
                </div>
                <a href="<?= e(base_url('view/recuperar.php')) ?>" class="forgot-link">¿Olvidaste tu contraseña?</a>
                <button type="submit">Iniciar Sesión</button>
            </form>

            <p class="register-link">¿No tienes cuenta?
                <a href="<?= e(base_url('view/register.php')) ?>">Regístrate</a>
            </p>
        </div>
    </div>

    <script>
        const alertaBloqueo = document.getElementById('alertaBloqueo');
        if (alertaBloqueo) {
            let segundos = parseInt(alertaBloqueo.dataset.segundos, 10) || 60;
            const span = document.getElementById('segundosRestantes');
            const form = document.getElementById('loginForm');
            form.querySelectorAll('input[type="text"], input[type="password"], button').forEach(el => el.disabled = true);
            const intervalo = setInterval(() => {
                segundos--;
                if (segundos <= 0) {
                    clearInterval(intervalo);
                    window.location.href = '<?= e(base_url('view/login.php')) ?>';
                    return;
                }
                span.textContent = segundos;
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
