<?php
// Deshabilitar la memoria caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../helpers/funciones.php';

// OPCIONAL: si el usuario YA tiene sesión activa, no tiene sentido
// que vuelva a registrarse; lo mandamos directo al dashboard.
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
    <title>Tentaciones Marlly - Registrarse</title>
    <link rel="stylesheet" href="/Proyecto-TeMa/public/styles/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        html {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            height: 100%;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffd8e8;
            overflow-x: hidden;
            overflow-y: hidden;
            box-sizing: border-box;
        }

        .login-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: auto;
            padding: 12px;
            box-sizing: border-box;
        }

        .login-card--register {
            margin: auto;
        }

        @media (max-height: 550px) {
            html, body {
                height: auto;
                overflow-y: auto;
            }
            .login-wrapper {
                min-height: auto;
                padding-top: 15px;
                padding-bottom: 15px;
            }
        }
    </style>
</head>

<body>

    <div class="login-wrapper">
        <div class="login-card login-card--register" style="max-width: 490px; width: 100%;">

            <img src="/Proyecto-TeMa/public/logo.png" alt="Logo Tentaciones Marlly" class="shop-logo">

            <p class="tagline">MINI TIENDA DE CONSUMO DIARIO</p>

            <h2 class="welcome">Crear Cuenta</h2>
            <p class="subtitle">Regístrate para comenzar</p>

            <!-- Alertas dinámicas -->
            <?php if (isset($_GET['error']) && $_GET['error'] === 'user_exists'): ?>
                <p
                    style="color: #c62828; background-color: #ffebee; padding: 8px; border-radius: 5px; font-size: 13px; text-align: center; margin-bottom: 8px;">
                    El nombre de usuario, correo o documento ya existe. Por favor verifica.
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'short_password'): ?>
                <p
                    style="color: #c62828; background-color: #ffebee; padding: 8px; border-radius: 5px; font-size: 13px; text-align: center; margin-bottom: 8px;">
                    La contraseña debe tener al menos 8 caracteres.
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'password_mismatch'): ?>
                <p
                    style="color: #c62828; background-color: #ffebee; padding: 8px; border-radius: 5px; font-size: 13px; text-align: center; margin-bottom: 8px;">
                    Las contraseñas no coinciden. Por favor verifica.
                </p>
            <?php endif; ?>

            <p id="clientError" style="display:none; color: #c62828; background-color: #ffebee; padding: 8px; border-radius: 5px; font-size: 13px; text-align: center; margin-bottom: 8px;"></p>

            <!-- Formulario configurado hacia el controlador -->
            <form id="registerForm" action="/Proyecto-TeMa/index.php" method="POST">
                <input type="hidden" name="action" value="register">
                <?= csrf_field() ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="input-group">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="nombre" placeholder="Nombre" required>
                    </div>

                    <div class="input-group">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="apellido" placeholder="Apellido" required>
                    </div>
                </div>

                <div class="input-group1"> 
                    <i class="fa-solid fa-id-badge"></i>
                    <select name="rol" required>
                        <option value="">Selecciona un rol</option>
                        <option value="Administrador">Administrador</option>
                    </select>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Correo electrónico" required>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" id="documento" name="documento" placeholder="Número de documento" required>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Contraseña (mínimo 8 caracteres)" required minlength="8">
                    <i class="fa-solid fa-eye toggle-eye" id="toggleEye"></i>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirmar Contraseña" required minlength="8">
                    <i class="fa-solid fa-eye toggle-eye" id="toggleConfirmPassword"></i>
                </div>

                <button type="submit">Registrarme</button>
            </form>

            <p class="register-link" style="margin-top: 10px; font-size: 13px; color: #888;">
                ¿Ya tienes una cuenta? <a href="/Proyecto-TeMa/view/login.php"
                    style="color: #e63c82; text-decoration: none; font-weight: 600;">Iniciar Sesión</a>
            </p>

        </div>
    </div>

    <script>
        const toggleEye = document.getElementById('toggleEye');
        const password = document.getElementById('password');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        const form = document.getElementById('registerForm');
        const clientError = document.getElementById('clientError');

        toggleEye.addEventListener('click', () => {
            const isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            toggleEye.classList.toggle('fa-eye');
            toggleEye.classList.toggle('fa-eye-slash');
        });

        if (toggleConfirmPassword && confirmPassword) {
            toggleConfirmPassword.addEventListener('click', () => {
                const isPass = confirmPassword.type === 'password';
                confirmPassword.type = isPass ? 'text' : 'password';
                toggleConfirmPassword.classList.toggle('fa-eye');
                toggleConfirmPassword.classList.toggle('fa-eye-slash');
            });
        }

        form.addEventListener('submit', (e) => {
            clientError.style.display = 'none';
            if (password.value.length < 8) {
                e.preventDefault();
                clientError.textContent = 'La contraseña debe contener al menos 8 caracteres.';
                clientError.style.display = 'block';
                password.focus();
                return;
            }
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                clientError.textContent = 'Las contraseñas no coinciden. Por favor verifica.';
                clientError.style.display = 'block';
                confirmPassword.focus();
                return;
            }
        });
    </script>

</body>

</html>