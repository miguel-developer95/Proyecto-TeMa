<?php
// RF 1.5: solicitar enlace de restablecimiento.
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
    <title>Recuperar contraseña - Tentaciones Marlly</title>
    <link rel="stylesheet" href="<?= e(base_url('public/styles/app.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <img src="<?= e(base_url('public/logo.png')) ?>" alt="Logo Tentaciones Marlly" class="shop-logo">
            <h2 class="welcome">Recuperar contraseña</h2>
            <p class="subtitle">Te enviaremos un enlace válido por 1 hora</p>

            <?php foreach (flashes() as $f): ?>
                <p class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></p>
            <?php endforeach; ?>

            <form action="<?= e(base_url('index.php')) ?>" method="POST">
                <input type="hidden" name="action" value="solicitar_reset">
                <?= csrf_field() ?>
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Correo registrado" required>
                </div>
                <button type="submit">Enviar enlace</button>
            </form>

            <p class="register-link"><a href="<?= e(base_url('view/login.php')) ?>">Volver al inicio de sesión</a></p>
        </div>
    </div>
</body>
</html>
