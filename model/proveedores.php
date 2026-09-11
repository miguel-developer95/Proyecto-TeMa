<?php
// model/proveedores.php
require_once __DIR__ . '/../config/connection.php';

class Proveedores
{
    /** @var PDO */
    private PDO $conn;
    private string $tabla = 'proveedores';

    public function __construct()
    {
        $this->conn = (new Connection())->conn;
    }

    /**
     * Registra un nuevo proveedor (RF 4.2).
     *
     * @param array $datos Debe incluir: nom_proveedor, nit, direccion,
     *                      telefono, correo_electronico
     * @return int Id del proveedor insertado.
     */
    public function registrarProveedor(array $datos): int
    {
        $sql = "INSERT INTO {$this->tabla} (
                    nom_proveedor, nit, direccion, telefono, correo_electronico, estado
                ) VALUES (
                    :nomProveedor, :nit, :direccion, :telefono, :correoElectronico, 'activo'
                )";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':nomProveedor'      => $datos['nom_proveedor'],
            ':nit'               => $datos['nit'],
            ':direccion'         => $datos['direccion'],
            ':telefono'          => $datos['telefono'],
            ':correoElectronico' => $datos['correo_electronico'],
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Verifica si un NIT ya está registrado, para evitar duplicados.
     */
    public function existeNit(string $nit, ?int $idProveedorExcluir = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->tabla} WHERE nit = :nit";
        $params = [':nit' => $nit];

        if ($idProveedorExcluir !== null) {
            $sql .= " AND id_proveedor != :idProveedorExcluir";
            $params[':idProveedorExcluir'] = $idProveedorExcluir;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $resultado['total'] > 0;
    }

    /**
     * Actualiza los datos de un proveedor existente.
     */
    public function actualizarProveedor(int $idProveedor, array $datos): bool
    {
        $sql = "UPDATE {$this->tabla}
                SET nom_proveedor = :nomProveedor,
                    direccion = :direccion,
                    telefono = :telefono,
                    correo_electronico = :correoElectronico
                WHERE id_proveedor = :idProveedor";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':nomProveedor'      => $datos['nom_proveedor'],
            ':direccion'         => $datos['direccion'],
            ':telefono'          => $datos['telefono'],
            ':correoElectronico' => $datos['correo_electronico'],
            ':idProveedor'       => $idProveedor,
        ]);
    }

    /**
     * Desactiva un proveedor (eliminación lógica), manteniendo su historial
     * de compras asociadas intacto.
     */
    public function desactivarProveedor(int $idProveedor): bool
    {
        $sql = "UPDATE {$this->tabla} SET estado = 'inactivo' WHERE id_proveedor = :idProveedor";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':idProveedor' => $idProveedor]);
    }

    /**
     * Reactiva un proveedor previamente desactivado.
     */
    public function activarProveedor(int $idProveedor): bool
    {
        $sql = "UPDATE {$this->tabla} SET estado = 'activo' WHERE id_proveedor = :idProveedor";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':idProveedor' => $idProveedor]);
    }

    /**
     * Lista los proveedores, filtrando opcionalmente por estado.
     *
     * @param string|null $estado 'activo' | 'inactivo' | null (todos)
     */
    public function listarProveedores(?string $estado = 'activo'): array
    {
        $sql = "SELECT * FROM {$this->tabla}";
        $params = [];

        if ($estado !== null) {
            $sql .= " WHERE estado = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY nom_proveedor ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un proveedor por su ID, para asociarlo a un producto o a una compra.
     */
    public function obtenerPorId(int $idProveedor): ?array
    {
        $sql = "SELECT * FROM {$this->tabla} WHERE id_proveedor = :idProveedor";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':idProveedor' => $idProveedor]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Búsqueda de proveedores por nombre (para selección rápida al registrar
     * una compra o un producto).
     */
    public function buscarPorNombre(string $texto): array
    {
        $sql = "SELECT * FROM {$this->tabla}
                WHERE nom_proveedor LIKE :texto AND estado = 'activo'
                ORDER BY nom_proveedor ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':texto' => '%' . $texto . '%']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}