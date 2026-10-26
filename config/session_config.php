<?php
if (!defined('TIEMPO_INACTIVIDAD_MAX')) {
    // Expiración por inactividad a los 30 minutos (1800 segundos)
    define('TIEMPO_INACTIVIDAD_MAX', 1800);
}

// Configuración de endurecimiento para cookies de sesión
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/Proyecto-TeMa/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}
