<?php
// Deshabilitar la memoria caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user'])) {
    header("Location: /Proyecto-TeMa/view/login.php");
    exit();
}
$rolActual = strtolower($_SESSION['user']['rol'] ?? '');
if ($rolActual !== 'administrador') {
    header("Location: /Proyecto-TeMa/view/pos.php");
    exit();
}

require_once __DIR__ . '/../model/usuario.php';
$usuarioModel = new Usuario();

$totalUsuarios = $usuarioModel->contarUsuarios();
$usuarios = $usuarioModel->obtenerTodos();

$editMode = isset($_GET['edit_id']);
$editId = $_GET['edit_id'] ?? '';
$editUsername = $_GET['edit_username'] ?? '';
$editEmail = $_GET['edit_email'] ?? '';
$editDocumento = $_GET['edit_documento'] ?? '';
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

    .form-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1.5px solid #f3c6d8;
        border-radius: 8px;
        box-sizing: border-box;
        font-size: 14px;
        outline: none;
    }

    .form-group input:focus {
        border-color: #e63c82;
    }

    .btn-container {
        grid-column: span 2;
        display: flex;
        gap: 10px;
        margin-top: 10px;
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

    .action-btn {
        padding: 6px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-activate {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .btn-deactivate {
        background: #fff3e0;
        color: #e65100;
    }

    .btn-edit {
        background: #e3f2fd;
        color: #1976d2;
        margin-right: 5px;
    }

    .btn-delete {
        background: #ffebee;
        color: #c62828;
    }

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
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
            <div style="background:#e8f5e9; color:#2e7d32; padding:12px 16px; border-radius:8px; margin-bottom:15px; font-size:14px;">
                <i class="fa-solid fa-circle-check"></i> Usuario actualizado correctamente.
            </div>
        <?php endif; ?>

        <!-- Formulario (Registrar o Editar) -->
        <div class="crud-card">
            <h3><?php echo $editMode ? 'Editar Usuario #' . htmlspecialchars($editId) : 'Crear Nuevo Usuario'; ?></h3>
            <form action="/Proyecto-TeMa/index.php" method="POST">
                <input type="hidden" name="action" value="<?php echo $editMode ? 'update_user' : 'register'; ?>">
                <?php if ($editMode): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($editId); ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre de Usuario</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($editUsername); ?>" required placeholder="Nombre de usuario">
                    </div>

                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($editEmail); ?>" required placeholder="correo@ejemplo.com">
                    </div>

                    <div class="form-group">
                        <label>Número de Documento</label>
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
                        <label>Contraseña <?php echo $editMode ? '(Dejar vacío para conservar)' : ''; ?></label>
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
                                <a href="/Proyecto-TeMa/view/configuracion.php?edit_id=<?php echo $u['id']; ?>&edit_username=<?php echo urlencode($u['username']); ?>&edit_email=<?php echo urlencode($u['email'] ?? $u['correo_electronico'] ?? ''); ?>&edit_documento=<?php echo urlencode($u['documento'] ?? $u['No.Documento'] ?? ''); ?>&edit_rol=<?php echo urlencode($u['rol'] ?? ''); ?>&edit_estado=<?php echo urlencode($u['estado'] ?? ''); ?>" class="action-btn btn-edit">
                                    <i class="fa-solid fa-pen"></i> Editar
                                </a>
                                <a href="/Proyecto-TeMa/index.php?action=delete_user&id=<?php echo $u['id']; ?>" onclick="return confirm('¿Seguro que deseas eliminar este usuario?');" class="action-btn btn-delete">
                                    <i class="fa-solid fa-trash"></i> Eliminar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php require __DIR__ . '/partials/foot.php'; ?>