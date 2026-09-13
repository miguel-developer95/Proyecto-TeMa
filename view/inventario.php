<?php
// RF 3.x: registro, modificación, descontinuación, listado y alertas de stock.
require_once __DIR__ . '/../config/config.php';
require_role(['administrador']);

require_once __DIR__ . '/../model/producto.php';
require_once __DIR__ . '/../model/proveedor.php';

$prodModel = new Producto();
$q = trim((string) get('q'));
$verInactivos = get('estado') === 'inactivo';
$productos = $prodModel->listar($verInactivos ? 'inactivo' : 'activo', $q);
$proveedores = (new Proveedor())->listar('activo');

$editando = null;
if (get('edit') !== '') {
    $editando = $prodModel->obtenerPorId((int) get('edit')) ?: null;
}

$titulo = 'Inventario';
require __DIR__ . '/partials/head.php';
?>

<?php $subtituloNavbar = 'Gestiona los productos de la tienda.'; require __DIR__ . '/partials/navbar.php'; ?>

<div class="toolbar">
    <form method="GET" class="search-form">
        <input type="text" name="q" placeholder="Buscar por nombre, código o categoría..." value="<?= e($q) ?>">
        <?php if ($verInactivos): ?><input type="hidden" name="estado" value="inactivo"><?php endif; ?>
        <button type="submit" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
    </form>
    <div>
        <a class="btn-primary <?= $verInactivos ? '' : 'btn-cancel' ?>" href="<?= e(base_url('view/inventario.php')) ?>">Activos</a>
        <a class="btn-primary <?= $verInactivos ? 'btn-cancel' : '' ?>" href="<?= e(base_url('view/inventario.php?estado=inactivo')) ?>">Descontinuados</a>
    </div>
</div>

<div class="crud-card">
    <h3><?= $editando ? 'Editar Producto #' . e($editando['id_producto']) : 'Registrar Nuevo Producto' ?></h3>
    <form action="<?= e(base_url('index.php')) ?>" method="POST">
        <input type="hidden" name="action" value="<?= $editando ? 'producto_actualizar' : 'producto_guardar' ?>">
        <?= csrf_field() ?>
        <?php if ($editando): ?><input type="hidden" name="id_producto" value="<?= e($editando['id_producto']) ?>"><?php endif; ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Código de barras</label>
                <input type="text" name="codigo_barras" value="<?= e($editando['codigo_barras'] ?? '') ?>" maxlength="50">
            </div>
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="nombre" value="<?= e($editando['nombre'] ?? '') ?>" required maxlength="150">
            </div>
            <div class="form-group">
                <label>Categoría</label>
                <input type="text" name="categoria" value="<?= e($editando['categoria'] ?? '') ?>" maxlength="80">
            </div>
            <div class="form-group">
                <label>Proveedor</label>
                <select name="id_proveedor">
                    <option value="">— Sin proveedor —</option>
                    <?php foreach ($proveedores as $pr): ?>
                        <option value="<?= e($pr['id_proveedor']) ?>" <?= ((int) ($editando['id_proveedor'] ?? 0) === (int) $pr['id_proveedor']) ? 'selected' : '' ?>>
                            <?= e($pr['nombre_razon_social']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Precio compra *</label>
                <input type="number" name="precio_compra" value="<?= e($editando['precio_compra'] ?? '0') ?>" min="0" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Precio venta *</label>
                <input type="number" name="precio_venta" value="<?= e($editando['precio_venta'] ?? '0') ?>" min="0" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Cantidad en stock *</label>
                <input type="number" name="cantidad_stock" value="<?= e($editando['cantidad_stock'] ?? '0') ?>" min="0" step="1" required>
            </div>
            <div class="form-group">
                <label>Stock mínimo (alerta)</label>
                <input type="number" name="stock_minimo" value="<?= e($editando['stock_minimo'] ?? '0') ?>" min="0" step="1">
            </div>
            <div class="form-group form-span">
                <label>Descripción</label>
                <input type="text" name="descripcion" value="<?= e($editando['descripcion'] ?? '') ?>">
            </div>
            <div class="btn-container">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $editando ? 'Guardar Cambios' : 'Registrar' ?></button>
                <?php if ($editando): ?>
                    <a href="<?= e(base_url('view/inventario.php')) ?>" class="btn-primary btn-cancel">Cancelar</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="crud-card">
    <h3>Listado de Productos</h3>
    <div class="table-responsive">
    <table>
        <thead>
            <tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>P. Venta</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $p): ?>
                <tr class="<?= ((int) $p['alerta_stock_bajo'] === 1 && $p['estado'] === 'activo') ? 'row-alert' : '' ?>">
                    <td><?= e($p['codigo_barras'] ?? '—') ?></td>
                    <td><strong><?= e($p['nombre']) ?></strong>
                        <?php if ((int) $p['alerta_stock_bajo'] === 1 && $p['estado'] === 'activo'): ?>
                            <span class="badge badge-bajo">stock bajo</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($p['categoria'] ?? '—') ?></td>
                    <td><?= e(money($p['precio_venta'])) ?></td>
                    <td><?= e($p['cantidad_stock']) ?> / mín <?= e($p['stock_minimo']) ?></td>
                    <td><span class="badge badge-<?= e($p['estado']) ?>"><?= e($p['estado']) ?></span></td>
                    <td class="actions">
                        <a href="<?= e(base_url('view/inventario.php?edit=' . $p['id_producto'])) ?>" class="action-btn btn-edit"><i class="fa-solid fa-pen"></i> Editar</a>
                        <a href="<?= e(base_url('view/kardex.php?id_producto=' . $p['id_producto'])) ?>" class="action-btn btn-edit"><i class="fa-solid fa-clock-rotate-left"></i> Kardex</a>
                        <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                            <input type="hidden" name="action" value="producto_estado">
                            <input type="hidden" name="id_producto" value="<?= e($p['id_producto']) ?>">
                            <input type="hidden" name="estado" value="<?= $p['estado'] === 'activo' ? 'inactivo' : 'activo' ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="action-btn <?= $p['estado'] === 'activo' ? 'btn-delete' : 'btn-edit' ?>">
                                <?= $p['estado'] === 'activo' ? 'Descontinuar' : 'Reactivar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($productos)): ?>
                <tr><td colspan="7" class="muted">Sin productos para mostrar.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
