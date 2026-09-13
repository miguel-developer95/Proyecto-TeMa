<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Connection.php';

/**
 * Modelo de informes. Tablas: ventas, detalle_ventas, productos, informes. (RF 2.1, 2.2)
 */
class Informe
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    /** RF 2.1: ganancia real por producto en un rango de fechas. */
    public function gananciaPorProducto(string $inicio, string $fin): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id_producto, p.codigo_barras, p.nombre, p.categoria,
                    SUM(d.cantidad) AS cantidad_vendida,
                    SUM(d.cantidad * d.precio_unitario) AS total_ingresos,
                    SUM(d.cantidad * p.precio_compra) AS total_costo,
                    SUM(d.cantidad * (d.precio_unitario - p.precio_compra)) AS ganancia_real
             FROM detalle_ventas d
             INNER JOIN productos p ON p.id_producto = d.id_producto
             INNER JOIN ventas v ON v.id_venta = d.id_venta
             WHERE DATE(v.fecha) BETWEEN :ini AND :fin AND v.estado = 'completada'
             GROUP BY p.id_producto, p.codigo_barras, p.nombre, p.categoria
             ORDER BY ganancia_real DESC"
        );
        $stmt->execute([':ini' => $inicio, ':fin' => $fin]);
        return $stmt->fetchAll();
    }

    /** RF 2.1: ganancia real agrupada por categoría. */
    public function gananciaPorCategoria(string $inicio, string $fin): array
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(NULLIF(p.categoria, ''), 'Sin categoría') AS categoria,
                    SUM(d.cantidad) AS cantidad_vendida,
                    SUM(d.cantidad * d.precio_unitario) AS total_ingresos,
                    SUM(d.cantidad * p.precio_compra) AS total_costo,
                    SUM(d.cantidad * (d.precio_unitario - p.precio_compra)) AS ganancia_real
             FROM detalle_ventas d
             INNER JOIN productos p ON p.id_producto = d.id_producto
             INNER JOIN ventas v ON v.id_venta = d.id_venta
             WHERE DATE(v.fecha) BETWEEN :ini AND :fin AND v.estado = 'completada'
             GROUP BY categoria
             ORDER BY ganancia_real DESC"
        );
        $stmt->execute([':ini' => $inicio, ':fin' => $fin]);
        return $stmt->fetchAll();
    }

    /** RF 2.2: productos más vendidos (mayor rotación). */
    public function masVendidos(string $inicio, string $fin, int $limite = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id_producto, p.codigo_barras, p.nombre, p.categoria,
                    SUM(d.cantidad) AS unidades_vendidas
             FROM detalle_ventas d
             INNER JOIN productos p ON p.id_producto = d.id_producto
             INNER JOIN ventas v ON v.id_venta = d.id_venta
             WHERE DATE(v.fecha) BETWEEN :ini AND :fin AND v.estado = 'completada'
             GROUP BY p.id_producto, p.codigo_barras, p.nombre, p.categoria
             ORDER BY unidades_vendidas DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':ini', $inicio);
        $stmt->bindValue(':fin', $fin);
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** RF 2.2: productos activos sin ventas en el rango (baja rotación). */
    public function bajaRotacion(string $inicio, string $fin): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id_producto, p.codigo_barras, p.nombre, p.categoria, p.cantidad_stock
             FROM productos p
             WHERE p.estado = 'activo'
               AND p.id_producto NOT IN (
                   SELECT d.id_producto FROM detalle_ventas d
                   INNER JOIN ventas v ON v.id_venta = d.id_venta
                   WHERE DATE(v.fecha) BETWEEN :ini AND :fin AND v.estado = 'completada'
               )
             ORDER BY p.nombre ASC"
        );
        $stmt->execute([':ini' => $inicio, ':fin' => $fin]);
        return $stmt->fetchAll();
    }

    /** Deja constancia del informe generado (trazabilidad). */
    public function guardar(string $tipo, string $descripcion, int $idUsuario): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO informes (tipo_informe, descripcion, id_usuario)
             VALUES (:t, :d, :u)"
        );
        $stmt->execute([':t' => $tipo, ':d' => $descripcion, ':u' => $idUsuario]);
        return (int) $this->db->lastInsertId();
    }

    public function historial(?string $tipo = null): array
    {
        if ($tipo !== null && trim($tipo) !== '') {
            $stmt = $this->db->prepare(
                "SELECT i.*, u.username FROM informes i
                 LEFT JOIN usuarios u ON u.id = i.id_usuario
                 WHERE i.tipo_informe = :t ORDER BY i.fecha_generacion DESC"
            );
            $stmt->execute([':t' => trim($tipo)]);
        } else {
            $stmt = $this->db->query(
                "SELECT i.*, u.username FROM informes i
                 LEFT JOIN usuarios u ON u.id = i.id_usuario
                 ORDER BY i.fecha_generacion DESC"
            );
        }
        return $stmt->fetchAll();
    }
}
