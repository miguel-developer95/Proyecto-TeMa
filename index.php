<?php
date_default_timezone_set('America/Bogota');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/controller/UsuarioController.php';
$controller = new UsuarioController();

// Captura 'action' de GET o POST
$action = $_REQUEST['action'] ?? '';

if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Datos del registro (deben coincidir con los name="" del formulario en register.php)
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $rol = $_POST['rol'] ?? 'Administrador';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? null;
    $email = $_POST['email'] ?? null;
    $documento = $_POST['documento'] ?? null;

    if ($confirmPassword !== null && $password !== $confirmPassword) {
        header("Location: /Proyecto-TeMa/view/register.php?error=password_mismatch");
        exit();
    }

    if (strlen(trim($password)) < 8) {
        header("Location: /Proyecto-TeMa/view/register.php?error=short_password");
        exit();
    }

    $controller->registrar(
        $nombre,
        $apellido,
        $rol,
        $password,
        $email,
        $documento
    );
}
elseif ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (strlen($password) < 8) {
        header("Location: /Proyecto-TeMa/view/login.php?error=short_password");
        exit();
    }
    $controller->login($username, $password);
} 
elseif ($action === 'update_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? null;
    $documento = $_POST['documento'] ?? null;
    $password = !empty($_POST['password']) ? $_POST['password'] : null;

    if ($password !== null && strlen(trim($password)) < 8) {
        header("Location: /Proyecto-TeMa/view/configuracion.php?edit_id=" . urlencode((string)$id) . "&status=short_password");
        exit();
    }

    if ($id) {
        $controller->editar($id, $username, $email, $documento, $password);
    }
} 
elseif ($action === 'delete_user') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $controller->eliminar($id);
    }
} 
elseif ($action === 'toggle_estado') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $controller->cambiarEstado($id);
    }
}
elseif ($action === 'solicitar_recuperacion' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $controller->solicitarRecuperacion($email);
}
elseif ($action === 'restablecer_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirmar = $_POST['password_confirmar'] ?? '';

    if (strlen($password) < 8) {
        header("Location: /Proyecto-TeMa/view/recuperar_contra.php?token=" . urlencode($token) . "&error=muy_corta");
        exit();
    }

    $controller->restablecerPassword($token, $password, $passwordConfirmar);
}
elseif ($action === 'logout') {
    $controller->logout();
}
// --- Acciones de Punto de Venta (POS) y Ventas ---
elseif ($action === 'pos_add') {
    require_once __DIR__ . '/controller/VentaController.php';
    (new VentaController())->posAdd();
}
elseif ($action === 'pos_qty') {
    require_once __DIR__ . '/controller/VentaController.php';
    (new VentaController())->posSetQty();
}
elseif ($action === 'pos_remove') {
    require_once __DIR__ . '/controller/VentaController.php';
    (new VentaController())->posRemove();
}
elseif ($action === 'pos_clear') {
    require_once __DIR__ . '/controller/VentaController.php';
    (new VentaController())->posClear();
}
elseif ($action === 'pos_pause') {
    require_once __DIR__ . '/controller/VentaController.php';
    (new VentaController())->posPause();
}
elseif ($action === 'pos_resume') {
    require_once __DIR__ . '/controller/VentaController.php';
    (new VentaController())->posResume();
}
elseif ($action === 'pos_pausada_delete') {
    require_once __DIR__ . '/controller/VentaController.php';
    (new VentaController())->posPausadaDelete();
}
elseif ($action === 'pos_checkout') {
    require_once __DIR__ . '/controller/VentaController.php';
    (new VentaController())->checkout();
}
elseif ($action === 'pos_cliente') {
    require_once __DIR__ . '/controller/VentaController.php';
    (new VentaController())->posCliente();
}
elseif ($action === 'cliente_rapido') {
    require_once __DIR__ . '/controller/VentaController.php';
    (new VentaController())->clienteRapido();
}
elseif ($action === 'venta_anular') {
    require_once __DIR__ . '/controller/VentaController.php';
    (new VentaController())->anular();
}
// --- Acciones de Inventario de Productos ---
elseif ($action === 'descontinuar_producto') {
    require_once __DIR__ . '/controller/producto.php';
    (new ProductoController())->descontinuar();
}
elseif ($action === 'modificar_producto') {
    require_once __DIR__ . '/controller/producto.php';
    (new ProductoController())->editar();
}
elseif ($action === 'registrar_producto') {
    require_once __DIR__ . '/controller/producto.php';
    (new ProductoController())->registrar();
}
else {
    // Si el usuario ya inició sesión, redirigir al dashboard en lugar del login
    if (isset($_SESSION['user'])) {
        header("Location: /Proyecto-TeMa/view/dashboard.php");
    } else {
        header("Location: /Proyecto-TeMa/view/login.php");
    }
    exit();
}
?>