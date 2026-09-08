<?php
// model/detalle_venta.php
require_once __DIR__ . '/../config/conexion.php';

class DetalleVenta
{
    /** @var PDO */
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Conexion())->conn;
    }

    /**
     * Create a new sales detail record.
     */
    public function crear(int $id_venta, string $cod_producto, int $cantidad_producto, float $precio_unitario): bool
    {
        try {
            $query = "INSERT INTO detalle_venta (id_venta, cod_producto, cantidad_producto, precio_unitario)
                      VALUES (:id_venta, :cod_producto, :cantidad_producto, :precio_unitario)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id_venta", $id_venta);
            $stmt->bindParam(":cod_producto", $cod_producto);
            $stmt->bindParam(":cantidad_producto", $cantidad_producto);
            $stmt->bindParam(":precio_unitario", $precio_unitario);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Fetch all sales details.
     */
    public function obtenerTodos(): array
    {
        $query = "SELECT id_venta, cod_producto, cantidad_producto, precio_unitario FROM detalle_venta ORDER BY id_venta DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch all details for a specific sale.
     */
    public function obtenerPorVenta(int $id_venta): array
    {
        $query = "SELECT id_venta, cod_producto, cantidad_producto, precio_unitario FROM detalle_venta WHERE id_venta = :id_venta";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_venta", $id_venta);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a specific detail record by composite key.
     * 
     * @return array|false
     */
    public function obtenerPorId(int $id_venta, string $cod_producto)
    {
        $query = "SELECT id_venta, cod_producto, cantidad_producto, precio_unitario
                  FROM detalle_venta WHERE id_venta = :id_venta AND cod_producto = :cod_producto";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_venta", $id_venta);
        $stmt->bindParam(":cod_producto", $cod_producto);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update an existing sales detail record.
     */
    public function actualizar(int $id_venta, string $cod_producto, int $cantidad_producto, float $precio_unitario): bool
    {
        try {
            $query = "UPDATE detalle_venta SET cantidad_producto = :cantidad_producto, precio_unitario = :precio_unitario
                      WHERE id_venta = :id_venta AND cod_producto = :cod_producto";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":cantidad_producto", $cantidad_producto);
            $stmt->bindParam(":precio_unitario", $precio_unitario);
            $stmt->bindParam(":id_venta", $id_venta);
            $stmt->bindParam(":cod_producto", $cod_producto);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete a sales detail record by composite key.
     */
    public function eliminar(int $id_venta, string $cod_producto): bool
    {
        $query = "DELETE FROM detalle_venta WHERE id_venta = :id_venta AND cod_producto = :cod_producto";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_venta", $id_venta);
        $stmt->bindParam(":cod_producto", $cod_producto);
        return $stmt->execute();
    }
}