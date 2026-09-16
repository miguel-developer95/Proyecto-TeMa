<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/session_config.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['last_activity'])) {
    echo json_encode([
        'activa' => false,
        'segundosRestantes' => 0,
        'tiempoMaximo' => TIEMPO_INACTIVIDAD_MAX,
        'umbralAviso' => 0
    ]);
    exit;
}

$transcurrido = time() - (int)$_SESSION['last_activity'];
$restante = TIEMPO_INACTIVIDAD_MAX - $transcurrido;

if ($restante <= 0) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    echo json_encode([
        'activa' => false,
        'segundosRestantes' => 0,
        'tiempoMaximo' => TIEMPO_INACTIVIDAD_MAX,
        'umbralAviso' => 0
    ]);
    exit;
}

// Umbral proporcional de aviso: para tiempos cortos (ej. <= 60s) avisar a los 20s; para 1800s avisar a los 60s
$umbralAviso = (TIEMPO_INACTIVIDAD_MAX <= 60) 
    ? min(20, (int)(TIEMPO_INACTIVIDAD_MAX / 2)) 
    : ((TIEMPO_INACTIVIDAD_MAX <= 180) ? 30 : 60);

echo json_encode([
    'activa' => true,
    'segundosRestantes' => $restante,
    'tiempoMaximo' => TIEMPO_INACTIVIDAD_MAX,
    'umbralAviso' => $umbralAviso
]);


