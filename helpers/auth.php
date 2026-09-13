<?php
declare(strict_types=1);

/* Gestión de sesión y autorización. Requiere helpers/functions.php cargado. */

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('TEMA_SESSID');
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $secure,
    ]);
    session_start();
}

/** RF 1.8: cierra la sesión tras SESSION_TIMEOUT segundos sin actividad. */
function enforce_session_timeout(): void
{
    if (!isset($_SESSION['user'])) {
        return;
    }
    $last = (int) ($_SESSION['last_activity'] ?? time());
    if (time() - $last > SESSION_TIMEOUT) {
        logout_user();
        redirect('view/login.php?error=expired');
    }
    $_SESSION['last_activity'] = time();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function current_role(): string
{
    return strtolower((string) ($_SESSION['user']['rol'] ?? ''));
}

function is_admin(): bool
{
    return current_role() === 'administrador';
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('view/login.php');
    }
}

/** Exige uno de los roles indicados (minúsculas). 403 en caso contrario. */
function require_role(array $roles): void
{
    require_login();
    $roles = array_map('strtolower', $roles);
    if (!in_array(current_role(), $roles, true)) {
        show_error(403, 'No tienes permiso para acceder a esta sección.');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true); // anti fijación de sesión
    unset($user['password']);
    $_SESSION['user'] = $user;
    $_SESSION['last_activity'] = time();
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'] ?? false, $p['httponly'] ?? true);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/** Redirige al home según rol (RF 1.4). */
function redirect_by_role(): void
{
    if (in_array(current_role(), ['vendedor', 'cajero'], true)) {
        redirect('view/pos.php');
    }
    redirect('view/dashboard.php');
}
