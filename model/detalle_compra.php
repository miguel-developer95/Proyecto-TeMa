<?php
// model/detalle_compra.php
require_once __DIR__ . '/../config/connection.php';

class DetalleCompra
{
    /** @var PDO */
    private PDO $conn;
    private string $tabla = 'detalle_compras';

    public function __construct()
    {
        $this->conn = (new Connection())->conn;
    }

    /**
     * Resuelve el id_producto a partir del código de barras o del id provisto.
     */
    public function resolverIdProducto(string|int $identificador): ?int
    {
        // 1. Buscar por codigo_barras
        $stmt = $this->conn->prepare("SELECT id_producto FROM productos WHERE codigo_barras = :codigo LIMIT 1");
        $stmt->execute([':codigo' => (string) $identificador]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return (int) $row['id_producto'];
        }

        // 2. Si no encontró por código, intentar por id_producto directo si es numérico
        if (is_numeric($identificador)) {
            $stmt = $this->conn->prepare("SELECT id_producto FROM productos WHERE id_producto = :id LIMIT 1");
            $stmt->execute([':id' => (int) $identificador]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return (int) $row['id_producto'];
            }
        }

        return null;
    }

    /**
     * Agrega un producto (línea de detalle) a una compra (RF 4.1).
     */
    public function agregarDetalle(int $id_compra, string|int $codigo_o_id, int $cantidad, float $precio_unitario): bool
    {
        $id_producto = $this->resolverIdProducto($codigo_o_id);
        if (!$id_producto) {
            return false;
        }

        $sql = "INSERT INTO {$this->tabla} (id_compra, id_producto, cantidad, precio_unitario_compra)
                VALUES (:id_compra, :id_producto, :cantidad, :precio_unitario)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_compra', $id_compra, PDO::PARAM_INT);
        $stmt->bindValue(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmt->bindValue(':precio_unitario', $precio_unitario);

        $ok = $stmt->execute();
        if ($ok) {
            // Actualizar stock y precio de compra en inventario
            $upd = $this->conn->prepare(
                "UPDATE productos
                 SET cantidad_stock = cantidad_stock + :cantidad,
                     precio_compra = :precio_compra
                 WHERE id_producto = :id_producto"
            );
            $upd->execute([
                ':cantidad'      => $cantidad,
                ':precio_compra' => $precio_unitario,
                ':id_producto'   => $id_producto,
            ]);
        }

        return $ok;
    }

    /**
     * Agrega varias líneas de una vez al registrar una compra.
     *
     * @param array $items Cada item: ['codigo_barras' => ..., 'cantidad_producto' => ..., 'precio_unitario' => ...]
     */
    public function agregarDetalles(int $id_compra, array $items): bool
    {
        foreach ($items as $item) {
            $codigo = $item['codigo_barras'] ?? $item['id_producto'] ?? '';
            $cantidad = (int) ($item['cantidad_producto'] ?? $item['cantidad'] ?? 0);
            $precio = (float) ($item['precio_unitario'] ?? $item['precio_unitario_compra'] ?? 0);

            if ($cantidad <= 0 || empty($codigo)) {
                continue;
            }

            if (!$this->agregarDetalle($id_compra, $codigo, $cantidad, $precio)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtiene todas las líneas de detalle de una compra.
     */
    public function obtenerPorCompra(int $id_compra): array
    {
        $sql = "SELECT dc.id_compra, dc.id_producto, dc.cantidad, dc.cantidad AS cantidad_producto,
                       dc.precio_unitario_compra, dc.precio_unitario_compra AS precio_unitario,
                       (dc.cantidad * dc.precio_unitario_compra) AS subtotal,
                       p.codigo_barras, p.nombre AS nombre_producto
                FROM {$this->tabla} dc
                LEFT JOIN productos p ON p.id_producto = dc.id_producto
                WHERE dc.id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_compra', $id_compra, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcula el total de una compra a partir de sus líneas de detalle.
     */
    public function calcularTotalCompra(int $id_compra): float
    {
        $sql = "SELECT COALESCE(SUM(cantidad * precio_unitario_compra), 0) AS total
                FROM {$this->tabla} WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_compra', $id_compra, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) ($resultado['total'] ?? 0);
    }

    /**
     * Elimina las líneas de una compra.
     */
    public function eliminarPorCompra(int $id_compra): bool
    {
        $sql = "DELETE FROM {$this->tabla} WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_compra', $id_compra, PDO::PARAM_INT);

        return $stmt->execute();
    }
}