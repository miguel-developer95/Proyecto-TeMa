<?php
// model/detalle_recibo.php
require_once __DIR__ . '/../config/connection.php';

class DetalleRecibo
{
    /** @var PDO */
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    /**
     * Registrar un detalle de recibo
     */
    public function crear(int $numero_recibo, string $producto_vendido, int $cantidad_producto, float $precio_unitario): bool
    {
        try {
            $query = "INSERT INTO detalle_recibo (numero_recibo, producto_vendido, cantidad_producto, precio_unitario)
                      VALUES (:numero_recibo, :producto_vendido, :cantidad_producto, :precio_unitario)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":numero_recibo", $numero_recibo);
            $stmt->bindParam(":producto_vendido", $producto_vendido);
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
     * Obtener todos los detalles de recibo
     */
    public function obtenerTodos(): array
    {
        $query = "SELECT numero_recibo, producto_vendido, cantidad_producto, precio_unitario FROM detalle_recibo ORDER BY numero_recibo DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todos los detalles de un recibo específico
     */
    public function obtenerPorRecibo(int $numero_recibo): array
    {
        $query = "SELECT numero_recibo, producto_vendido, cantidad_producto, precio_unitario FROM detalle_recibo WHERE numero_recibo = :numero_recibo";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":numero_recibo", $numero_recibo);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un detalle específico (clave compuesta)
     * @return array|false
     */
    public function obtenerPorId(int $numero_recibo, string $producto_vendido)
    {
        $query = "SELECT numero_recibo, producto_vendido, cantidad_producto, precio_unitario
                  FROM detalle_recibo WHERE numero_recibo = :numero_recibo AND producto_vendido = :producto_vendido";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":numero_recibo", $numero_recibo);
        $stmt->bindParam(":producto_vendido", $producto_vendido);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar un detalle de recibo
     */
    public function actualizar(int $numero_recibo, string $producto_vendido, int $cantidad_producto, float $precio_unitario): bool
    {
        try {
            $query = "UPDATE detalle_recibo SET cantidad_producto = :cantidad_producto, precio_unitario = :precio_unitario
                      WHERE numero_recibo = :numero_recibo AND producto_vendido = :producto_vendido";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":cantidad_producto", $cantidad_producto);
            $stmt->bindParam(":precio_unitario", $precio_unitario);
            $stmt->bindParam(":numero_recibo", $numero_recibo);
            $stmt->bindParam(":producto_vendido", $producto_vendido);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Eliminar un detalle de recibo (clave compuesta)
     */
    public function eliminar(int $numero_recibo, string $producto_vendido): bool
    {
        $query = "DELETE FROM detalle_recibo WHERE numero_recibo = :numero_recibo AND producto_vendido = :producto_vendido";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":numero_recibo", $numero_recibo);
        $stmt->bindParam(":producto_vendido", $producto_vendido);
        return $stmt->execute();
    }
}