<?php
// helpers/csrf.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Genera (o reutiliza) el token CSRF de la sesión actual.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Imprime el input oculto para incluir en los formularios.
 */
function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token());
    return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
}

/**
 * Verifica el token recibido por POST contra el de la sesión.
 * Llamar al inicio del manejo de POST en cada vista/controlador.
 */
function csrf_verify(): bool
{
    $tokenRecibido = $_POST['csrf_token'] ?? '';
    $tokenSesion = $_SESSION['csrf_token'] ?? '';

    if (empty($tokenRecibido) || empty($tokenSesion)) {
        return false;
    }

    return hash_equals($tokenSesion, $tokenRecibido);
}