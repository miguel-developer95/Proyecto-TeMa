<?php

require_once __DIR__ . '/../config/connection.php';

class Cliente
{
    private PDO $conn;
    private string $tabla = 'cliente';

    public int $id_cliente;
    public string $nombre_cliente;
    public string $correo_electronico;

    public function __construct()
    {
        $database = new connection();
        $this->conn = $database->getConnection();
    }

    // RF: registrar un nuevo cliente
    public function crear(): bool
    {
        $sql = "INSERT INTO {$this->tabla} (nombre_cliente, correo_electronico)
                VALUES (:nombre_cliente, :correo_electronico)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':nombre_cliente', $this->nombre_cliente);
        $stmt->bindParam(':correo_electronico', $this->correo_electronico);

        if ($stmt->execute()) {
            $this->id_cliente = (int) $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Listar todos los clientes
    public function obtenerTodos(): PDOStatement
    {
        $sql = "SELECT id_cliente, nombre_cliente, correo_electronico
                FROM {$this->tabla}
                ORDER BY nombre_cliente ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    // Buscar un cliente por su id
    public function obtenerPorId(int $id_cliente): array|false
    {
        $sql = "SELECT id_cliente, nombre_cliente, correo_electronico
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
        $sql = "SELECT id_cliente, nombre_cliente, correo_electronico
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
                SET nombre_cliente = :nombre_cliente,
                    correo_electronico = :correo_electronico
                WHERE id_cliente = :id_cliente";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':nombre_cliente', $this->nombre_cliente);
        $stmt->bindParam(':correo_electronico', $this->correo_electronico);
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