<?php
// helpers/funciones.php
// Funciones de utilidad general para vistas y controladores.

require_once __DIR__ . '/../config/session_config.php';

/**
 * Escapa una cadena para salida segura en HTML (previene XSS).
 */
if (!function_exists('e')) {
    function e($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Formatea un número como moneda en pesos colombianos.
 */
if (!function_exists('money')) {
    function money($valor): string
    {
        return '$ ' . number_format((float) $valor, 2, ',', '.');
    }
}

/**
 * Construye una URL absoluta a partir de una ruta relativa del proyecto.
 */
if (!function_exists('base_url')) {
    function base_url(string $ruta = ''): string
    {
        $base = '/Proyecto-TeMa/';
        return $base . ltrim($ruta, '/');
    }
}

/**
 * Obtiene un valor de $_GET de forma segura, con un valor por defecto.
 */
if (!function_exists('get')) {
    function get(string $clave, $default = '')
    {
        return $_GET[$clave] ?? $default;
    }
}

/**
 * Obtiene un valor de $_POST de forma segura, con un valor por defecto.
 */
if (!function_exists('post')) {
    function post(string $clave, $default = '')
    {
        return $_POST[$clave] ?? $default;
    }
}

/**
 * Genera (o reutiliza) un token CSRF almacenado en sesión.
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
 * Devuelve el campo oculto <input> con el token CSRF, listo para usar en formularios.
 */
if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

/**
 * Valida el token CSRF recibido en un formulario contra el de sesión.
 */
if (!function_exists('csrf_verificar')) {
    function csrf_verificar(?string $tokenRecibido = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = $tokenRecibido ?? ($_POST['csrf_token'] ?? '');
        return !empty($token) && !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(?string $tokenRecibido = null): bool
    {
        return csrf_verificar($tokenRecibido);
    }
}

/**
 * Devuelve los datos del usuario autenticado actualmente, o un array vacío si no hay sesión.
 */
if (!function_exists('current_user')) {
    function current_user(): array
    {
        return $_SESSION['user'] ?? [];
    }
}

/**
 * Guarda o recupera mensajes flash temporales en sesión.
 */
if (!function_exists('flash')) {
    function flash(?string $tipo = null, ?string $mensaje = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($tipo !== null && $mensaje !== null) {
            $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $mensaje];
            return null;
        }

        if (isset($_SESSION['flash'])) {
            $f = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $f;
        }

        return null;
    }
}

/**
 * Redirige a una ruta del proyecto y finaliza la ejecución.
 */
if (!function_exists('redirect')) {
    function redirect(string $ruta): void
    {
        header('Location: ' . base_url($ruta));
        exit();
    }
}
