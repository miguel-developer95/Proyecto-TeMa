<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolActual = strtolower($_SESSION['user']['rol'] ?? '');
$paginaActual = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div>
        <div class="sidebar-header">
            <img src="/Proyecto-TeMa/public/logo.png" alt="Logo">
            <h3>Tentaciones Marlly</h3>
        </div>
        <ul class="menu-list">

            <?php if ($rolActual === 'administrador'): ?>
                <li class="<?= $paginaActual === 'dashboard.php' ? 'active' : '' ?>">
                    <a href="/Proyecto-TeMa/view/dashboard.php">
                        <i class="fa-solid fa-chart-line"></i> Dashboard
                    </a>
                </li>

                <li class="<?= $paginaActual === 'inventario.php' ? 'active' : '' ?>">
                    <a href="/Proyecto-TeMa/view/inventario.php">
                        <i class="fa-solid fa-box"></i> Inventario
                    </a>
                </li>

                <li class="<?= $paginaActual === 'compra.php' ? 'active' : '' ?>">
                    <a href="/Proyecto-TeMa/view/compra.php">
                        <i class="fa-solid fa-cart-shopping"></i> Compras
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($rolActual, ['administrador', 'vendedor', 'cajero'], true)): ?>
                <li class="<?= $paginaActual === 'pos.php' ? 'active' : '' ?>">
                    <a href="/Proyecto-TeMa/view/pos.php">
                        <i class="fa-solid fa-cash-register"></i> Ventas
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($rolActual === 'administrador'): ?>
                <li class="<?= $paginaActual === 'configuracion.php' ? 'active' : '' ?>">
                    <a href="/Proyecto-TeMa/view/configuracion.php">
                        <i class="fa-solid fa-gear"></i> Configuración
                    </a>
                </li>
            <?php endif; ?>

        </ul>
    </div>
    <a href="/Proyecto-TeMa/index.php?action=logout" class="logout-btn">
        <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
    </a>
</aside>