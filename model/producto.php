<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Connection.php';

/**
 * Modelo de inventario. Tabla: productos. (RF 3.1 – 3.5)
 */
class Producto
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    /** RF 3.1: registra un producto. Retorna el id o 0 si falla. */
    public function registrar(array $d): int
    {
        try {
            $codigo = trim((string) ($d['codigo_barras'] ?? ''));
            $stmt = $this->db->prepare(
                "INSERT INTO productos (codigo_barras, nombre, descripcion, categoria,
                    precio_compra, precio_venta, cantidad_stock, stock_minimo, id_proveedor, estado)
                 VALUES (:cb, :nombre, :descripcion, :categoria, :pc, :pv, :stock, :min, :prov, 'activo')"
            );
            $stmt->execute([
                ':cb' => $codigo === '' ? null : $codigo,
                ':nombre' => trim((string) ($d['nombre'] ?? '')),
                ':descripcion' => trim((string) ($d['descripcion'] ?? '')) ?: null,
                ':categoria' => trim((string) ($d['categoria'] ?? '')) ?: null,
                ':pc' => (float) ($d['precio_compra'] ?? 0),
                ':pv' => (float) ($d['precio_venta'] ?? 0),
                ':stock' => (int) ($d['cantidad_stock'] ?? 0),
                ':min' => (int) ($d['stock_minimo'] ?? 0),
                ':prov' => !empty($d['id_proveedor']) ? (int) $d['id_proveedor'] : null,
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function existeCodigoBarras(string $codigo, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM productos WHERE codigo_barras = :cb";
        $params = [':cb' => $codigo];
        if ($excluirId !== null) {
            $sql .= " AND id_producto != :excluir";
            $params[':excluir'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** RF 3.2: actualización completa con auditoría. */
    public function actualizar(int $id, array $d, int $idUsuario): bool
    {
        try {
            $codigo = trim((string) ($d['codigo_barras'] ?? ''));
            $stmt = $this->db->prepare(
                "UPDATE productos SET codigo_barras = :cb, nombre = :nombre, descripcion = :descripcion,
                    categoria = :categoria, precio_compra = :pc, precio_venta = :pv,
                    cantidad_stock = :stock, stock_minimo = :min, id_proveedor = :prov,
                    fecha_modificacion = NOW(), id_usuario_modificacion = :u
                 WHERE id_producto = :id"
            );
            return $stmt->execute([
                ':cb' => $codigo === '' ? null : $codigo,
                ':nombre' => trim((string) ($d['nombre'] ?? '')),
                ':descripcion' => trim((string) ($d['descripcion'] ?? '')) ?: null,
                ':categoria' => trim((string) ($d['categoria'] ?? '')) ?: null,
                ':pc' => (float) ($d['precio_compra'] ?? 0),
                ':pv' => (float) ($d['precio_venta'] ?? 0),
                ':stock' => (int) ($d['cantidad_stock'] ?? 0),
                ':min' => (int) ($d['stock_minimo'] ?? 0),
                ':prov' => !empty($d['id_proveedor']) ? (int) $d['id_proveedor'] : null,
                ':u' => $idUsuario,
                ':id' => $id,
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /** Suma (positivo) o resta (negativo) unidades al stock. */
    public function ajustarStock(int $id, int $cantidad): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE productos SET cantidad_stock = cantidad_stock + :cant
             WHERE id_producto = :id"
        );
        return $stmt->execute([':cant' => $cantidad, ':id' => $id]);
    }

    public function hayStock(int $id, int $cantidad): bool
    {
        $stmt = $this->db->prepare("SELECT cantidad_stock FROM productos WHERE id_producto = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row && (int) $row['cantidad_stock'] >= $cantidad;
    }

    /** RF 3.3: borrado lógico (activo/inactivo). */
    public function cambiarEstado(int $id, string $estado, int $idUsuario): bool
    {
        $estado = $estado === 'inactivo' ? 'inactivo' : 'activo';
        $stmt = $this->db->prepare(
            "UPDATE productos SET estado = :estado, fecha_modificacion = NOW(),
                id_usuario_modificacion = :u WHERE id_producto = :id"
        );
        return $stmt->execute([':estado' => $estado, ':u' => $idUsuario, ':id' => $id]);
    }

    /** RF 3.4: listado con bandera de alerta y búsqueda opcional. */
    public function listar(?string $estado = 'activo', string $q = ''): array
    {
        $sql = "SELECT p.*, pr.nombre_razon_social AS proveedor,
                    CASE WHEN p.cantidad_stock <= p.stock_minimo THEN 1 ELSE 0 END AS alerta_stock_bajo
                FROM productos p
                LEFT JOIN proveedores pr ON pr.id_proveedor = p.id_proveedor
                WHERE 1 = 1";
        $params = [];
        if ($estado !== null) {
            $sql .= " AND p.estado = :estado";
            $params[':estado'] = $estado;
        }
        if (trim($q) !== '') {
            // Cada aparición de :q necesita su propio nombre de marcador,
            // porque PDO no permite repetir el mismo parámetro en modo nativo.
            // Se usa LOWER() en ambos lados para que la búsqueda no distinga
            // mayúsculas de minúsculas, sin depender de la collation de la BD.
            $sql .= " AND (LOWER(p.nombre) LIKE LOWER(:q1) OR LOWER(p.codigo_barras) LIKE LOWER(:q2) OR LOWER(p.categoria) LIKE LOWER(:q3))";
            $like = '%' . trim($q) . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }
        $sql .= " ORDER BY p.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM productos WHERE id_producto = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function obtenerPorCodigoBarras(string $codigo)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM productos WHERE codigo_barras = :cb AND estado = 'activo' LIMIT 1"
        );
        $stmt->execute([':cb' => $codigo]);
        return $stmt->fetch();
    }

    /** RF 3.5: productos con stock igual o bajo el mínimo. */
    public function conStockBajo(): array
    {
        return $this->db->query(
            "SELECT id_producto, codigo_barras, nombre, categoria, cantidad_stock, stock_minimo
             FROM productos WHERE estado = 'activo' AND cantidad_stock <= stock_minimo
             ORDER BY cantidad_stock ASC"
        )->fetchAll();
    }

    public function contarActivos(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM productos WHERE estado = 'activo'"
        )->fetchColumn();
    }

    public function valorInventario(): float
    {
        return (float) $this->db->query(
            "SELECT COALESCE(SUM(cantidad_stock * precio_compra), 0)
             FROM productos WHERE estado = 'activo'"
        )->fetchColumn();
    }
}