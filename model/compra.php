<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Connection.php';

/**
 * Modelo de compras. Tablas: compras, detalle_compras. (RF 4.1, 4.3, 4.4)
 */
class Compra
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    /**
     * RF 4.1: registra una compra y aumenta el stock (transacción).
     * $items: [['id_producto'=>int,'cantidad'=>int,'precio_unitario_compra'=>float], ...]
     * Retorna el id_compra o 0 si falla.
     */
    public function crear(
        int $idProveedor,
        int $idUsuario,
        array $items,
        ?int $idMetodoPago = null
    ): int {
        if (empty($items)) {
            return 0;
        }
        try {
            $this->db->beginTransaction();

            $total = 0.0;
            foreach ($items as $it) {
                $cant = (int) $it['cantidad'];
                $precio = (float) $it['precio_unitario_compra'];
                if ($cant <= 0 || $precio < 0) {
                    throw new RuntimeException('Cantidades o precios inválidos.');
                }
                $total += $cant * $precio;
            }

            $stmt = $this->db->prepare(
                "INSERT INTO compras (id_proveedor, id_usuario, id_metodo_pago, estado, total_compra)
                 VALUES (:prov, :u, :mp, 'registrada', :total)"
            );
            $stmt->execute([
                ':prov' => $idProveedor,
                ':u' => $idUsuario,
                ':mp' => $idMetodoPago,
                ':total' => $total,
            ]);
            $idCompra = (int) $this->db->lastInsertId();

            $stmtDet = $this->db->prepare(
                "INSERT INTO detalle_compras (id_compra, id_producto, cantidad, precio_unitario_compra)
                 VALUES (:c, :p, :cant, :precio)"
            );
            $stmtAntes = $this->db->prepare(
                "SELECT cantidad_stock FROM productos WHERE id_producto = :p FOR UPDATE"
            );
            $stmtStock = $this->db->prepare(
                "UPDATE productos SET cantidad_stock = cantidad_stock + :cant WHERE id_producto = :p"
            );
            // RF 5.2: kardex de entradas vinculadas a la compra
            $stmtMov = $this->db->prepare(
                "INSERT INTO movimientos_inventario
                 (id_producto, tipo, cantidad, stock_antes, stock_despues, id_compra, id_usuario)
                 VALUES (:p, 'entrada', :cant, :antes, :despues, :c, :u)"
            );
            foreach ($items as $it) {
                $idProd = (int) $it['id_producto'];
                $cant = (int) $it['cantidad'];
                $stmtDet->execute([
                    ':c' => $idCompra,
                    ':p' => $idProd,
                    ':cant' => $cant,
                    ':precio' => (float) $it['precio_unitario_compra'],
                ]);
                $stmtAntes->execute([':p' => $idProd]);
                $prow = $stmtAntes->fetch();
                $antes = $prow ? (int) $prow['cantidad_stock'] : 0;
                $stmtStock->execute([':cant' => $cant, ':p' => $idProd]);
                $stmtMov->execute([
                    ':p' => $idProd, ':cant' => $cant,
                    ':antes' => $antes, ':despues' => $antes + $cant,
                    ':c' => $idCompra, ':u' => $idUsuario,
                ]);
            }

            $this->db->commit();
            return $idCompra;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return 0;
        }
    }

    /** RF 4.3: historial con proveedor y usuario. */
    public function obtenerTodas(int $limite = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, p.nombre_razon_social AS proveedor, u.username AS usuario
             FROM compras c
             INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
             INNER JOIN usuarios u ON u.id = c.id_usuario
             ORDER BY c.id_compra DESC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id)
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, p.nombre_razon_social AS proveedor, u.username AS usuario
             FROM compras c
             INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
             INNER JOIN usuarios u ON u.id = c.id_usuario
             WHERE c.id_compra = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function obtenerDetalle(int $idCompra): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, p.nombre AS nombre_producto,
                    (d.cantidad * d.precio_unitario_compra) AS subtotal
             FROM detalle_compras d
             INNER JOIN productos p ON p.id_producto = d.id_producto
             WHERE d.id_compra = :c ORDER BY p.nombre ASC"
        );
        $stmt->execute([':c' => $idCompra]);
        return $stmt->fetchAll();
    }

    public function obtenerPorProveedor(int $idProveedor): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM compras WHERE id_proveedor = :p ORDER BY fecha_hora DESC"
        );
        $stmt->execute([':p' => $idProveedor]);
        return $stmt->fetchAll();
    }

    /**
     * RF 4.4: anula una compra y revierte el stock que había aumentado.
     */
    public function anular(int $idCompra, ?int $idUsuario = null): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT estado FROM compras WHERE id_compra = :c LIMIT 1");
            $stmt->execute([':c' => $idCompra]);
            $row = $stmt->fetch();
            if (!$row || $row['estado'] !== 'registrada') {
                $this->db->rollBack();
                return false;
            }

            // Validar que haya stock suficiente para revertir
            $det = $this->obtenerDetalle($idCompra);
            foreach ($det as $d) {
                $stmt = $this->db->prepare(
                    "SELECT cantidad_stock FROM productos WHERE id_producto = :p FOR UPDATE"
                );
                $stmt->execute([':p' => (int) $d['id_producto']]);
                $prod = $stmt->fetch();
                if (!$prod || (int) $prod['cantidad_stock'] < (int) $d['cantidad']) {
                    $this->db->rollBack();
                    return false;
                }
            }

            $stmt = $this->db->prepare("UPDATE compras SET estado = 'anulada' WHERE id_compra = :c");
            $stmt->execute([':c' => $idCompra]);

            $stmtAntes = $this->db->prepare(
                "SELECT cantidad_stock FROM productos WHERE id_producto = :p"
            );
            $stmtStock = $this->db->prepare(
                "UPDATE productos SET cantidad_stock = cantidad_stock - :cant WHERE id_producto = :p"
            );
            $stmtMov = $this->db->prepare(
                "INSERT INTO movimientos_inventario
                 (id_producto, tipo, cantidad, stock_antes, stock_despues, id_compra, motivo, id_usuario)
                 VALUES (:p, 'salida', :cant, :antes, :despues, :c, :motivo, :u)"
            );
            foreach ($det as $d) {
                $idProd = (int) $d['id_producto'];
                $cant = (int) $d['cantidad'];
                $stmtAntes->execute([':p' => $idProd]);
                $prow = $stmtAntes->fetch();
                $antes = $prow ? (int) $prow['cantidad_stock'] : 0;
                $stmtStock->execute([':cant' => $cant, ':p' => $idProd]);
                $stmtMov->execute([
                    ':p' => $idProd, ':cant' => $cant,
                    ':antes' => $antes, ':despues' => $antes - $cant,
                    ':c' => $idCompra,
                    ':motivo' => 'Anulación compra #' . $idCompra,
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

    /**
     * RF 4.4: edita una compra REGISTRADA (proveedor, método y líneas).
     * $lineas: [['id_producto'=>int,'cantidad'=>int,'precio_unitario_compra'=>float], ...]
     * Cantidad 0 = quitar línea. Ajusta stock por diferencia y escribe kardex.
     * Retorna ['ok'=>bool,'error'=>?string].
     */
    public function actualizar(
        int $idCompra,
        int $idProveedor,
        array $lineas,
        ?int $idMetodoPago,
        ?int $idUsuario = null
    ): array {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT estado FROM compras WHERE id_compra = :c LIMIT 1 FOR UPDATE");
            $stmt->execute([':c' => $idCompra]);
            $cab = $stmt->fetch();
            if (!$cab) {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'Compra no encontrada.'];
            }
            if ($cab['estado'] !== 'registrada') {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'Solo se pueden editar compras registradas (esta está anulada).'];
            }

            $stmt = $this->db->prepare("SELECT estado FROM proveedores WHERE id_proveedor = :p LIMIT 1");
            $stmt->execute([':p' => $idProveedor]);
            $prov = $stmt->fetch();
            if (!$prov || $prov['estado'] !== 'activo') {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'Selecciona un proveedor activo.'];
            }

            // Normalizar líneas (0 = quitar)
            $nuevas = [];
            foreach ($lineas as $ln) {
                $idProd = (int) ($ln['id_producto'] ?? 0);
                $cant = (int) ($ln['cantidad'] ?? 0);
                $precio = (float) ($ln['precio_unitario_compra'] ?? 0);
                if ($idProd <= 0 || $cant < 0 || $precio < 0) {
                    $this->db->rollBack();
                    return ['ok' => false, 'error' => 'Líneas inválidas (cantidades y precios no pueden ser negativos).'];
                }
                if ($cant === 0) {
                    continue;
                }
                if (isset($nuevas[$idProd])) {
                    $nuevas[$idProd]['cantidad'] += $cant;
                } else {
                    $nuevas[$idProd] = ['cantidad' => $cant, 'precio_unitario_compra' => $precio];
                }
            }
            if (empty($nuevas)) {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'La compra debe conservar al menos una línea.'];
            }

            $detActual = $this->obtenerDetalle($idCompra);
            $viejas = [];
            foreach ($detActual as $d) {
                $viejas[(int) $d['id_producto']] = (int) $d['cantidad'];
            }

            // Validar productos y stock para reducciones netas
            $stmtProd = $this->db->prepare("SELECT cantidad_stock FROM productos WHERE id_producto = :p FOR UPDATE");
            $stocks = [];
            $todos = array_unique(array_merge(array_keys($viejas), array_keys($nuevas)));
            foreach ($todos as $idProd) {
                $stmtProd->execute([':p' => $idProd]);
                $prow = $stmtProd->fetch();
                if (!$prow) {
                    $this->db->rollBack();
                    return ['ok' => false, 'error' => "Producto #$idProd no existe."];
                }
                $stocks[$idProd] = (int) $prow['cantidad_stock'];
            }
            foreach ($todos as $idProd) {
                $diff = ($nuevas[$idProd]['cantidad'] ?? 0) - ($viejas[$idProd] ?? 0);
                if ($diff < 0 && $stocks[$idProd] < abs($diff)) {
                    $this->db->rollBack();
                    return ['ok' => false, 'error' => "Stock insuficiente para reducir el producto #$idProd (disponible {$stocks[$idProd]})."];
                }
            }

            // Aplicar: reemplazar detalle, ajustar stock, recalcular total
            $stmtDel = $this->db->prepare("DELETE FROM detalle_compras WHERE id_compra = :c");
            $stmtDel->execute([':c' => $idCompra]);
            $stmtIns = $this->db->prepare(
                "INSERT INTO detalle_compras (id_compra, id_producto, cantidad, precio_unitario_compra)
                 VALUES (:c, :p, :cant, :precio)"
            );
            $stmtStock = $this->db->prepare(
                "UPDATE productos SET cantidad_stock = cantidad_stock + :dif WHERE id_producto = :p"
            );
            $stmtMov = $this->db->prepare(
                "INSERT INTO movimientos_inventario
                 (id_producto, tipo, cantidad, stock_antes, stock_despues, id_compra, motivo, id_usuario)
                 VALUES (:p, :tipo, :cant, :antes, :despues, :c, :motivo, :u)"
            );
            $total = 0.0;
            foreach ($nuevas as $idProd => $ln) {
                $stmtIns->execute([
                    ':c' => $idCompra, ':p' => $idProd,
                    ':cant' => $ln['cantidad'], ':precio' => $ln['precio_unitario_compra'],
                ]);
                $total += $ln['cantidad'] * $ln['precio_unitario_compra'];
            }
            foreach ($todos as $idProd) {
                $diff = ($nuevas[$idProd]['cantidad'] ?? 0) - ($viejas[$idProd] ?? 0);
                if ($diff === 0) {
                    continue;
                }
                $antes = $stocks[$idProd];
                $stmtStock->execute([':dif' => $diff, ':p' => $idProd]);
                $stmtMov->execute([
                    ':p' => $idProd,
                    ':tipo' => $diff > 0 ? 'entrada' : 'salida',
                    ':cant' => abs($diff),
                    ':antes' => $antes, ':despues' => $antes + $diff,
                    ':c' => $idCompra,
                    ':motivo' => 'Ajuste edición compra #' . $idCompra,
                    ':u' => $idUsuario,
                ]);
            }

            $stmt = $this->db->prepare(
                "UPDATE compras SET id_proveedor = :prov, id_metodo_pago = :mp, total_compra = :total
                 WHERE id_compra = :c"
            );
            $stmt->execute([':prov' => $idProveedor, ':mp' => $idMetodoPago, ':total' => $total, ':c' => $idCompra]);

            $this->db->commit();
            return ['ok' => true, 'error' => null];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'error' => 'No se pudo actualizar la compra.'];
        }
    }

    public function contarCompras(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM compras")->fetchColumn();
    }
}
