-- =============================================================
-- Tentaciones Marlly · Esquema ÚNICO de base de datos (v2 consolidada)
-- Este archivo crea TODO: las 14 tablas que usan los modelos.
-- (Incluye lo que antes vivía en migrate_rf445256.sql: kardex y pausas.)
--
-- Instalación limpia (XAMPP, MariaDB 10.4):
--   1. C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql
--   2. C:\xampp\mysql\bin\mysql.exe -u root tentaciones_marlly < database\views.sql
-- O con phpMyAdmin: pestaña Importar, primero schema.sql y luego views.sql.
--
-- Si tu BD ya existe de una versión anterior, aplica ADEMÁS:
--   C:\xampp\mysql\bin\mysql.exe -u root tentaciones_marlly < database\migrate_rf445256.sql
-- (idempotente: solo crea lo que falte).
--
-- CHARSET: cada tabla declara explícitamente
--   ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
-- para que funcione aunque la BD haya sido creada antes con otro charset
-- (p. ej. utf8 de 3 bytes o latin1, que rompen tildes/ñ/emojis).
-- NO usar utf8mb4_0900_ai_ci (solo MySQL 8) ni utf8mb4_uca1400_ai_ci
-- (solo MariaDB 10.6+): en el MariaDB 10.4 de este XAMPP dan error 1273
-- "Unknown collation". utf8mb4_unicode_ci es el moderno válido aquí.
-- El PDO ya pide charset=utf8mb4 en el DSN (config/Connection.php).
-- =============================================================

CREATE DATABASE IF NOT EXISTS tentaciones_marlly
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tentaciones_marlly;

-- ---------------- Usuarios (RF 1.x) ----------------
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  apellido VARCHAR(80) NOT NULL,
  rol VARCHAR(30) NOT NULL DEFAULT 'Vendedor',
  username VARCHAR(60) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(120) NULL UNIQUE,
  documento VARCHAR(30) NULL UNIQUE,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  intentos_fallidos INT NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- Proveedores (RF 4.2) ----------------
