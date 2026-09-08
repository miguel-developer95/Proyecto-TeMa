<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) {
    header("Location: /Proyecto-TeMa/view/login.php");
    exit();
}

require_once __DIR__ . '/../model/usuario.php';
$usuarioModel = new Usuario();

$totalUsuarios = $usuarioModel->contarUsuarios();
$usuarios = $usuarioModel->obtenerTodos();

$editMode = isset($_GET['edit_id']);
$editId = $_GET['edit_id'] ?? '';
$editUsername = $_GET['edit_username'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Tentaciones Marlly</title>
    <link rel="stylesheet" href="/Proyecto-TeMa/public/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Estilos Base e idénticos al Dashboard */
        * html,
        body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100%;
            width: 100%;
        }

        body {
            display: flex;
            background-color: #fce4ec;
            overflow-x: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .sidebar {
            width: 280px;
            min-width: 280px;
            background: linear-gradient(0deg, #3d405b 10%, #ffffff 50%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px 0;
            height: 100vh;
            box-sizing: border-box;
            box-shadow: 10px 0 30px 10px rgba(0, 0, 0, 0.2);
        }

        .sidebar-header {
            text-align: center;
            padding: 0 15px 20px;
        }

        .sidebar-header img {
            width: 190px;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .sidebar-header h3 {
            font-size: 16px;
            color: #e63c82;
        }

        .menu-list {
            list-style: none;
            margin-top: 20px;
        }

        .menu-list li a {
            display: flex;
            align-items: center;
            font-size: 16px;
            font-weight: 600;
            color: #adb5bd;
            text-decoration: none;
            padding: 12px 20px;
            margin: 6px 15px;
            border-radius: 8px;
            border: 2px solid transparent;
            transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1),
                background-color 0.3s ease,
                box-shadow 0.3s ease;
            will-change: transform;
        }

        .menu-list li a i {
            margin-right: 12px;
            font-size: 16px;
        }

        .menu-list li a:hover,
        .menu-list li.active a {
            background-color: #e63c82;
            color: #ffffff;
            border-color: #c22b68;
            transform: translateY(-6px);
            box-shadow: 0 6px 16px rgba(230, 60, 130, 0.3);
        }

        .logout-btn {
            padding: 12px 20px;
            color: #ff6b6b;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 16px;
            font-weight: 600;
            margin: 10px 15px;
            border-radius: 8px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .logout-btn i {
            margin-right: 12px;
            font-size: 18px;
        }

        .logout-btn:hover {
            background-color: #c41313;
            color: #ffffff;
            transform: translateY(-6px);
            border-color: #ff6b6b;
        }

        /* Contenido Principal */
        .main-content { 
            flex: 1; 
            padding: 30px; 
            overflow-y: auto; 
        }

        .crud-card { 
            background: #fff; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            margin-bottom: 25px; 
        }
        
        /* Formulario y Botón de Registro Corregido */
        .form-grid { 
            display: flex; 
            gap: 15px; 
            align-items: flex-end; 
            flex-wrap: wrap;
        }

        .form-group { 
            flex: 1; 
            min-width: 200px;
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
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 14px;
        }

        .btn-container {
            display: flex;
            gap: 10px;
        }

        .btn-primary {
            background-color: #e63c82; 
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
            gap: 8px; 
            transition: 0.3s; 
            height: 42px;
            font-size: 14px;
            white-space: nowrap;
        }

        .btn-primary:hover { 
            background-color: #c22b68; 
            transform: translateY(-2px);
        }

        .btn-cancel { 
            background-color: #6c757d; 
        }

        .btn-cancel:hover { 
            background-color: #5a6268; 
        }

        /* Tabla de Usuarios */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
        }

        th, td { 
            padding: 14px; 
            text-align: left; 
            border-bottom: 1px solid #eee; 
            font-size: 14px; 
        }

        th { 
            background-color: #f8f9fa; 
            color: #555; 
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

        .btn-edit { 
            background: #e3f2fd; 
            color: #1976d2; 
            margin-right: 5px; 
        }

        .btn-delete { 
            background: #ffebee; 
            color: #c62828; 
        }
    </style>
</head>
<body>

    <!-- Sidebar / Menú Lateral -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-header">
                <img src="/Proyecto-TeMa/public/logo.png" alt="Logo">
                <h3>Tentaciones Marlly</h3>
            </div>

            <ul class="menu-list">
                <li>
                    <a href="/Proyecto-TeMa/view/dashboard.php">
                        <i class="fa-solid fa-chart-line"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fa-solid fa-box"></i> Productos
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fa-solid fa-cart-shopping"></i> Ventas
                    </a>
                </li>
                <li class="active">
                    <a href="/Proyecto-TeMa/view/configuracion.php">
                        <i class="fa-solid fa-gear"></i> Configuración
                    </a>
                </li>
            </ul>
        </div>
        <a href="/Proyecto-TeMa/index.php?action=logout" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
        </a>
    </aside>

    <!-- Contenido Principal -->
    <main class="main-content">
        <h2>Gestión de Usuarios</h2>
        <p style="color: #666; margin-bottom: 20px;">Total de usuarios registrados en el sistema: <strong><?php echo $totalUsuarios; ?></strong></p>

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
                        <label>Contraseña <?php echo $editMode ? '(Dejar vacío para conservar)' : ''; ?></label>
                        <input type="password" name="password" <?php echo $editMode ? '' : 'required'; ?> placeholder="<?php echo $editMode ? 'Opcional' : 'Contraseña'; ?>">
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
                        <th>No.Documento</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td>#<?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['correo_electronico'] ?? $u['email'] ?? $u['USUA_correo'] ?? 'Sin correo'); ?></td>
                        <td><?php echo htmlspecialchars($u['No.Documento'] ?? $u['documento'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="/Proyecto-TeMa/view/configuracion.php?edit_id=<?php echo $u['id']; ?>&edit_username=<?php echo urlencode($u['username']); ?>" class="action-btn btn-edit">
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
    </main>

</body>
</html>