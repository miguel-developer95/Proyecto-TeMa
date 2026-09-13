<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Connection.php';

/**
 * Kardex de inventario (RF 5.2).
 * La escritura se hace dentro de las transacciones de Venta/Compra
 * con el mismo PDO; aquí solo van lecturas.
 */
class Movimiento
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    /** Kardex de un producto, más reciente primero. */
    public function kardexPorProducto(int $idProducto, int $limite = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, u.username AS usuario
             FROM movimientos_inventario m
             LEFT JOIN usuarios u ON u.id = m.id_usuario
             WHERE m.id_producto = :p
             ORDER BY m.fecha DESC, m.id DESC LIMIT :lim"
        );
        $stmt->bindValue(':p', $idProducto, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Movimientos vinculados a una venta o compra (trazabilidad RF 5.2). */
    public function porReferencia(?int $idVenta = null, ?int $idCompra = null): array
    {
        if ($idVenta !== null) {
            $stmt = $this->db->prepare(
                "SELECT m.*, p.nombre AS producto FROM movimientos_inventario m
                 INNER JOIN productos p ON p.id_producto = m.id_producto
                 WHERE m.id_venta = :v ORDER BY m.id ASC"
            );
            $stmt->execute([':v' => $idVenta]);
            return $stmt->fetchAll();
        }
        $stmt = $this->db->prepare(
            "SELECT m.*, p.nombre AS producto FROM movimientos_inventario m
             INNER JOIN productos p ON p.id_producto = m.id_producto
             WHERE m.id_compra = :c ORDER BY m.id ASC"
        );
        $stmt->execute([':c' => $idCompra]);
        return $stmt->fetchAll();
    }

    public function contar(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM movimientos_inventario")->fetchColumn();
    }

    /** Historial general de movimientos (más reciente primero). */
    public function recientes(int $limite = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, p.nombre AS producto, u.username AS usuario
             FROM movimientos_inventario m
             INNER JOIN productos p ON p.id_producto = m.id_producto
             LEFT JOIN usuarios u ON u.id = m.id_usuario
             ORDER BY m.fecha DESC, m.id DESC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
