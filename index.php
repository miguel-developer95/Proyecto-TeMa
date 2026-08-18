<?php
// Archivo: index.php
require_once "controller/UsuarioController.php";

session_start();
$controller = new UsuarioController();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
    // Acción de registro
    if ($_POST["action"] == "register") {
        $username = $_POST["username"];
        $password = $_POST["password"];

        // Registrar al usuario
        if ($controller->registrar($username, $password)) {
            echo "Usuario registrado correctamente.";
        } else {
            echo "Error al registrar el usuario.";
        }
    }

// Acción de inicio de sesión
    if ($_POST["action"] == "login") {
        $username = $_POST["username"];
        $password = $_POST["password"];

        // Intentar iniciar sesión
        $user = $controller->login($username, $password);

        if ($user) {
            // Usuario autenticado, guardamos la sesión y redirigimos
            $_SESSION["user"] = $user;
            header("Location: index.php");
        } else {
            // Error en la autenticación
            echo "Usuario o contraseña incorrectos.";
        }
    }
}

// Acción de cierre de sesión
if (isset($_GET["action"]) && $_GET["action"] == "logout") {
    // Destruir la sesión y redirigir
    session_destroy();
    header("Location: index.php");
}

// Mostrar vistas dependiendo del estado de la sesión
if (isset($_SESSION["user"])) {
    // Si hay una sesión activa, mostrar el panel principal
    require_once "view/dashboard.php";
} else {
    // Si no hay sesión, mostrar la página de inicio de sesión
    require_once "view/login.php";
}
?>