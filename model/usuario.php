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

    // Método para verificar el login (permite usuario o email)
    public function login($username, $password)
    {
        $query = "SELECT * FROM usuarios WHERE username = :username OR email = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    // Método para registrar un usuario
    public function registrar($username, $password, $email = null, $documento = null)
    {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $query = "INSERT INTO usuarios (username, password, email, documento) VALUES (:username, :password, :email, :documento)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $hash);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":documento", $documento);
            return $stmt->execute();
        } catch (PDOException $e) {
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

    // Obtener todos los usuarios de la tabla de forma segura
    public function obtenerTodos()
    {
        // Se envuelve `No.Documento` en comillas invertidas para evitar el error de sintaxis de MariaDB
        $query = "SELECT id, username, email AS correo_electronico, documento AS `No.Documento` FROM usuarios ORDER BY id DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener usuario por ID
    public function obtenerPorId($id)
    {
        $query = "SELECT id, username, email AS correo_electronico, documento AS `No.Documento` FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar usuario
    public function actualizar($id, $username, $email = null, $documento = null, $password = null)
    {
        try {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $query = "UPDATE usuarios SET username = :username, email = :email, documento = :documento, password = :password WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(":password", $hash);
            } else {
                $query = "UPDATE usuarios SET username = :username, email = :email, documento = :documento WHERE id = :id";
                $stmt = $this->db->prepare($query);
            }
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":documento", $documento);
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
?>