<?php
// controller/CompraController.php
require_once __DIR__ . '/../model/compra.php';
require_once __DIR__ . '/../model/detalle_compra.php';
require_once __DIR__ . '/../model/proveedores.php';
require_once __DIR__ . '/../model/historial_modificacion.php';

/**
 * Controlador del módulo de Compras (RF 4.1 - RF 4.4).
 *
 * Asume que hay una sesión activa con $_SESSION['user']['id_usuario']
 * (mismo patrón usado en dashboard.php, donde $_SESSION['user'] guarda
 * username y rol). Ajustar la clave si tu tabla `usuarios` la llama distinto.
 */
class CompraController
{
    private Compra $compraModel;
    private DetalleCompra $detalleModel;
    private Proveedores $proveedorModel;
    private HistorialModificacion $historialModel;

    public function __construct()
    {
        $this->compraModel = new Compra();
        $this->detalleModel = new DetalleCompra();
        $this->proveedorModel = new Proveedores();
        $this->historialModel = new HistorialModificacion();
    }

    /**
     * RF 4.1 — Registra una compra completa: cabecera + líneas de detalle.
     *
     * @param array $datos [
     *   'id_proveedor' => int,
     *   'metodo_pago' => string,
     *   'items' => [ ['codigo_barras' => ..., 'cantidad_producto' => ..., 'precio_unitario' => ...], ... ]
     * ]
     * @return array ['success' => bool, 'id_compra' => int|null, 'error' => string|null]
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
        $this->compraModel->metodo_pago = $datos['metodo_pago'];
        $this->compraModel->estado = 'registrada';
        $this->compraModel->total_compra = $total;
        $this->compraModel->id_proveedor = (int) $datos['id_proveedor'];

        if (!$this->compraModel->crear()) {
            return ['success' => false, 'id_compra' => null, 'error' => 'No se pudo registrar la compra.'];
        }

        $id_compra = $this->compraModel->id_compra;

        if (!$this->detalleModel->agregarDetalles($id_compra, $datos['items'])) {
            // La cabecera ya se creó; se deja registrado el fallo parcial para revisión manual.
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
     * RF 4.4 — Modifica una compra existente y deja registro en el
     * historial de trazabilidad (RI 4.4).
     *
     * @param array $datosNuevos ['metodo_pago' => ..., 'estado' => ..., 'total_compra' => ..., 'id_proveedor' => ...]
     */
    public function modificarCompra(int $id_compra, array $datosNuevos, int $id_usuario): array
    {
        $compraActual = $this->compraModel->obtenerPorId($id_compra);
        if (!$compraActual) {
            return ['success' => false, 'error' => 'La compra no existe.'];
        }

        $this->compraModel->id_compra = $id_compra;
        $this->compraModel->fecha_hora = $compraActual['fecha_hora'];
        $this->compraModel->metodo_pago = $datosNuevos['metodo_pago'] ?? $compraActual['metodo_pago'];
        $this->compraModel->estado = $datosNuevos['estado'] ?? $compraActual['estado'];
        $this->compraModel->total_compra = $datosNuevos['total_compra'] ?? $compraActual['total_compra'];
        $this->compraModel->id_proveedor = $datosNuevos['id_proveedor'] ?? $compraActual['id_proveedor'];

        if (!$this->compraModel->actualizar()) {
            return ['success' => false, 'error' => 'No se pudo actualizar la compra.'];
        }

        // Detecta y registra en el historial únicamente los campos que realmente cambiaron.
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
     * Anula una compra (cambia estado, no la borra) — mantiene el historial intacto.
     */
    public function anularCompra(int $id_compra, int $id_usuario): array
    {
        $compraActual = $this->compraModel->obtenerPorId($id_compra);
        if (!$compraActual) {
            return ['success' => false, 'error' => 'La compra no existe.'];
        }

        if (!$this->compraModel->actualizarEstado($id_compra, 'anulada')) {
            return ['success' => false, 'error' => 'No se pudo anular la compra.'];
        }

        $this->historialModel->registrarCambioCompra(
            $id_compra,
            ['estado' => ['antes' => $compraActual['estado'], 'despues' => 'anulada']],
            $id_usuario
        );

        return ['success' => true, 'error' => null];
    }
}