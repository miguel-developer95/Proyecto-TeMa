<?php
// model/venta.php
require_once __DIR__ . '/../config/connection.php';

class Venta
{
    /** @var PDO */
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    /**
     * Registra una nueva venta y descuenta el stock de los productos.
     *
     * @param array $productos Lista de productos: id_producto, cantidad, precio_unitario
     */
    public function registrar(
        int $idUsuario,
        array $productos,
        float $total,
        float $valorRecibido,
        ?int $idMetodoPago = null,
        ?string $empresa = null,
        ?int $idCliente = null,
        string $pagado = 'si'
    ) {
        try {
            $this->db->beginTransaction();

            $cambio = max(0, round($valorRecibido - $total, 2));
            $numeroRecibo = 'REC-' . date('YmdHis') . '-' . mt_rand(100, 999);
            $pagadoVal = strtolower(trim($pagado)) === 'no' ? 'no' : 'si';

            $queryVenta = "INSERT INTO ventas
                (fecha, id_usuario, id_cliente, id_metodo_pago, empresa, total, valor_recibido, cambio, pagado, estado, numero_recibo)
                VALUES (NOW(), :id_usuario, :id_cliente, :id_metodo_pago, :empresa, :total, :valor_recibido, :cambio, :pagado, 'completada', :numero_recibo)";
            $stmtVenta = $this->db->prepare($queryVenta);
            $stmtVenta->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
            $stmtVenta->bindParam(':id_cliente', $idCliente, PDO::PARAM_INT);
            $stmtVenta->bindParam(':id_metodo_pago', $idMetodoPago, PDO::PARAM_INT);
            $stmtVenta->bindParam(':empresa', $empresa);
            $stmtVenta->bindParam(':total', $total);
            $stmtVenta->bindParam(':valor_recibido', $valorRecibido);
            $stmtVenta->bindParam(':cambio', $cambio);
            $stmtVenta->bindParam(':pagado', $pagadoVal);
            $stmtVenta->bindParam(':numero_recibo', $numeroRecibo);
            $stmtVenta->execute();
            $idVenta = (int) $this->db->lastInsertId();

            foreach ($productos as $producto) {
                $queryDetalle = "INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario)
                                 VALUES (:id_venta, :id_producto, :cantidad, :precio_unitario)";
                $stmtDetalle = $this->db->prepare($queryDetalle);
                $stmtDetalle->bindParam(':id_venta', $idVenta, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':id_producto', $producto['id_producto'], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':cantidad', $producto['cantidad']);
                $stmtDetalle->bindParam(':precio_unitario', $producto['precio_unitario']);
                $stmtDetalle->execute();

                $queryStock = "UPDATE productos SET cantidad_stock = cantidad_stock - :cantidad WHERE id_producto = :id_producto";
                $stmtStock = $this->db->prepare($queryStock);
                $stmtStock->bindParam(':cantidad', $producto['cantidad']);
                $stmtStock->bindParam(':id_producto', $producto['id_producto'], PDO::PARAM_INT);
                $stmtStock->execute();

                // RF 5.2: Registrar movimiento de salida vinculado a la venta para trazabilidad
                $queryHist = "INSERT INTO historial (fecha_accion, tipo_accion, detalle_cambio, id_usuario)
                              VALUES (NOW(), 'Salida por Venta', :detalle, :id_usuario)";
                $stmtHist = $this->db->prepare($queryHist);
                $detalleHist = "Venta #{$idVenta} ({$numeroRecibo}): Salida de {$producto['cantidad']} unid. de producto ID #{$producto['id_producto']}";
                $stmtHist->bindParam(':detalle', $detalleHist);
                $stmtHist->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
                $stmtHist->execute();
            }

            $this->db->commit();
            return $idVenta;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Lista las ventas más recientes.
     */
    public function obtenerTodas(int $limite = 20): array
    {
        $query = "SELECT v.id_venta, v.fecha, v.empresa, v.total, v.valor_recibido, v.cambio,
                         v.pagado, v.estado, v.numero_recibo, c.nombre AS cliente,
                         mp.nombre_metodo, mp.empresa AS metodo_empresa
                  FROM ventas v
                  LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                  LEFT JOIN metodos_pago mp ON v.id_metodo_pago = mp.id_metodo_pago
                  ORDER BY v.id_venta DESC
                  LIMIT :limite";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una venta por su ID.
     */
    public function obtenerPorId(int $id)
    {
        $query = "SELECT * FROM ventas WHERE id_venta = :id_venta";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_venta', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Anula una venta y reintegra el stock.
     */
    public function cancelar(int $id, string $motivo): bool
    {
        try {
            $this->db->beginTransaction();

            $query = "UPDATE ventas SET estado = 'anulada', motivo_anulacion = :motivo WHERE id_venta = :id_venta";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':id_venta', $id, PDO::PARAM_INT);
            $stmt->execute();

            $queryDetalle = "SELECT id_producto, cantidad FROM detalle_ventas WHERE id_venta = :id_venta";
            $stmtDetalle = $this->db->prepare($queryDetalle);
            $stmtDetalle->bindParam(':id_venta', $id, PDO::PARAM_INT);
            $stmtDetalle->execute();
            $productos = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

            $idUsuarioSesion = (int) ($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 1);
            foreach ($productos as $producto) {
                $queryStock = "UPDATE productos SET cantidad_stock = cantidad_stock + :cantidad WHERE id_producto = :id_producto";
                $stmtStock = $this->db->prepare($queryStock);
                $stmtStock->bindParam(':cantidad', $producto['cantidad']);
                $stmtStock->bindParam(':id_producto', $producto['id_producto'], PDO::PARAM_INT);
                $stmtStock->execute();

                // RF 5.5: Registrar movimiento de reintegro vinculado a la anulación de venta
                $queryHist = "INSERT INTO historial (fecha_accion, tipo_accion, detalle_cambio, id_usuario)
                              VALUES (NOW(), 'Reintegro por Anulación', :detalle, :id_usuario)";
                $stmtHist = $this->db->prepare($queryHist);
                $detalleHist = "Venta #{$id} anulada. Motivo: {$motivo}. Reintegro de {$producto['cantidad']} unid. de producto ID #{$producto['id_producto']}";
                $stmtHist->bindParam(':detalle', $detalleHist);
                $stmtHist->bindParam(':id_usuario', $idUsuarioSesion, PDO::PARAM_INT);
                $stmtHist->execute();
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Genera los datos del recibo electrónico.
     */
    public function generarRecibo(int $idVenta): array
    {
        $query = "SELECT v.id_venta, v.fecha, v.empresa, v.total, v.valor_recibido, v.cambio,
                         v.pagado, v.numero_recibo, v.estado, c.nombre AS cliente, c.correo_electronico,
                         c.telefono AS cliente_telefono, c.documento AS cliente_documento,
                         m.nombre_metodo, u.nombre AS cajero_nombre, u.username AS cajero_usuario
                  FROM ventas v
                  LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                  LEFT JOIN metodos_pago m ON v.id_metodo_pago = m.id_metodo_pago
                  LEFT JOIN usuarios u ON v.id_usuario = u.id
                  WHERE v.id_venta = :id_venta";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_venta', $idVenta, PDO::PARAM_INT);
        $stmt->execute();
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        $queryDetalle = "SELECT p.nombre, dv.cantidad, dv.precio_unitario
                         FROM detalle_ventas dv
                         INNER JOIN productos p ON dv.id_producto = p.id_producto
                         WHERE dv.id_venta = :id_venta";
        $stmtDetalle = $this->db->prepare($queryDetalle);
        $stmtDetalle->bindParam(':id_venta', $idVenta, PDO::PARAM_INT);
        $stmtDetalle->execute();
        $detalle = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

        return [
            'venta' => $venta,
            'detalle' => $detalle,
        ];
    }

    /**
     * Alias de registro compatible con VentaController.
     */
    public function crear(
        array $items,
        int $idMetodoPago,
        float $valorRecibido,
        int $idUsuario,
        ?int $idCliente = null,
        ?string $empresa = null,
        string $pagado = 'si'
    ): int {
        $total = 0.0;
        foreach ($items as $item) {
            $total += ((int) $item['cantidad']) * ((float) $item['precio_unitario']);
        }
        return (int) $this->registrar($idUsuario, $items, $total, $valorRecibido, $idMetodoPago, $empresa, $idCliente, $pagado);
    }

    /**
     * Alias de cancelación compatible con VentaController.
     */
    public function anular(int $id, string $motivo, int $idUsuario = 0): bool
    {
        return $this->cancelar($id, $motivo);
    }
}