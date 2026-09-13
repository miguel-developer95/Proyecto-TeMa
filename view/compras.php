<?php
// RF 4.1, 4.3, 4.4: crear compras, historial y anulación.
require_once __DIR__ . '/../config/config.php';
require_role(['administrador']);

require_once __DIR__ . '/../model/compra.php';
require_once __DIR__ . '/../model/proveedor.php';
require_once __DIR__ . '/../model/producto.php';
require_once __DIR__ . '/../model/metodo_pago.php';

$compraModel = new Compra();
$ver = get('ver') !== '' ? $compraModel->obtenerPorId((int) get('ver')) : false;
$nueva = get('nueva') === '1';
$editarId = get('editar_compra') !== '' ? (int) get('editar_compra') : 0;
$editCompra = $editarId > 0 ? $compraModel->obtenerPorId($editarId) : false;
if ($editCompra && $editCompra['estado'] !== 'registrada') {
    flash('error', 'Solo se pueden editar compras registradas.');
    redirect('view/compras.php?ver=' . $editarId);
}

if ($ver) {
    $detalle = $compraModel->obtenerDetalle((int) $ver['id_compra']);
    require_once __DIR__ . '/../model/movimiento.php';
    $movsCompra = (new Movimiento())->porReferencia(null, (int) $ver['id_compra']);
} elseif ($editCompra) {
    $detalleEdit = $compraModel->obtenerDetalle($editarId);
    $proveedoresEdit = (new Proveedor())->listar('activo');
    $productosEdit = (new Producto())->listar('activo');
    $metodosEdit = (new MetodoPago())->listarActivos();
} elseif ($nueva) {
    $proveedores = (new Proveedor())->listar('activo');
    $productos = (new Producto())->listar('activo');
    $metodos = (new MetodoPago())->listarActivos();
    $items = $_SESSION['compra_items'] ?? [];
    $mapProd = [];
    foreach ($productos as $p) {
        $mapProd[(int) $p['id_producto']] = $p;
    }
    $totalBorrador = 0.0;
    foreach ($items as $idProd => $it) {
        $totalBorrador += $it['cantidad'] * $it['precio'];
    }
} else {
    $historial = $compraModel->obtenerTodas();
}

$titulo = 'Compras';
require __DIR__ . '/partials/head.php';
?>

<h2>Compras</h2>
<p class="muted">Registro de compras a proveedores con actualización de stock.</p>

