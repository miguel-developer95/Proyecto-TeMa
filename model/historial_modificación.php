<?php
// model/historial_modificacion.php
require_once __DIR__ . '/../config/conexion.php';

class HistorialModificacion
{
    /** @var PDO */
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    /**
     * Create a new log entry in the modification history.
     */
    public function crear(string $fecha_accion, string $tipo_accion, string $detalle_cambio, int $id_usuario): bool
    {
        try {
            $query = "INSERT INTO historial_modificacion (fecha_accion, tipo_accion, detalle_cambio, id_usuario)
                      VALUES (:fecha_accion, :tipo_accion, :detalle_cambio, :id_usuario)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":fecha_accion", $fecha_accion);
            $stmt->bindParam(":tipo_accion", $tipo_accion);
            $stmt->bindParam(":detalle_cambio", $detalle_cambio);
            $stmt->bindParam(":id_usuario", $id_usuario);
            return $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Get the total count of modification records.
     */
    public function contarModificaciones(): int
    {
        $query = "SELECT COUNT(*) as total FROM historial_modificacion";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Fetch all modification logs (including user details).
     */
    public function obtenerTodos(): array
    {
        $query = "SELECT h.id_modificacion, h.fecha_accion, h.tipo_accion, h.detalle_cambio, h.id_usuario,
                         u.username AS usuario
                  FROM historial_modificacion h
                  LEFT JOIN usuarios u ON u.id = h.id_usuario
                  ORDER BY h.fecha_accion DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a specific modification log by ID.
     * 
     * @return array|false
     */
    public function obtenerPorId(int $id_modificacion)
    {
        $query = "SELECT id_modificacion, fecha_accion, tipo_accion, detalle_cambio, id_usuario
                  FROM historial_modificacion WHERE id_modificacion = :id_modificacion";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_modificacion", $id_modificacion);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Filter modification logs by user ID.
     */
    public function obtenerPorUsuario(int $id_usuario): array
    {
        $query = "SELECT id_modificacion, fecha_accion, tipo_accion, detalle_cambio, id_usuario
                  FROM historial_modificacion WHERE id_usuario = :id_usuario ORDER BY fecha_accion DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Filter modification logs by action type (e.g., 'create', 'update', 'delete').
     */
    public function obtenerPorTipoAccion(string $tipo_accion): array
    {
        $query = "SELECT id_modificacion, fecha_accion, tipo_accion, detalle_cambio, id_usuario
                  FROM historial_modificacion WHERE tipo_accion = :tipo_accion ORDER BY fecha_accion DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":tipo_accion", $tipo_accion);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update an existing modification log record.
     */
    public function actualizar(int $id_modificacion, string $fecha_accion, string $tipo_accion, string $detalle_cambio, int $id_usuario): bool
    {
        try {
            $query = "UPDATE historial_modificacion
                      SET fecha_accion = :fecha_accion, tipo_accion = :tipo_accion,
                          detalle_cambio = :detalle_cambio, id_usuario = :id_usuario
                      WHERE id_modificacion = :id_modificacion";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":fecha_accion", $fecha_accion);
            $stmt->bindParam(":tipo_accion", $tipo_accion);
            $stmt->bindParam(":detalle_cambio", $detalle_cambio);
            $stmt->bindParam(":id_usuario", $id_usuario);
            $stmt->bindParam(":id_modificacion", $id_modificacion);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete a modification log record by ID.
     */
    public function eliminar(int $id_modificacion): bool
    {
        $query = "DELETE FROM historial_modificacion WHERE id_modificacion = :id_modificacion";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_modificacion", $id_modificacion);
        return $stmt->execute();
    }
}