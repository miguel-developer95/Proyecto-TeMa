<?php
// model/metodos_pago.php
require_once __DIR__ . '/../config/conexion.php';

class MetodoPago
{
    private $db;

    public function __construct()
    {
        $this->db = (new Conexion())->conn;
    }

    // Registrar un método de pago
    public function registrar($tipo, $empresa = null)
    {
        try {
            $query = "INSERT INTO metodos_pago (tipo, empresa) VALUES (:tipo, :empresa)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":tipo", $tipo);
            $stmt->bindParam(":empresa", $empresa);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false; // Ejemplo: duplicado
            }
            throw $e;
        }
    }

    // Obtener todos los métodos de pago
    public function obtenerTodos()
    {
        $query = "SELECT id, tipo, empresa FROM metodos_pago ORDER BY id DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener método de pago por ID
    public function obtenerPorId($id)
    {
        $query = "SELECT id, tipo, empresa FROM metodos_pago WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar método de pago
    public function actualizar($id, $tipo, $empresa = null)
    {
        try {
            $query = "UPDATE metodos_pago SET tipo = :tipo, empresa = :empresa WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":tipo", $tipo);
            $stmt->bindParam(":empresa", $empresa);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Eliminar método de pago
    public function eliminar($id)
    {
        $query = "DELETE FROM metodos_pago WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    // Contar métodos de pago registrados
    public function contarMetodos()
    {
        $query = "SELECT COUNT(*) as total FROM metodos_pago";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
