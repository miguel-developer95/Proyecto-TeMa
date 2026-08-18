<?php
// Config/conexion.php
class Conexion {
    private $host = "localhost";
    private $dbname ="login_db";
    private $user = "root";
    private $password = "";
    public $conn;

    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host=localhost;dbname=$this->dbname", $this->user, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException  $e) {
            die("Error en la conexión: " . $e->getMessage());
        }
    }
}
?>