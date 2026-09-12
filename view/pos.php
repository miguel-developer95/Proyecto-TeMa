<?php
require_once __DIR__ . '/../helpers/auth_guard.php';
verificarRol(['vendedor', 'cajero', 'administrador']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS</title>
</head>
<body>
    <h1>hello POS</h1>
    Añadir función para que el sistema recorte el nombre, el apellido (a las primeras 3 letras) y el rol del usuario (si es administrador: admin, si es vendedor: vend) para hacer mas corto el username y que se vea mejor en la interfaz de usuario. Por ejemplo, si el nombre es "Juan Carlos", el apellido es "Pérez Gómez", el correo electrónico es "juan.perez@example.com", el rol es "vendedor", entonces el username sería "juaperadmin" o algo similar.
    <script src="/Proyecto-TeMa/assets/js/session-timeout.js"></script>
</body>
</html>