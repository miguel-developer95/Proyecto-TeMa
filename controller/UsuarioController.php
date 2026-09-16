<?php

require_once __DIR__ . '/../model/usuario.php';

class UsuarioController {

    public function registrar($nombre, $apellido, $rol, $password, $email = null, $documento = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (strlen(trim((string)$password)) < 8) {
            header("Location: /Proyecto-TeMa/view/register.php?error=short_password");
            exit();
        }

        $usuarioModel = new Usuario();
        $usernameGenerado = $usuarioModel->registrar($nombre, $apellido, $rol, $password, $email, $documento);

        if ($usernameGenerado !== false) {
            $from = $_POST['from'] ?? '';
            if ($from === 'configuracion') {
                header("Location: /Proyecto-TeMa/view/configuracion.php?status=created&username=" . urlencode($usernameGenerado));
                exit();
            }

            // Guardar temporalmente en sesión para mostrarlo en la pantalla de confirmación
            $_SESSION['registro_exitoso'] = [
                'username' => $usernameGenerado,
                'password' => $password, // texto plano SOLO para esta pantalla, nunca se guarda así en BD
            ];

            header("Location: /Proyecto-TeMa/view/registro_exitoso.php");
            exit();
        } else {
            $from = $_POST['from'] ?? '';
            if ($from === 'configuracion') {
                header("Location: /Proyecto-TeMa/view/configuracion.php?status=user_exists");
                exit();
            }
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

            // AGREGAR AQUÍ: verificar que la cuenta esté activa
            if (($user['estado'] ?? 'activo') !== 'activo') {
                header("Location: /Proyecto-TeMa/view/login.php?error=inactive");
                exit();
            }

            // 2. Login correcto: resetear contador de intentos
            $usuarioModel->resetearIntentos($username);

            session_regenerate_id(true);
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
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        $param = isset($_GET['sesion_expirada']) ? '?sesion_expirada=1' : '';
        header("Location: /Proyecto-TeMa/view/login.php" . $param);
        exit();
    }

    public function solicitarRecuperacion($email) {
        require_once __DIR__ . '/../helpers/mailer_recuperacion.php'; // <-- cambio aquí

        $usuarioModel = new Usuario();
        $datos = $usuarioModel->generarTokenRecuperacion($email);

        if ($datos) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $link = "{$protocol}{$host}/Proyecto-TeMa/view/recuperar_contra.php?token=" . $datos['token'];
            enviarCorreoRecuperacion($datos['email'], $datos['nombre'], $link);
        }

        header("Location: /Proyecto-TeMa/view/recuperar_contra.php?status=enviado");
        exit();
    }

    public function restablecerPassword($token, $password, $passwordConfirmar) {
        if ($password !== $passwordConfirmar) {
            header("Location: /Proyecto-TeMa/view/recuperar_contra.php?token=" . urlencode($token) . "&error=no_coincide");
            exit();
        }

        if (strlen($password) < 8) {
            header("Location: /Proyecto-TeMa/view/recuperar_contra.php?token=" . urlencode($token) . "&error=muy_corta");
            exit();
        }

        $usuarioModel = new Usuario();
        $id = $usuarioModel->validarTokenRecuperacion($token);

        if (!$id) {
            header("Location: /Proyecto-TeMa/view/recuperar_contra.php?error=token_invalido");
            exit();
        }

        $usuarioModel->restablecerPassword($id, $password);
        header("Location: /Proyecto-TeMa/view/login.php?status=password_actualizada");
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

    public function editar($id, $username, $email = null, $documento = null, $password = null, $rol = null) {
        if (!empty($password) && strlen(trim($password)) < 8) {
            header("Location: /Proyecto-TeMa/view/configuracion.php?edit_id=" . urlencode($id) . "&status=short_password");
            exit();
        }
        $usuarioModel = new Usuario();
        $usuarioModel->actualizar((int)$id, $username, $email, $documento, $password, $rol);
        header("Location: /Proyecto-TeMa/view/configuracion.php?status=updated");
        exit();
    }

    public function cambiarEstado($id) {
    $usuarioModel = new Usuario();
    $usuarioModel->cambiarEstado((int)$id);
    header("Location: /Proyecto-TeMa/view/configuracion.php?status=estado_actualizado");
    exit();
}
}
?>