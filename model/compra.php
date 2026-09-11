<?php
// model/compra.php
require_once __DIR__ . '/../config/connection.php';

class Compra
{
    /** @var PDO */
    private PDO $conn;
    private string $tabla = 'compra';

    public ?int $id_compra = null;
    public ?string $fecha_hora = null;
    public ?string $metodo_pago = null;
    public ?string $estado = null;
    public ?float $total_compra = null;
    public ?int $id_proveedor = null;

    public function __construct()
    {
        $this->conn = (new Connection())->conn;
    }

    /**
     * Crea un nuevo registro de compra (RF 4.1).
     */
    public function crear(): bool
    {
        $sql = "INSERT INTO {$this->tabla}
                    (fecha_hora, metodo_pago, estado, total_compra, id_proveedor)
                VALUES
                    (:fecha_hora, :metodo_pago, :estado, :total_compra, :id_proveedor)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':fecha_hora', $this->fecha_hora);
        $stmt->bindParam(':metodo_pago', $this->metodo_pago);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':total_compra', $this->total_compra);
        $stmt->bindParam(':id_proveedor', $this->id_proveedor, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->id_compra = (int) $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Historial completo de compras con nombre del proveedor (RF 4.3).
     */
    public function obtenerTodas(): array
    {
        $sql = "SELECT c.id_compra, c.fecha_hora, c.metodo_pago, c.estado,
                       c.total_compra, c.id_proveedor, p.nom_proveedor,
                       mp.tipo_pago, mp.nombre_empresa
                FROM {$this->tabla} c
                LEFT JOIN proveedores p ON p.id_proveedor = c.id_proveedor
                LEFT JOIN metodos_pago mp ON mp.id_pago = c.metodo_pago
                ORDER BY c.fecha_hora DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una compra específica por ID.
     *
     * @return array|false
     */
    public function obtenerPorId(int $id_compra)
    {
        $sql = "SELECT id_compra, fecha_hora, metodo_pago, estado, total_compra, id_proveedor
                FROM {$this->tabla}
                WHERE id_compra = :id_compra
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Filtra el historial de compras por proveedor.
     */
    public function obtenerPorProveedor(int $id_proveedor): array
    {
        $sql = "SELECT id_compra, fecha_hora, metodo_pago, estado, total_compra, id_proveedor
                FROM {$this->tabla}
                WHERE id_proveedor = :id_proveedor
                ORDER BY fecha_hora DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_proveedor', $id_proveedor, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza una compra existente (RF 4.4). La trazabilidad de este cambio
     * (usuario responsable, fecha) debe registrarse aparte en historial_modificacion.
     */
    public function actualizar(): bool
    {
        $sql = "UPDATE {$this->tabla}
                SET fecha_hora = :fecha_hora,
                    metodo_pago = :metodo_pago,
                    estado = :estado,
                    total_compra = :total_compra,
                    id_proveedor = :id_proveedor
                WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':fecha_hora', $this->fecha_hora);
        $stmt->bindParam(':metodo_pago', $this->metodo_pago);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':total_compra', $this->total_compra);
        $stmt->bindParam(':id_proveedor', $this->id_proveedor, PDO::PARAM_INT);
        $stmt->bindParam(':id_compra', $this->id_compra, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Actualiza solo el estado de una compra.
     */
    public function actualizarEstado(int $id_compra, string $estado): bool
    {
        $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina un registro de compra por ID.
     */
    public function eliminar(int $id_compra): bool
    {
        $sql = "DELETE FROM {$this->tabla} WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);

        return $stmt->execute();
    }
}