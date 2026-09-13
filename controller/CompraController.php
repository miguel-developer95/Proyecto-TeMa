<?php
declare(strict_types=1);

require_once __DIR__ . '/../model/proveedor.php';
require_once __DIR__ . '/../model/compra.php';
require_once __DIR__ . '/../model/producto.php';
require_once __DIR__ . '/../model/historial.php';

/** Compras y proveedores. Solo administradores (el router lo exige). */
class CompraController
{
    private Proveedor $proveedores;
    private Compra $compras;
    private Producto $productos;
    private Historial $historial;

    public function __construct()
    {
        $this->proveedores = new Proveedor();
        $this->compras = new Compra();
        $this->productos = new Producto();
        $this->historial = new Historial();
    }

    /* ---------- Proveedores ---------- */

    public function proveedorGuardar(): void
    {
        $id = (int) post('id_proveedor');
        $d = [
            'identificacion_nit' => trim((string) post('identificacion_nit')),
            'nombre_razon_social' => trim((string) post('nombre_razon_social')),
            'direccion' => trim((string) post('direccion')),
            'telefono' => trim((string) post('telefono')),
            'correo_electronico' => trim((string) post('correo_electronico')),
        ];
        if ($d['identificacion_nit'] === '' || $d['nombre_razon_social'] === '') {
            flash('error', 'NIT y nombre/razón social son obligatorios.');
            redirect('view/proveedores.php');
        }
        if ($d['correo_electronico'] !== '' && !filter_var($d['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Correo electrónico inválido.');
            redirect('view/proveedores.php');
        }
        if ($this->proveedores->existeIdentificacion($d['identificacion_nit'], $id > 0 ? $id : null)) {
            flash('error', 'Ese NIT ya está registrado.');
            redirect('view/proveedores.php');
        }

        if ($id > 0) {
            $ok = $this->proveedores->actualizar($id, $d);
            flash($ok ? 'success' : 'error', $ok ? 'Proveedor actualizado.' : 'No se pudo actualizar.');
        } else {
            $nuevo = $this->proveedores->registrar($d);
            flash($nuevo > 0 ? 'success' : 'error', $nuevo > 0 ? 'Proveedor registrado.' : 'No se pudo registrar.');
        }
        redirect('view/proveedores.php');
    }

    public function proveedorEstado(): void
    {
        $id = (int) post('id_proveedor');
        $estado = post('estado') === 'inactivo' ? 'inactivo' : 'activo';
        if ($id <= 0 || !$this->proveedores->cambiarEstado($id, $estado)) {
            flash('error', 'No se pudo cambiar el estado.');
        } else {
            flash('success', $estado === 'activo' ? 'Proveedor reactivado.' : 'Proveedor desactivado.');
        }
        redirect('view/proveedores.php');
    }

    /* ---------- Constructor de compra (líneas en sesión) ---------- */

    /** @return array<int, array{cantidad:int, precio:float}> */
    private function items(): array
    {
        return $_SESSION['compra_items'] ?? [];
    }

    private function guardarItems(array $items): void
    {
        $_SESSION['compra_items'] = $items;
    }

    public function compraItemAdd(): void
    {
        $id = (int) post('id_producto');
        $cant = (int) post('cantidad');
        $precio = (float) post('precio_compra');
        $prod = $id > 0 ? $this->productos->obtenerPorId($id) : false;

        if (!$prod || $prod['estado'] !== 'activo' || $cant <= 0 || $precio < 0) {
            flash('error', 'Producto o cantidad inválidos.');
            redirect('view/compras.php?nueva=1');
        }
        $items = $this->items();
        $items[$id] = [
            'cantidad' => ($items[$id]['cantidad'] ?? 0) + $cant,
            'precio' => $precio,
        ];
        $this->guardarItems($items);
        redirect('view/compras.php?nueva=1');
    }

    public function compraItemRemove(): void
    {
        $items = $this->items();
        unset($items[(int) post('id_producto')]);
        $this->guardarItems($items);
        redirect('view/compras.php?nueva=1');
    }

    public function compraGuardar(): void
    {
        $idProv = (int) post('id_proveedor');
        $idMetodo = post('id_metodo_pago') !== '' ? (int) post('id_metodo_pago') : null;
        $items = $this->items();

        $prov = $idProv > 0 ? $this->proveedores->obtenerPorId($idProv) : false;
        if (!$prov || $prov['estado'] !== 'activo') {
            flash('error', 'Selecciona un proveedor válido.');
            redirect('view/compras.php?nueva=1');
        }
        if (empty($items)) {
            flash('error', 'Agrega al menos un producto a la compra.');
            redirect('view/compras.php?nueva=1');
        }

        $lineas = [];
        foreach ($items as $idProd => $it) {
            $lineas[] = [
                'id_producto' => $idProd,
                'cantidad' => $it['cantidad'],
                'precio_unitario_compra' => $it['precio'],
            ];
        }
        $idCompra = $this->compras->crear($idProv, (int) (current_user()['id'] ?? 0), $lineas, $idMetodo);
        if ($idCompra > 0) {
            $this->guardarItems([]);
            $this->historial->registrar('crear_compra', "Compra #$idCompra a {$prov['nombre_razon_social']}", current_user()['id'] ?? null);
            flash('success', 'Compra registrada y stock actualizado.');
            redirect('view/compras.php?ver=' . $idCompra);
        }
        flash('error', 'No se pudo registrar la compra.');
        redirect('view/compras.php?nueva=1');
    }

    public function compraAnular(): void
    {
        $id = (int) post('id_compra');
        if ($id <= 0 || !$this->compras->anular($id, (int) (current_user()['id'] ?? 0))) {
            flash('error', 'No se pudo anular (verifica que haya stock suficiente para revertir).');
        } else {
            $this->historial->registrar('anular_compra', "Compra #$id", current_user()['id'] ?? null);
            flash('success', 'Compra anulada y stock revertido.');
        }
        redirect('view/compras.php?ver=' . $id);
    }

    /** RF 4.4: actualiza una compra registrada (proveedor, método y líneas). */
    public function compraActualizar(): void
    {
        $id = (int) post('id_compra');
        $idProv = (int) post('id_proveedor');
        $idMetodo = post('id_metodo_pago') !== '' ? (int) post('id_metodo_pago') : null;
        if ($id <= 0) {
            flash('error', 'Compra inválida.');
            redirect('view/compras.php');
        }
        $ids = (array) (post('linea_id') ?? []);
        $cants = (array) (post('linea_cantidad') ?? []);
        $precios = (array) (post('linea_precio') ?? []);
        $lineas = [];
        foreach ($ids as $i => $idProd) {
            $idProd = (int) $idProd;
            if ($idProd <= 0) {
                continue;
            }
            $lineas[] = [
                'id_producto' => $idProd,
                'cantidad' => (int) ($cants[$i] ?? 0),
                'precio_unitario_compra' => (float) ($precios[$i] ?? 0),
            ];
        }
        $res = $this->compras->actualizar($id, $idProv, $lineas, $idMetodo, (int) (current_user()['id'] ?? 0));
        if ($res['ok']) {
            $this->historial->registrar('editar_compra', "Compra #$id editada", current_user()['id'] ?? null);
            flash('success', 'Compra actualizada y stock ajustado.');
            redirect('view/compras.php?ver=' . $id);
        }
        flash('error', $res['error'] ?? 'No se pudo actualizar la compra.');
        redirect('view/compras.php?editar_compra=' . $id);
    }
}
