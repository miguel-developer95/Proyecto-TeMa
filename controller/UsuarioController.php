<?php
// controller/UsuarioController.php
require_once __DIR__ . '/../model/usuario.php';

class UsuarioController {
    
    public function registrar($username, $password) {
        $usuarioModel = new Usuario();
        $resultado = $usuarioModel->registrar($username, $password);

        if ($resultado) {
            // Éxito -> Redirige al login usando ruta raíz para evitar 404
            header("Location: /Proyecto-TeMa/view/login.php?status=registered");
            exit(); 
        } else {
            // Error (Usuario duplicado) -> Redirige a la vista de registro
            header("Location: /Proyecto-TeMa/view/register.php?error=user_exists");
            exit();
        }
    }

    public function login($username, $password) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $usuarioModel = new Usuario();
        $user = $usuarioModel->login($username, $password);

        if ($user) {
            $_SESSION['user'] = $user;
            header("Location: /Proyecto-TeMa/view/dashboard.php");
            exit();
        } else {
            header("Location: /Proyecto-TeMa/view/login.php?error=invalid_credentials");
            exit();
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header("Location: /Proyecto-TeMa/view/login.php");
        exit();
    }

    public function eliminar($id) {
        $usuarioModel = new Usuario();
        if ($usuarioModel->eliminar($id)) {
            header("Location: /Proyecto-TeMa/view/configuracion.php?status=deleted");
        } else {
            header("Location: /Proyecto-TeMa/view/configuracion.php?error=delete_failed");
        }
        exit();
    }

    public function editar($id, $username, $password) {
    $usuarioModel = new Usuario();
    $usuarioModel->actualizar($id, $username, $password);
    header("Location: /Proyecto-TeMa/view/configuracion.php?status=updated");
    exit();
}    
}
?>