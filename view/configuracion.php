<?php
require_once __DIR__ . '/../config/config.php';
require_role(['administrador']); // RF 2.3: solo el administrador gestiona usuarios

require_once __DIR__ . '/../model/usuario.php';
$model = new Usuario();

$totalUsuarios = $model->contarUsuarios();
$totalActivos = $model->contarPorEstado('activo');
$totalInactivos = $model->contarPorEstado('inactivo');

$verInactivos = get('estado') === 'inactivo';
$usuarios = $model->listar($verInactivos ? 'inactivo' : 'activo');
$filtroQS = $verInactivos ? '?estado=inactivo' : '';

$editando = null;
if (get('edit') !== '') {
    $editando = $model->obtenerPorId((int) get('edit')) ?: null;
}

$titulo = 'Configuración';
require __DIR__ . '/partials/head.php';
?>

<?php $tituloNavbar = 'Gestión de Usuarios'; $subtituloNavbar = "Total: {$totalUsuarios} · Activos: {$totalActivos} · Inactivos: {$totalInactivos}"; require __DIR__ . '/partials/navbar.php'; ?>

<div class="toolbar">
    <div></div>
    <div>
        <a class="btn-primary <?= $verInactivos ? '' : 'btn-cancel' ?>" href="<?= e(base_url('view/configuracion.php')) ?>">Activos</a>
        <a class="btn-primary <?= $verInactivos ? 'btn-cancel' : '' ?>" href="<?= e(base_url('view/configuracion.php?estado=inactivo')) ?>">Inactivos</a>
    </div>
</div>

<div class="crud-card">
    <h3><?= $editando ? 'Editar Usuario #' . e($editando['id']) : 'Crear Nuevo Usuario' ?></h3>
    <form action="<?= e(base_url('index.php')) ?>" method="POST">
        <input type="hidden" name="action" value="save_user">
        <?= csrf_field() ?>
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?= e($editando['id']) ?>">
        <?php endif; ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" value="<?= e($editando['nombre'] ?? '') ?>" required maxlength="80">
            </div>
            <div class="form-group">
                <label>Apellido</label>
                <input type="text" name="apellido" value="<?= e($editando['apellido'] ?? '') ?>" required maxlength="80">
            </div>
            <div class="form-group">
                <label>Nombre de Usuario</label>
                <input type="text" name="username" value="<?= e($editando['username'] ?? '') ?>" required maxlength="60">
            </div>
            <div class="form-group">
                <label>Rol</label>
                <select name="rol" required>
                    <?php foreach (['Administrador', 'Vendedor'] as $r): ?>
                        <option value="<?= e($r) ?>" <?= ($editando['rol'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="email" value="<?= e($editando['email'] ?? '') ?>" placeholder="correo@ejemplo.com" maxlength="120">
            </div>
            <div class="form-group">
                <label>Número de Documento</label>
                <input type="text" name="documento" value="<?= e($editando['documento'] ?? '') ?>" placeholder="Número de documento" maxlength="30">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado">
                    <option value="activo" <?= ($editando['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactivo" <?= ($editando['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="form-group">
                <label>Contraseña <?= $editando ? '(vacía = conservar)' : '' ?></label>
                <input type="password" name="password" <?= $editando ? '' : 'required' ?> minlength="<?= PASSWORD_MIN_LENGTH ?>" placeholder="<?= $editando ? 'Opcional' : 'Contraseña' ?>" autocomplete="new-password">
            </div>
            <div class="btn-container">
                <button type="submit" class="btn-primary">
                    <i class="fa-solid <?= $editando ? 'fa-floppy-disk' : 'fa-user-plus' ?>"></i>
                    <?= $editando ? 'Guardar Cambios' : 'Registrar' ?>
                </button>
                <?php if ($editando): ?>
                    <a href="<?= e(base_url('view/configuracion.php' . $filtroQS)) ?>" class="btn-primary btn-cancel">Cancelar</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="crud-card">
    <h3>Lista de Usuarios <?= $verInactivos ? 'Inactivos' : 'Activos' ?></h3>
    <div class="table-responsive">
    <table>
        <thead>
            <tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Correo</th><th>Documento</th><th>Estado</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td>#<?= e($u['id']) ?></td>
                    <td><strong><?= e($u['username']) ?></strong></td>
                    <td><?= e(trim($u['nombre'] . ' ' . $u['apellido'])) ?></td>
                    <td><?= e($u['rol']) ?></td>
                    <td><?= e($u['email'] ?? '—') ?></td>
                    <td><?= e($u['documento'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= e($u['estado']) ?>"><?= e($u['estado']) ?></span></td>
                    <td class="actions">
                        <a href="<?= e(base_url('view/configuracion.php' . $filtroQS . ($filtroQS ? '&' : '?') . 'edit=' . $u['id'])) ?>" class="action-btn btn-edit">
                            <i class="fa-solid fa-pen"></i> Editar
                        </a>
                        <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form"
                              onsubmit="return confirm('¿Seguro que deseas <?= $u['estado'] === 'activo' ? 'desactivar' : 'reactivar' ?> este usuario?');">
                            <input type="hidden" name="action" value="user_estado">
                            <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                            <input type="hidden" name="estado" value="<?= $u['estado'] === 'activo' ? 'inactivo' : 'activo' ?>">
                            <input type="hidden" name="return_estado" value="<?= $verInactivos ? 'inactivo' : 'activo' ?>">
                            <?= csrf_field() ?>
                            <?php if ($u['estado'] === 'activo'): ?>
                                <button type="submit" class="action-btn btn-delete">
                                    <i class="fa-solid fa-ban"></i> Desactivar
                                </button>
                            <?php else: ?>
                                <button type="submit" class="action-btn btn-edit">
                                    <i class="fa-solid fa-rotate-left"></i> Reactivar
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="8" class="muted">Sin usuarios <?= $verInactivos ? 'inactivos' : 'activos' ?> para mostrar.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
