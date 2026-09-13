<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Connection.php';

/**
 * Modelo de proveedores. Tabla: proveedores. (RF 4.2)
 */
class Proveedor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    public function registrar(array $d): int
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO proveedores (identificacion_nit, nombre_razon_social, direccion, telefono, correo_electronico, estado)
                 VALUES (:nit, :nombre, :dir, :tel, :correo, 'activo')"
            );
            $stmt->execute([
                ':nit' => trim((string) ($d['identificacion_nit'] ?? '')),
                ':nombre' => trim((string) ($d['nombre_razon_social'] ?? '')),
                ':dir' => trim((string) ($d['direccion'] ?? '')) ?: null,
                ':tel' => trim((string) ($d['telefono'] ?? '')) ?: null,
                ':correo' => trim((string) ($d['correo_electronico'] ?? '')) ?: null,
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function existeIdentificacion(string $nit, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM proveedores WHERE identificacion_nit = :nit";
        $params = [':nit' => $nit];
        if ($excluirId !== null) {
            $sql .= " AND id_proveedor != :excluir";
            $params[':excluir'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function actualizar(int $id, array $d): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE proveedores SET nombre_razon_social = :nombre, direccion = :dir,
                    telefono = :tel, correo_electronico = :correo WHERE id_proveedor = :id"
            );
            return $stmt->execute([
                ':nombre' => trim((string) ($d['nombre_razon_social'] ?? '')),
                ':dir' => trim((string) ($d['direccion'] ?? '')) ?: null,
                ':tel' => trim((string) ($d['telefono'] ?? '')) ?: null,
                ':correo' => trim((string) ($d['correo_electronico'] ?? '')) ?: null,
                ':id' => $id,
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /** Borrado lógico: activo/inactivo. */
    public function cambiarEstado(int $id, string $estado): bool
    {
        $estado = $estado === 'inactivo' ? 'inactivo' : 'activo';
        $stmt = $this->db->prepare("UPDATE proveedores SET estado = :e WHERE id_proveedor = :id");
        return $stmt->execute([':e' => $estado, ':id' => $id]);
    }

    public function listar(?string $estado = 'activo'): array
    {
        $sql = "SELECT * FROM proveedores";
        $params = [];
        if ($estado !== null) {
            $sql .= " WHERE estado = :e";
            $params[':e'] = $estado;
        }
        $sql .= " ORDER BY nombre_razon_social ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM proveedores WHERE id_proveedor = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function buscarPorNombre(string $texto): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM proveedores
             WHERE nombre_razon_social LIKE :t AND estado = 'activo'
             ORDER BY nombre_razon_social ASC"
        );
        $stmt->execute([':t' => '%' . $texto . '%']);
        return $stmt->fetchAll();
    }
}
