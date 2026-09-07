<?php
// model/venta.php
require_once __DIR__ . '/../config/conexion.php';

class Venta
{
    private $db;

    public function __construct()
    {
        $this->db = (new Conexion())->conn;
    }

    // Registrar una nueva venta (RF 5.1, RF 5.2, RF 5.3)
    public function registrar($productos, $tipo_pago, $empresa = null, $valor_recibido, $id_cliente = null)
    {
        try {
            $this->db->beginTransaction();

            // Insertar venta
            $queryVenta = "INSERT INTO ventas (fecha, tipo_pago, empresa, valor_recibido, id_cliente) 
                           VALUES (NOW(), :tipo_pago, :empresa, :valor_recibido, :id_cliente)";
            $stmtVenta = $this->db->prepare($queryVenta);
            $stmtVenta->bindParam(":tipo_pago", $tipo_pago);
            $stmtVenta->bindParam(":empresa", $empresa);
            $stmtVenta->bindParam(":valor_recibido", $valor_recibido);
            $stmtVenta->bindParam(":id_cliente", $id_cliente);
            $stmtVenta->execute();
            $idVenta = $this->db->lastInsertId();

            // Insertar detalle de productos y actualizar stock
            foreach ($productos as $producto) {
                $queryDetalle = "INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario) 
                                 VALUES (:id_venta, :id_producto, :cantidad, :precio_unitario)";
                $stmtDetalle = $this->db->prepare($queryDetalle);
                $stmtDetalle->bindParam(":id_venta", $idVenta);
                $stmtDetalle->bindParam(":id_producto", $producto['id_producto']);
                $stmtDetalle->bindParam(":cantidad", $producto['cantidad']);
                $stmtDetalle->bindParam(":precio_unitario", $producto['precio_unitario']);
                $stmtDetalle->execute();

                // Actualizar stock
                $queryStock = "UPDATE productos SET cantidad = cantidad - :cantidad WHERE id = :id_producto";
                $stmtStock = $this->db->prepare($queryStock);
                $stmtStock->bindParam(":cantidad", $producto['cantidad']);
                $stmtStock->bindParam(":id_producto", $producto['id_producto']);
                $stmtStock->execute();
            }

            $this->db->commit();
            return $idVenta;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Obtener todas las ventas (RF 5.4, RF 5.5, RF 5.6)
    public function obtenerTodas()
    {
        $query = "SELECT v.id, v.fecha, v.tipo_pago, v.empresa, v.valor_recibido, c.nombre as cliente
                  FROM ventas v
                  LEFT JOIN clientes c ON v.id_cliente = c.id
                  ORDER BY v.id DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener venta por ID
    public function obtenerPorId($id)
    {
        $query = "SELECT * FROM ventas WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cancelar una venta (RF 5.5)
    public function cancelar($id, $motivo)
    {
        try {
            $this->db->beginTransaction();

            // Registrar motivo de cancelación
            $query = "UPDATE ventas SET estado = 'anulada', motivo = :motivo WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":motivo", $motivo);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            // Reintegrar productos al stock
            $queryDetalle = "SELECT id_producto, cantidad FROM detalle_ventas WHERE id_venta = :id_venta";
            $stmtDetalle = $this->db->prepare($queryDetalle);
            $stmtDetalle->bindParam(":id_venta", $id);
            $stmtDetalle->execute();
            $productos = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

            foreach ($productos as $producto) {
                $queryStock = "UPDATE productos SET cantidad = cantidad + :cantidad WHERE id = :id_producto";
                $stmtStock = $this->db->prepare($queryStock);
                $stmtStock->bindParam(":cantidad", $producto['cantidad']);
                $stmtStock->bindParam(":id_producto", $producto['id_producto']);
                $stmtStock->execute();
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Generar recibo electrónico (RF 5.6)
    public function generarRecibo($idVenta)
    {
        $query = "SELECT v.id, v.fecha, v.tipo_pago, v.empresa, v.valor_recibido, c.nombre as cliente, c.correo_electronico
                  FROM ventas v
                  LEFT JOIN clientes c ON v.id_cliente = c.id
                  WHERE v.id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $idVenta);
        $stmt->execute();
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        $queryDetalle = "SELECT p.nombre, dv.cantidad, dv.precio_unitario
                         FROM detalle_ventas dv
                         INNER JOIN productos p ON dv.id_producto = p.id
                         WHERE dv.id_venta = :id_venta";
        $stmtDetalle = $this->db->prepare($queryDetalle);
        $stmtDetalle->bindParam(":id_venta", $idVenta);
        $stmtDetalle->execute();
        $detalle = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

        return [
            "venta" => $venta,
            "detalle" => $detalle
        ];
    }
}
