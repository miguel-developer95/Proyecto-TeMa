<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/funciones.php';
require_once __DIR__ . '/../model/venta.php';
require_once __DIR__ . '/../model/producto.php';
require_once __DIR__ . '/../model/cliente.php';
require_once __DIR__ . '/../model/metodos_pago.php';
require_once __DIR__ . '/../model/ventas_pausadas.php';
require_once __DIR__ . '/../model/historial_modificacion.php';

/** Punto de venta y anulaciones. Roles vendedor/cajero/administrador. */
class VentaController
{
    private Venta $ventas;
    private Producto $productos;
    private Historial $historial;

    public function __construct()
    {
        $this->ventas = new Venta();
        $this->productos = new Producto();
        $this->historial = new Historial();
    }

    /** Compara el estado de un producto sin importar mayúsculas/minúsculas ni espacios. */
    private function estaActivo(array $p): bool
    {
        return strtolower(trim((string) ($p['estado'] ?? ''))) === 'activo';
    }

    /** @return array<int, int> id_producto => cantidad */
    private function carrito(): array
    {
        return $_SESSION['carrito'] ?? [];
    }

    private function guardarCarrito(array $cart): void
    {
        $_SESSION['carrito'] = $cart;
    }

    /** RF 5.1: agregar por código de barras exacto o por id. */
    public function posAdd(): void
    {
        $busqueda = trim((string) post('busqueda'));
        $cant = max(1, (int) post('cantidad'));
        $prod = false;

        $idDirecto = (int) post('id_producto');
        if ($idDirecto > 0) {
            $prod = $this->productos->obtenerPorId($idDirecto);
        } elseif ($busqueda !== '') {
            $prod = $this->productos->obtenerPorCodigoBarras($busqueda);
            if (!$prod) {
                $posibles = $this->productos->listar('activo', $busqueda);
                $prod = $posibles[0] ?? false;
            }
        }

        if (!$prod || !$this->estaActivo($prod)) {
            flash('error', 'Producto no encontrado o inactivo.');
            redirect('view/pos.php');
        }

        $id = (int) $prod['id_producto'];
        $cart = $this->carrito();
        $nuevaCant = ($cart[$id] ?? 0) + $cant;
        if (!$this->productos->hayStock($id, $nuevaCant)) {
            flash('error', 'Stock insuficiente para "' . $prod['nombre'] . '" (disponible: ' . $prod['cantidad_stock'] . ').');
            redirect('view/pos.php');
        }
        $cart[$id] = $nuevaCant;
        $this->guardarCarrito($cart);
        redirect('view/pos.php');
    }

    public function posSetQty(): void
    {
        $id = (int) post('id_producto');
        $cant = (int) post('cantidad');
        $cart = $this->carrito();
        if (!isset($cart[$id])) {
            redirect('view/pos.php');
        }
        if ($cant <= 0) {
            unset($cart[$id]);
        } elseif ($this->productos->hayStock($id, $cant)) {
            $cart[$id] = $cant;
        } else {
            flash('error', 'Stock insuficiente para esa cantidad.');
            redirect('view/pos.php');
        }
        $this->guardarCarrito($cart);
        redirect('view/pos.php');
    }

    public function posRemove(): void
    {
        $cart = $this->carrito();
        unset($cart[(int) post('id_producto')]);
        $this->guardarCarrito($cart);
        redirect('view/pos.php');
    }

    public function posClear(): void
    {
        $this->guardarCarrito([]);
        redirect('view/pos.php');
    }

    /** RF 5.4: pausar la venta en curso (múltiple, persistente en BD, máx 10 por usuario). */
    public function posPause(): void
    {
        $cart = $this->carrito();
        if (empty($cart)) {
            flash('error', 'No hay venta en curso para pausar.');
            redirect('view/pos.php');
        }
        $yo = (int) (current_user()['id'] ?? 0);
        $pausas = new VentaPausada();
        // Migrar pausa legacy en sesión (una sola vez)
        if (!empty($_SESSION['venta_pausada']['items'])) {
            $pausas->crear(
                'Pausa ' . ($_SESSION['venta_pausada']['hora'] ?? date('H:i')),
                $_SESSION['venta_pausada']['items'],
                null, $yo ?: null
            );
            unset($_SESSION['venta_pausada']);
        }
        if ($pausas->contarPorUsuario($yo) >= 10) {
            flash('error', 'Tienes 10 ventas pausadas (límite). Recupera o descarta alguna.');
            redirect('view/pos.php');
        }
        $etiqueta = trim((string) post('etiqueta'));
        if ($etiqueta === '') {
            $etiqueta = 'Pausa ' . date('H:i') . ' (' . count($cart) . ' prod.)';
        }
        $pausas->crear($etiqueta, $cart, !empty($_SESSION['pos_cliente']) ? (int) $_SESSION['pos_cliente'] : null, $yo ?: null);
        $this->guardarCarrito([]);
        unset($_SESSION['pos_cliente']);
        flash('success', 'Venta pausada como "' . $etiqueta . '".');
        redirect('view/pos.php');
    }

