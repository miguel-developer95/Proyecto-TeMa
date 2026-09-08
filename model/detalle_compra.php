<?php

if (!class_exists('Database', false)) {
    $databaseFile = dirname(__DIR__) . '/config/tentaciones_marlly.php';
    if (file_exists($databaseFile)) {
        require_once $databaseFile;
    }
}

class Compra
{
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
        $databaseClass = 'Database';

        if (!class_exists($databaseClass, false)) {
            $databaseFiles = [
                dirname(__DIR__) . '/config/tentaciones_marlly.php',
                dirname(__DIR__) . '/config/database.php',
            ];

            foreach ($databaseFiles as $databaseFile) {
                if (file_exists($databaseFile)) {
                    require_once $databaseFile;
                    if (class_exists($databaseClass, false)) {
                        break;
                    }
                }
            }
        }

        if (!class_exists($databaseClass, false)) {
            throw new RuntimeException("The class {$databaseClass} is not defined.");
        }

        /** @var mixed $database */
        $database = new $databaseClass();
        $this->conn = $database->getConnection();
    }

    /**
     * Create a purchase record.
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
     * Fetch all purchase history with supplier names.
     */
    public function obtenerTodas(): PDOStatement
    {
        $sql = "SELECT c.id_compra, c.fecha_hora, c.metodo_pago, c.estado,
                       c.total_compra, c.id_proveedor, p.nombre_proveedor
                FROM {$this->tabla} c
                LEFT JOIN proveedor p ON p.id_proveedor = c.id_proveedor
                ORDER BY c.fecha_hora DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Fetch a specific purchase by ID.
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
     * Filter purchase history by supplier ID.
     */
    public function obtenerPorProveedor(int $id_proveedor): PDOStatement
    {
        $sql = "SELECT id_compra, fecha_hora, metodo_pago, estado, total_compra, id_proveedor
                FROM {$this->tabla}
                WHERE id_proveedor = :id_proveedor
                ORDER BY fecha_hora DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_proveedor', $id_proveedor, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Update an existing purchase record.
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
     * Update only the status of a purchase.
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
     * Delete a purchase record by ID.
     */
    public function eliminar(int $id_compra): bool
    {
        $sql = "DELETE FROM {$this->tabla} WHERE id_compra = :id_compra";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_compra', $id_compra, PDO::PARAM_INT);

        return $stmt->execute();
    }
}