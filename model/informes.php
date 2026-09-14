<?php
// model/informes.php - RF 2.1 & RF 2.2: Informes de ganancia real y rotación de inventario.
require_once __DIR__ . '/../config/connection.php';

class Informes
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = (new Connection())->conn;
    }

    private function formatearRangoFechas(string $fechaInicio, string $fechaFin): array
    {
        $inicio = (strlen(trim($fechaInicio)) === 10) ? trim($fechaInicio) . ' 00:00:00' : trim($fechaInicio);
        $fin = (strlen(trim($fechaFin)) === 10) ? trim($fechaFin) . ' 23:59:59' : trim($fechaFin);
        return [$inicio, $fin];
    }

    /* =========================================================
     * RF 2.1 - Ganancia real por producto o por categoría
     * Ganancia Real = (precio_venta - precio_compra) * cantidad_vendida
     * ========================================================= */

    /**
     * Calcula la ganancia real agrupada por producto dentro de un rango de fechas.
     */
    public function gananciaPorProducto(string $fechaInicio, string $fechaFin): array
    {
        [$inicio, $fin] = $this->formatearRangoFechas($fechaInicio, $fechaFin);

        $sql = "SELECT
                    p.id_producto,
                    p.codigo_barras,
                    p.nombre,
                    p.categoria,
                    p.precio_compra,
                    SUM(dv.cantidad)                                         AS cantidad_vendida,
                    SUM(dv.cantidad * dv.precio_unitario)                     AS total_ingresos,
                    SUM(dv.cantidad * p.precio_compra)                        AS total_costo,
                    SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)) AS ganancia_real
                FROM detalle_ventas dv
                INNER JOIN productos p ON p.id_producto = dv.id_producto
                INNER JOIN ventas v ON v.id_venta = dv.id_venta
                WHERE v.fecha BETWEEN :fechaInicio AND :fechaFin
                  AND v.estado = 'completada'
                GROUP BY p.id_producto, p.codigo_barras, p.nombre, p.categoria, p.precio_compra
                ORDER BY ganancia_real DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':fechaInicio' => $inicio,
            ':fechaFin'    => $fin,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcula la ganancia real agrupada por categoría dentro de un rango de fechas.
     */
    public function gananciaPorCategoria(string $fechaInicio, string $fechaFin): array
    {
        [$inicio, $fin] = $this->formatearRangoFechas($fechaInicio, $fechaFin);

        $sql = "SELECT
                    COALESCE(NULLIF(TRIM(p.categoria), ''), 'Sin categoría') AS categoria,
                    COUNT(DISTINCT p.id_producto)                            AS productos_distintos,
                    SUM(dv.cantidad)                                         AS cantidad_vendida,
                    SUM(dv.cantidad * dv.precio_unitario)                     AS total_ingresos,
                    SUM(dv.cantidad * p.precio_compra)                        AS total_costo,
                    SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)) AS ganancia_real
                FROM detalle_ventas dv
                INNER JOIN productos p ON p.id_producto = dv.id_producto
                INNER JOIN ventas v ON v.id_venta = dv.id_venta
                WHERE v.fecha BETWEEN :fechaInicio AND :fechaFin
                  AND v.estado = 'completada'
                GROUP BY COALESCE(NULLIF(TRIM(p.categoria), ''), 'Sin categoría')
                ORDER BY ganancia_real DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':fechaInicio' => $inicio,
            ':fechaFin'    => $fin,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================================================
     * RF 2.2 - Productos con mayor rotación / baja rotación
     * ========================================================= */

    /**
     * Devuelve los productos más vendidos (mayor rotación) en un rango de fechas.
     */
    public function productosMasVendidos(string $fechaInicio, string $fechaFin, int $limite = 10): array
    {
        [$inicio, $fin] = $this->formatearRangoFechas($fechaInicio, $fechaFin);

        $sql = "SELECT
                    p.id_producto,
                    p.codigo_barras,
                    p.nombre,
                    p.categoria,
                    p.cantidad_stock,
                    SUM(dv.cantidad)                      AS unidades_vendidas,
                    SUM(dv.cantidad * dv.precio_unitario)  AS total_recaudado
                FROM detalle_ventas dv
                INNER JOIN productos p ON p.id_producto = dv.id_producto
                INNER JOIN ventas v ON v.id_venta = dv.id_venta
                WHERE v.fecha BETWEEN :fechaInicio AND :fechaFin
                  AND v.estado = 'completada'
                GROUP BY p.id_producto, p.codigo_barras, p.nombre, p.categoria, p.cantidad_stock
                ORDER BY unidades_vendidas DESC
                LIMIT :limite";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':fechaInicio', $inicio);
        $stmt->bindValue(':fechaFin', $fin);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve productos activos que NO registraron ventas en un rango de fechas
     * (baja rotación / sin movimiento).
     */
    public function productosBajaRotacion(string $fechaInicio, string $fechaFin): array
    {
        [$inicio, $fin] = $this->formatearRangoFechas($fechaInicio, $fechaFin);

        $sql = "SELECT
                    p.id_producto,
                    p.codigo_barras,
                    p.nombre,
                    p.categoria,
                    p.precio_venta,
                    p.precio_compra,
                    p.cantidad_stock,
                    p.stock_minimo
                FROM productos p
                WHERE p.estado = 'activo'
                  AND p.id_producto NOT IN (
                      SELECT dv.id_producto
                      FROM detalle_ventas dv
                      INNER JOIN ventas v ON v.id_venta = dv.id_venta
                      WHERE v.fecha BETWEEN :fechaInicio AND :fechaFin
                        AND v.estado = 'completada'
                  )
                ORDER BY p.nombre ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':fechaInicio' => $inicio,
            ':fechaFin'    => $fin,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene métricas rápidas consolidadas para el Dashboard
     */
    public function obtenerMetricasDashboard(): array
    {
        // 1. Ventas del día
        $sqlVentasHoy = "SELECT COALESCE(SUM(total), 0) AS ventas_hoy
                         FROM ventas
                         WHERE DATE(fecha) = CURDATE() AND estado = 'completada'";
        $stmtVentas = $this->pdo->query($sqlVentasHoy);
        $ventasHoy = (float) ($stmtVentas->fetch(PDO::FETCH_ASSOC)['ventas_hoy'] ?? 0);

        // 2. Total productos activos
        $sqlProds = "SELECT COUNT(*) AS total_productos FROM productos WHERE estado = 'activo'";
        $stmtProds = $this->pdo->query($sqlProds);
        $totalProductos = (int) ($stmtProds->fetch(PDO::FETCH_ASSOC)['total_productos'] ?? 0);

        // 3. Alertas de stock bajo
        $sqlStockBajo = "SELECT COUNT(*) AS stock_bajo FROM productos WHERE estado = 'activo' AND cantidad_stock <= stock_minimo";
        $stmtStock = $this->pdo->query($sqlStockBajo);
        $alertasStock = (int) ($stmtStock->fetch(PDO::FETCH_ASSOC)['stock_bajo'] ?? 0);

        // 4. Total usuarios registrados
        $sqlUsuarios = "SELECT COUNT(*) AS total_usuarios FROM usuarios";
        $stmtUsuarios = $this->pdo->query($sqlUsuarios);
        $totalUsuarios = (int) ($stmtUsuarios->fetch(PDO::FETCH_ASSOC)['total_usuarios'] ?? 0);

        return [
            'ventas_hoy'      => $ventasHoy,
            'total_productos' => $totalProductos,
            'alertas_stock'   => $alertasStock,
            'total_usuarios'  => $totalUsuarios,
        ];
    }
}
