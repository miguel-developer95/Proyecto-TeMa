<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración centralizada de inactividad
require_once __DIR__ . '/../config/session_config.php';

// 1. Verificar autenticación (tu lógica actual)
if (!isset($_SESSION['user'])) {
    header('Location: /Proyecto-TeMa/view/login.php');
    exit;
}

// Verificar inactividad 
if (isset($_SESSION['last_activity'])) {
    $tiempoInactivo = time() - $_SESSION['last_activity'];

    if ($tiempoInactivo > TIEMPO_INACTIVIDAD_MAX) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: /Proyecto-TeMa/view/login.php?sesion_expirada=1');
        exit;
    }
}

// Renovar la marca de tiempo en cada carga de página
$_SESSION['last_activity'] = time();

function verificarRol(array $rolesPermitidos) {
    $rolActual = strtolower($_SESSION['user']['rol'] ?? '');

    if (!in_array($rolActual, $rolesPermitidos, true)) {
        if (in_array($rolActual, ['vendedor', 'cajero'], true)) {
            header("Location: /Proyecto-TeMa/view/pos.php");
        } else {
            header("Location: /Proyecto-TeMa/view/dashboard.php");
        }
        exit();
    }
}