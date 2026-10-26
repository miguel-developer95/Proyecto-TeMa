<?php
class Connection
{
    private $host = "localhost";
    private $db_name = "tentaciones_marlly"; 
    private $username = "root";
    private $password = "";
    public $conn;

    public function __construct()
    {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("Error de conexión a BD: " . $e->getMessage());
            die("Error de conexión con la base de datos. Por favor contacte al administrador.");
        }
    }
}
?>
