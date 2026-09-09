<?php

require_once __DIR__ . '/conexion.php';

class Producto
{
    private PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /* =========================================================
     * RF 3.1 - Registrar nuevo producto
     * ========================================================= */

    /**
     * Registra un nuevo producto en el inventario.
     *
     * @param array $datos Debe incluir: codigo_barras, nombre, descripcion,
     *                      categoria, precio_compra, precio_venta,
     *                      cantidad_stock, stock_minimo, id_proveedor
     * @return int Id del producto insertado.
     */
    public function registrarProducto(array $datos): int
    {
        $sql = "INSERT INTO PRODUCTOS (
                    codigo_barras, nombre, descripcion, categoria,
                    precio_compra, precio_venta, cantidad_stock, stock_minimo,
                    id_proveedor, fecha_registro, estado
                ) VALUES (
                    :codigoBarras, :nombre, :descripcion, :categoria,
                    :precioCompra, :precioVenta, :cantidadStock, :stockMinimo,
                    :idProveedor, NOW(), 'activo'
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':codigoBarras'  => $datos['codigo_barras'],
            ':nombre'        => $datos['nombre'],
            ':descripcion'   => $datos['descripcion'],
            ':categoria'     => $datos['categoria'],
            ':precioCompra'  => $datos['precio_compra'],
            ':precioVenta'   => $datos['precio_venta'],
            ':cantidadStock' => $datos['cantidad_stock'],
            ':stockMinimo'   => $datos['stock_minimo'],
            ':idProveedor'   => $datos['id_proveedor'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Verifica si un código de barras ya existe (para evitar duplicados
     * al registrar o modificar un producto, según RI 3.2).
     */
    public function existeCodigoBarras(string $codigoBarras, ?int $idProductoExcluir = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM PRODUCTOS WHERE codigo_barras = :codigoBarras";
        $params = [':codigoBarras' => $codigoBarras];

        if ($idProductoExcluir !== null) {
            $sql .= " AND id_producto != :idProductoExcluir";
            $params[':idProductoExcluir'] = $idProductoExcluir;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $resultado['total'] > 0;
    }

    /* =========================================================
     * RF 3.2 - Modificar producto registrado
     * ========================================================= */

    /**
     * Actualiza precio de compra, precio de venta, estado y descripción
     * de un producto, registrando fecha de modificación y usuario responsable.
     * El código de barras NO se modifica (RI 3.2).
     */
    public function actualizarProducto(
        int $idProducto,
        float $precioCompra,
        float $precioVenta,
        string $estado,
        string $descripcion,
        int $idUsuarioModificacion
    ): bool {
        $sql = "UPDATE PRODUCTOS
                SET precio_compra = :precioCompra,
                    precio_venta = :precioVenta,
                    estado = :estado,
                    descripcion = :descripcion,
                    fecha_modificacion = NOW(),
                    id_usuario_modificacion = :idUsuarioModificacion
                WHERE id_producto = :idProducto";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':precioCompra'           => $precioCompra,
            ':precioVenta'            => $precioVenta,
            ':estado'                 => $estado,
            ':descripcion'            => $descripcion,
            ':idUsuarioModificacion'  => $idUsuarioModificacion,
            ':idProducto'             => $idProducto,
        ]);
    }

    /**
     * Actualiza únicamente la cantidad en stock (usado por Ventas y Compras
     * para descontar/reintegrar unidades - RF 5.2, RF 5.5 y RF 4.1).
     *
     * @param int $idProducto
     * @param int $cantidad   Cantidad a sumar (positivo) o restar (negativo).
     */
    public function ajustarStock(int $idProducto, int $cantidad): bool
    {
        $sql = "UPDATE PRODUCTOS
                SET cantidad_stock = cantidad_stock + :cantidad
                WHERE id_producto = :idProducto";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':cantidad'   => $cantidad,
            ':idProducto' => $idProducto,
        ]);
    }

    /* =========================================================
     * RF 3.3 - Descontinuar producto (eliminación lógica)
     * ========================================================= */

    /**
     * Descontinúa un producto (estado = inactivo), registrando el usuario
     * y la fecha de la acción, conservando el resto de su información
     * para trazabilidad e informes históricos.
     */
    public function descontinuarProducto(int $idProducto, int $idUsuarioResponsable): bool
    {
        $sql = "UPDATE PRODUCTOS
                SET estado = 'inactivo',
                    fecha_modificacion = NOW(),
                    id_usuario_modificacion = :idUsuarioResponsable
                WHERE id_producto = :idProducto";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':idUsuarioResponsable' => $idUsuarioResponsable,
            ':idProducto'           => $idProducto,
        ]);
    }

    /* =========================================================
     * RF 3.4 - Listado de productos y cantidad disponible
     * ========================================================= */

    /**
     * Lista todos los productos con su información básica, incluyendo
     * una bandera de alerta cuando el stock esté por debajo del mínimo.
     *
     * @param string|null $estado Filtra por 'activo' o 'inactivo'; null = todos.
     */
    public function listarProductos(?string $estado = 'activo'): array
    {
        $sql = "SELECT
                    id_producto, codigo_barras, nombre, categoria,
                    precio_venta, precio_compra, cantidad_stock, stock_minimo, estado,
                    CASE WHEN cantidad_stock <= stock_minimo THEN 1 ELSE 0 END AS alerta_stock_bajo
                FROM PRODUCTOS";

        $params = [];
        if ($estado !== null) {
            $sql .= " WHERE estado = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY nombre ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un producto por su código de barras (usado en el escaneo de Ventas).
     */
    public function obtenerPorCodigoBarras(string $codigoBarras): ?array
    {
        $sql = "SELECT * FROM PRODUCTOS WHERE codigo_barras = :codigoBarras AND estado = 'activo'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':codigoBarras' => $codigoBarras]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Búsqueda manual de productos por nombre parcial (RF 5.1).
     */
    public function buscarPorNombre(string $texto): array
    {
        $sql = "SELECT * FROM PRODUCTOS
                WHERE nombre LIKE :texto AND estado = 'activo'
                ORDER BY nombre ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':texto' => '%' . $texto . '%']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================================================
     * RF 3.5 - Alerta de stock agotándose
     * ========================================================= */

    /**
     * Devuelve los productos activos cuya cantidad en stock es igual o
     * inferior al mínimo definido por el administrador.
     */
    public function productosConStockBajo(): array
    {
        $sql = "SELECT id_producto, codigo_barras, nombre, categoria, cantidad_stock, stock_minimo
                FROM PRODUCTOS
                WHERE estado = 'activo'
                  AND cantidad_stock <= stock_minimo
                ORDER BY cantidad_stock ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
