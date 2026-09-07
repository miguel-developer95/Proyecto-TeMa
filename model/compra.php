<?php

require_once __DIR__ . '/../config/tentaciones_marlly.php';

class Compra
{
    private PDO $conn;
    private string $tabla = 'compra';

    public int $id_compra;
    public string $fecha_hora;
    public string $metodo_pago;
    public string $estado;
    public float $total_compra;
    public int $id_proveedor;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // RF 4.1: crear un registro de compra
    public function crear(): bool
    {
        $sql = "INSERT INTO {$this->tabla}
                    (fecha_hora, metodo_pago, estado, total_compra, id_proveedor)
                VALUES
                    (:fecha_hora, :metodo_pago, :estado, :total_compra, :id_proveedor)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':fecha_hora', $this->fecha_hora);
        $stmt->bindParam(':metodo_pago', $this->metodo_pago);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':total_compra', $this->total_compra);
        $stmt->bindParam(':id_proveedor', $this->id_proveedor, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->id_compra = (int) $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // RF 4.3: consultar el historial de compras (con nombre del proveedor)
    public function obtenerTodas(): PDOStatement
    {
        $sql = "SELECT c.id_compra, c.fecha_hora, c.metodo_pago, c.estado,
                       c.total_compra, c.id_proveedor, p.nombre_proveedor
                FROM {$this->tabla} c
                LEFT JOIN proveedor p ON p.id_proveedor = c.id_proveedor
                ORDER BY c.fecha_hora DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    // Consultar una compra específica por id
    public function obtenerPorId(int $id_compra): array|false
    {
        $sql = "SELECT id_compra, fecha_hora, metodo_pago, estado, total_compra, id_proveedor
                FROM {$this->tabla}
                WHERE id_compra = :id_compra
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Filtrar historial de compras por proveedor
    public function obtenerPorProveedor(int $id_proveedor): PDOStatement
    {
        $sql = "SELECT id_compra, fecha_hora, metodo_pago, estado, total_compra, id_proveedor
                FROM {$this->tabla}
                WHERE id_proveedor = :id_proveedor
                ORDER BY fecha_hora DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_proveedor', $id_proveedor, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // RF 4.4: modificar una compra existente
    public function actualizar(): bool
    {
        $sql = "UPDATE {$this->tabla}
                SET fecha_hora = :fecha_hora,
                    metodo_pago = :metodo_pago,
                    estado = :estado,
                    total_compra = :total_compra,
                    id_proveedor = :id_proveedor
                WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':fecha_hora', $this->fecha_hora);
        $stmt->bindParam(':metodo_pago', $this->metodo_pago);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':total_compra', $this->total_compra);
        $stmt->bindParam(':id_proveedor', $this->id_proveedor, PDO::PARAM_INT);
        $stmt->bindParam(':id_compra', $this->id_compra, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Actualizar solo el estado (ej: anular, completar)
    public function actualizarEstado(int $id_compra, string $estado): bool
    {
        $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function eliminar(int $id_compra): bool
    {
        $sql = "DELETE FROM {$this->tabla} WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);

        return $stmt->execute();
    }
}