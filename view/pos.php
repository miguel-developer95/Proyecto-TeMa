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
    <script src="/Proyecto-TeMa/assets/js/session-timeout.js"></script>
</body>
</html>