<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Connection.php';

/**
 * Modelo de usuarios. Tabla: usuarios.
 */
class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    /** Login con username o email. Retorna el usuario o false. */
    public function login(string $username, string $password)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM usuarios
             WHERE (username = :u1 OR email = :u2) AND estado = 'activo' LIMIT 1"
        );
        $stmt->execute([':u1' => $username, ':u2' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    /**
     * Registra un usuario generando el username automáticamente.
     * Retorna ['ok'=>bool, 'username'=>?string].
     */
    public function registrar(
        string $nombre,
        string $apellido,
        string $rol,
        string $password,
        ?string $email = null,
        ?string $documento = null
    ): array {
        try {
            $email = ($email !== null && trim($email) !== '') ? trim($email) : null;
            $documento = ($documento !== null && trim($documento) !== '') ? trim($documento) : null;

            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['ok' => false, 'error' => 'Correo electrónico inválido.'];
            }

            $username = $this->generarUsername($nombre, $apellido, $rol);
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $this->db->prepare(
                "INSERT INTO usuarios (nombre, apellido, rol, username, password, email, documento)
                 VALUES (:nombre, :apellido, :rol, :username, :password, :email, :documento)"
            );
            $stmt->execute([
                ':nombre' => trim($nombre),
                ':apellido' => trim($apellido),
                ':rol' => trim($rol),
                ':username' => $username,
                ':password' => $hash,
                ':email' => $email,
                ':documento' => $documento,
            ]);
            return ['ok' => true, 'username' => $username];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['ok' => false, 'error' => 'El correo o documento ya está registrado.'];
            }
            throw $e;
        }
    }

    /* ---------- Bloqueo temporal (RF 1.7) ---------- */

    /** Segundos restantes de bloqueo, 0 si no está bloqueada. */
    public function verificarBloqueo(string $username): int
    {
        $stmt = $this->db->prepare(
            "SELECT bloqueado_hasta FROM usuarios
             WHERE (username = :u1 OR email = :u2) AND estado = 'activo' LIMIT 1"
        );
        $stmt->execute([':u1' => $username, ':u2' => $username]);
        $row = $stmt->fetch();

        if ($row && $row['bloqueado_hasta'] !== null) {
            try {
                $ahora = new DateTime();
                $hasta = new DateTime($row['bloqueado_hasta']);
                if ($ahora < $hasta) {
                    return $hasta->getTimestamp() - $ahora->getTimestamp();
                }
                $this->resetearIntentos($username);
            } catch (Exception $e) {
                return 0;
            }
        }
        return 0;
    }

    public function registrarIntentoFallido(string $username): void
    {
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1
             WHERE (username = :u1 OR email = :u2) AND estado = 'activo'"
        );
        $stmt->execute([':u1' => $username, ':u2' => $username]);

        $stmt = $this->db->prepare(
            "SELECT intentos_fallidos FROM usuarios
             WHERE (username = :u1 OR email = :u2) AND estado = 'activo' LIMIT 1"
        );
        $stmt->execute([':u1' => $username, ':u2' => $username]);
        $row = $stmt->fetch();

        if ($row && (int) $row['intentos_fallidos'] >= LOCKOUT_ATTEMPTS) {
            $stmt = $this->db->prepare(
                "UPDATE usuarios SET bloqueado_hasta = DATE_ADD(NOW(), INTERVAL " . LOCKOUT_MINUTES . " MINUTE)
                 WHERE (username = :u1 OR email = :u2) AND estado = 'activo'"
            );
            $stmt->execute([':u1' => $username, ':u2' => $username]);
        }
    }

    public function resetearIntentos(string $username): void
    {
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL
             WHERE (username = :u1 OR email = :u2) AND estado = 'activo'"
        );
        $stmt->execute([':u1' => $username, ':u2' => $username]);
    }

    /* ---------- Recuperación de contraseña (RF 1.5) ---------- */

    public function obtenerPorEmail(string $email)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM usuarios WHERE email = :email AND estado = 'activo' LIMIT 1"
        );
        $stmt->execute([':email' => trim($email)]);
        return $stmt->fetch();
    }

    /** Crea un token de restablecimiento (válido 1 hora). Retorna el token plano. */
    public function crearTokenReset(int $idUsuario): string
    {
        $token = bin2hex(random_bytes(32));
        $stmt = $this->db->prepare(
            "INSERT INTO password_resets (id_usuario, token_hash, expira_en)
             VALUES (:id, :hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))"
        );
        $stmt->execute([':id' => $idUsuario, ':hash' => hash('sha256', $token)]);
        return $token;
    }

    /** Valida el token y cambia la clave. Retorna true/false. */
    public function restablecerConToken(string $token, string $nuevaClave): bool
    {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                "SELECT id, id_usuario, expira_en, usado_en FROM password_resets
                 WHERE token_hash = :hash LIMIT 1"
            );
            $stmt->execute([':hash' => hash('sha256', $token)]);
            $row = $stmt->fetch();

            if (!$row || $row['usado_en'] !== null || new DateTime($row['expira_en']) < new DateTime()) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("UPDATE usuarios SET password = :p WHERE id = :id");
            $stmt->execute([':p' => password_hash($nuevaClave, PASSWORD_BCRYPT), ':id' => $row['id_usuario']]);

            $stmt = $this->db->prepare("UPDATE password_resets SET usado_en = NOW() WHERE id = :id");
            $stmt->execute([':id' => $row['id']]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    /* ---------- Administración ---------- */

    public function contarUsuarios(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    }

    public function contarAdminsActivos(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM usuarios WHERE rol = 'Administrador' AND estado = 'activo'"
        )->fetchColumn();
    }

    public function obtenerTodos(): array
    {
        return $this->listar(null);
    }

    /** Lista por estado (igual que Proveedor::listar). null = todos. */
    public function listar(?string $estado = 'activo'): array
    {
        $sql = "SELECT id, nombre, apellido, username, rol, email, documento, estado, creado_en
             FROM usuarios";
        $params = [];
        if ($estado !== null) {
            $sql .= " WHERE estado = :e";
            $params[':e'] = $estado;
        }
        $sql .= " ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function contarPorEstado(string $estado): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE estado = :e");
        $stmt->execute([':e' => $estado]);
        return (int) $stmt->fetchColumn();
    }

    public function obtenerPorId(int $id)
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, apellido, username, rol, email, documento, estado
             FROM usuarios WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /** Actualización completa (ya no borra email/documento). */
    public function actualizar(int $id, array $datos): bool
    {
        try {
            $sql = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, rol = :rol,
                    username = :username, email = :email, documento = :documento, estado = :estado";
            $params = [
                ':nombre' => trim((string) ($datos['nombre'] ?? '')),
                ':apellido' => trim((string) ($datos['apellido'] ?? '')),
                ':rol' => trim((string) ($datos['rol'] ?? 'Vendedor')),
                ':username' => trim((string) ($datos['username'] ?? '')),
                ':email' => ($datos['email'] ?? null) ?: null,
                ':documento' => ($datos['documento'] ?? null) ?: null,
                ':estado' => ($datos['estado'] ?? 'activo') === 'inactivo' ? 'inactivo' : 'activo',
                ':id' => $id,
            ];
            if (!empty($datos['password'])) {
                $sql .= ", password = :password";
                $params[':password'] = password_hash((string) $datos['password'], PASSWORD_BCRYPT);
            }
            $sql .= " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function eliminar(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false; // p. ej. tiene ventas asociadas (FK RESTRICT)
        }
    }

    /** Activa/desactiva sin borrar (conserva trazabilidad). */
    public function cambiarEstado(int $id, string $estado): bool
    {
        $estado = $estado === 'inactivo' ? 'inactivo' : 'activo';
        $stmt = $this->db->prepare("UPDATE usuarios SET estado = :e WHERE id = :id");
        return $stmt->execute([':e' => $estado, ':id' => $id]);
    }

    /* ---------- Utilidades ---------- */

    private function generarUsername(string $nombre, string $apellido, string $rol): string
    {
        $nombre = mb_strtolower(trim($nombre), "UTF-8");
        $apellido = mb_strtolower(trim($apellido), "UTF-8");

        $sinTildesN = @iconv('UTF-8', 'ASCII//TRANSLIT', $nombre);
        $sinTildesA = @iconv('UTF-8', 'ASCII//TRANSLIT', $apellido);
        $nombre = $sinTildesN !== false ? $sinTildesN : $nombre;
        $apellido = $sinTildesA !== false ? $sinTildesA : $apellido;

        $nombre = preg_replace('/[^a-z0-9]/', '', $nombre) ?: 'user';
        $apellido = preg_replace('/[^a-z0-9]/', '', $apellido) ?: 'gen';

        $abreviaturas = ['Administrador' => 'adm', 'Vendedor' => 'ven'];
        $rolLimpio = trim($rol);
        $abr = $rolLimpio === '' ? 'usr' : ($abreviaturas[$rolLimpio] ?? mb_strtolower(mb_substr($rolLimpio, 0, 3, "UTF-8"), "UTF-8"));

        $base = $nombre . '.' . $apellido . '.' . $abr;
        $username = $base;
        $n = 2;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE username = :u");
        while (true) {
            $stmt->execute([':u' => $username]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $username;
            }
            $username = $base . $n;
            $n++;
        }
    }
}
