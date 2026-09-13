<?php
declare(strict_types=1);

/** URL absoluta dentro de la app: base_url('view/login.php') */
function base_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_URL . ($path === '' ? '' : '/' . $path);
}

/**
 * URL ABSOLUTA (con esquema y host) para usar en correos.
 * En web usa el host de la petición; en CLI usa APP_URL del .env.
 */
function base_url_abs(string $path = ''): string
{
    $rel = base_url($path);
    $appUrl = rtrim(env('APP_URL', ''), '/');
    if ($appUrl !== '') {
        return $appUrl . $rel;
    }
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        $host = 'localhost';
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $host . $rel;
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit();
}

/** Escape HTML (anti-XSS). */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Formato moneda COP. */
function money($n): string
{
    return '$ ' . number_format((float) $n, 2, ',', '.');
}

function post(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

function get(string $key, $default = '')
{
    return $_GET[$key] ?? $default;
}

/* ---------------- CSRF ---------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = (string) ($_POST['csrf_token'] ?? '');
    $expected = (string) ($_SESSION['csrf_token'] ?? '');
    if ($expected === '' || $sent === '' || !hash_equals($expected, $sent)) {
        show_error(403, 'Token de seguridad inválido o ausente. Recarga la página e intenta de nuevo.');
    }
}

/* ---------------- Mensajes flash ---------------- */

function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'msg' => $message];
}

/** Obtiene y limpia los mensajes pendientes. */
function flashes(): array
{
    $f = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return is_array($f) ? $f : [];
}

/* ---------------- Errores HTTP ---------------- */

function show_error(int $code, string $message): void
{
    http_response_code($code);
    $errorCode = $code;
    $errorMessage = $message;
    require dirname(__DIR__) . '/view/error.php';
    exit();
}
