<?php
require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    redirect_by_role();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentaciones Marlly - Registrarse</title>
    <link rel="stylesheet" href="<?= e(base_url('public/styles/app.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <img src="<?= e(base_url('public/logo.png')) ?>" alt="Logo Tentaciones Marlly" class="shop-logo">
            <p class="tagline">MINI TIENDA DE CONSUMO DIARIO</p>
            <h2 class="welcome">Crear Cuenta</h2>
            <p class="subtitle">Regístrate para comenzar</p>

            <?php foreach (flashes() as $f): ?>
                <p class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></p>
            <?php endforeach; ?>

            <form action="<?= e(base_url('index.php')) ?>" method="POST">
                <input type="hidden" name="action" value="register">
                <?= csrf_field() ?>
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="nombre" placeholder="Nombre" required maxlength="80">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="apellido" placeholder="Apellido" required maxlength="80">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-id-badge"></i>
                    <select name="rol" required>
                        <option value="">Selecciona un rol</option>
                        <option value="Administrador">Administrador</option>
                        <option value="Vendedor">Vendedor</option>
                    </select>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Correo electrónico" required maxlength="120">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" name="documento" placeholder="Número de documento" required maxlength="30">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Contraseña (mínimo 8 caracteres)" required minlength="8" autocomplete="new-password">
                    <i class="fa-solid fa-eye toggle-eye" id="toggleEye"></i>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password_confirm" placeholder="Confirmar contraseña" required minlength="8" autocomplete="new-password">
                </div>
                <button type="submit">Registrarme</button>
            </form>

            <p class="register-link">¿Ya tienes una cuenta?
                <a href="<?= e(base_url('view/login.php')) ?>">Iniciar Sesión</a>
            </p>
        </div>
    </div>

    <script>
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
