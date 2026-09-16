<?php
// model/producto.php
require_once __DIR__ . '/../config/connection.php';

class Producto
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    // Usado por pos.php: obtiene un producto por su id
    public function obtenerPorId(int $id_producto): array|false
    {
        $sql = "SELECT * FROM productos WHERE id_producto = :id_producto LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Usado por pos.php: busca productos activos por nombre o código de barras
    public function listar(string $estado = 'activo', string $busqueda = ''): array
    {
        $sql = "SELECT * FROM productos WHERE estado = :estado";
        $params = [':estado' => $estado];

        if ($busqueda !== '') {
            $sql .= " AND (nombre LIKE :busqueda OR codigo_barras LIKE :busqueda)";
            $params[':busqueda'] = '%' . $busqueda . '%';
        }

        $sql .= " ORDER BY nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Usado por producto.php: listado completo con alerta de stock bajo
    public function listarTodos(): array
    {
        $sql = "SELECT id_producto, codigo_barras, nombre, descripcion, categoria,
                       precio_compra, precio_venta, cantidad_stock, stock_minimo, estado,
                       (cantidad_stock <= stock_minimo) AS alerta_stock
                FROM productos
                ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // RF 3.1 & RI 3.1: Registrar nuevo producto
    public function registrar(array $datos): bool
    {
        $sql = "INSERT INTO productos
                (codigo_barras, nombre, descripcion, categoria, precio_compra, precio_venta, cantidad_stock, stock_minimo, id_proveedor, estado)
                VALUES (:codigo_barras, :nombre, :descripcion, :categoria, :precio_compra, :precio_venta, :cantidad_stock, :stock_minimo, :id_proveedor, 'activo')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($datos);
    }

    // RF 3.2 & RI 3.2: Modificar producto + Trazabilidad
    public function modificar(int $id_producto, float $precio_compra, float $precio_venta, string $estado, ?string $descripcion, int $id_usuario): bool
    {
        $this->db->beginTransaction();
        try {
            $sql = "UPDATE productos
                    SET precio_compra = :precio_compra,
                        precio_venta = :precio_venta,
                        estado = :estado,
                        descripcion = :descripcion,
                        fecha_modificacion = NOW(),
                        id_usuario_modificacion = :id_usuario
                    WHERE id_producto = :id_producto";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':precio_compra' => $precio_compra,
                ':precio_venta'  => $precio_venta,
                ':estado'        => $estado,
                ':descripcion'   => $descripcion,
                ':id_usuario'    => $id_usuario,
                ':id_producto'   => $id_producto,
            ]);

            $this->registrarAuditoria($id_producto, $id_usuario, 'MODIFICACION', 'Se actualizó precio de compra, precio de venta, estado o descripción.');

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // RF 3.3 & RI 3.3: Descontinuar producto (eliminación lógica)
    public function descontinuar(int $id_producto, int $id_usuario): bool
    {
        $this->db->beginTransaction();
        try {
            $sql = "UPDATE productos SET estado = 'inactivo' WHERE id_producto = :id_producto";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_producto' => $id_producto]);

            $this->registrarAuditoria($id_producto, $id_usuario, 'DESCONTINUACION', 'Producto marcado como inactivo (descontinuado).');

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function obtenerPorCodigoBarras(string $codigo): array|false
    {
        $sql = "SELECT * FROM productos WHERE codigo_barras = :codigo LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', $codigo);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function hayStock(int $id_producto, int $cantidad): bool
    {
        $p = $this->obtenerPorId($id_producto);
        if (!$p) return false;
        return ((int) $p['cantidad_stock']) >= $cantidad;
    }

    private function registrarAuditoria(int $id_producto, int $id_usuario, string $accion, string $detalles): void
    {
        try {
            $sql = "INSERT INTO historial (tipo_accion, detalle_cambio, id_usuario)
                    VALUES (:accion, :detalles, :id_usuario)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_usuario' => $id_usuario,
                ':accion'     => $accion,
                ':detalles'   => "Producto #$id_producto: $detalles",
            ]);
        } catch (\Throwable $e) {
            // Evita romper la transacción si la tabla historial varía
        }
    }
}

// Alias de clase para compatibilidad
class ProductoModel extends Producto {}