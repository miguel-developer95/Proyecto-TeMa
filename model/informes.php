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
     * Obtiene métricas ejecutivas consolidadas para el Dashboard
     */
    public function obtenerMetricasEjecutivas(string $fechaInicio, string $fechaFin): array
    {
        [$inicio, $fin] = $this->formatearRangoFechas($fechaInicio, $fechaFin);

        // 1. Ventas y transacciones de HOY
        $sqlVentasHoy = "SELECT COALESCE(SUM(total), 0) AS ventas_hoy,
                                COUNT(id_venta) AS transacciones_hoy
                         FROM ventas
                         WHERE DATE(fecha) = CURDATE() AND estado = 'completada'";
        $stmtVentasHoy = $this->pdo->query($sqlVentasHoy);
        $hoyData = $stmtVentasHoy->fetch(PDO::FETCH_ASSOC) ?: [];
        $ventasHoy = (float) ($hoyData['ventas_hoy'] ?? 0);
        $transaccionesHoy = (int) ($hoyData['transacciones_hoy'] ?? 0);

        // 2. Ventas y transacciones en el PERÍODO FILTRADO
        $sqlPeriodo = "SELECT COALESCE(SUM(total), 0) AS ingresos_periodo,
                              COUNT(id_venta) AS transacciones_periodo
                       FROM ventas
                       WHERE fecha BETWEEN :inicio AND :fin
                         AND estado = 'completada'";
        $stmtPeriodo = $this->pdo->prepare($sqlPeriodo);
        $stmtPeriodo->execute([':inicio' => $inicio, ':fin' => $fin]);
        $periodoData = $stmtPeriodo->fetch(PDO::FETCH_ASSOC) ?: [];
        $ingresosPeriodo = (float) ($periodoData['ingresos_periodo'] ?? 0);
        $transaccionesPeriodo = (int) ($periodoData['transacciones_periodo'] ?? 0);

        // 3. Ganancia real y costos del período
        $sqlRentabilidad = "SELECT
                                COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) AS total_ventas,
                                COALESCE(SUM(dv.cantidad * p.precio_compra), 0) AS costo_mercancia,
                                COALESCE(SUM(dv.cantidad * (dv.precio_unitario - p.precio_compra)), 0) AS ganancia_real
                            FROM detalle_ventas dv
                            INNER JOIN productos p ON p.id_producto = dv.id_producto
                            INNER JOIN ventas v ON v.id_venta = dv.id_venta
                            WHERE v.fecha BETWEEN :inicio AND :fin
                              AND v.estado = 'completada'";
        $stmtRent = $this->pdo->prepare($sqlRentabilidad);
        $stmtRent->execute([':inicio' => $inicio, ':fin' => $fin]);
        $rentData = $stmtRent->fetch(PDO::FETCH_ASSOC) ?: [];
        $costoMercancia = (float) ($rentData['costo_mercancia'] ?? 0);
        $gananciaReal = (float) ($rentData['ganancia_real'] ?? 0);
        $margenPorcentaje = $ingresosPeriodo > 0 ? ($gananciaReal / $ingresosPeriodo) * 100 : 0.0;

        // 4. Ticket promedio
        $ticketPromedio = $transaccionesPeriodo > 0 ? ($ingresosPeriodo / $transaccionesPeriodo) : 0.0;

        // 5. Valoración del Inventario actual (a costo y a precio venta)
        $sqlInv = "SELECT
                        COUNT(*) AS total_productos,
                        COALESCE(SUM(cantidad_stock * precio_compra), 0) AS valor_inventario_costo,
                        COALESCE(SUM(cantidad_stock * precio_venta), 0) AS valor_inventario_venta,
                        COALESCE(SUM(CASE WHEN cantidad_stock <= stock_minimo THEN 1 ELSE 0 END), 0) AS alertas_stock
                   FROM productos
                   WHERE estado = 'activo'";
        $invData = $this->pdo->query($sqlInv)->fetch(PDO::FETCH_ASSOC) ?: [];
        $totalProductos = (int) ($invData['total_productos'] ?? 0);
        $valorInventarioCosto = (float) ($invData['valor_inventario_costo'] ?? 0);
        $valorInventarioVenta = (float) ($invData['valor_inventario_venta'] ?? 0);
        $alertasStock = (int) ($invData['alertas_stock'] ?? 0);

        // 6. Total usuarios
        $totalUsuarios = (int) $this->pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

        return [
            'ventas_hoy'             => $ventasHoy,
            'transacciones_hoy'      => $transaccionesHoy,
            'ingresos_periodo'       => $ingresosPeriodo,
            'transacciones_periodo'  => $transaccionesPeriodo,
            'costo_mercancia'        => $costoMercancia,
            'ganancia_real'          => $gananciaReal,
            'margen_porcentaje'      => round($margenPorcentaje, 1),
            'ticket_promedio'        => round($ticketPromedio, 2),
            'total_productos'        => $totalProductos,
            'valor_inventario_costo' => $valorInventarioCosto,
            'valor_inventario_venta' => $valorInventarioVenta,
            'alertas_stock'          => $alertasStock,
            'total_usuarios'         => $totalUsuarios,
        ];
    }

    /**
     * Mantiene retrocompatibilidad con el método anterior
     */
    public function obtenerMetricasDashboard(): array
    {
        $fechaInicio = date('Y-m-01');
        $fechaFin = date('Y-m-d');
        return $this->obtenerMetricasEjecutivas($fechaInicio, $fechaFin);
    }

    /**
     * Serie temporal de ventas día por día para gráfica de tendencia (Chart.js)
     */
    public function obtenerTendenciaVentas(string $fechaInicio, string $fechaFin): array
    {
        [$inicio, $fin] = $this->formatearRangoFechas($fechaInicio, $fechaFin);

        $sql = "SELECT DATE(fecha) AS dia,
                       COALESCE(SUM(total), 0) AS total_dia,
                       COUNT(id_venta) AS num_ventas
                FROM ventas
                WHERE fecha BETWEEN :inicio AND :fin
                  AND estado = 'completada'
                GROUP BY DATE(fecha)
                ORDER BY dia ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $inicio, ':fin' => $fin]);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapaDias = [];
        foreach ($filas as $f) {
            $mapaDias[$f['dia']] = [
                'total' => (float) $f['total_dia'],
                'ventas' => (int) $f['num_ventas'],
            ];
        }

        // Generar secuencia continua de fechas si el rango no supera los 60 días
        $labels = [];
        $valores = [];
        $conteos = [];

        $dStart = new DateTime(substr($inicio, 0, 10));
        $dEnd   = new DateTime(substr($fin, 0, 10));
        $diff   = $dStart->diff($dEnd)->days;

        if ($diff <= 62) {
            $curr = clone $dStart;
            while ($curr <= $dEnd) {
                $k = $curr->format('Y-m-d');
                $labels[]  = $curr->format('d/m');
                $valores[] = isset($mapaDias[$k]) ? $mapaDias[$k]['total'] : 0.0;
                $conteos[] = isset($mapaDias[$k]) ? $mapaDias[$k]['ventas'] : 0;
                $curr->modify('+1 day');
            }
        } else {
            foreach ($filas as $f) {
                $labels[]  = date('d/m/y', strtotime($f['dia']));
                $valores[] = (float) $f['total_dia'];
                $conteos[] = (int) $f['num_ventas'];
            }
        }

        return [
            'labels'  => $labels,
            'valores' => $valores,
            'conteos' => $conteos,
        ];
    }

    /**
     * Ventas por categoría para gráfica de Dona/Pie (Chart.js)
     */
    public function obtenerDistribucionCategorias(string $fechaInicio, string $fechaFin): array
    {
        [$inicio, $fin] = $this->formatearRangoFechas($fechaInicio, $fechaFin);

        $sql = "SELECT COALESCE(NULLIF(TRIM(p.categoria), ''), 'Sin categoría') AS categoria,
                       COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) AS total_ingresos,
                       COALESCE(SUM(dv.cantidad), 0) AS total_unidades
                FROM detalle_ventas dv
                INNER JOIN productos p ON p.id_producto = dv.id_producto
                INNER JOIN ventas v ON v.id_venta = dv.id_venta
                WHERE v.fecha BETWEEN :inicio AND :fin
                  AND v.estado = 'completada'
                GROUP BY COALESCE(NULLIF(TRIM(p.categoria), ''), 'Sin categoría')
                ORDER BY total_ingresos DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $inicio, ':fin' => $fin]);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $valores = [];
        $unidades = [];

        foreach ($filas as $f) {
            $labels[]   = $f['categoria'];
            $valores[]  = (float) $f['total_ingresos'];
            $unidades[] = (int) $f['total_unidades'];
        }

        return [
            'labels'   => $labels,
            'valores'  => $valores,
            'unidades' => $unidades,
        ];
    }

    /**
     * Métodos de pago para gráfica circular (Chart.js)
     */
    public function obtenerVentasPorMetodoPago(string $fechaInicio, string $fechaFin): array
    {
        [$inicio, $fin] = $this->formatearRangoFechas($fechaInicio, $fechaFin);

        $sql = "SELECT COALESCE(m.nombre_metodo, 'Otro') AS metodo,
                       COALESCE(m.empresa, '') AS empresa,
                       COUNT(v.id_venta) AS transacciones,
                       COALESCE(SUM(v.total), 0) AS total_monto
                FROM ventas v
                LEFT JOIN metodos_pago m ON m.id_metodo_pago = v.id_metodo_pago
                WHERE v.fecha BETWEEN :inicio AND :fin
                  AND v.estado = 'completada'
                GROUP BY v.id_metodo_pago, m.nombre_metodo, m.empresa
                ORDER BY total_monto DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inicio' => $inicio, ':fin' => $fin]);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = [];
        $valores = [];
        $conteos = [];

        foreach ($filas as $f) {
            $nombre = trim($f['metodo']);
            if (!empty($f['empresa'])) {
                $nombre .= ' (' . trim($f['empresa']) . ')';
            }
            $labels[]  = $nombre;
            $valores[] = (float) $f['total_monto'];
            $conteos[] = (int) $f['transacciones'];
        }

        return [
            'labels'  => $labels,
            'valores' => $valores,
            'conteos' => $conteos,
        ];
    }

    /**
     * Inserta ventas de demostración realistas si la tabla de ventas está completamente vacía
     * para que las gráficas y métricas muestren información viva al administrador.
     */
    public function asegurarDatosDemostracion(): void
    {
        $conteo = (int) $this->pdo->query("SELECT COUNT(*) FROM ventas WHERE estado = 'completada'")->fetchColumn();
        if ($conteo > 0) {
            return;
        }

        // Obtener productos activos
        $prods = $this->pdo->query("SELECT id_producto, nombre, precio_venta, precio_compra, cantidad_stock FROM productos WHERE estado = 'activo'")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($prods)) {
            return;
        }

        $userId = (int) $this->pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;
        $metodos = $this->pdo->query("SELECT id_metodo_pago FROM metodos_pago WHERE estado = 'activo'")->fetchAll(PDO::FETCH_COLUMN) ?: [1];

        // Crear clientes de prueba si no existen
        $clienteCount = (int) $this->pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
        if ($clienteCount === 0) {
            $this->pdo->exec("INSERT INTO clientes (nombre, documento, telefono, correo_electronico) VALUES
                ('Consumidor Final', '2222222222', '3000000000', 'cliente@tentacionesmarlly.com'),
                ('María Gómez', '1010101010', '3112223344', 'maria.gomez@gmail.com'),
                ('Carlos Rodríguez', '1020202020', '3154445566', 'carlos.rodriguez@gmail.com')
            ");
        }
        $clienteIds = $this->pdo->query("SELECT id_cliente FROM clientes")->fetchAll(PDO::FETCH_COLUMN) ?: [1];

        // Insertar ventas distribuidas en los últimos 7 días
        $diasAtras = [6, 5, 4, 3, 2, 1, 0, 0];
        $horaBase = 9;

        foreach ($diasAtras as $idx => $d) {
            $fechaVenta = date('Y-m-d H:i:s', strtotime("-$d days +$horaBase hours +" . ($idx * 35) . " minutes"));
            $metodoId = $metodos[$idx % count($metodos)];
            $clienteId = $clienteIds[$idx % count($clienteIds)];
            $reciboNum = 'REC-' . date('Ymd', strtotime("-$d days")) . '-' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT);

            // Escoger 1 o 2 productos aleatorios
            $cantItems = ($idx % 2 === 0) ? 2 : 1;
            $itemsVenta = [];
            $totalVenta = 0.0;

            for ($i = 0; $i < $cantItems; $i++) {
                $p = $prods[($idx + $i) % count($prods)];
                $cant = ($idx % 3 === 0) ? 3 : 1;
                $subtotal = $cant * (float) $p['precio_venta'];
                $totalVenta += $subtotal;
                $itemsVenta[] = [
                    'id_producto'     => $p['id_producto'],
                    'cantidad'        => $cant,
                    'precio_unitario' => (float) $p['precio_venta'],
                ];
            }

            $stmtV = $this->pdo->prepare("INSERT INTO ventas (fecha, id_usuario, id_cliente, id_metodo_pago, empresa, total, valor_recibido, cambio, estado, numero_recibo)
                VALUES (:fecha, :id_usuario, :id_cliente, :id_metodo, 'Tentaciones Marlly', :total, :recibido, 0, 'completada', :recibo)");
            $stmtV->execute([
                ':fecha'      => $fechaVenta,
                ':id_usuario' => $userId,
                ':id_cliente' => $clienteId,
                ':id_metodo'  => $metodoId,
                ':total'      => $totalVenta,
                ':recibido'   => $totalVenta,
                ':recibo'     => $reciboNum,
            ]);
            $idVenta = (int) $this->pdo->lastInsertId();

            $stmtDet = $this->pdo->prepare("INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario) VALUES (:id_venta, :id_prod, :cant, :precio)");
            foreach ($itemsVenta as $it) {
                $stmtDet->execute([
                    ':id_venta' => $idVenta,
                    ':id_prod'  => $it['id_producto'],
                    ':cant'     => $it['cantidad'],
                    ':precio'   => $it['precio_unitario'],
                ]);
            }
        }
    }
}

