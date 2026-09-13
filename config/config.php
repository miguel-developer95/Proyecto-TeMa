<?php
declare(strict_types=1);

/**
 * Bootstrap central. Todo punto de entrada (index.php y vistas) debe
 * incluir este archivo en primer lugar.
 */
date_default_timezone_set('America/Bogota');

// --- Cargador mínimo de .env (sin dependencias externas) ---
$rootDir = dirname(__DIR__);
$envFile = $rootDir . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim(trim($v), "\"'");
            if ($k !== '' && getenv($k) === false && !array_key_exists($k, $_ENV)) {
                putenv($k . '=' . $v);
                $_ENV[$k] = $v;
            }
        }
    }
}

function env(string $key, string $default = ''): string
{
    $v = $_ENV[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : (string) $v;
}

define('APP_ENV', env('APP_ENV', 'local'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', APP_ENV === 'local' ? '1' : '0'), FILTER_VALIDATE_BOOL));
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'tentaciones_marlly'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int) env('SMTP_PORT', '587'));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_FROM', env('SMTP_FROM', ''));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'Tentaciones Marlly'));
define('SMTP_SECURE', env('SMTP_SECURE', 'tls'));
define('SESSION_TIMEOUT', (int) env('SESSION_TIMEOUT', '1800'));
define('LOCKOUT_ATTEMPTS', (int) env('LOCKOUT_ATTEMPTS', '3'));
define('LOCKOUT_MINUTES', (int) env('LOCKOUT_MINUTES', '1'));
define('PASSWORD_MIN_LENGTH', 8);

// --- URL base (configurable; con autodetección como respaldo) ---
$base = env('APP_BASE_URL', '');
if ($base === '') {
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $parts = array_values(array_filter(explode('/', $script)));
    $base = count($parts) > 1 ? '/' . $parts[0] : '';
}
define('BASE_URL', rtrim($base, '/'));

// --- Errores: visibles solo en depuración, a log en producción ---
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

require_once __DIR__ . '/Connection.php';
require_once $rootDir . '/helpers/functions.php';
require_once $rootDir . '/helpers/auth.php';

start_secure_session();
enforce_session_timeout();
