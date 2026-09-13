<?php
declare(strict_types=1);

require_once __DIR__ . '/../model/producto.php';
require_once __DIR__ . '/../model/proveedor.php';
require_once __DIR__ . '/../model/historial.php';

/** Solo administradores (el router lo exige). */
class ProductoController
{
    private Producto $model;
    private Historial $historial;

    public function __construct()
    {
        $this->model = new Producto();
        $this->historial = new Historial();
    }

    private function validar(array $d, ?int $id = null): ?string
    {
        if (trim((string) ($d['nombre'] ?? '')) === '') {
            return 'El nombre del producto es obligatorio.';
        }
        if (!is_numeric($d['precio_compra'] ?? null) || (float) $d['precio_compra'] < 0
            || !is_numeric($d['precio_venta'] ?? null) || (float) $d['precio_venta'] < 0) {
            return 'Los precios deben ser números mayores o iguales a cero.';
        }
        if (!empty($d['id_proveedor'])) {
            $prov = (new Proveedor())->obtenerPorId((int) $d['id_proveedor']);
            if (!$prov || $prov['estado'] !== 'activo') {
                return 'El proveedor seleccionado no es válido.';
            }
        }
        $codigo = trim((string) ($d['codigo_barras'] ?? ''));
        if ($codigo !== '' && $this->model->existeCodigoBarras($codigo, $id)) {
            return 'El código de barras ya está registrado en otro producto.';
        }
        return null;
    }

    public function guardar(): void
    {
        $d = $_POST;
        if (($error = $this->validar($d)) !== null) {
            flash('error', $error);
            redirect('view/inventario.php');
        }
        $id = $this->model->registrar($d);
        if ($id > 0) {
            $this->historial->registrar('crear_producto', 'Producto #' . $id . ': ' . $d['nombre'], current_user()['id'] ?? null);
            flash('success', 'Producto registrado.');
        } else {
            flash('error', 'No se pudo registrar el producto.');
        }
        redirect('view/inventario.php');
    }

    public function actualizar(): void
    {
        $id = (int) post('id_producto');
        $d = $_POST;
        if ($id <= 0) {
            flash('error', 'Producto inválido.');
            redirect('view/inventario.php');
        }
        if (($error = $this->validar($d, $id)) !== null) {
            flash('error', $error);
            redirect('view/inventario.php?edit=' . $id);
        }
        $ok = $this->model->actualizar($id, $d, (int) (current_user()['id'] ?? 0));
        if ($ok) {
            $this->historial->registrar('actualizar_producto', 'Producto #' . $id, current_user()['id'] ?? null);
        }
        flash($ok ? 'success' : 'error', $ok ? 'Producto actualizado.' : 'No se pudo actualizar.');
        redirect('view/inventario.php');
    }

    public function estado(): void
    {
        $id = (int) post('id_producto');
        $estado = post('estado') === 'inactivo' ? 'inactivo' : 'activo';
        if ($id <= 0 || !$this->model->cambiarEstado($id, $estado, (int) (current_user()['id'] ?? 0))) {
            flash('error', 'No se pudo cambiar el estado.');
        } else {
            $this->historial->registrar('estado_producto', "Producto #$id -> $estado", current_user()['id'] ?? null);
            flash('success', $estado === 'activo' ? 'Producto reactivado.' : 'Producto descontinuado.');
        }
        redirect('view/inventario.php');
    }
}
