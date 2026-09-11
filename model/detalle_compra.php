<?php
// model/detalle_compra.php
require_once __DIR__ . '/../config/connection.php';

class DetalleCompra
{
    /** @var PDO */
    private PDO $conn;
    private string $tabla = 'detalle_compra';

    public function __construct()
    {
        $this->conn = (new Connection())->conn;
    }

    /**
     * Agrega un producto (línea de detalle) a una compra (RF 4.1).
     */
    public function agregarDetalle(int $id_compra, string $codigo_barras, int $cantidad_producto, float $precio_unitario): bool
    {
        $sql = "INSERT INTO {$this->tabla} (id_compra, codigo_barras, cantidad_producto, precio_unitario)
                VALUES (:id_compra, :codigo_barras, :cantidad_producto, :precio_unitario)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $stmt->bindParam(':codigo_barras', $codigo_barras);
        $stmt->bindParam(':cantidad_producto', $cantidad_producto, PDO::PARAM_INT);
        $stmt->bindParam(':precio_unitario', $precio_unitario);

        return $stmt->execute();
    }

    /**
     * Agrega varias líneas de una vez (útil al registrar la compra completa).
     *
     * @param array $items Cada item: ['codigo_barras' => ..., 'cantidad_producto' => ..., 'precio_unitario' => ...]
     */
    public function agregarDetalles(int $id_compra, array $items): bool
    {
        $sql = "INSERT INTO {$this->tabla} (id_compra, codigo_barras, cantidad_producto, precio_unitario)
                VALUES (:id_compra, :codigo_barras, :cantidad_producto, :precio_unitario)";
        $stmt = $this->conn->prepare($sql);

        foreach ($items as $item) {
            $ok = $stmt->execute([
                ':id_compra'         => $id_compra,
                ':codigo_barras'     => $item['codigo_barras'],
                ':cantidad_producto' => $item['cantidad_producto'],
                ':precio_unitario'   => $item['precio_unitario'],
            ]);
            if (!$ok) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtiene todas las líneas de detalle de una compra, con el nombre
     * del producto (asume tabla `productos` con columna `nombre_producto`
     * relacionada por `codigo_barras` — ajustar si el nombre real difiere).
     */
    public function obtenerPorCompra(int $id_compra): array
    {
        $sql = "SELECT dc.id_compra, dc.codigo_barras, dc.cantidad_producto, dc.precio_unitario,
                       (dc.cantidad_producto * dc.precio_unitario) AS subtotal,
                       p.nombre_producto
                FROM {$this->tabla} dc
                LEFT JOIN productos p ON p.codigo_barras = dc.codigo_barras
                WHERE dc.id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcula el total de una compra a partir de sus líneas de detalle.
     * Útil para validar/recalcular `compra.total_compra`.
     */
    public function calcularTotalCompra(int $id_compra): float
    {
        $sql = "SELECT COALESCE(SUM(cantidad_producto * precio_unitario), 0) AS total
                FROM {$this->tabla} WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) $resultado['total'];
    }

    /**
     * Actualiza cantidad y/o precio de una línea de detalle específica
     * (usado en RF 4.4 — modificación de compras).
     */
    public function actualizarDetalle(int $id_compra, string $codigo_barras, int $cantidad_producto, float $precio_unitario): bool
    {
        $sql = "UPDATE {$this->tabla}
                SET cantidad_producto = :cantidad_producto, precio_unitario = :precio_unitario
                WHERE id_compra = :id_compra AND codigo_barras = :codigo_barras";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cantidad_producto', $cantidad_producto, PDO::PARAM_INT);
        $stmt->bindParam(':precio_unitario', $precio_unitario);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $stmt->bindParam(':codigo_barras', $codigo_barras);

        return $stmt->execute();
    }

    /**
     * Elimina una línea de detalle puntual.
     */
    public function eliminarDetalle(int $id_compra, string $codigo_barras): bool
    {
        $sql = "DELETE FROM {$this->tabla} WHERE id_compra = :id_compra AND codigo_barras = :codigo_barras";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $stmt->bindParam(':codigo_barras', $codigo_barras);

        return $stmt->execute();
    }

    /**
     * Elimina todas las líneas de una compra (por ejemplo, antes de
     * reemplazarlas por completo en una edición).
     */
    public function eliminarPorCompra(int $id_compra): bool
    {
        $sql = "DELETE FROM {$this->tabla} WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);

        return $stmt->execute();
    }
}