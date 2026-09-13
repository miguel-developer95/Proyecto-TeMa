<?php
// RF 5.x: punto de venta (escaneo/búsqueda, carrito, pago, pausar, anular).
require_once __DIR__ . '/../config/config.php';
require_role(['vendedor', 'cajero', 'administrador']);

require_once __DIR__ . '/../model/producto.php';
require_once __DIR__ . '/../model/cliente.php';
require_once __DIR__ . '/../model/metodo_pago.php';
require_once __DIR__ . '/../model/venta.php';
require_once __DIR__ . '/../model/venta_pausada.php';

$prodModel = new Producto();
$cart = $_SESSION['carrito'] ?? [];
$lineas = [];
$total = 0.0;
foreach ($cart as $idProd => $cant) {
    $p = $prodModel->obtenerPorId((int) $idProd);
    if (!$p || $p['estado'] !== 'activo') {
        continue;
    }
    $sub = $cant * (float) $p['precio_venta'];
    $total += $sub;
    $lineas[] = ['producto' => $p, 'cantidad' => $cant, 'subtotal' => $sub];
}

$busqueda = trim((string) get('buscar'));
$resultados = $busqueda !== '' ? $prodModel->listar('activo', $busqueda) : [];

$clientes = (new Cliente())->obtenerTodos();
$metodos = (new MetodoPago())->listarActivos();
$pausada = $_SESSION['venta_pausada'] ?? null;
$pausasDb = (new VentaPausada())->listar((int) (current_user()['id'] ?? 0), 10);
$ventasHoy = (new Venta())->obtenerTodas(20);

$titulo = 'Ventas';
require __DIR__ . '/partials/head.php';
?>

<?php $tituloNavbar = 'Punto de Venta'; $subtituloNavbar = 'Escanea el código de barras o busca el producto manualmente.'; require __DIR__ . '/partials/navbar.php'; ?>

