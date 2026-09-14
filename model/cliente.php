<?php
// model/cliente.php
require_once __DIR__ . '/../config/connection.php';

class Cliente
{
    private PDO $conn;
    private string $tabla = 'clientes';

    public int $id_cliente;
    public string $nombre;
    public ?string $correo_electronico = null;
    public ?string $telefono = null;
    public ?string $documento = null;

    public function __construct()
    {
        $this->conn = (new Connection())->conn;
    }

    // RF: registrar un nuevo cliente
    public function crear(?string $nombre = null, ?string $correo = null, ?string $telefono = null, ?string $documento = null): int|bool
    {
        $nom = $nombre ?? ($this->nombre ?? '');
        $email = $correo ?? ($this->correo_electronico ?? null);
        $tel = $telefono ?? ($this->telefono ?? null);
        $doc = $documento ?? ($this->documento ?? null);

        $sql = "INSERT INTO {$this->tabla} (nombre, correo_electronico, telefono, documento)
                VALUES (:nombre, :correo_electronico, :telefono, :documento)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(':nombre', $nom);
        $stmt->bindValue(':correo_electronico', $email);
        $stmt->bindValue(':telefono', $tel);
        $stmt->bindValue(':documento', $doc);

        if ($stmt->execute()) {
            $this->id_cliente = (int) $this->conn->lastInsertId();
            return $this->id_cliente ?: true;
        }

        return false;
    }

    // Listar todos los clientes
    public function obtenerTodos(): array
    {
        $sql = "SELECT id_cliente, nombre, nombre AS nombre_cliente, correo_electronico, telefono, documento
                FROM {$this->tabla}
                ORDER BY nombre ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar un cliente por su id
    public function obtenerPorId(int $id_cliente): array|false
    {
        $sql = "SELECT id_cliente, nombre, nombre AS nombre_cliente, correo_electronico, telefono, documento
                FROM {$this->tabla}
                WHERE id_cliente = :id_cliente
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Buscar un cliente por correo (útil para validar duplicados)
    public function obtenerPorCorreo(string $correo_electronico): array|false
    {
        $sql = "SELECT id_cliente, nombre, correo_electronico, telefono, documento
                FROM {$this->tabla}
                WHERE correo_electronico = :correo_electronico
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':correo_electronico', $correo_electronico);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar datos de un cliente
    public function actualizar(): bool
    {
        $sql = "UPDATE {$this->tabla}
                SET nombre = :nombre,
                    correo_electronico = :correo_electronico,
                    telefono = :telefono,
                    documento = :documento
                WHERE id_cliente = :id_cliente";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':correo_electronico', $this->correo_electronico);
        $stmt->bindParam(':telefono', $this->telefono);
        $stmt->bindParam(':documento', $this->documento);
        $stmt->bindParam(':id_cliente', $this->id_cliente, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Eliminar un cliente
    public function eliminar(int $id_cliente): bool
    {
        $sql = "DELETE FROM {$this->tabla} WHERE id_cliente = :id_cliente";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);

        return $stmt->execute();
    }
}