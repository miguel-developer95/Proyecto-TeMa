<?php
// model/usuario.php
require_once __DIR__ . '/../config/connection.php';

class Usuario
{
    /** @var PDO */
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Conexion())->conn;
    }

    /**
     * Verify user login credentials (supports username or email).
     * 
     * @return array|false Returns user associative array if verified, false otherwise.
     */
    public function login(string $username, string $password)
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

    /**
     * Register a new user record.
     */
    public function registrar(string $username, string $password, ?string $email = null, ?string $documento = null): bool
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

    /**
     * Get total count of registered users.
     */
    public function contarUsuarios(): int
    {
        $query = "SELECT COUNT(*) as total FROM usuarios";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Fetch all users securely from the database.
     */
    public function obtenerTodos(): array
    {
        $query = "SELECT id, username, email AS correo_electronico, documento AS `No.Documento` FROM usuarios ORDER BY id DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a specific user by ID.
     * 
     * @return array|false Returns user associative array if found, false otherwise.
     */
    public function obtenerPorId(int $id)
    {
        $query = "SELECT id, username, email AS correo_electronico, documento AS `No.Documento` FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update user details.
     */
    public function actualizar(int $id, string $username, ?string $email = null, ?string $documento = null, ?string $password = null): bool
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

    /**
     * Delete a user by ID.
     */
    public function eliminar(int $id): bool
    {
        $query = "DELETE FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}