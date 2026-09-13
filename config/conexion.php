<?php
// Shim de compatibilidad: el codigo original pide conexion.php / Conexion,
// el archivo real es connection.php / Connection. No se toca nada existente.
require_once __DIR__ . '/connection.php';
if (!class_exists('Conexion') && class_exists('Connection')) {
    class_alias('Connection', 'Conexion');
}
