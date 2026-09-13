-- Migración RF 4.4 / 5.2 / 5.4 / 5.6 — idempotente (MariaDB 10.4 / MySQL 8)
-- NOTA: desde el esquema único v2 estas 2 tablas YA están en schema.sql.
-- Este archivo solo sirve para BDs creadas ANTES de la consolidación.
-- Aplicar: C:\xampp\mysql\bin\mysql.exe -u root tentaciones_marlly < database\migrate_rf445256.sql
-- Charset igual que schema.sql: utf8mb4 / utf8mb4_unicode_ci (válido en MariaDB 10.4;
-- NO usar utf8mb4_0900_ai_ci ni utf8mb4_uca1400_ai_ci aquí: error 1273 en 10.4).
USE tentaciones_marlly;

-- RF 5.2: kardex de inventario (entradas/salidas vinculadas a venta o compra)
CREATE TABLE IF NOT EXISTS movimientos_inventario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_producto INT NOT NULL,
  tipo ENUM('entrada','salida') NOT NULL,
  cantidad INT NOT NULL,
  stock_antes INT NOT NULL,
  stock_despues INT NOT NULL,
  id_venta INT NULL,
  id_compra INT NULL,
  motivo VARCHAR(255) NULL,
  id_usuario INT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mov_producto FOREIGN KEY (id_producto)
    REFERENCES productos (id_producto) ON DELETE RESTRICT,
  CONSTRAINT fk_mov_venta FOREIGN KEY (id_venta)
    REFERENCES ventas (id_venta) ON DELETE SET NULL,
  CONSTRAINT fk_mov_compra FOREIGN KEY (id_compra)
    REFERENCES compras (id_compra) ON DELETE SET NULL,
  CONSTRAINT fk_mov_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id) ON DELETE SET NULL,
  INDEX idx_mov_prod_fecha (id_producto, fecha),
  INDEX idx_mov_venta (id_venta),
  INDEX idx_mov_compra (id_compra)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RF 5.4: pausas múltiples persistentes (JSON = LONGTEXT en MariaDB 10.4)
CREATE TABLE IF NOT EXISTS ventas_pausadas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  etiqueta VARCHAR(60) NOT NULL,
  items JSON NOT NULL,
  id_cliente INT NULL,
  id_usuario INT NULL,
  creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pausa_cliente FOREIGN KEY (id_cliente)
    REFERENCES clientes (id_cliente) ON DELETE SET NULL,
  CONSTRAINT fk_pausa_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id) ON DELETE SET NULL,
  INDEX idx_pausa_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
