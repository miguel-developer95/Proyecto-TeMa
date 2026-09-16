<?php
// model/compra.php
require_once __DIR__ . '/../config/connection.php';

class Compra
{
    /** @var PDO */
    private PDO $conn;
    private string $tabla = 'compras';

    public ?int $id_compra = null;
    public ?string $fecha_hora = null;
    public ?int $id_metodo_pago = null;
    public mixed $metodo_pago = null;
    public ?int $id_usuario = null;
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
        if (empty($this->id_usuario)) {
            $this->id_usuario = (int) ($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 1);
        }

        if (empty($this->id_metodo_pago) && !empty($this->metodo_pago)) {
            $this->id_metodo_pago = (int) $this->metodo_pago;
        }
        if (empty($this->id_metodo_pago)) {
            $this->id_metodo_pago = 1;
        }

        $sql = "INSERT INTO {$this->tabla}
                    (fecha_hora, id_proveedor, id_usuario, id_metodo_pago, estado, total_compra)
                VALUES
                    (:fecha_hora, :id_proveedor, :id_usuario, :id_metodo_pago, :estado, :total_compra)";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(':fecha_hora', $this->fecha_hora ?: date('Y-m-d H:i:s'));
        $stmt->bindValue(':id_proveedor', $this->id_proveedor, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $this->id_usuario, PDO::PARAM_INT);
        $stmt->bindValue(':id_metodo_pago', $this->id_metodo_pago, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $this->estado ?: 'registrada');
        $stmt->bindValue(':total_compra', $this->total_compra);

        if ($stmt->execute()) {
            $this->id_compra = (int) $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    /**
     * Historial completo de compras con nombre del proveedor y método de pago (RF 4.3).
     */
    public function obtenerTodas(): array
    {
        $sql = "SELECT c.id_compra, c.fecha_hora, c.id_metodo_pago, c.id_metodo_pago AS metodo_pago,
                       c.id_usuario, c.estado, c.total_compra, c.id_proveedor,
                       p.nombre_razon_social, p.nombre_razon_social AS nom_proveedor,
                       mp.nombre_metodo, mp.nombre_metodo AS tipo_pago,
                       mp.empresa, mp.empresa AS nombre_empresa
                FROM {$this->tabla} c
                LEFT JOIN proveedores p ON p.id_proveedor = c.id_proveedor
                LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = c.id_metodo_pago
                ORDER BY c.fecha_hora DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una compra específica por ID.
     */
    public function obtenerPorId(int $id_compra): ?array
    {
        $sql = "SELECT c.id_compra, c.fecha_hora, c.id_metodo_pago, c.id_metodo_pago AS metodo_pago,
                       c.id_usuario, c.estado, c.total_compra, c.id_proveedor,
                       p.nombre_razon_social, p.nombre_razon_social AS nom_proveedor,
                       mp.nombre_metodo, mp.nombre_metodo AS tipo_pago,
                       mp.empresa, mp.empresa AS nombre_empresa
                FROM {$this->tabla} c
                LEFT JOIN proveedores p ON p.id_proveedor = c.id_proveedor
                LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = c.id_metodo_pago
                WHERE c.id_compra = :id_compra
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_compra', $id_compra, PDO::PARAM_INT);
        $stmt->execute();

        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Filtra el historial de compras por proveedor.
     */
    public function obtenerPorProveedor(int $id_proveedor): array
    {
        $sql = "SELECT c.id_compra, c.fecha_hora, c.id_metodo_pago, c.id_metodo_pago AS metodo_pago,
                       c.id_usuario, c.estado, c.total_compra, c.id_proveedor,
                       p.nombre_razon_social, p.nombre_razon_social AS nom_proveedor,
                       mp.nombre_metodo, mp.nombre_metodo AS tipo_pago,
                       mp.empresa, mp.empresa AS nombre_empresa
                FROM {$this->tabla} c
                LEFT JOIN proveedores p ON p.id_proveedor = c.id_proveedor
                LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = c.id_metodo_pago
                WHERE c.id_proveedor = :id_proveedor
                ORDER BY c.fecha_hora DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_proveedor', $id_proveedor, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza una compra existente (RF 4.4).
     */
    public function actualizar(): bool
    {
        if (empty($this->id_metodo_pago) && !empty($this->metodo_pago)) {
            $this->id_metodo_pago = (int) $this->metodo_pago;
        }

        $sql = "UPDATE {$this->tabla}
                SET fecha_hora = :fecha_hora,
                    id_metodo_pago = :id_metodo_pago,
                    estado = :estado,
                    total_compra = :total_compra,
                    id_proveedor = :id_proveedor
                WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(':fecha_hora', $this->fecha_hora);
        $stmt->bindValue(':id_metodo_pago', $this->id_metodo_pago, PDO::PARAM_INT);
        $stmt->bindValue(':estado', $this->estado);
        $stmt->bindValue(':total_compra', $this->total_compra);
        $stmt->bindValue(':id_proveedor', $this->id_proveedor, PDO::PARAM_INT);
        $stmt->bindValue(':id_compra', $this->id_compra, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Actualiza solo el estado de una compra.
     */
    public function actualizarEstado(int $id_compra, string $estado): bool
    {
        $sql = "UPDATE {$this->tabla} SET estado = :estado WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':estado', $estado);
        $stmt->bindValue(':id_compra', $id_compra, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina un registro de compra por ID.
     */
    public function eliminar(int $id_compra): bool
    {
        $sql = "DELETE FROM {$this->tabla} WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_compra', $id_compra, PDO::PARAM_INT);

        return $stmt->execute();
    }
}