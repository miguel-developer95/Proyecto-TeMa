<?php
// model/usuario.php
require_once __DIR__ . '/../config/conexion.php';

class Usuario
{
    private $db;

    public function __construct()
    {
        $this->db = (new Conexion())->conn;
    }

    // Método para verificar el login
    public function login($username, $password)
    {
        $query = "SELECT * FROM usuarios WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    // Método para registrar un usuario con contraseña encriptada
    public function registrar($username, $password)
    {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $query = "INSERT INTO usuarios (username, password) VALUES (:username, :password)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $hash);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Código 23000 indica violación de restricción (ej. usuario duplicado)
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    // Obtener el total de usuarios registrados
    public function contarUsuarios()
    {
        $query = "SELECT COUNT(*) as total FROM usuarios";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Obtener todos los usuarios para la tabla
    public function obtenerTodos()
    {
        $query = "SELECT id, username FROM usuarios ORDER BY id DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Obtener usuario por ID
    public function obtenerPorId($id)
    {
        $query = "SELECT id, username FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar usuario
    public function actualizar($id, $username, $password = null)
    {
        try {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $query = "UPDATE usuarios SET username = :username, password = :password WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(":password", $hash);
            } else {
                $query = "UPDATE usuarios SET username = :username WHERE id = :id";
                $stmt = $this->db->prepare($query);
            }
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Eliminar usuario
    public function eliminar($id)
    {
        $query = "DELETE FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}