<?php
// model/Usuario.php
require_once "config/conexion.php";

class Usuario {
    private $db;

    public function __construct() {
        $this->db = (new Conexion())->conn;
    }

    // Método para verificar el login
    public function login($username, $password) {
        $query = "SELECT * FROM usuarios WHERE username = :username"; // Busca al usuario por nombre
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC); // Obtiene los datos del usuario

        // Verifica si el usuario existe y la contraseña es válida
        if ($user && password_verify($password, $user['password'])) {
            return $user; // Usuario autenticado
        }

        return false; // Usuario o contraseña incorrectos
    }

    // Método para registrar un usuario con contraseña encriptada
    public function registrar($username, $password) {
        $hash = password_hash($password, PASSWORD_BCRYPT); // Encripta la contraseña
        $query = "INSERT INTO usuarios (username, password) VALUES (:username, :password)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $hash);
        return $stmt->execute(); // Inserta en la base de datos
    }
}
?>