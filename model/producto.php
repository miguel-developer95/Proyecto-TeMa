<?php
// model/producto.php
require_once __DIR__ . '/../config/conexion.php';

class Producto
{
    private $db;

    public function __construct()
    {
        $this->db = (new Conexion())->conn;
    }

    // Registrar un nuevo producto (RF 3.1)
    public function registrar($codigo_barras, $nombre, $descripcion, $categoria, $precio_compra, $precio_venta, $cantidad, $proveedor, $estado = 'activo')
    {
        try {
            $query = "INSERT INTO productos 
                      (codigo_barras, nombre, descripcion, categoria, precio_compra, precio_venta, cantidad, proveedor, estado, fecha_registro) 
                      VALUES (:codigo_barras, :nombre, :descripcion, :categoria, :precio_compra, :precio_venta, :cantidad, :proveedor, :estado, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":codigo_barras", $codigo_barras);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":descripcion", $descripcion);
            $stmt->bindParam(":categoria", $categoria);
            $stmt->bindParam(":precio_compra", $precio_compra);
            $stmt->bindParam(":precio_venta", $precio_venta);
            $stmt->bindParam(":cantidad", $cantidad);
            $stmt->bindParam(":proveedor", $proveedor);
            $stmt->bindParam(":estado", $estado);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false; // Código duplicado
            }
            throw $e;
        }
    }

    // Obtener todos los productos (RF 3.4)
    public function obtenerTodos()
    {
        $query = "SELECT id, codigo_barras, nombre, descripcion, categoria, precio_compra, precio_venta, cantidad, proveedor, estado 
                  FROM productos ORDER BY id DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener producto por ID
    public function obtenerPorId($id)
    {
        $query = "SELECT * FROM productos WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar producto (RF 3.2)
    public function actualizar($id, $nombre, $descripcion, $categoria, $precio_compra, $precio_venta, $cantidad, $estado)
    {
        try {
            $query = "UPDATE productos 
                      SET nombre = :nombre, descripcion = :descripcion, categoria = :categoria, 
                          precio_compra = :precio_compra, precio_venta = :precio_venta, 
                          cantidad = :cantidad, estado = :estado, fecha_modificacion = NOW() 
                      WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":descripcion", $descripcion);
            $stmt->bindParam(":categoria", $categoria);
            $stmt->bindParam(":precio_compra", $precio_compra);
            $stmt->bindParam(":precio_venta", $precio_venta);
            $stmt->bindParam(":cantidad", $cantidad);
            $stmt->bindParam(":estado", $estado);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Descontinuar producto (RF 3.3)
    public function descontinuar($id)
    {
        $query = "UPDATE productos SET estado = 'inactivo', fecha_modificacion = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    // Eliminar producto (opcional)
    public function eliminar($id)
    {
        $query = "DELETE FROM productos WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    // Contar productos registrados
    public function contarProductos()
    {
        $query = "SELECT COUNT(*) as total FROM productos";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Avisar stock bajo (RF 3.5)
    public function productosConStockBajo($minimo)
    {
        $query = "SELECT id, nombre, cantidad FROM productos WHERE cantidad <= :minimo AND estado = 'activo'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":minimo", $minimo);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
