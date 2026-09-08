<?php
// model/usuario.php
require_once __DIR__ . '/../config/connection.php';

class Usuario
{
    /** @var PDO */
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
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
     * Generates the username automatically from nombre + apellido + rol.
     */
    public function registrar(
        string $nombre,
        string $apellido,
        string $rol,
        string $password,
        ?string $email = null,
        ?string $documento = null
    ): bool {
        try {

            // Generar automáticamente el nombre de usuario
            $username = $this->generarUsername($nombre, $apellido, $rol);

            // Encriptar contraseña
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $query = "INSERT INTO usuarios 
                    (nombre, apellido, rol, username, password, email, documento) 
                    VALUES 
                    (:nombre, :apellido, :rol, :username, :password, :email, :documento)";

            $stmt = $this->db->prepare($query);

            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":apellido", $apellido);
            $stmt->bindParam(":rol", $rol);
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
     * Builds a unique username from nombre + apellido + rol.
     * Falls back to safe defaults if iconv fails or inputs are empty
     * (common issue on some Windows/XAMPP setups).
     */
    private function generarUsername(
        string $nombre,
        string $apellido,
        string $rol
    ): string {

        // Convertir a minúsculas y quitar espacios extra
        $nombre = strtolower(trim($nombre));
        $apellido = strtolower(trim($apellido));

        // Eliminar tildes (con respaldo si iconv falla, cosa común en Windows)
        $nombreSinTildes = @iconv('UTF-8', 'ASCII//TRANSLIT', $nombre);
        $apellidoSinTildes = @iconv('UTF-8', 'ASCII//TRANSLIT', $apellido);

        $nombre = ($nombreSinTildes !== false) ? $nombreSinTildes : $nombre;
        $apellido = ($apellidoSinTildes !== false) ? $apellidoSinTildes : $apellido;

        // Eliminar espacios y caracteres especiales
        $nombre = preg_replace('/[^a-z0-9]/', '', $nombre);
        $apellido = preg_replace('/[^a-z0-9]/', '', $apellido);

        // Nunca dejar el username vacío
        if ($nombre === '') {
            $nombre = 'user';
        }
        if ($apellido === '') {
            $apellido = 'gen';
        }

        // Abreviaturas de los roles
        $abreviaturas = [
            'Administrador' => 'adm',
            'Vendedor' => 'ven'
        ];

        $rolLimpio = trim($rol);
        if ($rolLimpio === '') {
            $abreviaturaRol = 'usr';
        } else {
            $abreviaturaRol = $abreviaturas[$rolLimpio] ?? strtolower(substr($rolLimpio, 0, 3));
        }

        // Crear nombre de usuario base
        $usernameBase = $nombre . '.' . $apellido . '.' . $abreviaturaRol;

        $username = $usernameBase;
        $contador = 2;

        while (true) {

            $query = "SELECT COUNT(*) 
                      FROM usuarios 
                      WHERE username = :username";

            $stmt = $this->db->prepare($query);

            $stmt->execute([
                ':username' => $username
            ]);

            $existe = $stmt->fetchColumn();

            if ($existe == 0) {
                return $username;
            }

            $username = $usernameBase . $contador;
            $contador++;
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