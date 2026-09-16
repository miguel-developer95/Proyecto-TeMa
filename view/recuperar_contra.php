<?php
date_default_timezone_set('America/Bogota');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../helpers/funciones.php';

$token = $_GET['token'] ?? null;
$tokenValido = false;
$tokenExpiradoOInvalido = false;

if ($token) {
    require_once __DIR__ . '/../model/usuario.php';
    $usuarioModel = new Usuario();
    $idUsuario = $usuarioModel->validarTokenRecuperacion($token);
    if ($idUsuario) {
        $tokenValido = true;
    } else {
        $tokenExpiradoOInvalido = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($token && $tokenValido) ? 'Restablecer Contraseña' : 'Recuperar Contraseña' ?> - Tentaciones Marlly</title>
    <link rel="stylesheet" href="/Proyecto-TeMa/public/styles/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card login-card--recuperar" style="max-width: 500px; width: 100%;">
            <img src="/Proyecto-TeMa/public/logo.png" alt="Logo Tentaciones Marlly" class="shop-logo">
            <p class="tagline">MINI TIENDA DE CONSUMO DIARIO</p>

            <h2 class="welcome"><?= ($token && $tokenValido) ? 'Nueva Contraseña' : 'Recuperar Contraseña' ?></h2>
            <p class="subtitle"><?= ($token && $tokenValido) ? 'Ingresa tu nueva contraseña para acceder' : 'Te enviaremos un enlace de recuperación' ?></p>

            <?php if (isset($_GET['status']) && $_GET['status'] === 'enviado'): ?>
                <p style="color:#2e7d32;background:#e8f5e9;padding:12px;border-radius:8px;font-size:14px;margin-bottom:15px;line-height:1.4;">
                    <i class="fa-solid fa-circle-check"></i> Si el correo está registrado, recibirás un enlace de recuperación en unos minutos. Revisa tu bandeja de entrada o spam.
                </p>
            <?php endif; ?>

            <?php if ((isset($_GET['error']) && $_GET['error'] === 'token_invalido') || $tokenExpiradoOInvalido): ?>
                <p style="color:#c62828;background:#ffebee;padding:12px;border-radius:8px;font-size:14px;margin-bottom:15px;line-height:1.4;">
                    <i class="fa-solid fa-circle-exclamation"></i> El enlace de recuperación no es válido o ya expiró (5 min). Solicita uno nuevo a continuación.
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'muy_corta'): ?>
                <p style="color:#c62828;background:#ffebee;padding:12px;border-radius:8px;font-size:14px;margin-bottom:15px;">
                    <i class="fa-solid fa-circle-exclamation"></i> La contraseña debe tener al menos 8 caracteres.
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'no_coincide'): ?>
                <p style="color:#c62828;background:#ffebee;padding:12px;border-radius:8px;font-size:14px;margin-bottom:15px;">
                    <i class="fa-solid fa-circle-exclamation"></i> Las contraseñas no coinciden. Por favor verifica.
                </p>
            <?php endif; ?>

            <p id="clientError" style="display:none; color:#c62828; background:#ffebee; padding:12px; border-radius:8px; font-size:14px; margin-bottom:15px;"></p>

            <?php if ($token && $tokenValido): ?>
                <!-- Formulario de cambio de contraseña con token válido -->
                <form id="resetForm" method="POST" action="/Proyecto-TeMa/index.php">
                    <input type="hidden" name="action" value="restablecer_password">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <?= csrf_field() ?>

                    <div class="input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Nueva contraseña (mínimo 8 caracteres)" required minlength="8">
                        <i class="fa-solid fa-eye toggle-eye" id="toggleEye1"></i>
                    </div>

                    <div class="input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password_confirmar" id="password_confirmar" placeholder="Confirmar nueva contraseña" required minlength="8">
                        <i class="fa-solid fa-eye toggle-eye" id="toggleEye2"></i>
                    </div>

                    <button type="submit">Actualizar Contraseña</button>
                </form>
            <?php else: ?>
                <!-- Formulario de solicitud por email -->
                <form method="POST" action="/Proyecto-TeMa/index.php">
                    <input type="hidden" name="action" value="solicitar_recuperacion">
                    <?= csrf_field() ?>
                    <div class="input-group">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="Correo electrónico registrado" required>
                    </div>
                    <button type="submit">Enviar Enlace</button>
                </form>
            <?php endif; ?>

            <p style="margin-top: 20px; font-size: 14px;">
                <a href="/Proyecto-TeMa/view/login.php" style="color: #e63c82; text-decoration: none; font-weight: 600;">
                    <i class="fa-solid fa-arrow-left"></i> Volver a Iniciar Sesión
                </a>
            </p>
        </div>
    </div>

    <script>
        const resetForm = document.getElementById('resetForm');
        if (resetForm) {
            resetForm.addEventListener('submit', (e) => {
                const pass = document.getElementById('password');
                const conf = document.getElementById('password_confirmar');
                const err = document.getElementById('clientError');
                err.style.display = 'none';

                if (pass.value.length < 8) {
                    e.preventDefault();
                    err.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> La contraseña debe contener al menos 8 caracteres.';
                    err.style.display = 'block';
                    pass.focus();
                    return;
                }
                if (pass.value !== conf.value) {
                    e.preventDefault();
                    err.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Las contraseñas no coinciden. Por favor verifica.';
                    err.style.display = 'block';
                    conf.focus();
                    return;
                }
            });

            // Toggle visibilidad contraseña
            const setupEyeToggle = (eyeId, inputId) => {
                const eye = document.getElementById(eyeId);
                const input = document.getElementById(inputId);
                if (eye && input) {
                    eye.addEventListener('click', () => {
                        const isPass = input.type === 'password';
                        input.type = isPass ? 'text' : 'password';
                        eye.classList.toggle('fa-eye');
                        eye.classList.toggle('fa-eye-slash');
                    });
                }
            };

            setupEyeToggle('toggleEye1', 'password');
            setupEyeToggle('toggleEye2', 'password_confirmar');
        }
    </script>
</body>

</html>