<?php
class ProductoModel {
    private $db;

    public function __construct($databaseConnection) {
        $this->db = $databaseConnection;
    }

    // RF 3.1 & RI 3.1: Registrar nuevo producto
    public function registrar($datos) {
        $sql = "INSERT INTO producto 
                (codigo_barras, nombre, descripcion, id_categoria, precio_compra, precio_venta, stock, stock_minimo, id_proveedor, estado) 
                VALUES (:codigo_barras, :nombre, :descripcion, :id_categoria, :precio_compra, :precio_venta, :stock, :stock_minimo, :id_proveedor, 'activo')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($datos);
    }

    // RF 3.2 & RI 3.2: Modificar producto + Trazabilidad
    public function modificar($id_producto, $precio_compra, $precio_venta, $estado, $descripcion, $id_usuario) {
        $this->db->beginTransaction();
        try {
            // Actualizar datos permitidos manteniendo código de barras intacto
            $sql = "UPDATE producto 
                    SET precio_compra = :precio_compra, 
                        precio_venta = :precio_venta, 
                        estado = :estado, 
                        descripcion = :descripcion 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':precio_compra' => $precio_compra,
                ':precio_venta'  => $precio_venta,
                ':estado'        => $estado,
                ':descripcion'   => $descripcion,
                ':id'            => $id_producto
            ]);

            // Auditoría
            $this->registrarAuditoria($id_producto, $id_usuario, 'MODIFICACION', "Se actualizó precio de compra, precio de venta, estado o descripción.");

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // RF 3.3 & RI 3.3: Descontinuar producto (Eliminación lógica)
    public function descontinuar($id_producto, $id_usuario) {
        $this->db->beginTransaction();
        try {
            $sql = "UPDATE producto SET estado = 'inactivo' WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id_producto]);

            // Auditoría del cambio de estado a inactivo
            $this->registrarAuditoria($id_producto, $id_usuario, 'DESCONTINUACION', "Producto marcado como inactivo (descontinuado).");

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // RF 3.4, RI 3.4 & RI 3.5: Listado con indicador de alerta de stock
    public function listarTodos() {
        $sql = "SELECT p.id, p.codigo_barras, p.nombre, p.precio_venta, p.precio_compra, 
                       p.stock, p.stock_minimo, p.estado, c.nombre AS categoria,
                       (p.stock <= p.stock_minimo) AS alerta_stock
                FROM producto p
                LEFT JOIN categoria c ON p.id_categoria = c.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function registrarAuditoria($id_producto, $id_usuario, $accion, $detalles) {
        $sql = "INSERT INTO historial_modificacion (id_producto, id_usuario, accion, detalles) 
                VALUES (:id_producto, :id_usuario, :accion, :detalles)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_producto' => $id_producto,
            ':id_usuario'  => $id_usuario,
            ':accion'      => $accion,
            ':detalles'    => $detalles
        ]);
    }
}