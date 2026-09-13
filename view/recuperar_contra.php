<?php
date_default_timezone_set('America/Bogota');
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>
    <link rel="stylesheet" href="/Proyecto-TeMa/public/styles/recuperar_contra.css">
</head>

<body>
    <div class="container">
        <h2>Recuperar contraseña</h2>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'enviado'): ?>
            <p class="mensaje" style="color:#2e7d32;background:#e8f5e9;padding:10px;border-radius:5px;">
                Si el correo está registrado, recibirás un enlace de recuperación en unos minutos.
            </p>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'token_invalido'): ?>
            <p class="mensaje" style="color:#c62828;background:#ffebee;padding:10px;border-radius:5px;">
                El enlace de recuperación no es válido o ya expiró. Solicita uno nuevo.
            </p>
        <?php endif; ?>

        <form method="POST" action="/Proyecto-TeMa/index.php">
            <input type="hidden" name="action" value="solicitar_recuperacion">
            <label for="email">Ingresa tu correo:</label>
            <input type="email" name="email" id="email" required>
            <button type="submit">Enviar enlace</button>
        </form>
    </div>
</body>

</html>