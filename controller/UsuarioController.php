<?php
// Carpeta: controller
// Archivo: UsuarioController.php
require_once "model/usuario.php";

class UsuarioController {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
    }

    // Método para manejar el login
    public function login($username, $password) {
        return $this->usuarioModel->login($username, $password);
    }

    // Método para registrar un usuario con contraseña encriptada
    public function registrar($username, $password) {
        return $this->usuarioModel->registrar($username, $password);
    }
}
?>