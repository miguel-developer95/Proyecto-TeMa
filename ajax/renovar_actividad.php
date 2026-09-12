<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

$_SESSION['last_activity'] = time();
echo json_encode(['ok' => true]);