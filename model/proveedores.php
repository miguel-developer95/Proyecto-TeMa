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
     * Soporta tanto la nomenclatura nueva (nombre_razon_social, identificacion_nit)
     * como la anterior (nom_proveedor, nit).
     */
    public function registrarProveedor(array $datos): int
    {
        $nombre = trim($datos['nombre_razon_social'] ?? $datos['nom_proveedor'] ?? '');
        $nit = trim($datos['identificacion_nit'] ?? $datos['nit'] ?? '');
        $direccion = trim($datos['direccion'] ?? '');
        $telefono = trim($datos['telefono'] ?? '');
        $correo = trim($datos['correo_electronico'] ?? '');

        $sql = "INSERT INTO {$this->tabla} (
                    nombre_razon_social, identificacion_nit, direccion, telefono, correo_electronico, estado
                ) VALUES (
                    :nombreRazonSocial, :nit, :direccion, :telefono, :correoElectronico, 'activo'
                )";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':nombreRazonSocial' => $nombre,
            ':nit'               => $nit,
            ':direccion'         => $direccion,
            ':telefono'          => $telefono,
            ':correoElectronico' => $correo,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * Verifica si un NIT ya está registrado, para evitar duplicados.
     */
    public function existeNit(string $nit, ?int $idProveedorExcluir = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->tabla} WHERE identificacion_nit = :nit";
        $params = [':nit' => $nit];

        if ($idProveedorExcluir !== null) {
            $sql .= " AND id_proveedor != :idProveedorExcluir";
            $params[':idProveedorExcluir'] = $idProveedorExcluir;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return ((int) ($resultado['total'] ?? 0)) > 0;
    }

    /**
     * Actualiza los datos de un proveedor existente.
     */
    public function actualizarProveedor(int $idProveedor, array $datos): bool
    {
        $nombre = trim($datos['nombre_razon_social'] ?? $datos['nom_proveedor'] ?? '');
        $nit = trim($datos['identificacion_nit'] ?? $datos['nit'] ?? '');
        $direccion = trim($datos['direccion'] ?? '');
        $telefono = trim($datos['telefono'] ?? '');
        $correo = trim($datos['correo_electronico'] ?? '');

        $sql = "UPDATE {$this->tabla}
                SET nombre_razon_social = :nombreRazonSocial,
                    identificacion_nit = :nit,
                    direccion = :direccion,
                    telefono = :telefono,
                    correo_electronico = :correoElectronico
                WHERE id_proveedor = :idProveedor";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':nombreRazonSocial' => $nombre,
            ':nit'               => $nit,
            ':direccion'         => $direccion,
            ':telefono'          => $telefono,
            ':correoElectronico' => $correo,
            ':idProveedor'       => $idProveedor,
        ]);
    }

    /**
     * Desactiva un proveedor (eliminación lógica).
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
     * Retorna alias nom_proveedor y nit para retrocompatibilidad total.
     */
    public function listarProveedores(?string $estado = 'activo'): array
    {
        $sql = "SELECT id_proveedor, identificacion_nit, nombre_razon_social, direccion,
                       telefono, correo_electronico, estado, creado_en,
                       nombre_razon_social AS nom_proveedor, identificacion_nit AS nit
                FROM {$this->tabla}";
        $params = [];

        if ($estado !== null) {
            $sql .= " WHERE estado = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY nombre_razon_social ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un proveedor por su ID.
     */
    public function obtenerPorId(int $idProveedor): ?array
    {
        $sql = "SELECT id_proveedor, identificacion_nit, nombre_razon_social, direccion,
                       telefono, correo_electronico, estado, creado_en,
                       nombre_razon_social AS nom_proveedor, identificacion_nit AS nit
                FROM {$this->tabla}
                WHERE id_proveedor = :idProveedor";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':idProveedor' => $idProveedor]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Búsqueda de proveedores por nombre o NIT.
     */
    public function buscarPorNombre(string $texto): array
    {
        $sql = "SELECT id_proveedor, identificacion_nit, nombre_razon_social, direccion,
                       telefono, correo_electronico, estado, creado_en,
                       nombre_razon_social AS nom_proveedor, identificacion_nit AS nit
                FROM {$this->tabla}
                WHERE (nombre_razon_social LIKE :texto OR identificacion_nit LIKE :texto)
                  AND estado = 'activo'
                ORDER BY nombre_razon_social ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':texto' => '%' . $texto . '%']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}