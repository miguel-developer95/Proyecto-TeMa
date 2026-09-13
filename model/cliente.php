<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Connection.php';

/** Modelo de clientes. Tabla: clientes. */
class Cliente
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    public function crear(string $nombre, ?string $correo = null, ?string $telefono = null, ?string $documento = null): int
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO clientes (nombre, correo_electronico, telefono, documento)
                 VALUES (:n, :c, :t, :d)"
            );
            $stmt->execute([
                ':n' => trim($nombre),
                ':c' => ($correo !== null && trim($correo) !== '') ? trim($correo) : null,
                ':t' => ($telefono !== null && trim($telefono) !== '') ? trim($telefono) : null,
                ':d' => ($documento !== null && trim($documento) !== '') ? trim($documento) : null,
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function obtenerTodos(): array
    {
        return $this->db->query(
            "SELECT * FROM clientes ORDER BY nombre ASC"
        )->fetchAll();
    }

    public function obtenerPorId(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE id_cliente = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function obtenerPorCorreo(string $correo)
    {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE correo_electronico = :c LIMIT 1");
        $stmt->execute([':c' => trim($correo)]);
        return $stmt->fetch();
    }
}
