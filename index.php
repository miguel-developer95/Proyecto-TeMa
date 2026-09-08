<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/controller/UsuarioController.php';
$controller = new UsuarioController();

// Captura 'action' de GET o POST
$action = $_REQUEST['action'] ?? '';

if ($action === 'register') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? null;
    $documento = $_POST['documento'] ?? null;
    $controller->registrar($username, $password, $email, $documento);
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
elseif ($action === 'logout') {
    $controller->logout();
} 
else {
    // Si no hay acción válida, redirigir al login
    header("Location: /Proyecto-TeMa/view/login.php");
    exit();
}
?>