    /** RF 5.4: recuperar una venta pausada por id (o legacy si no hay id). */
    public function posResume(): void
    {
        $id = (int) post('id_pausada');
        if (!empty($this->carrito())) {
            flash('error', 'Termina o vacía la venta actual antes de recuperar la pausada.');
            redirect('view/pos.php');
        }
        if ($id > 0) {
            $pausas = new VentaPausada();
            $row = $pausas->obtenerPorId($id);
            if (!$row) {
                flash('error', 'Pausa no encontrada.');
                redirect('view/pos.php');
            }
            $items = VentaPausada::decodificarItems((string) $row['items']);
            if (empty($items)) {
                flash('error', 'La pausa está vacía o corrupta.');
                redirect('view/pos.php');
            }
            $this->guardarCarrito($items);
            $_SESSION['pos_cliente'] = !empty($row['id_cliente']) ? (int) $row['id_cliente'] : null;
            $pausas->eliminar($id);
            flash('success', 'Venta "' . $row['etiqueta'] . '" recuperada.');
            redirect('view/pos.php');
        }
        // Compatibilidad: pausa legacy en sesión
        if (empty($_SESSION['venta_pausada']['items'])) {
            flash('error', 'No hay ninguna venta pausada.');
            redirect('view/pos.php');
        }
        $this->guardarCarrito($_SESSION['venta_pausada']['items']);
        unset($_SESSION['venta_pausada']);
        flash('success', 'Venta recuperada.');
        redirect('view/pos.php');
    }

    /** RF 5.4: descartar una pausa sin recuperarla. */
    public function posPausadaDelete(): void
    {
        $id = (int) post('id_pausada');
        if ($id > 0) {
            (new VentaPausada())->eliminar($id);
            flash('success', 'Pausa descartada.');
        }
        redirect('view/pos.php');
    }

    /** Cliente rápido desde el POS (nombre + correo opcional). */
    public function clienteRapido(): void
    {
        $nombre = trim((string) post('nombre'));
        $correo = trim((string) post('correo'));
        if ($nombre === '') {
            flash('error', 'El nombre del cliente es obligatorio.');
            redirect('view/pos.php');
        }
        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Correo del cliente inválido.');
            redirect('view/pos.php');
        }
        $id = (new Cliente())->crear($nombre, $correo ?: null);
        if ($id > 0) {
            $_SESSION['pos_cliente'] = $id;
            flash('success', 'Cliente registrado y seleccionado.');
        } else {
            flash('error', 'No se pudo registrar el cliente.');
        }
        redirect('view/pos.php');
    }

    public function posCliente(): void
    {
        $_SESSION['pos_cliente'] = post('id_cliente') !== '' ? (int) post('id_cliente') : null;
        redirect('view/pos.php');
    }

    /**
     * RF 5.2/5.3/5.6: finaliza la venta. Los precios se leen de la BD,
     * nunca del formulario.
     */
    public function checkout(): void
    {
        $cart = $this->carrito();
        if (empty($cart)) {
            flash('error', 'El carrito está vacío.');
            redirect('view/pos.php');
        }

        $idMetodo = (int) post('id_metodo_pago');
        $metodo = (new MetodoPago())->obtenerPorId($idMetodo);
        if (!$metodo || strtolower(trim((string) ($metodo['estado'] ?? ''))) !== 'activo') {
            flash('error', 'Selecciona un método de pago válido.');
            redirect('view/pos.php');
        }

        $valorRecibido = (float) post('valor_recibido');
        $idCliente = !empty($_SESSION['pos_cliente']) ? (int) $_SESSION['pos_cliente'] : null;
        $empresa = strtolower((string) $metodo['nombre_metodo']) === 'efectivo'
            ? null
            : (string) ($metodo['empresa'] ?? $metodo['nombre_metodo']);

        $items = [];
        foreach ($cart as $idProd => $cant) {
            $p = $this->productos->obtenerPorId($idProd);
            if (!$p || !$this->estaActivo($p)) {
                flash('error', 'Un producto del carrito ya no está disponible.');
                redirect('view/pos.php');
            }
            $items[] = [
                'id_producto' => $idProd,
                'cantidad' => $cant,
                'precio_unitario' => (float) $p['precio_venta'],
            ];
        }

        $idVenta = $this->ventas->crear(
            $items, $idMetodo, $valorRecibido,
            (int) (current_user()['id'] ?? 0), $idCliente, $empresa
        );

        if ($idVenta > 0) {
            $this->guardarCarrito([]);
            unset($_SESSION['pos_cliente']);
            $this->historial->registrar('crear_venta', "Venta #$idVenta", current_user()['id'] ?? null);
            redirect('view/recibo.php?id=' . $idVenta);
        }
        flash('error', 'No se pudo procesar la venta (verifica stock y valor recibido).');
        redirect('view/pos.php');
    }

    /** RF 5.5: anular con motivo obligatorio y reintegro de stock. */
    public function anular(): void
    {
        $id = (int) post('id_venta');
        $motivo = trim((string) post('motivo'));
        if ($id <= 0 || $motivo === '') {
            flash('error', 'Debes indicar el motivo de la anulación.');
            redirect('view/pos.php');
        }
        if ($this->ventas->anular($id, $motivo, (int) (current_user()['id'] ?? 0))) {
            $this->historial->registrar('anular_venta', "Venta #$id: $motivo", current_user()['id'] ?? null);
            flash('success', 'Venta anulada y stock reintegrado.');
        } else {
            flash('error', 'No se pudo anular la venta.');
        }
        redirect('view/pos.php');
    }
}