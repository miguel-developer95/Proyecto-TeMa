<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php'; // aquí SÍ corre enforce_session_timeout() normal, y por tanto renueva

header('Content-Type: application/json');
echo json_encode(['ok' => is_logged_in()]);