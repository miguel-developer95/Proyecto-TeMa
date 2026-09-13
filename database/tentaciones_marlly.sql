-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: tentaciones_marlly
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `tentaciones_marlly`
--

CREATE DATABASE IF NOT EXISTS `tentaciones_marlly` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `tentaciones_marlly`;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `correo_electronico` varchar(120) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `documento` varchar(30) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cliente`),
  KEY `idx_clientes_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compras`
--

DROP TABLE IF EXISTS `compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compras` (
  `id_compra` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `id_proveedor` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_metodo_pago` int(11) DEFAULT NULL,
  `estado` enum('registrada','anulada') NOT NULL DEFAULT 'registrada',
  `total_compra` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id_compra`),
  KEY `fk_compras_proveedor` (`id_proveedor`),
  KEY `fk_compras_usuario` (`id_usuario`),
  KEY `fk_compras_metodo` (`id_metodo_pago`),
  KEY `idx_compras_fecha` (`fecha_hora`),
  CONSTRAINT `fk_compras_metodo` FOREIGN KEY (`id_metodo_pago`) REFERENCES `metodos_pago` (`id_metodo_pago`) ON DELETE SET NULL,
  CONSTRAINT `fk_compras_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  CONSTRAINT `fk_compras_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compras`
--

LOCK TABLES `compras` WRITE;
/*!40000 ALTER TABLE `compras` DISABLE KEYS */;
/*!40000 ALTER TABLE `compras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_compras`
--

DROP TABLE IF EXISTS `detalle_compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_compras` (
  `id_compra` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario_compra` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id_compra`,`id_producto`),
  KEY `fk_dc_producto` (`id_producto`),
  CONSTRAINT `fk_dc_compra` FOREIGN KEY (`id_compra`) REFERENCES `compras` (`id_compra`) ON DELETE CASCADE,
  CONSTRAINT `fk_dc_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_compras`
--

LOCK TABLES `detalle_compras` WRITE;
/*!40000 ALTER TABLE `detalle_compras` DISABLE KEYS */;
/*!40000 ALTER TABLE `detalle_compras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_ventas`
--

DROP TABLE IF EXISTS `detalle_ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_ventas` (
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id_venta`,`id_producto`),
  KEY `fk_dv_producto` (`id_producto`),
  CONSTRAINT `fk_dv_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `fk_dv_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_ventas`
--

LOCK TABLES `detalle_ventas` WRITE;
/*!40000 ALTER TABLE `detalle_ventas` DISABLE KEYS */;
/*!40000 ALTER TABLE `detalle_ventas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historial`
--

DROP TABLE IF EXISTS `historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historial` (
  `id_modificacion` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_accion` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo_accion` varchar(60) NOT NULL,
  `detalle_cambio` varchar(500) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_modificacion`),
  KEY `fk_historial_usuario` (`id_usuario`),
  KEY `idx_historial_fecha` (`fecha_accion`),
  CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historial`
--

LOCK TABLES `historial` WRITE;
/*!40000 ALTER TABLE `historial` DISABLE KEYS */;
INSERT INTO `historial` VALUES (1,'2026-09-13 08:01:41','registro_usuario','Registro: enheban.gomex.adm',NULL);
/*!40000 ALTER TABLE `historial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `informes`
--

DROP TABLE IF EXISTS `informes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `informes` (
  `id_informe` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_informe` varchar(80) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha_generacion` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_informe`),
  KEY `fk_informes_usuario` (`id_usuario`),
  CONSTRAINT `fk_informes_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `informes`
--

LOCK TABLES `informes` WRITE;
/*!40000 ALTER TABLE `informes` DISABLE KEYS */;
/*!40000 ALTER TABLE `informes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metodos_pago`
--

DROP TABLE IF EXISTS `metodos_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metodos_pago` (
  `id_metodo_pago` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_metodo` varchar(60) NOT NULL,
  `empresa` varchar(80) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id_metodo_pago`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metodos_pago`
--

LOCK TABLES `metodos_pago` WRITE;
/*!40000 ALTER TABLE `metodos_pago` DISABLE KEYS */;
INSERT INTO `metodos_pago` VALUES (1,'Efectivo',NULL,'activo'),(2,'Transferencia','Nequi','activo'),(3,'Transferencia','Daviplata','activo'),(4,'Efectivo',NULL,'activo'),(5,'Transferencia','Nequi','activo'),(6,'Transferencia','Daviplata','activo');
/*!40000 ALTER TABLE `metodos_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_inventario`
--

DROP TABLE IF EXISTS `movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `tipo` enum('entrada','salida') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `stock_antes` int(11) NOT NULL,
  `stock_despues` int(11) NOT NULL,
  `id_venta` int(11) DEFAULT NULL,
  `id_compra` int(11) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_mov_usuario` (`id_usuario`),
  KEY `idx_mov_prod_fecha` (`id_producto`,`fecha`),
  KEY `idx_mov_venta` (`id_venta`),
  KEY `idx_mov_compra` (`id_compra`),
  CONSTRAINT `fk_mov_compra` FOREIGN KEY (`id_compra`) REFERENCES `compras` (`id_compra`) ON DELETE SET NULL,
  CONSTRAINT `fk_mov_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mov_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_inventario`
--

LOCK TABLES `movimientos_inventario` WRITE;
/*!40000 ALTER TABLE `movimientos_inventario` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimientos_inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira_en` datetime NOT NULL,
  `usado_en` datetime DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_resets_usuario` (`id_usuario`),
  KEY `idx_resets_expira` (`expira_en`),
  CONSTRAINT `fk_resets_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (1,1,'f3e7bf7e1b25f2210cfd8f8225a99244fa793460d5ec5b2593139158f689ffd0','2026-09-13 08:42:37','2026-09-13 07:43:16','2026-09-13 07:42:37'),(2,3,'6413896c10da6833f8e4b5b906c1420621af3e6aba0fdd4eb5f27a6f3160cbe7','2026-09-13 09:02:21',NULL,'2026-09-13 08:02:21'),(3,3,'4384369a1e7621e3ef14a76b421750ca3373ffe0b57e0bd3c8fcd902dd371e29','2026-09-13 09:04:36',NULL,'2026-09-13 08:04:36'),(4,3,'8142513bceab46a954e335de6054843c1a6aebd138ffbbc3e55a1d9efa1c5c49','2026-09-13 09:07:36','2026-09-13 08:08:16','2026-09-13 08:07:36');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_barras` varchar(50) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(80) DEFAULT NULL,
  `precio_compra` decimal(12,2) NOT NULL DEFAULT 0.00,
  `precio_venta` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cantidad_stock` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 0,
  `id_proveedor` int(11) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL,
  `id_usuario_modificacion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `codigo_barras` (`codigo_barras`),
  KEY `fk_productos_proveedor` (`id_proveedor`),
  KEY `fk_productos_usuario` (`id_usuario_modificacion`),
  KEY `idx_productos_nombre` (`nombre`),
  KEY `idx_productos_categoria` (`categoria`),
  CONSTRAINT `fk_productos_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE SET NULL,
  CONSTRAINT `fk_productos_usuario` FOREIGN KEY (`id_usuario_modificacion`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,'7701001001','Arroz Diana 500g','Arroz blanco de consumo diario','Granos',1800.00,2500.00,48,10,1,'activo','2026-09-13 07:35:09',NULL,NULL),(2,'7701001002','Aceite Gourmet 1000ml','Aceite vegetal','Abarrotes',8500.00,10500.00,24,6,1,'activo','2026-09-13 07:35:09',NULL,NULL),(3,'7701001003','Panela Doña Panela x2','Panela en pastillas','Dulces',2200.00,3000.00,5,8,1,'activo','2026-09-13 07:35:09',NULL,NULL);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `identificacion_nit` varchar(30) NOT NULL,
  `nombre_razon_social` varchar(150) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `correo_electronico` varchar(120) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `identificacion_nit` (`identificacion_nit`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'900123456-7','Distribuidora La Economía','Calle 10 # 5-20, Bogotá','6015550101','contacto@laeconomia.co','activo','2026-09-13 07:35:09');
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `rol` varchar(30) NOT NULL DEFAULT 'Vendedor',
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `documento` varchar(30) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `intentos_fallidos` int(11) NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `documento` (`documento`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Admin','General','Administrador','admin','$2y$10$IjiQKaWUEnyGeFhwboibWOweDaGUav2UFhcH5CojxGS8ykAokipb2','admin@tentaciones.local','00000000','activo',0,NULL,'2026-09-13 07:35:09'),(3,'enheban','gomex','Administrador','enheban.gomex.adm','$2y$10$i0cZnT3F6Y0wXPKw5Vx.au6Kv7JGa4itpAxBLSLzJ/Uby492p4DQ2','gomex@gmail.com','1010101010','activo',0,NULL,'2026-09-13 08:01:41');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_compras_por_proveedor`
--

DROP TABLE IF EXISTS `v_compras_por_proveedor`;
/*!50001 DROP VIEW IF EXISTS `v_compras_por_proveedor`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_compras_por_proveedor` AS SELECT
 1 AS `id_proveedor`,
  1 AS `identificacion_nit`,
  1 AS `proveedor`,
  1 AS `num_compras`,
  1 AS `total_comprado` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_detalle_ventas_completo`
--

DROP TABLE IF EXISTS `v_detalle_ventas_completo`;
/*!50001 DROP VIEW IF EXISTS `v_detalle_ventas_completo`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_detalle_ventas_completo` AS SELECT
 1 AS `id_venta`,
  1 AS `fecha`,
  1 AS `estado`,
  1 AS `id_producto`,
  1 AS `codigo_barras`,
  1 AS `producto`,
  1 AS `categoria`,
  1 AS `cantidad`,
  1 AS `precio_unitario`,
  1 AS `subtotal`,
  1 AS `ganancia` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ganancia_producto`
--

DROP TABLE IF EXISTS `v_ganancia_producto`;
/*!50001 DROP VIEW IF EXISTS `v_ganancia_producto`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_ganancia_producto` AS SELECT
 1 AS `id_producto`,
  1 AS `codigo_barras`,
  1 AS `nombre`,
  1 AS `categoria`,
  1 AS `unidades_vendidas`,
  1 AS `total_ingresos`,
  1 AS `total_costo`,
  1 AS `ganancia_real` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ventas_completas`
--

DROP TABLE IF EXISTS `v_ventas_completas`;
/*!50001 DROP VIEW IF EXISTS `v_ventas_completas`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_ventas_completas` AS SELECT
 1 AS `id_venta`,
  1 AS `fecha`,
  1 AS `total`,
  1 AS `valor_recibido`,
  1 AS `cambio`,
  1 AS `estado`,
  1 AS `numero_recibo`,
  1 AS `empresa`,
  1 AS `metodo_pago`,
  1 AS `nombre_cliente`,
  1 AS `correo_cliente`,
  1 AS `vendedor`,
  1 AS `nombre_vendedor` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ventas_mensuales`
--

DROP TABLE IF EXISTS `v_ventas_mensuales`;
/*!50001 DROP VIEW IF EXISTS `v_ventas_mensuales`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_ventas_mensuales` AS SELECT
 1 AS `mes`,
  1 AS `num_ventas`,
  1 AS `total_ingresos`,
  1 AS `ganancia` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_ventas_por_metodo_pago`
--

DROP TABLE IF EXISTS `v_ventas_por_metodo_pago`;
/*!50001 DROP VIEW IF EXISTS `v_ventas_por_metodo_pago`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_ventas_por_metodo_pago` AS SELECT
 1 AS `metodo_pago`,
  1 AS `num_ventas`,
  1 AS `total` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_metodo_pago` int(11) DEFAULT NULL,
  `empresa` varchar(80) DEFAULT NULL,
  `total` decimal(12,2) NOT NULL,
  `valor_recibido` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cambio` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estado` enum('completada','anulada') NOT NULL DEFAULT 'completada',
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  `numero_recibo` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_venta`),
  UNIQUE KEY `numero_recibo` (`numero_recibo`),
  KEY `fk_ventas_usuario` (`id_usuario`),
  KEY `fk_ventas_cliente` (`id_cliente`),
  KEY `fk_ventas_metodo` (`id_metodo_pago`),
  KEY `idx_ventas_fecha` (`fecha`),
  KEY `idx_ventas_estado` (`estado`),
  CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE SET NULL,
  CONSTRAINT `fk_ventas_metodo` FOREIGN KEY (`id_metodo_pago`) REFERENCES `metodos_pago` (`id_metodo_pago`) ON DELETE SET NULL,
  CONSTRAINT `fk_ventas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas_pausadas`
--

DROP TABLE IF EXISTS `ventas_pausadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ventas_pausadas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `etiqueta` varchar(60) NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `id_cliente` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `creada_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pausa_cliente` (`id_cliente`),
  KEY `idx_pausa_usuario` (`id_usuario`),
  CONSTRAINT `fk_pausa_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE SET NULL,
  CONSTRAINT `fk_pausa_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas_pausadas`
--

LOCK TABLES `ventas_pausadas` WRITE;
/*!40000 ALTER TABLE `ventas_pausadas` DISABLE KEYS */;
/*!40000 ALTER TABLE `ventas_pausadas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'tentaciones_marlly'
--

--
-- Dumping routines for database 'tentaciones_marlly'
--

--
-- Current Database: `tentaciones_marlly`
--

USE `tentaciones_marlly`;

--
-- Final view structure for view `v_compras_por_proveedor`
--

/*!50001 DROP VIEW IF EXISTS `v_compras_por_proveedor`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_compras_por_proveedor` AS select `pr`.`id_proveedor` AS `id_proveedor`,`pr`.`identificacion_nit` AS `identificacion_nit`,`pr`.`nombre_razon_social` AS `proveedor`,count(`c`.`id_compra`) AS `num_compras`,coalesce(sum(`c`.`total_compra`),0) AS `total_comprado` from (`proveedores` `pr` left join `compras` `c` on(`c`.`id_proveedor` = `pr`.`id_proveedor` and `c`.`estado` = 'registrada')) group by `pr`.`id_proveedor`,`pr`.`identificacion_nit`,`pr`.`nombre_razon_social` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_detalle_ventas_completo`
--

/*!50001 DROP VIEW IF EXISTS `v_detalle_ventas_completo`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_detalle_ventas_completo` AS select `v`.`id_venta` AS `id_venta`,`v`.`fecha` AS `fecha`,`v`.`estado` AS `estado`,`p`.`id_producto` AS `id_producto`,`p`.`codigo_barras` AS `codigo_barras`,`p`.`nombre` AS `producto`,`p`.`categoria` AS `categoria`,`d`.`cantidad` AS `cantidad`,`d`.`precio_unitario` AS `precio_unitario`,`d`.`cantidad` * `d`.`precio_unitario` AS `subtotal`,`d`.`cantidad` * (`d`.`precio_unitario` - `p`.`precio_compra`) AS `ganancia` from ((`detalle_ventas` `d` join `ventas` `v` on(`v`.`id_venta` = `d`.`id_venta`)) join `productos` `p` on(`p`.`id_producto` = `d`.`id_producto`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ganancia_producto`
--

/*!50001 DROP VIEW IF EXISTS `v_ganancia_producto`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_ganancia_producto` AS select `p`.`id_producto` AS `id_producto`,`p`.`codigo_barras` AS `codigo_barras`,`p`.`nombre` AS `nombre`,`p`.`categoria` AS `categoria`,coalesce(sum(case when `v`.`estado` = 'completada' then `d`.`cantidad` else 0 end),0) AS `unidades_vendidas`,coalesce(sum(case when `v`.`estado` = 'completada' then `d`.`cantidad` * `d`.`precio_unitario` else 0 end),0) AS `total_ingresos`,coalesce(sum(case when `v`.`estado` = 'completada' then `d`.`cantidad` * `p`.`precio_compra` else 0 end),0) AS `total_costo`,coalesce(sum(case when `v`.`estado` = 'completada' then `d`.`cantidad` * (`d`.`precio_unitario` - `p`.`precio_compra`) else 0 end),0) AS `ganancia_real` from ((`productos` `p` left join `detalle_ventas` `d` on(`d`.`id_producto` = `p`.`id_producto`)) left join `ventas` `v` on(`v`.`id_venta` = `d`.`id_venta`)) group by `p`.`id_producto`,`p`.`codigo_barras`,`p`.`nombre`,`p`.`categoria` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ventas_completas`
--

/*!50001 DROP VIEW IF EXISTS `v_ventas_completas`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_ventas_completas` AS select `v`.`id_venta` AS `id_venta`,`v`.`fecha` AS `fecha`,`v`.`total` AS `total`,`v`.`valor_recibido` AS `valor_recibido`,`v`.`cambio` AS `cambio`,`v`.`estado` AS `estado`,`v`.`numero_recibo` AS `numero_recibo`,`v`.`empresa` AS `empresa`,`mp`.`nombre_metodo` AS `metodo_pago`,`c`.`nombre` AS `nombre_cliente`,`c`.`correo_electronico` AS `correo_cliente`,`u`.`username` AS `vendedor`,concat(`u`.`nombre`,' ',`u`.`apellido`) AS `nombre_vendedor` from (((`ventas` `v` left join `metodos_pago` `mp` on(`mp`.`id_metodo_pago` = `v`.`id_metodo_pago`)) left join `clientes` `c` on(`c`.`id_cliente` = `v`.`id_cliente`)) join `usuarios` `u` on(`u`.`id` = `v`.`id_usuario`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ventas_mensuales`
--

/*!50001 DROP VIEW IF EXISTS `v_ventas_mensuales`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_ventas_mensuales` AS select date_format(`v`.`fecha`,'%Y-%m') AS `mes`,count(distinct `v`.`id_venta`) AS `num_ventas`,coalesce(sum(`d`.`cantidad` * `d`.`precio_unitario`),0) AS `total_ingresos`,coalesce(sum(`d`.`cantidad` * (`d`.`precio_unitario` - `p`.`precio_compra`)),0) AS `ganancia` from ((`ventas` `v` join `detalle_ventas` `d` on(`d`.`id_venta` = `v`.`id_venta`)) join `productos` `p` on(`p`.`id_producto` = `d`.`id_producto`)) where `v`.`estado` = 'completada' group by date_format(`v`.`fecha`,'%Y-%m') order by date_format(`v`.`fecha`,'%Y-%m') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ventas_por_metodo_pago`
--

/*!50001 DROP VIEW IF EXISTS `v_ventas_por_metodo_pago`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_ventas_por_metodo_pago` AS select coalesce(`mp`.`nombre_metodo`,'Sin método') AS `metodo_pago`,count(0) AS `num_ventas`,coalesce(sum(`v`.`total`),0) AS `total` from (`ventas` `v` left join `metodos_pago` `mp` on(`mp`.`id_metodo_pago` = `v`.`id_metodo_pago`)) where `v`.`estado` = 'completada' group by coalesce(`mp`.`nombre_metodo`,'Sin método') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-13  8:17:17
