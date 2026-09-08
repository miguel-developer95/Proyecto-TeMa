<?php
// model/compra.php
require_once __DIR__ . '/../config/conexion.php';

class Compra
{
    /** @var PDO */
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    /**
     * Create a new purchase record.
     */
    public function crear(string $fecha_hora, string $metodo_pago, string $estado, float $total_compra, int $id_proveedor): bool
    {
        try {
            $query = "INSERT INTO compra (fecha_hora, metodo_pago, estado, total_compra, id_proveedor)
                      VALUES (:fecha_hora, :metodo_pago, :estado, :total_compra, :id_proveedor)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":fecha_hora", $fecha_hora);
            $stmt->bindParam(":metodo_pago", $metodo_pago);
            $stmt->bindParam(":estado", $estado);
            $stmt->bindParam(":total_compra", $total_compra);
            $stmt->bindParam(":id_proveedor", $id_proveedor, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get total count of purchases.
     */
    public function contarCompras(): int
    {
        $query = "SELECT COUNT(*) as total FROM compra";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Fetch all purchases with supplier information.
     */
    public function obtenerTodas(): array
    {
        $query = "SELECT c.id_compra, c.fecha_hora, c.metodo_pago, c.estado, c.total_compra, c.id_proveedor,
                         p.nombre_proveedor AS proveedor
                  FROM compra c
                  LEFT JOIN proveedor p ON p.id_proveedor = c.id_proveedor
                  ORDER BY c.fecha_hora DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a single purchase record by ID.
     * 
     * @return array|false Returns purchase data if found, false otherwise.
     */
    public function obtenerPorId(int $id_compra)
    {
        $query = "SELECT id_compra, fecha_hora, metodo_pago, estado, total_compra, id_proveedor
                  FROM compra WHERE id_compra = :id_compra";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_compra", $id_compra, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Filter purchases by supplier ID.
     */
    public function obtenerPorProveedor(int $id_proveedor): array
    {
        $query = "SELECT id_compra, fecha_hora, metodo_pago, estado, total_compra, id_proveedor
                  FROM compra WHERE id_proveedor = :id_proveedor ORDER BY fecha_hora DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_proveedor", $id_proveedor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update an existing purchase record.
     */
    public function actualizar(int $id_compra, string $fecha_hora, string $metodo_pago, string $estado, float $total_compra, int $id_proveedor): bool
    {
        try {
            $query = "UPDATE compra
                      SET fecha_hora = :fecha_hora, metodo_pago = :metodo_pago,
                          estado = :estado, total_compra = :total_compra, id_proveedor = :id_proveedor
                      WHERE id_compra = :id_compra";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":fecha_hora", $fecha_hora);
            $stmt->bindParam(":metodo_pago", $metodo_pago);
            $stmt->bindParam(":estado", $estado);
            $stmt->bindParam(":total_compra", $total_compra);
            $stmt->bindParam(":id_proveedor", $id_proveedor, PDO::PARAM_INT);
            $stmt->bindParam(":id_compra", $id_compra, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Update only the status of a purchase.
     */
    public function actualizarEstado(int $id_compra, string $estado): bool
    {
        $query = "UPDATE compra SET estado = :estado WHERE id_compra = :id_compra";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":id_compra", $id_compra, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Delete a purchase record by ID.
     */
    public function eliminar(int $id_compra): bool
    {
        $query = "DELETE FROM compra WHERE id_compra = :id_compra";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_compra", $id_compra, PDO::PARAM_INT);
        return $stmt->execute();
    }
}