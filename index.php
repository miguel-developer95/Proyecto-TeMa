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
    $rol = $_POST['rol'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? null;
    $documento = $_POST['documento'] ?? null;

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
    $controller->login($username, $password);
} 
elseif ($action === 'update_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($id) {
        $controller->editar($id, $username, $password);
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
elseif ($action === 'logout') {
    $controller->logout();
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