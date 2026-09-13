<?php
require_once __DIR__ . '/../config/config.php';

$code = (int) ($errorCode ?? 500);
$msg = (string) ($errorMessage ?? 'Ocurrió un error inesperado.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= e($code) ?> - Tentaciones Marlly</title>
    <link rel="stylesheet" href="<?= e(base_url('public/styles/app.css')) ?>">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <h2 class="welcome">Error <?= e($code) ?></h2>
            <p class="subtitle"><?= e($msg) ?></p>
            <a class="btn-primary" href="<?= e(base_url('index.php')) ?>">Volver al inicio</a>
        </div>
    </div>
</body>
</html>
