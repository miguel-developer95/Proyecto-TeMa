<?php
/**
 * model/metodos_pago.php
 * Sistema de Registro e Inventario Ke-rico - Tentaciones Marlly
 *
 * Módulo: Ventas -> Métodos de pago
 * Cubre: RF 5.3 (registro de pago en efectivo o transferencia digital,
 *        indicando la empresa cuando aplica: Nequi, Daviplata, Bancolombia, etc.)
 *
 * Patrón: MVC - Capa Modelo (acceso a datos con PDO)
 */

require_once __DIR__ . '/conexion.php';

class MetodosPago
{
    private PDO $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Registra un nuevo método de pago (ej: Efectivo, Transferencia - Nequi).
     *
     * @param string      $nombreMetodo 'efectivo' | 'transferencia'
     * @param string|null $empresa      Nombre de la entidad si es transferencia (ej: 'Nequi', 'Daviplata')
     */
    public function registrarMetodoPago(string $nombreMetodo, ?string $empresa = null): int
    {
        $sql = "INSERT INTO METODOS_PAGO (nombre_metodo, empresa, estado)
                VALUES (:nombreMetodo, :empresa, 'activo')";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nombreMetodo' => $nombreMetodo,
            ':empresa'      => $empresa,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza los datos de un método de pago existente.
     */
    public function actualizarMetodoPago(int $idMetodoPago, string $nombreMetodo, ?string $empresa, string $estado): bool
    {
        $sql = "UPDATE METODOS_PAGO
                SET nombre_metodo = :nombreMetodo,
                    empresa = :empresa,
                    estado = :estado
                WHERE id_metodo_pago = :idMetodoPago";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nombreMetodo'  => $nombreMetodo,
            ':empresa'       => $empresa,
            ':estado'        => $estado,
            ':idMetodoPago'  => $idMetodoPago,
        ]);
    }

    /**
     * Desactiva (eliminación lógica) un método de pago.
     */
    public function desactivarMetodoPago(int $idMetodoPago): bool
    {
        $sql = "UPDATE METODOS_PAGO SET estado = 'inactivo' WHERE id_metodo_pago = :idMetodoPago";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':idMetodoPago' => $idMetodoPago]);
    }

    /**
     * Lista todos los métodos de pago activos, para mostrarlos como opciones
     * en el módulo de Ventas (RF 5.3).
     */
    public function listarMetodosPagoActivos(): array
    {
        $sql = "SELECT id_metodo_pago, nombre_metodo, empresa
                FROM METODOS_PAGO
                WHERE estado = 'activo'
                ORDER BY nombre_metodo ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un método de pago por su ID.
     */
    public function obtenerPorId(int $idMetodoPago): ?array
    {
        $sql = "SELECT * FROM METODOS_PAGO WHERE id_metodo_pago = :idMetodoPago";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idMetodoPago' => $idMetodoPago]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ?: null;
    }

    /**
     * Calcula el cambio (vueltas) a entregar cuando el pago es en efectivo.
     * RI 5.3: "el valor recibido y el cálculo automático del cambio (vueltas)".
     *
     * @param float $valorRecibido
     * @param float $totalVenta
     * @return float Cambio a entregar (0 si el pago es exacto o digital).
     */
    public function calcularCambio(float $valorRecibido, float $totalVenta): float
    {
        $cambio = $valorRecibido - $totalVenta;
        return $cambio > 0 ? round($cambio, 2) : 0.0;
    }
}
