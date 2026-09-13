<?php
declare(strict_types=1);

/**
 * Única clase de conexión a BD del proyecto.
 * Lee credenciales desde las constantes definidas en config.php (vienen del .env).
 */
class Connection
{
    public PDO $conn;

    public function __construct()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit(APP_DEBUG
                ? 'Error de conexión a la base de datos. Revisa el archivo .env'
                : 'Error interno del servidor.');
        }
    }
}
