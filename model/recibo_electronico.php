<?php

require_once __DIR__ . '/conexion.php';

class ReciboElectronico
{
    private PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Genera el número de recibo correlativo siguiente.
     * Formato sugerido: REC-000001
     */
    public function generarNumeroRecibo(): string
    {
        $sql = "SELECT COUNT(*) AS total FROM VENTA WHERE numero_recibo IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $siguiente = ((int) $resultado['total']) + 1;
        return 'REC-' . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Asigna el número de recibo a una venta ya completada.
     */
    public function asignarNumeroReciboAVenta(int $idVenta, string $numeroRecibo): bool
    {
        $sql = "UPDATE VENTA SET numero_recibo = :numeroRecibo WHERE id_venta = :idVenta";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':numeroRecibo' => $numeroRecibo,
            ':idVenta'      => $idVenta,
        ]);
    }

    /**
     * Construye el recibo electrónico completo de una venta:
     * datos del cliente, productos vendidos, cantidades, precios,
     * valor total y número de recibo (RI 5.6).
     *
     * @param int $idVenta
     * @return array|null Estructura del recibo, o null si la venta no existe.
     */
    public function generarRecibo(int $idVenta): ?array
    {
        // Encabezado: datos de la venta y del cliente
        $sqlEncabezado = "SELECT
                    v.id_venta,
                    v.numero_recibo,
                    v.fecha,
                    v.total,
                    c.id_cliente,
                    c.nombre AS nombre_cliente,
                    c.correo_electronico AS correo_cliente,
                    mp.nombre_metodo,
                    mp.empresa AS empresa_pago
                FROM VENTA v
                LEFT JOIN CLIENTE c ON c.id_cliente = v.id_cliente
                LEFT JOIN METODOS_PAGO mp ON mp.id_metodo_pago = v.id_metodo_pago
                WHERE v.id_venta = :idVenta";

        $stmtEncabezado = $this->pdo->prepare($sqlEncabezado);
        $stmtEncabezado->execute([':idVenta' => $idVenta]);
        $encabezado = $stmtEncabezado->fetch(PDO::FETCH_ASSOC);

        if (!$encabezado) {
            return null;
        }

        // Detalle: productos vendidos, cantidades y precios
        $sqlDetalle = "SELECT
                    p.codigo_barras,
                    p.nombre AS nombre_producto,
                    dv.cantidad,
                    dv.precio_unitario,
                    (dv.cantidad * dv.precio_unitario) AS subtotal
                FROM DETALLE_VENTA dv
                INNER JOIN PRODUCTOS p ON p.id_producto = dv.id_producto
                WHERE dv.id_venta = :idVenta
                ORDER BY p.nombre ASC";

        $stmtDetalle = $this->pdo->prepare($sqlDetalle);
        $stmtDetalle->execute([':idVenta' => $idVenta]);
        $productos = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

        return [
            'numero_recibo'  => $encabezado['numero_recibo'],
            'fecha'          => $encabezado['fecha'],
            'cliente'        => [
                'id_cliente'         => $encabezado['id_cliente'],
                'nombre'             => $encabezado['nombre_cliente'],
                'correo_electronico' => $encabezado['correo_cliente'],
            ],
            'metodo_pago'    => [
                'nombre_metodo' => $encabezado['nombre_metodo'],
                'empresa'       => $encabezado['empresa_pago'],
            ],
            'productos'      => $productos,
            'valor_total'    => (float) $encabezado['total'],
        ];
    }

    /**
     * Obtiene un recibo ya generado a partir de su número de recibo
     * (por ejemplo, para reimprimirlo o consultarlo desde historial de ventas).
     */
    public function obtenerReciboPorNumero(string $numeroRecibo): ?array
    {
        $sql = "SELECT id_venta FROM VENTA WHERE numero_recibo = :numeroRecibo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':numeroRecibo' => $numeroRecibo]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            return null;
        }

        return $this->generarRecibo((int) $resultado['id_venta']);
    }
}