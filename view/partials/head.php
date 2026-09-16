<?php
// view/partials/head.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Consultar el primer nombre y primer apellido directamente desde la base de datos
$userId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 0);
if ($userId > 0) {
    require_once __DIR__ . '/../../config/connection.php';
    try {
        $dbConn = (new Connection())->conn;
        $stmtUser = $dbConn->prepare("SELECT nombre, apellido, username, rol FROM usuarios WHERE id = :id LIMIT 1");
        $stmtUser->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmtUser->execute();
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($userData) {
            $_SESSION['user']['nombre']   = $userData['nombre'];
            $_SESSION['user']['apellido'] = $userData['apellido'];
            $_SESSION['user']['username'] = $userData['username'];
            $_SESSION['user']['rol']      = $userData['rol'];
        }
    } catch (Exception $e) {
        // En caso de fallo de conexión temporal, mantiene datos de sesión
    }
}

$nombreRaw   = trim($_SESSION['user']['nombre'] ?? '');
$apellidoRaw = trim($_SESSION['user']['apellido'] ?? '');

$partesNom = preg_split('/\s+/', $nombreRaw);
$primerNombre = !empty($partesNom[0]) ? $partesNom[0] : ($_SESSION['user']['username'] ?? 'Usuario');

$partesApe = preg_split('/\s+/', $apellidoRaw);
$primerApellido = !empty($partesApe[0]) ? $partesApe[0] : '';

$nombreMostrar = trim($primerNombre . ' ' . $primerApellido);
$inicial = strtoupper(substr($primerNombre, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Tentaciones Marlly') ?></title>
    <!-- CSS del proyecto unificado y coherente -->
    <link rel="stylesheet" href="/Proyecto-TeMa/public/styles/index.css">
    <link rel="stylesheet" href="/Proyecto-TeMa/public/styles/sidebar.css">
    <link rel="stylesheet" href="/Proyecto-TeMa/public/styles/dashboard.css">
    <link rel="stylesheet" href="/Proyecto-TeMa/public/styles/tablas.css">
    <link rel="stylesheet" href="/Proyecto-TeMa/public/styles/pos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        (function() {
            try {
                if (localStorage.getItem('sidebar_collapsed') === 'true' && window.innerWidth >= 992) {
                    document.documentElement.classList.add('sidebar-is-collapsed');
                }
            } catch (e) {}
        })();
    </script>
</head>

<body>
<div class="layout">
    <?php require_once __DIR__ . '/../../helpers/sidebar.php'; ?>
    <main class="main-content">
        <!-- Barra Superior Unificada en todas las vistas -->
        <header class="top-navbar">
            <div class="top-navbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" title="Ocultar / Mostrar menú lateral" aria-label="Ocultar o mostrar menú">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h2><?= htmlspecialchars($titulo ?? 'Tentaciones Marlly') ?></h2>
                    <?php if (!empty($subtitulo)): ?>
                        <p class="top-navbar-subtitle"><?= htmlspecialchars($subtitulo) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="user-profile">
                <div class="user-avatar" title="<?= htmlspecialchars($nombreMostrar) ?>">
                    <?= htmlspecialchars($inicial) ?>
                </div>
                <span>¡Hola, <strong><?= htmlspecialchars($nombreMostrar) ?></strong>!</span>
            </div>
        </header>