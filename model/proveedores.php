<?php

require_once __DIR__ . '/conexion.php';

class Proveedores
{
    private PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }
    /**
     * Registra un nuevo proveedor.
     *
     * @param array $datos Debe incluir: identificacion_nit, nombre_razon_social,
     *                      direccion, telefono, correo_electronico
     * @return int Id del proveedor insertado.
     */
    public function registrarProveedor(array $datos): int
    {
        $sql = "INSERT INTO PROVEEDORES (
                    identificacion_nit, nombre_razon_social, direccion,
                    telefono, correo_electronico, fecha_registro, estado
                ) VALUES (
                    :identificacionNit, :nombreRazonSocial, :direccion,
                    :telefono, :correoElectronico, NOW(), 'activo'
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':identificacionNit' => $datos['identificacion_nit'],
            ':nombreRazonSocial' => $datos['nombre_razon_social'],
            ':direccion'         => $datos['direccion'],
            ':telefono'          => $datos['telefono'],
            ':correoElectronico' => $datos['correo_electronico'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Verifica si un NIT/identificación ya está registrado, para evitar duplicados.
     */
    public function existeIdentificacion(string $identificacionNit, ?int $idProveedorExcluir = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM PROVEEDORES WHERE identificacion_nit = :identificacionNit";
        $params = [':identificacionNit' => $identificacionNit];

        if ($idProveedorExcluir !== null) {
            $sql .= " AND id_proveedor != :idProveedorExcluir";
            $params[':idProveedorExcluir'] = $idProveedorExcluir;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $resultado['total'] > 0;
    }

    /**
     * Actualiza los datos de un proveedor existente.
     */
    public function actualizarProveedor(int $idProveedor, array $datos): bool
    {
        $sql = "UPDATE PROVEEDORES
                SET nombre_razon_social = :nombreRazonSocial,
                    direccion = :direccion,
                    telefono = :telefono,
                    correo_electronico = :correoElectronico
                WHERE id_proveedor = :idProveedor";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nombreRazonSocial' => $datos['nombre_razon_social'],
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
        $sql = "UPDATE PROVEEDORES SET estado = 'inactivo' WHERE id_proveedor = :idProveedor";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':idProveedor' => $idProveedor]);
    }

    /**
     * Reactiva un proveedor previamente desactivado.
     */
    public function activarProveedor(int $idProveedor): bool
    {
        $sql = "UPDATE PROVEEDORES SET estado = 'activo' WHERE id_proveedor = :idProveedor";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':idProveedor' => $idProveedor]);
    }

    /**
     * Lista los proveedores, filtrando opcionalmente por estado.
     *
     * @param string|null $estado 'activo' | 'inactivo' | null (todos)
     */
    public function listarProveedores(?string $estado = 'activo'): array
    {
        $sql = "SELECT * FROM PROVEEDORES";
        $params = [];

        if ($estado !== null) {
            $sql .= " WHERE estado = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY nombre_razon_social ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un proveedor por su ID, para asociarlo a un producto o a una compra.
     */
    public function obtenerPorId(int $idProveedor): ?array
    {
        $sql = "SELECT * FROM PROVEEDORES WHERE id_proveedor = :idProveedor";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idProveedor' => $idProveedor]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Búsqueda de proveedores por nombre o razón social (para selección rápida
     * al registrar una compra o un producto).
     */
    public function buscarPorNombre(string $texto): array
    {
        $sql = "SELECT * FROM PROVEEDORES
                WHERE nombre_razon_social LIKE :texto AND estado = 'activo'
                ORDER BY nombre_razon_social ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':texto' => '%' . $texto . '%']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}