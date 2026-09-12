<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Config de inactividad ---
define('TIEMPO_INACTIVIDAD_MAX', 1800); // 30 minutos en segundos

// 1. Verificar autenticación (tu lógica actual)
if (!isset($_SESSION['user'])) {
    header('Location: /Proyecto-TeMa/login.php');
    exit;
}

// Verificar inactividad 
if (isset($_SESSION['last_activity'])) {
    $tiempoInactivo = time() - $_SESSION['last_activity'];

    if ($tiempoInactivo > TIEMPO_INACTIVIDAD_MAX) {
        session_unset();
        session_destroy();
        header('Location: /Proyecto-TeMa/login.php?sesion_expirada=1');
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