<?php
// model/venta_pausada.php
require_once __DIR__ . '/../config/connection.php';

class VentaPausada
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = (new Connection())->conn;
    }

    /**
     * Lista las últimas ventas pausadas de un usuario, incluyendo el nombre del cliente si aplica.
     */
    public function listar(int $idUsuario, int $limite = 10): array
    {
        $sql = "SELECT vp.*, c.nombre AS cliente
                FROM ventas_pausadas vp
                LEFT JOIN clientes c ON c.id_cliente = vp.id_cliente
                WHERE vp.id_usuario = :id_usuario
                ORDER BY vp.creada_en DESC
                LIMIT :limite";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una venta pausada específica por su id.
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ventas_pausadas WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Guarda una nueva venta pausada. $items debe ser un array asociativo [id_producto => cantidad].
     * Devuelve el id insertado, o false si falla.
     */
    public function guardar(int $idUsuario, array $items, ?string $etiqueta = null, ?int $idCliente = null)
    {
        $sql = "INSERT INTO ventas_pausadas (id_usuario, etiqueta, id_cliente, items, creada_en)
                VALUES (:id_usuario, :etiqueta, :id_cliente, :items, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':etiqueta', $etiqueta, $etiqueta === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':id_cliente', $idCliente, $idCliente === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':items', json_encode($items, JSON_UNESCAPED_UNICODE));

        if ($stmt->execute()) {
            return (int) $this->pdo->lastInsertId();
        }

        return false;
    }

    /**
     * Elimina una venta pausada (al recuperarla o descartarla).
     */
    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM ventas_pausadas WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Cuenta el número de pausas activas de un usuario.
     */
    public function contarPorUsuario(int $idUsuario): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM ventas_pausadas WHERE id_usuario = :id_usuario");
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Alias compatible con VentaController.
     */
    public function crear(string $etiqueta, array $items, ?int $idCliente = null, ?int $idUsuario = null)
    {
        $idUsuario = $idUsuario ?: (int) ($_SESSION['user']['id'] ?? 1);
        return $this->guardar($idUsuario, $items, $etiqueta, $idCliente);
    }

    /**
     * Decodifica el JSON de items guardado en la BD a un array asociativo [id_producto => cantidad].
     */
    public static function decodificarItems(string $itemsJson): array
    {
        $decoded = json_decode($itemsJson, true);
        return is_array($decoded) ? $decoded : [];
    }
}