<?php
$rolActual = current_role();
$paginaActual = basename($_SERVER['PHP_SELF'] ?? '');
?>
<aside class="sidebar">
    <div>
        <div class="sidebar-header">
            <img src="<?= e(base_url('public/logo.png')) ?>" alt="Logo">
            <h3>Tentaciones Marlly</h3>
        </div>
        <ul class="menu-list">
            <?php if ($rolActual === 'administrador'): ?>
                <li class="<?= $paginaActual === 'dashboard.php' ? 'active' : '' ?>">
                    <a href="<?= e(base_url('view/dashboard.php')) ?>">
                        <i class="fa-solid fa-chart-line"></i> Dashboard
                    </a>
                </li>
                <li class="<?= $paginaActual === 'inventario.php' ? 'active' : '' ?>">
                    <a href="<?= e(base_url('view/inventario.php')) ?>">
                        <i class="fa-solid fa-box"></i> Inventario
                    </a>
                </li>
                <li class="<?= $paginaActual === 'kardex.php' ? 'active' : '' ?>">
                    <a href="<?= e(base_url('view/kardex.php')) ?>">
                        <i class="fa-solid fa-clock-rotate-left"></i> Kardex
                    </a>
                </li>
                <li class="<?= $paginaActual === 'compras.php' ? 'active' : '' ?>">
                    <a href="<?= e(base_url('view/compras.php')) ?>">
                        <i class="fa-solid fa-cart-shopping"></i> Compras
                    </a>
                </li>
                <li class="<?= $paginaActual === 'proveedores.php' ? 'active' : '' ?>">
                    <a href="<?= e(base_url('view/proveedores.php')) ?>">
                        <i class="fa-solid fa-truck-field"></i> Proveedores
                    </a>
                </li>
                <li class="<?= $paginaActual === 'informes.php' ? 'active' : '' ?>">
                    <a href="<?= e(base_url('view/informes.php')) ?>">
                        <i class="fa-solid fa-file-lines"></i> Informes
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($rolActual, ['administrador', 'vendedor', 'cajero'], true)): ?>
                <li class="<?= $paginaActual === 'pos.php' ? 'active' : '' ?>">
                    <a href="<?= e(base_url('view/pos.php')) ?>">
                        <i class="fa-solid fa-cash-register"></i> Ventas
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($rolActual === 'administrador'): ?>
                <li class="<?= $paginaActual === 'configuracion.php' ? 'active' : '' ?>">
                    <a href="<?= e(base_url('view/configuracion.php')) ?>">
                        <i class="fa-solid fa-gear"></i> Configuración
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    <a href="<?= e(base_url('index.php?action=logout')) ?>" class="logout-btn">
        <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
    </a>
</aside>
