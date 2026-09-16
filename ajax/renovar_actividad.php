<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/session_config.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['last_activity'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'expirada' => true]);
    exit;
}

if ((time() - (int)$_SESSION['last_activity']) > TIEMPO_INACTIVIDAD_MAX) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    http_response_code(401);
    echo json_encode(['ok' => false, 'expirada' => true]);
    exit;
}

$_SESSION['last_activity'] = time();
echo json_encode(['ok' => true, 'timestamp' => $_SESSION['last_activity']]);