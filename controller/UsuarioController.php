<?php

require_once __DIR__ . '/../config/session_config.php';
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

        $from = $_POST['from'] ?? '';

        // Solo un administrador autenticado puede asignar el rol de Administrador
        $rolFinal = 'Vendedor';
        if ($from === 'configuracion' && isset($_SESSION['user']) && strtolower($_SESSION['user']['rol'] ?? '') === 'administrador') {
            $rolFinal = (strtolower((string)$rol) === 'administrador') ? 'Administrador' : 'Vendedor';
        }

        $usuarioModel = new Usuario();
        $usernameGenerado = $usuarioModel->registrar($nombre, $apellido, $rolFinal, $password, $email, $documento);

        if ($usernameGenerado !== false) {
            if ($from === 'configuracion') {
                header("Location: /Proyecto-TeMa/view/configuracion.php?status=created&username=" . urlencode($usernameGenerado));
                exit();
            }

            // Guardar solo el username generado (nunca almacenar contraseñas en texto plano en la sesión)
            $_SESSION['registro_exitoso'] = [
                'username' => $usernameGenerado,
            ];

            header("Location: /Proyecto-TeMa/view/registro_exitoso.php");
            exit();
        } else {
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
            if (($user['estado'] ?? 'activo') !== 'activo') {
                header("Location: /Proyecto-TeMa/view/login.php?error=inactive");
                exit();
            }

            // 2. Login correcto: resetear contador de intentos
            $usuarioModel->resetearIntentos($username);

            session_regenerate_id(true);
            $_SESSION['user'] = $user;
            $_SESSION['last_activity'] = time();
            $this->redirigirPorRol($user['rol']);
        } else {
            // 3. Login fallido: registrar intento
            $usuarioModel->registrarIntentoFallido($username);

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
        require_once __DIR__ . '/../helpers/mailer_recuperacion.php';

        $usuarioModel = new Usuario();
        $datos = $usuarioModel->generarTokenRecuperacion($email);

        if ($datos) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $rawHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
            // Sanear host contra Host Header Injection
            $host = preg_replace('/[^a-zA-Z0-9\.\-\:]/', '', $rawHost);
            if (empty($host) || str_contains($host, '@')) {
                $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
            }
            $link = "{$protocol}{$host}/Proyecto-TeMa/view/recuperar_contra.php?token=" . urlencode($datos['token']);
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
        $idActual = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 0);
        if ($idActual === (int)$id) {
            header("Location: /Proyecto-TeMa/view/configuracion.php?error=cannot_delete_self");
            exit();
        }

        $usuarioModel = new Usuario();
        if ($usuarioModel->eliminar((int)$id)) {
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
        $idActual = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 0);
        if ($idActual === (int)$id) {
            header("Location: /Proyecto-TeMa/view/configuracion.php?error=cannot_deactivate_self");
            exit();
        }

        $usuarioModel = new Usuario();
        $usuarioModel->cambiarEstado((int)$id);
        header("Location: /Proyecto-TeMa/view/configuracion.php?status=estado_actualizado");
        exit();
    }
}
?>
