<?php
// RF 5.2: kardex / historial general de movimientos de inventario.
// Con ?id_producto=X filtra un producto; sin él muestra el historial general.
require_once __DIR__ . '/../config/config.php';
require_role(['administrador']);

require_once __DIR__ . '/../model/producto.php';
require_once __DIR__ . '/../model/movimiento.php';

$movModel = new Movimiento();
$idProd = (int) get('id_producto');

if ($idProd > 0) {
    $producto = (new Producto())->obtenerPorId($idProd);
    if (!$producto) {
        show_error(404, 'Producto no encontrado.');
    }
    $movs = $movModel->kardexPorProducto($idProd, 200);
    $titulo = 'Kardex · ' . $producto['nombre'];
} else {
    $producto = null;
    $productos = (new Producto())->listar(null);
    $movs = $movModel->recientes(100);
    $titulo = 'Kardex · Historial de movimientos';
}
require __DIR__ . '/partials/head.php';
?>

<h2>Kardex — Historial de movimientos</h2>
<p class="muted">Entradas y salidas de inventario vinculadas a compras y ventas.</p>

<div class="crud-card">
    <form method="GET" class="form-grid">
        <div class="form-group">
            <label>Filtrar por producto</label>
            <select name="id_producto">
                <option value="">— Todos (historial general) —</option>
                <?php if ($producto === null): ?>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= e($p['id_producto']) ?>"><?= e($p['nombre']) ?> (stock <?= e($p['cantidad_stock']) ?>)</option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="<?= e($producto['id_producto']) ?>" selected><?= e($producto['nombre']) ?></option>
                <?php endif; ?>
            </select>
        </div>
        <div class="btn-container">
            <button type="submit" class="btn-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
            <?php if ($producto !== null): ?>
                <a class="btn-primary btn-cancel" href="<?= e(base_url('view/kardex.php')) ?>">Ver todo</a>
                <a class="btn-primary btn-cancel" href="<?= e(base_url('view/inventario.php')) ?>">Inventario</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($producto !== null): ?>
<p class="muted">
    Producto: <strong><?= e($producto['nombre']) ?></strong> ·
    Código: <?= e($producto['codigo_barras'] ?? '—') ?> ·
    Stock actual: <strong><?= e($producto['cantidad_stock']) ?></strong>
</p>
<?php endif; ?>

<div class="crud-card">
    <h3><?= $producto !== null ? 'Movimientos del producto (' . e(count($movs)) . ')' : 'Movimientos recientes (' . e(count($movs)) . ')' ?></h3>
    <div class="table-responsive">
    <table>
        <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cant.</th><th>Antes → Después</th><th>Ref.</th><th>Motivo</th><th>Usuario</th></tr></thead>
        <tbody>
            <?php foreach ($movs as $m): ?>
                <tr>
                    <td><?= e($m['fecha']) ?></td>
                    <td>
                        <?php if ($producto !== null): ?>
                            <strong><?= e($producto['nombre']) ?></strong>
                        <?php else: ?>
                            <a href="<?= e(base_url('view/kardex.php?id_producto=' . $m['id_producto'])) ?>"><strong><?= e($m['producto']) ?></strong></a>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-<?= e($m['tipo'] === 'entrada' ? 'activo' : 'inactivo') ?>"><?= e($m['tipo']) ?></span></td>
                    <td><?= e($m['cantidad']) ?></td>
                    <td><?= e($m['stock_antes']) ?> → <?= e($m['stock_despues']) ?></td>
                    <td>
                        <?php if (!empty($m['id_venta'])): ?>
                            <a href="<?= e(base_url('view/recibo.php?id=' . $m['id_venta'])) ?>">Venta #<?= e($m['id_venta']) ?></a>
                        <?php elseif (!empty($m['id_compra'])): ?>
                            <a href="<?= e(base_url('view/compras.php?ver=' . $m['id_compra'])) ?>">Compra #<?= e($m['id_compra']) ?></a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><?= e($m['motivo'] ?? '—') ?></td>
                    <td><?= e($m['usuario'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($movs)): ?>
                <tr><td colspan="8" class="muted">Sin movimientos registrados. Las compras y ventas nuevas sí generan kardex.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
