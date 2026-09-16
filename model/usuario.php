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
    ) {
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

            $exito = $stmt->execute();

            // Devolver el username generado en éxito, o false en fallo
            return $exito ? $username : false;

        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {
                return false;
            }

            throw $e;
        }
    }

    // Verifica si la cuenta está bloqueada. Devuelve segundos restantes o 0 si no está bloqueada.
    public function verificarBloqueo(string $username): int {
        $sql = "SELECT bloqueado_hasta FROM usuarios WHERE (username = :username OR email = :username) AND estado = 'activo'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['bloqueado_hasta'] !== null) {
            $ahora = new DateTime();
            $hastaBloqueo = new DateTime($row['bloqueado_hasta']);

            if ($ahora < $hastaBloqueo) {
                // Sigue bloqueada: devolver segundos restantes
                return $hastaBloqueo->getTimestamp() - $ahora->getTimestamp();
            } else {
                // El bloqueo ya expiró: resetear el contador para dar 3 intentos nuevos
                $this->resetearIntentos($username);
            }
        }
        return 0;
    }

    public function registrarIntentoFallido(string $username) {

        $sql = "UPDATE usuarios 
                SET intentos_fallidos = intentos_fallidos + 1 
                WHERE (username = :username OR email = :username) AND estado = 'activo'";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $sql2 = "SELECT intentos_fallidos FROM usuarios WHERE (username = :username OR email = :username) AND estado = 'activo'";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->bindParam(':username', $username);
        $stmt2->execute();
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['intentos_fallidos'] >= 3) {
            $sql3 = "UPDATE usuarios 
                    SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 1 MINUTE) 
                    WHERE (username = :username OR email = :username) AND estado = 'activo'";

            $stmt3 = $this->db->prepare($sql3);
            $stmt3->bindParam(':username', $username);
            $stmt3->execute();
        }
    }

    public function resetearIntentos($username) {
        $sql = "UPDATE usuarios 
                SET intentos_fallidos = 0, bloqueado_hasta = NULL 
                WHERE (username = :username OR email = :username) AND estado = 'activo'";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
    }


    private function generarUsername(
        string $nombre,
        string $apellido,
        string $rol
    ): string {

        // Convertir a minúsculas y quitar espacios extra
        $nombre = strtolower(trim($nombre));
        $apellido = strtolower(trim($apellido));

        // Eliminar tildes de forma manual (más confiable que iconv en Windows/XAMPP)
        $nombre = $this->quitarAcentos($nombre);
        $apellido = $this->quitarAcentos($apellido);

        // Tomar solo la PRIMERA palabra de nombres/apellidos compuestos
        $primerNombre = explode(' ', trim($nombre))[0] ?? '';
        $primerApellido = explode(' ', trim($apellido))[0] ?? '';

        // Limpiar caracteres especiales de CADA PARTE POR SEPARADO
        // (nunca del string ya unido con puntos, o se pierden los puntos)
        $primerNombre = preg_replace('/[^a-z0-9]/', '', $primerNombre);
        $primerApellido = preg_replace('/[^a-z0-9]/', '', $primerApellido);

        if ($primerNombre === '') {
            $primerNombre = 'usr';
        }
        if ($primerApellido === '') {
            $primerApellido = 'gen';
        }

        // Truncar a las primeras 3 letras
        $primerNombre = substr($primerNombre, 0, 3);
        $primerApellido = substr($primerApellido, 0, 3);

        // Abreviaturas de los roles (comparación case-insensitive)
        $rolLimpio = strtolower(trim($rol));

        $abreviaturas = [
            'administrador' => 'admin',
            'vendedor'      => 'vend',
        ];

        if ($rolLimpio === '') {
            $abreviaturaRol = 'usr';
        } else {
            $abreviaturaRol = $abreviaturas[$rolLimpio] ?? substr($rolLimpio, 0, 4);
        }

        // Concatenar CON los puntos como paso final — nada debe tocar
        // este string después de este punto
        $usernameBase = $primerNombre . '.' . $primerApellido . '.' . $abreviaturaRol;

        $username = $usernameBase;
        $contador = 2;

        while (true) {
            $query = "SELECT COUNT(*) FROM usuarios WHERE username = :username";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':username' => $username]);
            $existe = $stmt->fetchColumn();

            if ($existe == 0) {
                return $username;
            }

            $username = $usernameBase . $contador;
            $contador++;
        }
    }

    private function quitarAcentos(string $texto): string {
        $buscar     = ['á','é','í','ó','ú','à','è','ì','ò','ù','ä','ë','ï','ö','ü','ñ',
                    'Á','É','Í','Ó','Ú','À','È','Ì','Ò','Ù','Ä','Ë','Ï','Ö','Ü','Ñ'];
        $reemplazar = ['a','e','i','o','u','a','e','i','o','u','a','e','i','o','u','n',
                    'A','E','I','O','U','A','E','I','O','U','A','E','I','O','U','N'];
        return str_replace($buscar, $reemplazar, $texto);
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
        $query = "SELECT id, username, email AS correo_electronico, documento AS `No.Documento`, rol, estado FROM usuarios ORDER BY id DESC";
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
        $query = "SELECT id, username, email AS correo_electronico, documento AS `No.Documento`, rol, estado FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update user details.
     */
    public function actualizar(int $id, string $username, ?string $email = null, ?string $documento = null, ?string $password = null, ?string $rol = null): bool
    {
        try {
            $sql = "UPDATE usuarios SET username = :username, email = :email, documento = :documento";
            $params = [
                ':username' => $username,
                ':email' => $email,
                ':documento' => $documento,
                ':id' => $id,
            ];

            if (!empty($rol)) {
                $sql .= ", rol = :rol";
                $params[':rol'] = $rol;
            }

            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $sql .= ", password = :password";
                $params[':password'] = $hash;
            }

            $sql .= " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete a user by ID using Soft Delete (marcar como inactivo).
     */
    public function eliminar(int $id): bool
    {
        $query = "UPDATE usuarios SET estado = 'inactivo' WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
    
        /**
     * Alterna el estado del usuario entre 'activo' e 'inactivo'.
     */
    public function cambiarEstado(int $id): bool
    {
        // 1. Obtener el estado actual
        $query = "SELECT estado FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        // 2. Calcular el nuevo estado (invertido)
        $nuevoEstado = ($row['estado'] === 'activo') ? 'inactivo' : 'activo';

        // 3. Actualizar
        $query2 = "UPDATE usuarios SET estado = :estado WHERE id = :id";
        $stmt2 = $this->db->prepare($query2);
        $stmt2->bindParam(':estado', $nuevoEstado);
        $stmt2->bindParam(':id', $id);
        return $stmt2->execute();
    }

    // ===== A PARTIR DE AQUÍ: recuperación de contraseña =====

    /**
     * Genera un token de recuperación y lo guarda con expiración de 30 minutos.
     * Devuelve el token en texto plano (para el link) y datos del usuario, o null si el correo no existe.
     */
    public function generarTokenRecuperacion(string $email): ?array
    {
        $query = "SELECT id, nombre, email FROM usuarios WHERE email = :email AND estado = 'activo'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        // Token en texto plano (va en el link del correo)
        $tokenPlano = bin2hex(random_bytes(32));

        // Solo el hash se guarda en la BD
        $tokenHash = hash('sha256', $tokenPlano);

        $expira = date('Y-m-d H:i:s', time() + 300); // 5 minutos

        $update = "UPDATE usuarios SET reset_token = :hash, reset_token_expira = :expira WHERE id = :id";
        $stmtUpdate = $this->db->prepare($update);
        $stmtUpdate->bindParam(':hash', $tokenHash);
        $stmtUpdate->bindParam(':expira', $expira);
        $stmtUpdate->bindParam(':id', $user['id']);
        $stmtUpdate->execute();

        try {
            $stmtReset = $this->db->prepare("INSERT INTO password_resets (id_usuario, token_hash, expira_en) VALUES (:id_usuario, :token_hash, :expira_en)");
            $stmtReset->bindParam(':id_usuario', $user['id']);
            $stmtReset->bindParam(':token_hash', $tokenHash);
            $stmtReset->bindParam(':expira_en', $expira);
            $stmtReset->execute();
        } catch (Exception $e) {
            // No interrumpir si hay error en la tabla secundaria
        }

        return [
            'token'  => $tokenPlano,
            'nombre' => $user['nombre'],
            'email'  => $user['email'],
        ];
    }

    /**
     * Valida un token de recuperación. Devuelve el id del usuario si es válido
     * y no ha expirado, o false en caso contrario.
     */
    public function validarTokenRecuperacion(string $token)
    {
        $hash = hash('sha256', $token);

        $query = "SELECT id, reset_token_expira FROM usuarios WHERE reset_token = :hash AND estado = 'activo'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':hash', $hash);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        $expira = new DateTime($row['reset_token_expira']);
        $ahora = new DateTime();

        if ($ahora > $expira) {
            return false; // Token expirado
        }

        return (int) $row['id'];
    }

    /**
     * Actualiza la contraseña del usuario y limpia el token (uso único).
     * También desbloquea al usuario e inicializa los intentos fallidos.
     */
    public function restablecerPassword(int $id, string $nuevaPassword): bool
    {
        $hash = password_hash($nuevaPassword, PASSWORD_BCRYPT);

        $query = "UPDATE usuarios SET password = :password, reset_token = NULL, reset_token_expira = NULL, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':id', $id);
        $result = $stmt->execute();

        try {
            $stmtReset = $this->db->prepare("UPDATE password_resets SET usado_en = NOW() WHERE id_usuario = :id AND usado_en IS NULL");
            $stmtReset->bindParam(':id', $id);
            $stmtReset->execute();
        } catch (Exception $e) {
            // Ignorar para no bloquear
        }

        return $result;
    }
}