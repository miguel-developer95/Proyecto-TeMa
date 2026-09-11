<?php
// model/historial_modificacion.php
require_once __DIR__ . '/../config/connection.php';

class HistorialModificacion
{
    /** @var PDO */
    private PDO $conn;
    private string $tabla = 'historial_modificacion';

    public function __construct()
    {
        $this->conn = (new Connection())->conn;
    }

    /**
     * Registra un cambio en el historial (RI 4.4 — trazabilidad de modificaciones).
     *
     * NOTA: esta tabla no tiene columna id_compra, así que la referencia a
     * qué compra se modificó debe ir incluida dentro de $detalle_cambio
     * (ver registrarCambioCompra() más abajo, que arma ese texto por ti).
     */
    public function registrarCambio(string $tipo_accion, string $detalle_cambio, int $id_usuario): bool
    {
        $sql = "INSERT INTO {$this->tabla} (fecha_accion, tipo_accion, detalle_cambio, id_usuario)
                VALUES (NOW(), :tipo_accion, :detalle_cambio, :id_usuario)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':tipo_accion', $tipo_accion);
        $stmt->bindParam(':detalle_cambio', $detalle_cambio);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Helper específico para modificaciones de compras: arma el texto de
     * detalle_cambio incluyendo el id_compra, ya que la tabla no tiene
     * una columna dedicada para relacionarlo.
     *
     * @param array $cambios Ej: ['total_compra' => ['antes' => 500, 'despues' => 600]]
     */
    public function registrarCambioCompra(int $id_compra, array $cambios, int $id_usuario): bool
    {
        $partes = [];
        foreach ($cambios as $campo => $valores) {
            $partes[] = "{$campo}: '{$valores['antes']}' -> '{$valores['despues']}'";
        }

        $detalle = "Compra #{$id_compra} — " . implode('; ', $partes);

        return $this->registrarCambio('modificacion_compra', $detalle, $id_usuario);
    }

    /**
     * Obtiene el historial completo, con datos del usuario responsable.
     */
    public function obtenerTodo(): array
    {
        $sql = "SELECT h.id_modificacion, h.fecha_accion, h.tipo_accion, h.detalle_cambio, h.id_usuario
                FROM {$this->tabla} h
                ORDER BY h.fecha_accion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Filtra el historial por usuario responsable.
     */
    public function obtenerPorUsuario(int $id_usuario): array
    {
        $sql = "SELECT id_modificacion, fecha_accion, tipo_accion, detalle_cambio, id_usuario
                FROM {$this->tabla}
                WHERE id_usuario = :id_usuario
                ORDER BY fecha_accion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Filtra el historial por tipo de acción (ej. 'modificacion_compra',
     * 'anulacion_venta', etc.)
     */
    public function obtenerPorTipoAccion(string $tipo_accion): array
    {
        $sql = "SELECT id_modificacion, fecha_accion, tipo_accion, detalle_cambio, id_usuario
                FROM {$this->tabla}
                WHERE tipo_accion = :tipo_accion
                ORDER BY fecha_accion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':tipo_accion', $tipo_accion);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Búsqueda por texto libre dentro de detalle_cambio — útil, dado que
     * es la única forma de encontrar los cambios de una compra específica
     * (ej. buscarPorTexto("Compra #12")).
     */
    public function buscarPorTexto(string $texto): array
    {
        $sql = "SELECT id_modificacion, fecha_accion, tipo_accion, detalle_cambio, id_usuario
                FROM {$this->tabla}
                WHERE detalle_cambio LIKE :texto
                ORDER BY fecha_accion DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':texto' => '%' . $texto . '%']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}