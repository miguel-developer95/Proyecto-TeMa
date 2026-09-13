<?php
// RF 4.2: registro de proveedores.
require_once __DIR__ . '/../config/config.php';
require_role(['administrador']);

require_once __DIR__ . '/../model/proveedor.php';
$model = new Proveedor();

$verInactivos = get('estado') === 'inactivo';
$lista = $model->listar($verInactivos ? 'inactivo' : 'activo');

$editando = null;
if (get('edit') !== '') {
    $editando = $model->obtenerPorId((int) get('edit')) ?: null;
}

$titulo = 'Proveedores';
require __DIR__ . '/partials/head.php';
?>

<h2>Proveedores</h2>
<p class="muted">Registro y mantenimiento de proveedores.</p>

<div class="toolbar">
    <div></div>
    <div>
        <a class="btn-primary <?= $verInactivos ? '' : 'btn-cancel' ?>" href="<?= e(base_url('view/proveedores.php')) ?>">Activos</a>
        <a class="btn-primary <?= $verInactivos ? 'btn-cancel' : '' ?>" href="<?= e(base_url('view/proveedores.php?estado=inactivo')) ?>">Inactivos</a>
    </div>
</div>

<div class="crud-card">
    <h3><?= $editando ? 'Editar Proveedor' : 'Registrar Nuevo Proveedor' ?></h3>
    <form action="<?= e(base_url('index.php')) ?>" method="POST">
        <input type="hidden" name="action" value="proveedor_guardar">
        <?= csrf_field() ?>
        <?php if ($editando): ?><input type="hidden" name="id_proveedor" value="<?= e($editando['id_proveedor']) ?>"><?php endif; ?>
        <div class="form-grid">
            <div class="form-group">
                <label>NIT / Identificación *</label>
                <input type="text" name="identificacion_nit" value="<?= e($editando['identificacion_nit'] ?? '') ?>" required maxlength="30">
            </div>
            <div class="form-group">
                <label>Nombre / Razón social *</label>
                <input type="text" name="nombre_razon_social" value="<?= e($editando['nombre_razon_social'] ?? '') ?>" required maxlength="150">
            </div>
            <div class="form-group">
                <label>Dirección</label>
                <input type="text" name="direccion" value="<?= e($editando['direccion'] ?? '') ?>" maxlength="200">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" value="<?= e($editando['telefono'] ?? '') ?>" maxlength="30">
            </div>
            <div class="form-group">
                <label>Correo electrónico</label>
                <input type="email" name="correo_electronico" value="<?= e($editando['correo_electronico'] ?? '') ?>" maxlength="120">
            </div>
            <div class="btn-container">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $editando ? 'Guardar Cambios' : 'Registrar' ?></button>
                <?php if ($editando): ?>
                    <a href="<?= e(base_url('view/proveedores.php')) ?>" class="btn-primary btn-cancel">Cancelar</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="crud-card">
    <h3>Listado de Proveedores</h3>
    <div class="table-responsive">
    <table>
        <thead><tr><th>NIT</th><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
            <?php foreach ($lista as $pr): ?>
                <tr>
                    <td><?= e($pr['identificacion_nit']) ?></td>
                    <td><strong><?= e($pr['nombre_razon_social']) ?></strong></td>
                    <td><?= e($pr['telefono'] ?? '—') ?></td>
                    <td><?= e($pr['correo_electronico'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= e($pr['estado']) ?>"><?= e($pr['estado']) ?></span></td>
                    <td class="actions">
                        <a href="<?= e(base_url('view/proveedores.php?edit=' . $pr['id_proveedor'])) ?>" class="action-btn btn-edit"><i class="fa-solid fa-pen"></i> Editar</a>
                        <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                            <input type="hidden" name="action" value="proveedor_estado">
                            <input type="hidden" name="id_proveedor" value="<?= e($pr['id_proveedor']) ?>">
                            <input type="hidden" name="estado" value="<?= $pr['estado'] === 'activo' ? 'inactivo' : 'activo' ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="action-btn <?= $pr['estado'] === 'activo' ? 'btn-delete' : 'btn-edit' ?>">
                                <?= $pr['estado'] === 'activo' ? 'Desactivar' : 'Reactivar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($lista)): ?>
                <tr><td colspan="6" class="muted">Sin proveedores para mostrar.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
