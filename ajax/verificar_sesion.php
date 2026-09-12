<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

define('TIEMPO_INACTIVIDAD_MAX', 1800); // 30 minutos de inactividad

if (!isset($_SESSION['user']) || !isset($_SESSION['last_activity'])) {
    echo json_encode(['activa' => false, 'segundosRestantes' => 0]);
    exit;
}

$restante = TIEMPO_INACTIVIDAD_MAX - (time() - $_SESSION['last_activity']);

if ($restante <= 0) {
    session_unset();
    session_destroy();
    echo json_encode(['activa' => false, 'segundosRestantes' => 0]);
    exit;
}

echo json_encode(['activa' => true, 'segundosRestantes' => $restante]);


