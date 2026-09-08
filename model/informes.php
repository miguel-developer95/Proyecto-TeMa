<?php 

require_once __DIR__ . '/conexion.php';

class Informes
{
    private PDO $pdo;

    public function __construct()
    {
        // conexion.php debe exponer una instancia PDO en $pdo
        global $pdo;
        $this->pdo = $pdo;
    }

    /* =========================================================
     * RF 2.1 - Ganancia real por producto o por categoría
     * Ganancia = (precio_venta - precio_compra) * cantidad_vendida
     * ========================================================= */

    /**
     * Calcula la ganancia real agrupada por producto dentro de un rango de fechas.
     *
     * @param string $fechaInicio formato 'YYYY-MM-DD'
     * @param string $fechaFin    formato 'YYYY-MM-DD'
     * @return array Lista de productos con cantidad vendida, ingresos, costo y ganancia.
     */
    public function gananciaPorProducto(string $fechaInicio, string $fechaFin): array
    {
        $sql = "SELECT
                    p.id_producto,
                    p.codigo_barras,
                    p.nombre,
                    p.categoria,
                    SUM(dv.cantidad)                                   AS cantidad_vendida,
                    SUM(dv.cantidad * dv.precio_unitario)               AS total_ingresos,
                    SUM(dv.cantidad * p.precio_compra)                  AS total_costo,
                    SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)) AS ganancia_real
                FROM DETALLE_VENTA dv
                INNER JOIN PRODUCTOS p ON p.id_producto = dv.id_producto
                INNER JOIN VENTA v ON v.id_venta = dv.id_venta
                WHERE v.fecha BETWEEN :fechaInicio AND :fechaFin
                  AND v.estado = 'completada'
                GROUP BY p.id_producto, p.codigo_barras, p.nombre, p.categoria
                ORDER BY ganancia_real DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':fechaInicio' => $fechaInicio,
            ':fechaFin'    => $fechaFin,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcula la ganancia real agrupada por categoría dentro de un rango de fechas.
     */
    public function gananciaPorCategoria(string $fechaInicio, string $fechaFin): array
    {
        $sql = "SELECT
                    p.categoria,
                    SUM(dv.cantidad)                                   AS cantidad_vendida,
                    SUM(dv.cantidad * dv.precio_unitario)               AS total_ingresos,
                    SUM(dv.cantidad * p.precio_compra)                  AS total_costo,
                    SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)) AS ganancia_real
                FROM DETALLE_VENTA dv
                INNER JOIN PRODUCTOS p ON p.id_producto = dv.id_producto
                INNER JOIN VENTA v ON v.id_venta = dv.id_venta
                WHERE v.fecha BETWEEN :fechaInicio AND :fechaFin
                  AND v.estado = 'completada'
                GROUP BY p.categoria
                ORDER BY ganancia_real DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':fechaInicio' => $fechaInicio,
            ':fechaFin'    => $fechaFin,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================================================
     * RF 2.2 - Productos con mayor rotación / baja rotación
     * ========================================================= */

    /**
     * Devuelve los productos más vendidos (mayor rotación) en un rango de fechas.
     *
     * @param string $fechaInicio
     * @param string $fechaFin
     * @param int    $limite Cantidad de productos a retornar (top N).
     */
    public function productosMasVendidos(string $fechaInicio, string $fechaFin, int $limite = 10): array
    {
        $sql = "SELECT
                    p.id_producto,
                    p.codigo_barras,
                    p.nombre,
                    p.categoria,
                    SUM(dv.cantidad) AS unidades_vendidas
                FROM DETALLE_VENTA dv
                INNER JOIN PRODUCTOS p ON p.id_producto = dv.id_producto
                INNER JOIN VENTA v ON v.id_venta = dv.id_venta
                WHERE v.fecha BETWEEN :fechaInicio AND :fechaFin
                  AND v.estado = 'completada'
                GROUP BY p.id_producto, p.codigo_barras, p.nombre, p.categoria
                ORDER BY unidades_vendidas DESC
                LIMIT :limite";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':fechaInicio', $fechaInicio);
        $stmt->bindValue(':fechaFin', $fechaFin);
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
        $sql = "SELECT
                    p.id_producto,
                    p.codigo_barras,
                    p.nombre,
                    p.categoria,
                    p.cantidad_stock
                FROM PRODUCTOS p
                WHERE p.estado = 'activo'
                  AND p.id_producto NOT IN (
                      SELECT dv.id_producto
                      FROM DETALLE_VENTA dv
                      INNER JOIN VENTA v ON v.id_venta = dv.id_venta
                      WHERE v.fecha BETWEEN :fechaInicio AND :fechaFin
                        AND v.estado = 'completada'
                  )
                ORDER BY p.nombre ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':fechaInicio' => $fechaInicio,
            ':fechaFin'    => $fechaFin,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda un registro de informe generado en la tabla INFORMES,
     * para trazabilidad (ID único, fecha de generación, tipo y descripción).
     *
     * @param string $tipoInforme  Ej: 'Ganancias', 'Productos Más Vendidos', 'Productos de Baja Rotación'
     * @param string $descripcion  Resumen textual de los resultados obtenidos.
     * @param int    $idUsuario    Usuario que generó el informe.
     * @return int Id del informe insertado.
     */
    public function guardarInforme(string $tipoInforme, string $descripcion, int $idUsuario): int
    {
        $sql = "INSERT INTO INFORMES (tipo_informe, descripcion, fecha_generacion, id_usuario)
                VALUES (:tipoInforme, :descripcion, NOW(), :idUsuario)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':tipoInforme' => $tipoInforme,
            ':descripcion' => $descripcion,
            ':idUsuario'   => $idUsuario,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Lista el historial de informes generados, opcionalmente filtrado por tipo.
     */
    public function historialInformes(?string $tipoInforme = null): array
    {
        if ($tipoInforme !== null) {
            $sql = "SELECT * FROM INFORMES WHERE tipo_informe = :tipoInforme ORDER BY fecha_generacion DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':tipoInforme' => $tipoInforme]);
        } else {
            $sql = "SELECT * FROM INFORMES ORDER BY fecha_generacion DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================================================
     * RF 2.3 - Matriz de permisos (Administrador vs Vendedor)
     * ========================================================= */

    /**
     * Verifica si un usuario tiene permiso para acceder a los informes
     * o para realizar acciones administrativas (anular ventas, cambiar precios).
     *
     * Asume que la tabla USUARIO tiene una columna id_rol y que ROLES/PERMISOS
     * define qué puede hacer cada rol. Ajusta los nombres según tu script SQL final.
     *
     * @param int    $idUsuario
     * @param string $permiso  Ej: 'ver_informes', 'anular_venta', 'cambiar_precio'
     */
    public function usuarioTienePermiso(int $idUsuario, string $permiso): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM USUARIO u
                INNER JOIN ROLES r ON r.id_rol = u.id_rol
                INNER JOIN ROL_PERMISO rp ON rp.id_rol = r.id_rol
                INNER JOIN PERMISOS pe ON pe.id_permiso = rp.id_permiso
                WHERE u.id_usuario = :idUsuario
                  AND pe.nombre_permiso = :permiso";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':idUsuario' => $idUsuario,
            ':permiso'   => $permiso,
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado && (int) $resultado['total'] > 0;
    }
}

