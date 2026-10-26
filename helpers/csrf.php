<?php
// helpers/csrf.php
require_once __DIR__ . '/../config/session_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Genera (o reutiliza) el token CSRF de la sesión actual.
 */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Imprime el input oculto para incluir en los formularios.
 */
if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = htmlspecialchars(csrf_token());
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
    }
}

/**
 * Verifica el token recibido por POST contra el de la sesión.
 */
if (!function_exists('csrf_verify')) {
    function csrf_verify(?string $token = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $tokenRecibido = $token ?? ($_POST['csrf_token'] ?? '');
        $tokenSesion = $_SESSION['csrf_token'] ?? '';

        if (empty($tokenRecibido) || empty($tokenSesion)) {
            return false;
        }

        return hash_equals($tokenSesion, $tokenRecibido);
    }
}

if (!function_exists('csrf_verificar')) {
    function csrf_verificar(?string $token = null): bool
    {
        return csrf_verify($token);
    }
}