<div class="pos-grid">
    <div>
        <div class="crud-card">
            <h3><i class="fa-solid fa-barcode"></i> Agregar producto</h3>
            <form action="<?= e(base_url('index.php')) ?>" method="POST" class="search-form">
                <input type="hidden" name="action" value="pos_add">
                <?= csrf_field() ?>
                <input type="text" name="busqueda" placeholder="Código de barras..." autofocus autocomplete="off">
                <input type="number" name="cantidad" value="1" min="1" class="qty-input" title="Cantidad">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Agregar</button>
            </form>
            <form method="GET" class="search-form">
                <input type="text" name="buscar" placeholder="Búsqueda manual por nombre..." value="<?= e($busqueda) ?>" autocomplete="off">
                <button type="submit" class="btn-primary btn-cancel"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            </form>
            <?php if ($busqueda !== ''): ?>
                <table>
                    <thead><tr><th>Producto</th><th>Precio</th><th>Stock</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($resultados as $r): ?>
                            <tr>
                                <td><strong><?= e($r['nombre']) ?></strong><br><small class="muted"><?= e($r['codigo_barras'] ?? '') ?></small></td>
                                <td><?= e(money($r['precio_venta'])) ?></td>
                                <td><?= e($r['cantidad_stock']) ?></td>
                                <td>
                                    <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="pos_add">
                                        <input type="hidden" name="id_producto" value="<?= e($r['id_producto']) ?>">
                                        <input type="hidden" name="cantidad" value="1">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="action-btn btn-edit"><i class="fa-solid fa-plus"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($resultados)): ?>
                            <tr><td colspan="4" class="muted">Sin resultados para "<?= e($busqueda) ?>".</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="crud-card">
            <h3><i class="fa-solid fa-cart-shopping"></i> Venta en curso (<?= e(money($total)) ?>)</h3>
            <?php if (empty($lineas)): ?>
                <p class="muted">El carrito está vacío.</p>
            <?php else: ?>
                <div class="table-responsive">
                <table>
                    <thead><tr><th>Producto</th><th>P. unit</th><th>Cant.</th><th>Subtotal</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($lineas as $l): $p = $l['producto']; ?>
                            <tr>
                                <td><strong><?= e($p['nombre']) ?></strong></td>
                                <td><?= e(money($p['precio_venta'])) ?></td>
                                <td>
                                    <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="pos_qty">
                                        <input type="hidden" name="id_producto" value="<?= e($p['id_producto']) ?>">
                                        <?= csrf_field() ?>
                                        <input type="number" name="cantidad" value="<?= e($l['cantidad']) ?>" min="0" max="<?= e($p['cantidad_stock']) ?>" class="qty-input">
                                        <button type="submit" class="action-btn btn-edit"><i class="fa-solid fa-check"></i></button>
                                    </form>
                                </td>
                                <td><?= e(money($l['subtotal'])) ?></td>
                                <td>
                                    <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="pos_remove">
                                        <input type="hidden" name="id_producto" value="<?= e($p['id_producto']) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="action-btn btn-delete"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <div class="pos-actions">
                    <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                        <input type="hidden" name="action" value="pos_clear">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-primary btn-cancel">Vaciar</button>
                    </form>
                    <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                        <input type="hidden" name="action" value="pos_pause">
                        <?= csrf_field() ?>
                        <input type="text" name="etiqueta" placeholder="Etiqueta (opcional)" maxlength="60" style="max-width:160px">
                        <button type="submit" class="btn-primary btn-cancel"><i class="fa-solid fa-pause"></i> Pausar venta</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($pausada): ?>
                <p class="flash flash-info">
                    Hay una venta pausada anterior (<?= e($pausada['hora']) ?>, <?= e(count($pausada['items'])) ?> productos).
                    <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                        <input type="hidden" name="action" value="pos_resume">
                        <?= csrf_field() ?>
                        <button type="submit" class="action-btn btn-edit">Recuperar</button>
                    </form>
                </p>
            <?php endif; ?>
            <?php if (!empty($pausasDb)): ?>
                <div class="crud-card" style="margin-top:10px">
                    <h3><i class="fa-solid fa-pause"></i> Ventas pausadas (<?= e(count($pausasDb)) ?>/10)</h3>
                    <table>
                        <thead><tr><th>Etiqueta</th><th>Cliente</th><th>Ítems</th><th>Hora</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($pausasDb as $pz):
                                $nItems = count(VentaPausada::decodificarItems((string) $pz['items'])); ?>
                                <tr>
                                    <td><strong><?= e($pz['etiqueta']) ?></strong></td>
                                    <td><?= e($pz['cliente'] ?? '—') ?></td>
                                    <td><?= e($nItems) ?></td>
                                    <td><?= e($pz['creada_en']) ?></td>
                                    <td class="actions">
                                        <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                                            <input type="hidden" name="action" value="pos_resume">
                                            <input type="hidden" name="id_pausada" value="<?= e($pz['id']) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="action-btn btn-edit">Recuperar</button>
                                        </form>
                                        <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form"
                                              onsubmit="return confirm('¿Descartar esta pausa?');">
                                            <input type="hidden" name="action" value="pos_pausada_delete">
                                            <input type="hidden" name="id_pausada" value="<?= e($pz['id']) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="action-btn btn-delete"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="crud-card">
            <h3><i class="fa-solid fa-cash-register"></i> Cobro</h3>
            <form action="<?= e(base_url('index.php')) ?>" method="POST" id="checkoutForm">
                <input type="hidden" name="action" value="checkout">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Cliente (opcional)</label>
                    <select name="id_cliente" id="clienteSelect">
                        <option value="">— Consumidor final —</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= e($c['id_cliente']) ?>" <?= ((int) ($_SESSION['pos_cliente'] ?? 0) === (int) $c['id_cliente']) ? 'selected' : '' ?>>
                                <?= e($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Método de pago *</label>
                    <select name="id_metodo_pago" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($metodos as $m): ?>
                            <option value="<?= e($m['id_metodo_pago']) ?>"><?= e($m['nombre_metodo']) ?><?= $m['empresa'] ? ' - ' . e($m['empresa']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Total a pagar</label>
                    <input type="text" value="<?= e(money($total)) ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Valor recibido *</label>
                    <input type="number" name="valor_recibido" id="valorRecibido" min="0" step="0.01" required data-total="<?= e($total) ?>">
                </div>
                <div class="form-group">
                    <label>Cambio (vueltas)</label>
                    <input type="text" id="cambio" value="<?= e(money(0)) ?>" disabled>
                </div>
                <button type="submit" class="btn-primary btn-block" <?= empty($lineas) ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-check"></i> Finalizar venta
                </button>
            </form>
            <details class="quick-client">
                <summary>Registrar cliente rápido</summary>
                <form action="<?= e(base_url('index.php')) ?>" method="POST">
                    <input type="hidden" name="action" value="cliente_rapido">
                    <?= csrf_field() ?>
                    <input type="text" name="nombre" placeholder="Nombre *" required maxlength="150">
                    <input type="email" name="correo" placeholder="Correo (opcional)" maxlength="120">
                    <button type="submit" class="btn-primary">Guardar y seleccionar</button>
                </form>
            </details>
        </div>

        <div class="crud-card">
            <h3>Ventas recientes</h3>
            <table>
                <thead><tr><th>Recibo</th><th>Total</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    <?php foreach (array_slice($ventasHoy, 0, 8) as $v): ?>
                        <tr>
                            <td><?= e($v['numero_recibo'] ?? ('#' . $v['id_venta'])) ?></td>
                            <td><?= e(money($v['total'])) ?></td>
                            <td><span class="badge badge-<?= e($v['estado']) ?>"><?= e($v['estado']) ?></span></td>
                            <td class="actions">
                                <a class="action-btn btn-edit" href="<?= e(base_url('view/recibo.php?id=' . $v['id_venta'])) ?>">Ver</a>
                                <?php if ($v['estado'] === 'completada'): ?>
                                    <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form"
                                          onsubmit="const m = prompt('Motivo de la anulación (obligatorio):'); if (!m) return false; this.motivo.value = m; return true;">
                                        <input type="hidden" name="action" value="venta_anular">
                                        <input type="hidden" name="id_venta" value="<?= e($v['id_venta']) ?>">
                                        <input type="hidden" name="motivo" value="">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="action-btn btn-delete">Anular</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const recibido = document.getElementById('valorRecibido');
    const cambio = document.getElementById('cambio');
    const fmt = n => '$ ' + n.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    recibido.addEventListener('input', () => {
        const total = parseFloat(recibido.dataset.total) || 0;
        const val = parseFloat(recibido.value) || 0;
        cambio.value = fmt(Math.max(0, val - total));
    });
    document.getElementById('clienteSelect').addEventListener('change', function () {
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = '<?= e(base_url('index.php')) ?>';
        f.innerHTML = '<input type="hidden" name="action" value="pos_cliente">' +
            '<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">' +
            '<input type="hidden" name="id_cliente" value="' + this.value + '">';
        document.body.appendChild(f);
        f.submit();
    });
</script>

<?php require __DIR__ . '/partials/foot.php'; ?>
