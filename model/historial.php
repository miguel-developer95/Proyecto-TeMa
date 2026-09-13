<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Connection.php';

/** Auditoría de acciones relevantes. Tabla: historial. */
class Historial
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    public function registrar(string $tipo, string $detalle, ?int $idUsuario): void
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO historial (tipo_accion, detalle_cambio, id_usuario)
                 VALUES (:t, :d, :u)"
            );
            $stmt->execute([
                ':t' => mb_substr($tipo, 0, 60),
                ':d' => mb_substr($detalle, 0, 500),
                ':u' => $idUsuario,
            ]);
        } catch (PDOException $e) {
            // La auditoría nunca debe romper la operación principal.
            error_log('Historial falló: ' . $e->getMessage());
        }
    }

    public function obtenerTodos(int $limite = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT h.*, u.username FROM historial h
             LEFT JOIN usuarios u ON u.id = h.id_usuario
             ORDER BY h.fecha_accion DESC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
