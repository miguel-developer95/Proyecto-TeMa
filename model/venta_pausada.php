<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Connection.php';

/**
 * Ventas pausadas persistentes (RF 5.4).
 * items: {id_producto: cantidad} serializado en JSON.
 */
class VentaPausada
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Connection())->conn;
    }

    public function crear(string $etiqueta, array $items, ?int $idCliente, ?int $idUsuario): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ventas_pausadas (etiqueta, items, id_cliente, id_usuario)
             VALUES (:e, :items, :c, :u)"
        );
        $stmt->execute([
            ':e' => mb_substr(trim($etiqueta) !== '' ? trim($etiqueta) : 'Pausa ' . date('H:i'), 0, 60),
            ':items' => json_encode($items, JSON_THROW_ON_ERROR),
            ':c' => $idCliente,
            ':u' => $idUsuario,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function listar(?int $idUsuario = null, int $limite = 10): array
    {
        if ($idUsuario !== null) {
            $stmt = $this->db->prepare(
                "SELECT v.*, c.nombre AS cliente FROM ventas_pausadas v
                 LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
                 WHERE v.id_usuario = :u OR v.id_usuario IS NULL
                 ORDER BY v.id DESC LIMIT :lim"
            );
            $stmt->bindValue(':u', $idUsuario, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        $stmt = $this->db->prepare(
            "SELECT v.*, c.nombre AS cliente FROM ventas_pausadas v
             LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
             ORDER BY v.id DESC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ventas_pausadas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function contarPorUsuario(int $idUsuario): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ventas_pausadas WHERE id_usuario = :u");
        $stmt->execute([':u' => $idUsuario]);
        return (int) $stmt->fetchColumn();
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM ventas_pausadas WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /** Decodifica items JSON a [id_producto => cantidad] validado. */
    public static function decodificarItems(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return [];
        }
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $id => $cant) {
            $id = (int) $id;
            $cant = (int) $cant;
            if ($id > 0 && $cant > 0) {
                $out[$id] = $cant;
            }
        }
        return $out;
    }
}
