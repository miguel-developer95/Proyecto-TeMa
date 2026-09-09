<?php

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user'])) {
    header("Location: /Proyecto-TeMa/view/login.php");
    exit();
}

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