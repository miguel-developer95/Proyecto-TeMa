<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay datos de registro en sesión, no debería estar aquí -> redirigir
if (!isset($_SESSION['registro_exitoso'])) {
    header("Location: /Proyecto-TeMa/view/login.php");
    exit();
}

$datos = $_SESSION['registro_exitoso'];

// Limpiar la sesión INMEDIATAMENTE después de leerla, para que
// un refresh (F5) o el botón "atrás" del navegador no vuelvan a mostrar
// la contraseña ni permitan re-acceder a esta pantalla
unset($_SESSION['registro_exitoso']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuenta creada - Tentaciones Marlly</title>
    <link rel="stylesheet" href="/Proyecto-TeMa/public/styles/registro_exitoso.css">
</head>
<body>
    <div class="card">
        <img src="/Proyecto-TeMa/public/logo.png" alt="Logo Tentaciones Marlly" class="shop-logo">
        <h2>¡Cuenta creada con éxito!</h2>
        <p>Guarda estos datos, los necesitarás para iniciar sesión:</p>

        <div class="credencial">
            <strong>Usuario</strong>
            <?= htmlspecialchars($datos['username']) ?>
        </div>

        <div class="credencial">
            <strong>Contraseña</strong>
            <?= htmlspecialchars($datos['password']) ?>
        </div>

        <form action="/Proyecto-TeMa/view/login.php" method="get">
            <button type="submit">Continuar</button>
        </form>
    </div>
</body>
</html>