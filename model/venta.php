<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Connection.php';

/**
 * Modelo de ventas. Tablas: ventas, detalle_ventas. (RF 5.1 – 5.6)
 */
class Venta
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    /**
     * Registra una venta con sus detalles y descuenta stock (transacción).
     * $items: [['id_producto'=>int,'cantidad'=>int,'precio_unitario'=>float], ...]
     * Retorna el id_venta o 0 si falla (p. ej. stock insuficiente).
     */
    public function crear(
        array $items,
        int $idMetodoPago,
        float $valorRecibido,
        int $idUsuario,
        ?int $idCliente = null,
        ?string $empresa = null
    ): int {
        if (empty($items)) {
            return 0;
        }
        try {
            $this->db->beginTransaction();

            $total = 0.0;
            $stocksAntes = [];
            foreach ($items as $it) {
                $cant = (int) $it['cantidad'];
                $precio = (float) $it['precio_unitario'];
                if ($cant <= 0 || $precio < 0) {
                    throw new RuntimeException('Cantidades o precios inválidos.');
                }
                $stmt = $this->db->prepare(
                    "SELECT cantidad_stock FROM productos WHERE id_producto = :id FOR UPDATE"
                );
                $stmt->execute([':id' => (int) $it['id_producto']]);
                $row = $stmt->fetch();
                if (!$row || (int) $row['cantidad_stock'] < $cant) {
                    throw new RuntimeException('Stock insuficiente.');
                }
                $stocksAntes[(int) $it['id_producto']] = (int) $row['cantidad_stock'];
                $total += $cant * $precio;
            }

            if ($valorRecibido < $total) {
                throw new RuntimeException('El valor recibido es menor que el total.');
            }
            $cambio = round($valorRecibido - $total, 2);

            $stmt = $this->db->prepare(
                "INSERT INTO ventas (id_usuario, id_cliente, id_metodo_pago, empresa, total, valor_recibido, cambio, estado)
                 VALUES (:u, :c, :mp, :emp, :total, :recibido, :cambio, 'completada')"
            );
            $stmt->execute([
                ':u' => $idUsuario,
                ':c' => $idCliente,
                ':mp' => $idMetodoPago,
                ':emp' => $empresa ?: null,
                ':total' => $total,
                ':recibido' => $valorRecibido,
                ':cambio' => $cambio,
            ]);
            $idVenta = (int) $this->db->lastInsertId();

            $stmtDet = $this->db->prepare(
                "INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario)
                 VALUES (:v, :p, :cant, :precio)"
            );
            $stmtStock = $this->db->prepare(
                "UPDATE productos SET cantidad_stock = cantidad_stock - :cant WHERE id_producto = :p"
            );
            // RF 5.2: kardex de salidas vinculadas a la venta
            $stmtMov = $this->db->prepare(
                "INSERT INTO movimientos_inventario
                 (id_producto, tipo, cantidad, stock_antes, stock_despues, id_venta, id_usuario)
                 VALUES (:p, 'salida', :cant, :antes, :despues, :v, :u)"
            );
            foreach ($items as $it) {
                $idProd = (int) $it['id_producto'];
                $cant = (int) $it['cantidad'];
                $stmtDet->execute([
                    ':v' => $idVenta,
                    ':p' => $idProd,
                    ':cant' => $cant,
                    ':precio' => (float) $it['precio_unitario'],
                ]);
                $stmtStock->execute([':cant' => $cant, ':p' => $idProd]);
                $antes = $stocksAntes[$idProd] ?? 0;
                $stmtMov->execute([
                    ':p' => $idProd, ':cant' => $cant,
                    ':antes' => $antes, ':despues' => $antes - $cant,
                    ':v' => $idVenta, ':u' => $idUsuario,
                ]);
            }

            // RF 5.6: número de recibo correlativo
            $recibo = 'REC-' . str_pad((string) $idVenta, 6, '0', STR_PAD_LEFT);
            $stmt = $this->db->prepare("UPDATE ventas SET numero_recibo = :r WHERE id_venta = :v");
            $stmt->execute([':r' => $recibo, ':v' => $idVenta]);

            $this->db->commit();
            return $idVenta;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return 0;
        }
    }

    /** RF 5.5: anula una venta exigiendo motivo y reintegra el stock. */
    public function anular(int $idVenta, string $motivo, ?int $idUsuario = null): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT estado FROM ventas WHERE id_venta = :v LIMIT 1"
            );
            $stmt->execute([':v' => $idVenta]);
            $row = $stmt->fetch();
            if (!$row || $row['estado'] !== 'completada') {
                $this->db->rollBack();
                return false;
            }

            $motivo = trim($motivo);
            $stmt = $this->db->prepare(
                "UPDATE ventas SET estado = 'anulada', motivo_anulacion = :m WHERE id_venta = :v"
            );
            $stmt->execute([':m' => $motivo, ':v' => $idVenta]);

            $stmt = $this->db->prepare(
                "SELECT id_producto, cantidad FROM detalle_ventas WHERE id_venta = :v"
            );
            $stmt->execute([':v' => $idVenta]);
            $detalles = $stmt->fetchAll();
            $stmtAntes = $this->db->prepare(
                "SELECT cantidad_stock FROM productos WHERE id_producto = :p FOR UPDATE"
            );
            $stmtStock = $this->db->prepare(
                "UPDATE productos SET cantidad_stock = cantidad_stock + :cant WHERE id_producto = :p"
            );
            $stmtMov = $this->db->prepare(
                "INSERT INTO movimientos_inventario
                 (id_producto, tipo, cantidad, stock_antes, stock_despues, id_venta, motivo, id_usuario)
                 VALUES (:p, 'entrada', :cant, :antes, :despues, :v, :motivo, :u)"
            );
            foreach ($detalles as $det) {
                $idProd = (int) $det['id_producto'];
                $cant = (int) $det['cantidad'];
                $stmtAntes->execute([':p' => $idProd]);
                $prow = $stmtAntes->fetch();
                $antes = $prow ? (int) $prow['cantidad_stock'] : 0;
                $stmtStock->execute([':cant' => $cant, ':p' => $idProd]);
                $stmtMov->execute([
                    ':p' => $idProd, ':cant' => $cant,
                    ':antes' => $antes, ':despues' => $antes + $cant,
                    ':v' => $idVenta, ':motivo' => mb_substr('Anulación venta #' . $idVenta . ': ' . $motivo, 0, 255),
                    ':u' => $idUsuario,
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function obtenerTodas(int $limite = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT v.*, u.username AS vendedor, c.nombre AS cliente, m.nombre_metodo, m.empresa AS empresa_pago
             FROM ventas v
             INNER JOIN usuarios u ON u.id = v.id_usuario
             LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
             LEFT JOIN metodos_pago m ON m.id_metodo_pago = v.id_metodo_pago
             ORDER BY v.id_venta DESC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id)
    {
        $stmt = $this->db->prepare(
            "SELECT v.*, u.username AS vendedor, c.nombre AS cliente, c.correo_electronico AS correo_cliente,
                    m.nombre_metodo, m.empresa AS empresa_pago
             FROM ventas v
             INNER JOIN usuarios u ON u.id = v.id_usuario
             LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
             LEFT JOIN metodos_pago m ON m.id_metodo_pago = v.id_metodo_pago
             WHERE v.id_venta = :v LIMIT 1"
        );
        $stmt->execute([':v' => $id]);
        return $stmt->fetch();
    }

    public function obtenerDetalle(int $idVenta): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, p.nombre AS nombre_producto, p.codigo_barras,
                    (d.cantidad * d.precio_unitario) AS subtotal
             FROM detalle_ventas d
             INNER JOIN productos p ON p.id_producto = d.id_producto
             WHERE d.id_venta = :v ORDER BY p.nombre ASC"
        );
        $stmt->execute([':v' => $idVenta]);
        return $stmt->fetchAll();
    }

    /** Ventas del día para el dashboard. */
    public function resumenHoy(): array
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS total
             FROM ventas WHERE DATE(fecha) = CURDATE() AND estado = 'completada'"
        );
        return $stmt->fetch() ?: ['cantidad' => 0, 'total' => 0];
    }

    public function ultimas(int $limite = 5): array
    {
        return $this->obtenerTodas($limite);
    }
}
