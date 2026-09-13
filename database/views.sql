-- =============================================================
-- Tentaciones Marlly · Vistas modernas de consulta (informes)
-- Reemplazan las tablas planas legacy_v1_*/v2_*/v3_* (snapshots viejos
-- con columnas que ya no existen). Idempotente: CREATE OR REPLACE.
-- Aplicar: C:\xampp\mysql\bin\mysql.exe -u root tentaciones_marlly < database\views.sql
-- =============================================================

USE tentaciones_marlly;

-- Ventas con cliente, vendedor y método de pago (RF 5.x / recibo).
CREATE OR REPLACE VIEW v_ventas_completas AS
SELECT v.id_venta,
       v.fecha,
       v.total,
       v.valor_recibido,
       v.cambio,
       v.estado,
       v.numero_recibo,
       v.empresa,
       mp.nombre_metodo AS metodo_pago,
       c.nombre AS nombre_cliente,
       c.correo_electronico AS correo_cliente,
       u.username AS vendedor,
       CONCAT(u.nombre, ' ', u.apellido) AS nombre_vendedor
FROM ventas v
LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = v.id_metodo_pago
LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
INNER JOIN usuarios u ON u.id = v.id_usuario;

-- Detalle de cada venta con subtotal y ganancia por línea (RF 2.1 / 5.6).
CREATE OR REPLACE VIEW v_detalle_ventas_completo AS
SELECT v.id_venta,
       v.fecha,
       v.estado,
       p.id_producto,
       p.codigo_barras,
       p.nombre AS producto,
       p.categoria,
       d.cantidad,
       d.precio_unitario,
       (d.cantidad * d.precio_unitario) AS subtotal,
       (d.cantidad * (d.precio_unitario - p.precio_compra)) AS ganancia
FROM detalle_ventas d
INNER JOIN ventas v ON v.id_venta = d.id_venta
INNER JOIN productos p ON p.id_producto = d.id_producto;

-- Ganancia real acumulada por producto, solo ventas completadas (RF 2.1).
CREATE OR REPLACE VIEW v_ganancia_producto AS
SELECT p.id_producto,
       p.codigo_barras,
       p.nombre,
       p.categoria,
       COALESCE(SUM(CASE WHEN v.estado = 'completada' THEN d.cantidad ELSE 0 END), 0) AS unidades_vendidas,
       COALESCE(SUM(CASE WHEN v.estado = 'completada' THEN d.cantidad * d.precio_unitario ELSE 0 END), 0) AS total_ingresos,
       COALESCE(SUM(CASE WHEN v.estado = 'completada' THEN d.cantidad * p.precio_compra ELSE 0 END), 0) AS total_costo,
       COALESCE(SUM(CASE WHEN v.estado = 'completada' THEN d.cantidad * (d.precio_unitario - p.precio_compra) ELSE 0 END), 0) AS ganancia_real
FROM productos p
LEFT JOIN detalle_ventas d ON d.id_producto = p.id_producto
LEFT JOIN ventas v ON v.id_venta = d.id_venta
GROUP BY p.id_producto, p.codigo_barras, p.nombre, p.categoria;

-- Ventas agregadas por método de pago, solo completadas (RF 2.x / 5.3).
CREATE OR REPLACE VIEW v_ventas_por_metodo_pago AS
SELECT COALESCE(mp.nombre_metodo, 'Sin método') AS metodo_pago,
       COUNT(*) AS num_ventas,
       COALESCE(SUM(v.total), 0) AS total
FROM ventas v
LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = v.id_metodo_pago
WHERE v.estado = 'completada'
GROUP BY metodo_pago;

-- Compras agregadas por proveedor, solo registradas (RF 4.3).
CREATE OR REPLACE VIEW v_compras_por_proveedor AS
SELECT pr.id_proveedor,
       pr.identificacion_nit,
       pr.nombre_razon_social AS proveedor,
       COUNT(c.id_compra) AS num_compras,
       COALESCE(SUM(c.total_compra), 0) AS total_comprado
FROM proveedores pr
LEFT JOIN compras c ON c.id_proveedor = pr.id_proveedor AND c.estado = 'registrada'
GROUP BY pr.id_proveedor, pr.identificacion_nit, pr.nombre_razon_social;

-- Resumen mensual de ventas completadas con ganancia (RF 2.x).
CREATE OR REPLACE VIEW v_ventas_mensuales AS
SELECT DATE_FORMAT(v.fecha, '%Y-%m') AS mes,
       COUNT(DISTINCT v.id_venta) AS num_ventas,
       COALESCE(SUM(d.cantidad * d.precio_unitario), 0) AS total_ingresos,
       COALESCE(SUM(d.cantidad * (d.precio_unitario - p.precio_compra)), 0) AS ganancia
FROM ventas v
INNER JOIN detalle_ventas d ON d.id_venta = v.id_venta
INNER JOIN productos p ON p.id_producto = d.id_producto
WHERE v.estado = 'completada'
GROUP BY mes
ORDER BY mes;
