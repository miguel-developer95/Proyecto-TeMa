<?php
declare(strict_types=1);

define('SKIP_SESSION_TIMEOUT_CHECK', true);
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['activa' => false, 'segundos_restantes' => 0]);
    exit;
}

$last = (int) ($_SESSION['last_activity'] ?? time());
$restantes = SESSION_TIMEOUT - (time() - $last);

if ($restantes <= 0) {
    logout_user();
    echo json_encode(['activa' => false, 'segundos_restantes' => 0]);
    exit;
}

echo json_encode(['activa' => true, 'segundos_restantes' => $restantes]);