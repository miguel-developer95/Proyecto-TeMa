<?php
// RF 1.5: definir nueva contraseña con un token válido.
require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    redirect_by_role();
}

$token = (string) get('token');
if ($token === '') {
    flash('error', 'Enlace inválido. Solicita uno nuevo.');
    redirect('view/recuperar.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña - Tentaciones Marlly</title>
    <link rel="stylesheet" href="<?= e(base_url('public/styles/app.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <img src="<?= e(base_url('public/logo.png')) ?>" alt="Logo Tentaciones Marlly" class="shop-logo">
            <h2 class="welcome">Nueva contraseña</h2>
            <p class="subtitle">Mínimo <?= PASSWORD_MIN_LENGTH ?> caracteres</p>

            <?php foreach (flashes() as $f): ?>
                <p class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></p>
            <?php endforeach; ?>

            <form action="<?= e(base_url('index.php')) ?>" method="POST">
                <input type="hidden" name="action" value="restablecer">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <?= csrf_field() ?>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Nueva contraseña" required minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password">
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password_confirm" placeholder="Confirmar contraseña" required minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password">
                </div>
                <button type="submit">Guardar contraseña</button>
            </form>

            <p class="register-link"><a href="<?= e(base_url('view/login.php')) ?>">Volver al inicio de sesión</a></p>
        </div>
    </div>
</body>
</html>
