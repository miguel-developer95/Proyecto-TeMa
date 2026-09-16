<?php
// Deshabilitar la memoria caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../helpers/auth_guard.php';
verificarRol(['administrador']);
require_once __DIR__ . '/../helpers/funciones.php';
require_once __DIR__ . '/../model/usuario.php';

$usuarioModel = new Usuario();

$totalUsuarios = $usuarioModel->contarUsuarios();
$usuarios = $usuarioModel->obtenerTodos();

$editMode = isset($_GET['edit_id']);
$editId = $_GET['edit_id'] ?? '';
$editUsername = $_GET['edit_username'] ?? '';
$editEmail = $_GET['edit_email'] ?? '';
$editDocumento = $_GET['edit_documento'] ?? '';
$editRol = $_GET['edit_rol'] ?? 'Administrador';
$titulo = 'Configuración';
$subtitulo = 'Gestión y administración de cuentas de usuario del sistema';
require __DIR__ . '/partials/head.php';
?>

<style>
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px 20px;
        margin-top: 15px;
    }

    .form-group {
        width: 100%;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 5px;
        color: #333;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1.5px solid #f3c6d8;
        border-radius: 8px;
        box-sizing: border-box;
        font-size: 14px;
        outline: none;
        background-color: #fff;
    }

    .form-group select {
        cursor: pointer;
    }

    .form-group input:focus,
    .form-group select:focus {
        border-color: #e63c82;
    }

    .btn-container {
        grid-column: span 2;
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    @media (max-width: 680px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .btn-container {
            grid-column: span 1;
            flex-direction: column;
        }

        .btn-container button,
        .btn-container a {
            width: 100%;
            justify-content: center;
        }
    }

    .btn-cancel {
        background-color: #6c757d;
        color: white;
        padding: 0 25px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 42px;
        font-size: 14px;
    }

    .btn-cancel:hover {
        background-color: #5a6268;
    }

    .crud-card .action-btn,
    .crud-card .action-btn:link,
    .crud-card .action-btn:visited {
        all: revert;
        box-sizing: border-box !important;
        display: inline-flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        white-space: nowrap !important;
        position: relative !important;
        padding: 6px 12px !important;
        border-radius: 6px !important;
        text-decoration: none !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        line-height: 1.3 !important;
        cursor: pointer !important;
    }

    /* No usar "all: revert" aquí: resetea también font-family y rompe
       el ícono de Font Awesome (queda como un cuadrito/tofu). Solo
       normalizamos posición/tamaño y dejamos que .fa-solid siga
       controlando la fuente y el glifo del ícono. */
    .crud-card .action-btn i {
        display: inline-block !important;
        flex-shrink: 0 !important;
        position: static !important;
        margin: 0 !important;
        padding: 0 !important;
        font-size: 13px !important;
        line-height: 1 !important;
        font-style: normal !important;
        font-variant: normal !important;
        text-rendering: auto !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
    }

    .crud-card .btn-activate { background: #e8f5e9 !important; color: #2e7d32 !important; }
    .crud-card .btn-deactivate { background: #fff3e0 !important; color: #e65100 !important; }
    .crud-card .btn-edit { background: #e3f2fd !important; color: #1976d2 !important; margin-right: 5px !important; }
    .crud-card .btn-delete { background: #ffebee !important; color: #c62828 !important; }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .btn-container {
            grid-column: span 1;
        }
    }
</style>

        <p style="color: #666; margin-bottom: 20px;">Total de usuarios registrados en el sistema: <strong><?php echo $totalUsuarios; ?></strong></p>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'short_password'): ?>
            <div style="background:#ffebee; color:#c62828; padding:12px 16px; border-radius:8px; margin-bottom:15px; font-size:14px;">
                <i class="fa-solid fa-triangle-exclamation"></i> La contraseña debe tener al menos 8 caracteres.
            </div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'created'): ?>
            <div style="background:#e8f5e9; color:#2e7d32; padding:12px 16px; border-radius:8px; margin-bottom:15px; font-size:14px;">
                <i class="fa-solid fa-circle-check"></i> Usuario creado exitosamente. Nombre de usuario asignado automáticamente: <strong><?php echo htmlspecialchars($_GET['username'] ?? ''); ?></strong>
            </div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'user_exists'): ?>
            <div style="background:#ffebee; color:#c62828; padding:12px 16px; border-radius:8px; margin-bottom:15px; font-size:14px;">
                <i class="fa-solid fa-triangle-exclamation"></i> El correo electrónico o número de documento ya está registrado. Por favor verifica.
            </div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
            <div style="background:#e8f5e9; color:#2e7d32; padding:12px 16px; border-radius:8px; margin-bottom:15px; font-size:14px;">
                <i class="fa-solid fa-circle-check"></i> Usuario actualizado correctamente.
            </div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
            <div style="background:#e8f5e9; color:#2e7d32; padding:12px 16px; border-radius:8px; margin-bottom:15px; font-size:14px;">
                <i class="fa-solid fa-circle-check"></i> Usuario desactivado correctamente del sistema (eliminación lógica).
            </div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'estado_actualizado'): ?>
            <div style="background:#e8f5e9; color:#2e7d32; padding:12px 16px; border-radius:8px; margin-bottom:15px; font-size:14px;">
                <i class="fa-solid fa-circle-check"></i> Estado del usuario actualizado correctamente.
            </div>
        <?php endif; ?>

        <!-- Formulario (Registrar o Editar) -->
        <div class="crud-card">
            <h3><?php echo $editMode ? 'Editar Usuario #' . htmlspecialchars($editId) : 'Crear Nuevo Usuario'; ?></h3>
            <form action="/Proyecto-TeMa/index.php" method="POST">
                <input type="hidden" name="action" value="<?php echo $editMode ? 'update_user' : 'register'; ?>">
                <input type="hidden" name="from" value="configuracion">
                <?= csrf_field() ?>
                <?php if ($editMode): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($editId); ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <?php if ($editMode): ?>
                        <div class="form-group">
                            <label>Nombre de Usuario *</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($editUsername); ?>" required placeholder="Nombre de usuario">
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" name="nombre" required placeholder="Primer nombre">
                        </div>

                        <div class="form-group">
                            <label>Apellido *</label>
                            <input type="text" name="apellido" required placeholder="Primer apellido">
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Rol *</label>
                        <select name="rol" required>
                            <?php if (!$editMode): ?>
                                <option value="">Selecciona un rol</option>
                            <?php endif; ?>
                            <option value="Administrador" <?php echo ($editRol === 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                            <option value="Vendedor" <?php echo ($editRol === 'Vendedor') ? 'selected' : ''; ?>>Vendedor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Correo Electrónico *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($editEmail); ?>" required placeholder="correo@ejemplo.com">
                    </div>

                    <div class="form-group">
                        <label>Número de Documento *</label>
                        <input
                            type="text"
                            name="documento"
                            value="<?php echo htmlspecialchars($editDocumento); ?>"
                            required
                            placeholder="Número de documento"
                            inputmode="numeric"
                            pattern="[0-9]+"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            title="Ingresa únicamente números">
                    </div>

                    <div class="form-group">
                        <label>Contraseña <?php echo $editMode ? '(Dejar vacío para conservar)' : '* (mínimo 8 caracteres)'; ?></label>
                        <input type="password" name="password" minlength="8" <?php echo $editMode ? '' : 'required'; ?> placeholder="<?php echo $editMode ? 'Opcional (mínimo 8 caracteres)' : 'Contraseña (mínimo 8 caracteres)'; ?>">
                    </div>

                    <div class="btn-container">
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid <?php echo $editMode ? 'fa-floppy-disk' : 'fa-user-plus'; ?>"></i>
                            <?php echo $editMode ? 'Guardar Cambios' : 'Registrar'; ?>
                        </button>

                        <?php if ($editMode): ?>
                            <a href="/Proyecto-TeMa/view/configuracion.php" class="btn-primary btn-cancel">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="crud-card">
            <h3>Lista de Usuarios Registrados</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Usuario</th>
                            <th>Correo</th>
                            <th>No. Documento</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td>#<?php echo $u['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($u['email'] ?? $u['correo_electronico'] ?? 'Sin correo'); ?></td>
                                <td><?php echo htmlspecialchars($u['documento'] ?? $u['No.Documento'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($u['rol'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if (($u['estado'] ?? '') === 'activo'): ?>
                                        <a href="/Proyecto-TeMa/index.php?action=toggle_estado&id=<?php echo $u['id']; ?>"
                                        onclick="return confirm('¿Desactivar a este usuario? No podrá iniciar sesión mientras esté inactivo.');"
                                        class="action-btn btn-deactivate">
                                            <i class="fa-solid fa-user-slash"></i> Desactivar
                                        </a>
                                    <?php else: ?>
                                        <a href="/Proyecto-TeMa/index.php?action=toggle_estado&id=<?php echo $u['id']; ?>"
                                        onclick="return confirm('¿Activar a este usuario? Podrá volver a iniciar sesión.');"
                                        class="action-btn btn-activate">
                                            <i class="fa-solid fa-user-check"></i> Activar
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/Proyecto-TeMa/view/configuracion.php?edit_id=<?php echo $u['id']; ?>&edit_username=<?php echo urlencode($u['username']); ?>&edit_email=<?php echo urlencode($u['email'] ?? $u['correo_electronico'] ?? ''); ?>&edit_documento=<?php echo urlencode($u['documento'] ?? $u['No.Documento'] ?? ''); ?>&edit_rol=<?php echo urlencode($u['rol'] ?? ''); ?>" class="action-btn btn-edit">
                                        <i class="fa-solid fa-pen"></i> Editar
                                    </a>
                                    <a href="/Proyecto-TeMa/index.php?action=delete_user&id=<?php echo $u['id']; ?>" onclick="return confirm('¿Seguro que deseas desactivar (eliminación lógica) a este usuario?');" class="action-btn btn-delete">
                                        <i class="fa-solid fa-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php require __DIR__ . '/partials/foot.php'; ?>
