<?php
require_once __DIR__ . '/../helpers/auth_guard.php';
verificarRol(['vendedor', 'cajero', 'administrador']);
?>
<!DOCTYPE html>
hello POS