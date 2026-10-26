<?php
// controller/CompraController.php
require_once __DIR__ . '/../model/compra.php';
require_once __DIR__ . '/../model/detalle_compra.php';
require_once __DIR__ . '/../model/proveedores.php';
require_once __DIR__ . '/../model/historial_modificacion.php';
require_once __DIR__ . '/../config/connection.php';

/**
 * Controlador del módulo de Compras (RF 4.1 - RF 4.4).
 */
class CompraController
{
    private Compra $compraModel;
    private DetalleCompra $detalleModel;
    private Proveedores $proveedorModel;
    private HistorialModificacion $historialModel;
    private PDO $db;

    public function __construct()
    {
        $this->compraModel = new Compra();
        $this->detalleModel = new DetalleCompra();
        $this->proveedorModel = new Proveedores();
        $this->historialModel = new HistorialModificacion();
        $this->db = (new Connection())->conn;
    }

    /**
     * RF 4.1 — Registra una compra completa: cabecera + líneas de detalle.
     */
    public function registrarCompra(array $datos): array
    {
        if (empty($datos['items'])) {
            return ['success' => false, 'id_compra' => null, 'error' => 'La compra debe tener al menos un producto.'];
        }

        $total = 0.0;
        foreach ($datos['items'] as $item) {
            $total += $item['cantidad_producto'] * $item['precio_unitario'];
        }

        $this->compraModel->fecha_hora = date('Y-m-d H:i:s');
        $this->compraModel->id_metodo_pago = (int) ($datos['metodo_pago'] ?? 1);
        $this->compraModel->metodo_pago = $this->compraModel->id_metodo_pago;
        $this->compraModel->estado = 'registrada';
        $this->compraModel->total_compra = $total;
        $this->compraModel->id_proveedor = (int) $datos['id_proveedor'];

        if (!$this->compraModel->crear()) {
            return ['success' => false, 'id_compra' => null, 'error' => 'No se pudo registrar la compra.'];
        }

        $id_compra = $this->compraModel->id_compra;

        if (!$this->detalleModel->agregarDetalles($id_compra, $datos['items'])) {
            return ['success' => false, 'id_compra' => $id_compra, 'error' => 'La compra se creó pero falló el registro de sus productos.'];
        }

        return ['success' => true, 'id_compra' => $id_compra, 'error' => null];
    }

    /**
     * RF 4.2 — Registra un nuevo proveedor.
     */
    public function registrarProveedor(array $datos): array
    {
        if ($this->proveedorModel->existeNit($datos['nit'])) {
            return ['success' => false, 'error' => 'Ya existe un proveedor registrado con ese NIT.'];
        }

        $id = $this->proveedorModel->registrarProveedor($datos);
        return ['success' => $id > 0, 'id_proveedor' => $id, 'error' => null];
    }

    /**
     * RF 4.3 — Historial de compras, con opción de filtrar por proveedor.
     */
    public function listarHistorial(?int $id_proveedor = null): array
    {
        if ($id_proveedor !== null) {
            return $this->compraModel->obtenerPorProveedor($id_proveedor);
        }

        return $this->compraModel->obtenerTodas();
    }

    /**
     * Detalle completo de una compra: cabecera + líneas de producto.
     */
    public function obtenerCompraCompleta(int $id_compra): ?array
    {
        $cabecera = $this->compraModel->obtenerPorId($id_compra);
        if (!$cabecera) {
            return null;
        }

        $cabecera['detalle'] = $this->detalleModel->obtenerPorCompra($id_compra);
        return $cabecera;
    }

    /**
     * RF 4.4 — Modifica una compra existente y deja registro en el historial.
     */
    public function modificarCompra(int $id_compra, array $datosNuevos, int $id_usuario): array
    {
        $compraActual = $this->compraModel->obtenerPorId($id_compra);
        if (!$compraActual) {
            return ['success' => false, 'error' => 'La compra no existe.'];
        }

        $this->compraModel->id_compra = $id_compra;
        $this->compraModel->fecha_hora = $compraActual['fecha_hora'];
        $this->compraModel->id_metodo_pago = (int) ($datosNuevos['metodo_pago'] ?? $compraActual['id_metodo_pago'] ?? 1);
        $this->compraModel->metodo_pago = $this->compraModel->id_metodo_pago;
        $this->compraModel->estado = $datosNuevos['estado'] ?? $compraActual['estado'];
        $this->compraModel->total_compra = $datosNuevos['total_compra'] ?? $compraActual['total_compra'];
        $this->compraModel->id_proveedor = $datosNuevos['id_proveedor'] ?? $compraActual['id_proveedor'];

        if (!$this->compraModel->actualizar()) {
            return ['success' => false, 'error' => 'No se pudo actualizar la compra.'];
        }

        $cambios = [];
        foreach (['metodo_pago', 'estado', 'total_compra', 'id_proveedor'] as $campo) {
            if (isset($datosNuevos[$campo]) && $datosNuevos[$campo] != $compraActual[$campo]) {
                $cambios[$campo] = ['antes' => $compraActual[$campo], 'despues' => $datosNuevos[$campo]];
            }
        }

        if (!empty($cambios)) {
            $this->historialModel->registrarCambioCompra($id_compra, $cambios, $id_usuario);
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * Anula una compra, revierte el stock ingresado y mantiene la trazabilidad.
     */
    public function anularCompra(int $id_compra, int $id_usuario): array
    {
        $compraActual = $this->compraModel->obtenerPorId($id_compra);
        if (!$compraActual) {
            return ['success' => false, 'error' => 'La compra no existe.'];
        }

        if (strtolower(trim((string)$compraActual['estado'])) === 'anulada') {
            return ['success' => false, 'error' => 'La compra ya se encuentra anulada.'];
        }

        try {
            $this->db->beginTransaction();

            if (!$this->compraModel->actualizarEstado($id_compra, 'anulada')) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'No se pudo anular la compra.'];
            }

            // Revertir el stock que fue agregado por esta compra
            $detalles = $this->detalleModel->obtenerPorCompra($id_compra);
            $stmtRevert = $this->db->prepare(
                "UPDATE productos SET cantidad_stock = GREATEST(0, cantidad_stock - :cant) WHERE id_producto = :id_prod"
            );

            foreach ($detalles as $d) {
                $cant = (int) ($d['cantidad'] ?? $d['cantidad_producto'] ?? 0);
                $idProd = (int) ($d['id_producto'] ?? 0);
                if ($cant > 0 && $idProd > 0) {
                    $stmtRevert->execute([
                        ':cant'    => $cant,
                        ':id_prod' => $idProd
                    ]);
                }
            }

            $this->historialModel->registrarCambioCompra(
                $id_compra,
                ['estado' => ['antes' => $compraActual['estado'], 'despues' => 'anulada']],
                $id_usuario
            );

            $this->db->commit();
            return ['success' => true, 'error' => null];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => 'Error al revertir stock: ' . $e->getMessage()];
        }
    }
}
