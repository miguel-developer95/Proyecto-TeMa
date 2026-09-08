<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica si hay sesión iniciada
if (!isset($_SESSION['user'])) {
    header("Location: /Proyecto-TeMa/view/login.php");
    exit();
}
require_once __DIR__ . '/../model/usuario.php';
$usuarioModel = new Usuario();
$totalUsuarios = $usuarioModel->contarUsuarios();
$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tentaciones Marlly</title>
    <!-- CSS del proyecto o específico del dashboard -->
    <link rel="stylesheet" href="/Proyecto-TeMa/public/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Estilos del Dashboard */
        * html,
        body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100%;
            width: 100%;
        }

        body {
            display: flex;
            background-color: #fce4ec;
            overflow-x: hidden;
        }

        .sidebar {
            width: 280px;
            min-width: 280px;
            background: linear-gradient(0deg, #3d405b 10%, #ffffff 50%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px 0;
            height: 100vh;
            box-sizing: border-box;

            /* Efecto de borde derecho difuminado mediante sombra */
            box-shadow: 10px 0 30px 10px rgba(0, 0, 0, 0.2);
        }

        .sidebar-header {
            text-align: center;
            padding: 0 15px 20px;
        }

        .sidebar-header img {
            width: 190px;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .sidebar-header h3 {
            font-size: 16px;
            color: #e63c82;
        }

        .menu-list {
            list-style: none;
            margin-top: 20px;
        }

        .menu-list li a {
            display: flex;
            align-items: center;
            font-size: 16px;
            font-weight: 600;
            color: #adb5bd;
            text-decoration: none;
            padding: 12px 20px;
            margin: 6px 15px;
            border-radius: 8px;
            border: 2px solid transparent;
            transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1),
                background-color 0.3s ease,
                box-shadow 0.3s ease;
            will-change: transform;
        }

        .menu-list li a i {
            margin-right: 12px;
            font-size: 16px;
        }

        .menu-list li a:hover,
        .menu-list li.active a {
            background-color: #e63c82;
            color: #ffffff;
            border-color: #c22b68;
            transform: translateY(-6px);
            box-shadow: 0 6px 16px rgba(230, 60, 130, 0.3);
        }

        .logout-btn {
            padding: 12px 20px;
            color: #ff6b6b;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 16px;
            font-weight: 600;
            margin: 10px 15px;
            border-radius: 8px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .logout-btn i {
            margin-right: 12px;
            font-size: 18px;
        }

        .logout-btn:hover {
            background-color: #c41313;
            color: #ffffff;
            transform: translateY(-6px);
            border-color: #ff6b6b;
        }

        /* Contenido Principal */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 40px 20px;
            border-radius: 30px;
            box-shadow: 0 2px 5px rgba(255, 17, 17, 0.05);
            margin-bottom: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 0%;
            background: #e63c82;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 30px;
            box-shadow: 0 5px 5px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-info h4 {
            font-size: 18px;
            color: #888;
            margin-bottom: -10px;
        }

        .card-info span {
            font-size: 30px;
            font-weight: bold;
            color: #333;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: #fce4ec;
            color: #e63c82;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Tabla o Contenido Generico */
        .content-box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .content-box h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
        }
    </style>
</head>

<body>

    <!-- Sidebar / Menú Lateral -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-header">
                <img src="/Proyecto-TeMa/public/logo.png" alt="Logo">
                <h3>Tentaciones Marlly</h3>
            </div>
            <ul class="menu-list">
                <li class="active">
                    <a href="/Proyecto-TeMa/view/dashboard.php">
                        <i class="fa-solid fa-chart-line"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fa-solid fa-box"></i> Inventario
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fa-solid fa-cart-shopping"></i> Ventas
                    </a>
                </li>
                <li>
                    <a href="/Proyecto-TeMa/view/configuracion.php">
                        <i class="fa-solid fa-gear"></i> Configuración
                    </a>
                </li>
            </ul>
        </div>
        <a href="/Proyecto-TeMa/index.php?action=logout" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
        </a>
    </aside>

    <!-- Área de Contenido -->
    <main class="main-content">
        <!-- Barra Superior -->
        <header class="top-navbar">
            <h2>Panel de Control</h2>
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
                <span>¡Hola, <strong><?php echo htmlspecialchars($user['username']); ?></strong>!</span>
            </div>
        </header>

        <!-- Tarjetas de Información Rápidas -->
        <section class="cards-grid">
            <div class="card">
                <div class="card-info">
                    <h4>Ventas del Día</h4>
                    <span>$ 0.00</span>
                </div>
                <div class="card-icon"><i class="fa-solid fa-dollar-sign"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h4>Productos</h4>
                    <span>0</span>
                </div>
                <div class="card-icon"><i class="fa-solid fa-box-open"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h4>Pedidos Pendientes</h4>
                    <span>0</span>
                </div>
                <div class="card-icon"><i class="fa-solid fa-truck"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h4>Usuarios Registrados</h4>
                    <span><?php echo $totalUsuarios; ?></span>
                </div>
                <div class="card-icon"><i class="fa-solid fa-users"></i></div>
            </div>
        </section>

        <!-- Área Central -->
        <section class="content-box">
            <h3>Resumen de Actividades</h3>
            <p style="color: #666; font-size: 14px;">Bienvenido al sistema de administración de Tentaciones Marlly.
                Selecciona una opción del menú lateral para comenzar a gestionar tu tienda.</p>
        </section>
    </main>

</body>

</html>