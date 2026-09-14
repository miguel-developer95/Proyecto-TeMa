<?php
// helpers/funciones.php
// Funciones de utilidad general para vistas y controladores.

/**
 * Escapa una cadena para salida segura en HTML (previene XSS).
 */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Formatea un número como moneda en pesos colombianos.
 */
function money($valor): string
{
    return '$ ' . number_format((float) $valor, 2, ',', '.');
}

/**
 * Construye una URL absoluta a partir de una ruta relativa del proyecto.
 * Ajusta BASE_URL según cómo tengas configurado tu proyecto.
 */
function base_url(string $ruta = ''): string
{
    $base = '/Proyecto-TeMa/';
    return $base . ltrim($ruta, '/');
}

/**
 * Obtiene un valor de $_GET de forma segura, con un valor por defecto.
 */
function get(string $clave, $default = '')
{
    return $_GET[$clave] ?? $default;
}

/**
 * Obtiene un valor de $_POST de forma segura, con un valor por defecto.
 */
function post(string $clave, $default = '')
{
    return $_POST[$clave] ?? $default;
}

/**
 * Genera (o reutiliza) un token CSRF almacenado en sesión.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Devuelve el campo oculto <input> con el token CSRF, listo para usar en formularios.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Valida el token CSRF recibido en un formulario contra el de sesión.
 */
function csrf_verificar(?string $tokenRecibido): bool
{
    return !empty($tokenRecibido) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $tokenRecibido);
}

/**
 * Devuelve los datos del usuario autenticado actualmente, o un array vacío si no hay sesión.
 */
function current_user(): array
{
    return $_SESSION['user'] ?? [];
}

/**
 * Guarda o recupera mensajes flash temporales en sesión.
 */
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

/**
 * Redirige a una ruta del proyecto y finaliza la ejecución.
 */
function redirect(string $ruta): void
{
    header('Location: ' . base_url($ruta));
    exit();
}