<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Connection.php';

/** Modelo de métodos de pago. Tabla: metodos_pago. (RF 5.3) */
class MetodoPago
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    public function listarActivos(): array
    {
        return $this->db->query(
            "SELECT * FROM metodos_pago WHERE estado = 'activo' ORDER BY nombre_metodo ASC, empresa ASC"
        )->fetchAll();
    }

    public function obtenerPorId(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM metodos_pago WHERE id_metodo_pago = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /** RI 5.3: cambio a entregar en pagos en efectivo. */
    public function calcularCambio(float $valorRecibido, float $totalVenta): float
    {
        $cambio = $valorRecibido - $totalVenta;
        return $cambio > 0 ? round($cambio, 2) : 0.0;
    }
}