<div class="toolbar">
    <div></div>
    <div>
        <?php if ($ver || $nueva || $editCompra): ?>
            <a class="btn-primary btn-cancel" href="<?= e(base_url('view/compras.php')) ?>">Volver al historial</a>
        <?php endif; ?>
        <?php if (!$nueva && !$editCompra): ?>
            <a class="btn-primary" href="<?= e(base_url('view/compras.php?nueva=1')) ?>"><i class="fa-solid fa-plus"></i> Nueva Compra</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($ver): ?>
    <div class="crud-card">
        <h3>Compra #<?= e($ver['id_compra']) ?>
            <span class="badge badge-<?= e($ver['estado']) ?>"><?= e($ver['estado']) ?></span>
        </h3>
        <p class="muted">
            Fecha: <?= e($ver['fecha_hora']) ?> ·
            Proveedor: <strong><?= e($ver['proveedor']) ?></strong> ·
            Registró: <?= e($ver['usuario']) ?> ·
            Total: <strong><?= e(money($ver['total_compra'])) ?></strong>
        </p>
        <div class="table-responsive">
        <table>
            <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio compra</th><th>Subtotal</th></tr></thead>
            <tbody>
                <?php foreach ($detalle as $d): ?>
                    <tr>
                        <td><?= e($d['nombre_producto']) ?></td>
                        <td><?= e($d['cantidad']) ?></td>
                        <td><?= e(money($d['precio_unitario_compra'])) ?></td>
                        <td><?= e(money($d['subtotal'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php if ($ver['estado'] === 'registrada'): ?>
            <a class="btn-primary" href="<?= e(base_url('view/compras.php?editar_compra=' . $ver['id_compra'])) ?>"><i class="fa-solid fa-pen"></i> Editar compra</a>
            <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form"
                  onsubmit="return confirm('¿Anular esta compra? Se revertirá el stock.');">
                <input type="hidden" name="action" value="compra_anular">
                <input type="hidden" name="id_compra" value="<?= e($ver['id_compra']) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn-primary btn-cancel"><i class="fa-solid fa-ban"></i> Anular compra</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!empty($movsCompra)): ?>
    <div class="crud-card">
        <h3>Kardex generado por esta compra</h3>
        <div class="table-responsive">
        <table>
            <thead><tr><th>Producto</th><th>Tipo</th><th>Cant.</th><th>Antes → Después</th><th>Motivo</th></tr></thead>
            <tbody>
                <?php foreach ($movsCompra as $m): ?>
                    <tr>
                        <td><?= e($m['producto']) ?></td>
                        <td><span class="badge badge-<?= e($m['tipo'] === 'entrada' ? 'activo' : 'inactivo') ?>"><?= e($m['tipo']) ?></span></td>
                        <td><?= e($m['cantidad']) ?></td>
                        <td><?= e($m['stock_antes']) ?> → <?= e($m['stock_despues']) ?></td>
                        <td><?= e($m['motivo'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

<?php elseif ($editCompra): ?>
    <div class="crud-card">
        <h3>Editar Compra #<?= e($editCompra['id_compra']) ?> <span class="badge badge-registrada">registrada</span></h3>
        <p class="muted">Cantidad en 0 quita la línea. Al guardar se ajusta el stock por diferencia y se escribe kardex.</p>
        <form action="<?= e(base_url('index.php')) ?>" method="POST">
            <input type="hidden" name="action" value="compra_actualizar">
            <input type="hidden" name="id_compra" value="<?= e($editCompra['id_compra']) ?>">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Proveedor *</label>
                    <select name="id_proveedor" required>
                        <?php foreach ($proveedoresEdit as $pr): ?>
                            <option value="<?= e($pr['id_proveedor']) ?>" <?= (int) $pr['id_proveedor'] === (int) $editCompra['id_proveedor'] ? 'selected' : '' ?>><?= e($pr['nombre_razon_social']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Método de pago</label>
                    <select name="id_metodo_pago">
                        <option value="">—</option>
                        <?php foreach ($metodosEdit as $m): ?>
                            <option value="<?= e($m['id_metodo_pago']) ?>" <?= (int) ($editCompra['id_metodo_pago'] ?? 0) === (int) $m['id_metodo_pago'] ? 'selected' : '' ?>><?= e($m['nombre_metodo']) ?><?= $m['empresa'] ? ' - ' . e($m['empresa']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
            <table>
                <thead><tr><th>Producto</th><th>Cantidad (0=quitar)</th><th>Precio compra c/u</th></tr></thead>
                <tbody>
                    <?php foreach ($detalleEdit as $d): ?>
                        <tr>
                            <td><strong><?= e($d['nombre_producto']) ?></strong><input type="hidden" name="linea_id[]" value="<?= e($d['id_producto']) ?>"></td>
                            <td><input type="number" name="linea_cantidad[]" value="<?= e($d['cantidad']) ?>" min="0" step="1" required></td>
                            <td><input type="number" name="linea_precio[]" value="<?= e($d['precio_unitario_compra']) ?>" min="0" step="0.01" required></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <tr>
                            <td>
                                <select name="linea_id[]">
                                    <option value="">— Agregar producto —</option>
                                    <?php foreach ($productosEdit as $p): ?>
                                        <option value="<?= e($p['id_producto']) ?>"><?= e($p['nombre']) ?> (stock <?= e($p['cantidad_stock']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" name="linea_cantidad[]" value="0" min="0" step="1"></td>
                            <td><input type="number" name="linea_precio[]" value="0" min="0" step="0.01"></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            </div>
            <div class="btn-container">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button>
                <a class="btn-primary btn-cancel" href="<?= e(base_url('view/compras.php?ver=' . $editCompra['id_compra'])) ?>">Cancelar</a>
            </div>
        </form>
    </div>

<?php elseif ($nueva): ?>
    <div class="crud-card">
        <h3>Nueva Compra</h3>
        <form action="<?= e(base_url('index.php')) ?>" method="POST">
            <input type="hidden" name="action" value="compra_item_add">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Producto</label>
                    <select name="id_producto" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?= e($p['id_producto']) ?>"><?= e($p['nombre']) ?> (stock <?= e($p['cantidad_stock']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cantidad</label>
                    <input type="number" name="cantidad" value="1" min="1" step="1" required>
                </div>
                <div class="form-group">
                    <label>Precio de compra c/u</label>
                    <input type="number" name="precio_compra" value="0" min="0" step="0.01" required>
                </div>
                <div class="btn-container">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Agregar línea</button>
                </div>
            </div>
        </form>
    </div>

    <div class="crud-card">
        <h3>Líneas de la compra (<?= e(money($totalBorrador)) ?>)</h3>
        <?php if (empty($items)): ?>
            <p class="muted">Agrega productos para construir la compra.</p>
        <?php else: ?>
            <div class="table-responsive">
            <table>
                <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio c/u</th><th>Subtotal</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($items as $idProd => $it): $p = $mapProd[$idProd] ?? null; ?>
                        <tr>
                            <td><?= e($p['nombre'] ?? ('#' . $idProd)) ?></td>
                            <td><?= e($it['cantidad']) ?></td>
                            <td><?= e(money($it['precio'])) ?></td>
                            <td><?= e(money($it['cantidad'] * $it['precio'])) ?></td>
                            <td>
                                <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                                    <input type="hidden" name="action" value="compra_item_remove">
                                    <input type="hidden" name="id_producto" value="<?= e($idProd) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="action-btn btn-delete"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <form action="<?= e(base_url('index.php')) ?>" method="POST">
                <input type="hidden" name="action" value="compra_guardar">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Proveedor *</label>
                        <select name="id_proveedor" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($proveedores as $pr): ?>
                                <option value="<?= e($pr['id_proveedor']) ?>"><?= e($pr['nombre_razon_social']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Método de pago</label>
                        <select name="id_metodo_pago">
                            <option value="">—</option>
                            <?php foreach ($metodos as $m): ?>
                                <option value="<?= e($m['id_metodo_pago']) ?>"><?= e($m['nombre_metodo']) ?><?= $m['empresa'] ? ' - ' . e($m['empresa']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="btn-container">
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> Guardar compra (<?= e(money($totalBorrador)) ?>)</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div class="crud-card">
        <h3>Historial de Compras</h3>
        <div class="table-responsive">
        <table>
            <thead><tr><th>#</th><th>Fecha</th><th>Proveedor</th><th>Usuario</th><th>Total</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($historial as $c): ?>
                    <tr>
                        <td>#<?= e($c['id_compra']) ?></td>
                        <td><?= e($c['fecha_hora']) ?></td>
                        <td><?= e($c['proveedor']) ?></td>
                        <td><?= e($c['usuario']) ?></td>
                        <td><?= e(money($c['total_compra'])) ?></td>
                        <td><span class="badge badge-<?= e($c['estado']) ?>"><?= e($c['estado']) ?></span></td>
                        <td><a class="action-btn btn-edit" href="<?= e(base_url('view/compras.php?ver=' . $c['id_compra'])) ?>">Ver</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($historial)): ?>
                    <tr><td colspan="7" class="muted">Sin compras registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/foot.php'; ?>