CREATE TABLE IF NOT EXISTS proveedores (
  id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
  identificacion_nit VARCHAR(30) NOT NULL UNIQUE,
  nombre_razon_social VARCHAR(150) NOT NULL,
  direccion VARCHAR(200) NULL,
  telefono VARCHAR(30) NULL,
  correo_electronico VARCHAR(120) NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- Productos / Inventario (RF 3.x) ----------------
CREATE TABLE IF NOT EXISTS productos (
  id_producto INT AUTO_INCREMENT PRIMARY KEY,
  codigo_barras VARCHAR(50) NULL UNIQUE,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT NULL,
  categoria VARCHAR(80) NULL,
  precio_compra DECIMAL(12,2) NOT NULL DEFAULT 0,
  precio_venta DECIMAL(12,2) NOT NULL DEFAULT 0,
  cantidad_stock INT NOT NULL DEFAULT 0,
  stock_minimo INT NOT NULL DEFAULT 0,
  id_proveedor INT NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_modificacion DATETIME NULL,
  id_usuario_modificacion INT NULL,
  CONSTRAINT fk_productos_proveedor FOREIGN KEY (id_proveedor)
    REFERENCES proveedores (id_proveedor) ON DELETE SET NULL,
  CONSTRAINT fk_productos_usuario FOREIGN KEY (id_usuario_modificacion)
    REFERENCES usuarios (id) ON DELETE SET NULL,
  INDEX idx_productos_nombre (nombre),
  INDEX idx_productos_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- Clientes (ventas / recibos) ----------------
CREATE TABLE IF NOT EXISTS clientes (
  id_cliente INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  correo_electronico VARCHAR(120) NULL,
  telefono VARCHAR(30) NULL,
  documento VARCHAR(30) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_clientes_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- Métodos de pago (RF 5.3) ----------------
CREATE TABLE IF NOT EXISTS metodos_pago (
  id_metodo_pago INT AUTO_INCREMENT PRIMARY KEY,
  nombre_metodo VARCHAR(60) NOT NULL,
  empresa VARCHAR(80) NULL,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- Ventas (RF 5.x) ----------------
CREATE TABLE IF NOT EXISTS ventas (
  id_venta INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_usuario INT NOT NULL,
  id_cliente INT NULL,
  id_metodo_pago INT NULL,
  empresa VARCHAR(80) NULL,
  total DECIMAL(12,2) NOT NULL,
  valor_recibido DECIMAL(12,2) NOT NULL DEFAULT 0,
  cambio DECIMAL(12,2) NOT NULL DEFAULT 0,
  estado ENUM('completada','anulada') NOT NULL DEFAULT 'completada',
  motivo_anulacion VARCHAR(255) NULL,
  numero_recibo VARCHAR(20) NULL UNIQUE,
  CONSTRAINT fk_ventas_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id) ON DELETE RESTRICT,
  CONSTRAINT fk_ventas_cliente FOREIGN KEY (id_cliente)
    REFERENCES clientes (id_cliente) ON DELETE SET NULL,
  CONSTRAINT fk_ventas_metodo FOREIGN KEY (id_metodo_pago)
    REFERENCES metodos_pago (id_metodo_pago) ON DELETE SET NULL,
  INDEX idx_ventas_fecha (fecha),
  INDEX idx_ventas_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detalle_ventas (
  id_venta INT NOT NULL,
  id_producto INT NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id_venta, id_producto),
  CONSTRAINT fk_dv_venta FOREIGN KEY (id_venta)
    REFERENCES ventas (id_venta) ON DELETE CASCADE,
  CONSTRAINT fk_dv_producto FOREIGN KEY (id_producto)
    REFERENCES productos (id_producto) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- Compras (RF 4.x) ----------------
CREATE TABLE IF NOT EXISTS compras (
  id_compra INT AUTO_INCREMENT PRIMARY KEY,
  fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_proveedor INT NOT NULL,
  id_usuario INT NOT NULL,
  id_metodo_pago INT NULL,
  estado ENUM('registrada','anulada') NOT NULL DEFAULT 'registrada',
  total_compra DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_compras_proveedor FOREIGN KEY (id_proveedor)
    REFERENCES proveedores (id_proveedor) ON DELETE RESTRICT,
  CONSTRAINT fk_compras_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id) ON DELETE RESTRICT,
  CONSTRAINT fk_compras_metodo FOREIGN KEY (id_metodo_pago)
    REFERENCES metodos_pago (id_metodo_pago) ON DELETE SET NULL,
  INDEX idx_compras_fecha (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detalle_compras (
  id_compra INT NOT NULL,
  id_producto INT NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario_compra DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id_compra, id_producto),
  CONSTRAINT fk_dc_compra FOREIGN KEY (id_compra)
    REFERENCES compras (id_compra) ON DELETE CASCADE,
  CONSTRAINT fk_dc_producto FOREIGN KEY (id_producto)
    REFERENCES productos (id_producto) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- Recuperación de contraseña (RF 1.5) ----------------
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expira_en DATETIME NOT NULL,
  usado_en DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_resets_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id) ON DELETE CASCADE,
  INDEX idx_resets_expira (expira_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- Auditoría ----------------
CREATE TABLE IF NOT EXISTS historial (
  id_modificacion INT AUTO_INCREMENT PRIMARY KEY,
  fecha_accion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo_accion VARCHAR(60) NOT NULL,
  detalle_cambio VARCHAR(500) NOT NULL,
  id_usuario INT NULL,
  CONSTRAINT fk_historial_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id) ON DELETE SET NULL,
  INDEX idx_historial_fecha (fecha_accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- Informes generados (RF 2.x) ----------------
CREATE TABLE IF NOT EXISTS informes (
  id_informe INT AUTO_INCREMENT PRIMARY KEY,
  tipo_informe VARCHAR(80) NOT NULL,
  descripcion TEXT NOT NULL,
  fecha_generacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_usuario INT NULL,
  CONSTRAINT fk_informes_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------- Kardex de inventario (RF 5.2) ----------------
-- (Antes solo en migrate_rf445256.sql; ahora parte del esquema único.)
-- Entradas/salidas vinculadas a venta o compra para trazabilidad.
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

-- ---------------- Ventas pausadas (RF 5.4) ----------------
-- (Antes solo en migrate_rf445256.sql; ahora parte del esquema único.)
-- Pausas múltiples persistentes. items es JSON (LONGTEXT en MariaDB 10.4).
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

-- =============================================================
-- Datos iniciales
-- =============================================================

INSERT INTO metodos_pago (nombre_metodo, empresa, estado) VALUES
  ('Efectivo', NULL, 'activo'),
  ('Transferencia', 'Nequi', 'activo'),
  ('Transferencia', 'Daviplata', 'activo')
ON DUPLICATE KEY UPDATE nombre_metodo = VALUES(nombre_metodo);

-- Administrador inicial: usuario "admin", clave "Admin123*"
-- (cambiar la clave tras el primer ingreso)
INSERT INTO usuarios (nombre, apellido, rol, username, password, email, documento, estado)
VALUES ('Admin', 'General', 'Administrador', 'admin',
  '$2y$10$PprlBKE5CHRmVvlTVCaDSus5x8PtF8SUerOaNa6tDXIsxDoUWeLMm',
  'admin@tentaciones.local', '00000000', 'activo')
ON DUPLICATE KEY UPDATE username = VALUES(username);

-- Proveedor y productos de demostración (para probar POS e informes)
INSERT INTO proveedores (identificacion_nit, nombre_razon_social, direccion, telefono, correo_electronico, estado)
VALUES ('900123456-7', 'Distribuidora La Economía', 'Calle 10 # 5-20, Bogotá', '6015550101', 'contacto@laeconomia.co', 'activo')
ON DUPLICATE KEY UPDATE nombre_razon_social = VALUES(nombre_razon_social);

INSERT INTO productos (codigo_barras, nombre, descripcion, categoria, precio_compra, precio_venta, cantidad_stock, stock_minimo, id_proveedor, estado)
VALUES
  ('7701001001', 'Arroz Diana 500g', 'Arroz blanco de consumo diario', 'Granos', 1800, 2500, 48, 10, 1, 'activo'),
  ('7701001002', 'Aceite Gourmet 1000ml', 'Aceite vegetal', 'Abarrotes', 8500, 10500, 24, 6, 1, 'activo'),
  ('7701001003', 'Panela Doña Panela x2', 'Panela en pastillas', 'Dulces', 2200, 3000, 5, 8, 1, 'activo')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
