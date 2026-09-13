<?php
require_once __DIR__ . '/../config/config.php';
require_role(['administrador']);

require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../model/producto.php';
require_once __DIR__ . '/../model/venta.php';

$user = current_user();
$hoy = (new Venta())->resumenHoy();
$prodModel = new Producto();
$totalProductos = $prodModel->contarActivos();
$stockBajo = $prodModel->conStockBajo();
$totalUsuarios = (new Usuario())->contarUsuarios();
$ultimasVentas = (new Venta())->ultimas(5);

$titulo = 'Dashboard';
require __DIR__ . '/partials/head.php';
?>

<?php $tituloNavbar = 'Panel de Control'; require __DIR__ . '/partials/navbar.php'; ?>

<section class="cards-grid">
    <div class="card">
        <div class="card-info">
            <h4>Ventas de Hoy</h4>
            <span><?= e(money($hoy['total'] ?? 0)) ?></span>
            <small><?= (int) ($hoy['cantidad'] ?? 0) ?> ventas</small>
        </div>
        <div class="card-icon"><i class="fa-solid fa-dollar-sign"></i></div>
    </div>
    <div class="card">
        <div class="card-info">
            <h4>Productos Activos</h4>
            <span><?= e($totalProductos) ?></span>
            <small>Valor inventario: <?= e(money($prodModel->valorInventario())) ?></small>
        </div>
        <div class="card-icon"><i class="fa-solid fa-box-open"></i></div>
    </div>
    <div class="card">
        <div class="card-info">
            <h4>Stock Bajo</h4>
            <span><?= e(count($stockBajo)) ?></span>
            <small>productos por agotarse</small>
        </div>
        <div class="card-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    </div>
    <div class="card">
        <div class="card-info">
            <h4>Usuarios Registrados</h4>
            <span><?= e($totalUsuarios) ?></span>
            <small>en el sistema</small>
        </div>
        <div class="card-icon"><i class="fa-solid fa-users"></i></div>
    </div>
</section>

<section class="grid-2">
    <div class="content-box">
        <h3>Últimas Ventas</h3>
        <?php if (empty($ultimasVentas)): ?>
            <p class="muted">Aún no hay ventas registradas.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>#</th><th>Fecha</th><th>Vendedor</th><th>Total</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php foreach ($ultimasVentas as $v): ?>
                        <tr>
                            <td><?= e($v['numero_recibo'] ?? ('#' . $v['id_venta'])) ?></td>
                            <td><?= e($v['fecha']) ?></td>
                            <td><?= e($v['vendedor']) ?></td>
                            <td><?= e(money($v['total'])) ?></td>
                            <td><span class="badge badge-<?= e($v['estado']) ?>"><?= e($v['estado']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div class="content-box">
        <h3>Alerta de Stock Bajo</h3>
        <?php if (empty($stockBajo)): ?>
            <p class="muted">Sin alertas. Todo el inventario está por encima del mínimo.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Producto</th><th>Stock</th><th>Mínimo</th></tr></thead>
                <tbody>
                    <?php foreach ($stockBajo as $p): ?>
                        <tr>
                            <td><strong><?= e($p['nombre']) ?></strong></td>
                            <td><span class="badge badge-bajo"><?= e($p['cantidad_stock']) ?></span></td>
                            <td><?= e($p['stock_minimo']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/partials/foot.php'; ?>
