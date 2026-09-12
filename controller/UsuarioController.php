<?php

require_once __DIR__ . '/../model/usuario.php';

class UsuarioController {

    public function registrar($nombre, $apellido, $rol, $password, $email = null, $documento = null) {
        $usuarioModel = new Usuario();
        $resultado = $usuarioModel->registrar($nombre, $apellido, $rol, $password, $email, $documento);

        if ($resultado) {
            header("Location: /Proyecto-TeMa/view/login.php?status=registered");
            exit();
        } else {
            header("Location: /Proyecto-TeMa/view/register.php?error=user_exists");
            exit();
        }
    }

public function login($username, $password) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $usuarioModel = new Usuario();

    // 1. Verificar si la cuenta está bloqueada ANTES de validar la contraseña
    $segundosRestantes = $usuarioModel->verificarBloqueo($username);
    if ($segundosRestantes > 0) {
        header("Location: /Proyecto-TeMa/view/login.php?error=locked&segundos=$segundosRestantes");
        exit();
    }

    $user = $usuarioModel->login($username, $password);

    if ($user) {
        // 2. Login correcto: resetear contador de intentos
        $usuarioModel->resetearIntentos($username);

        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time(); // iniciar el reloj de inactividad
        $this->redirigirPorRol($user['rol']);
    } else {
        // 3. Login fallido: registrar intento
        $usuarioModel->registrarIntentoFallido($username);

        // Revisar si este intento fue el que activó el bloqueo (el 3ro)
        $segundosRestantes = $usuarioModel->verificarBloqueo($username);
        if ($segundosRestantes > 0) {
            header("Location: /Proyecto-TeMa/view/login.php?error=locked&segundos=$segundosRestantes");
            exit();
        }

        header("Location: /Proyecto-TeMa/view/login.php?error=invalid_credentials");
        exit();
    }
}

    private function redirigirPorRol($rol) {
        switch (strtolower($rol)) {
            case 'vendedor':
            case 'cajero':
                header("Location: /Proyecto-TeMa/view/pos.php");
                break;
            case 'administrador':
                header("Location: /Proyecto-TeMa/view/dashboard.php");
                break;
            default:
                header("Location: /Proyecto-TeMa/view/dashboard.php");
        }
        exit();
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