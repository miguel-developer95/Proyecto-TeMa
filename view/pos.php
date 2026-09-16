<?php
// view/pos.php - RF 5.x: punto de venta (escaneo/búsqueda, carrito, pago, pausar, anular).
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../helpers/auth_guard.php';
verificarRol(['vendedor', 'administrador', 'cajero']);
require_once __DIR__ . '/../helpers/funciones.php';

require_once __DIR__ . '/../model/producto.php';
require_once __DIR__ . '/../model/cliente.php';
require_once __DIR__ . '/../model/metodos_pago.php';
require_once __DIR__ . '/../model/venta.php';
require_once __DIR__ . '/../model/ventas_pausadas.php';

$prodModel = new Producto();
$cart = $_SESSION['carrito'] ?? [];
$lineas = [];
$total = 0.0;
foreach ($cart as $idProd => $cant) {
    $p = $prodModel->obtenerPorId((int) $idProd);
    if (!$p || strtolower(trim((string) ($p['estado'] ?? ''))) !== 'activo') {
        continue;
    }
    $sub = $cant * (float) $p['precio_venta'];
    $total += $sub;
    $lineas[] = ['producto' => $p, 'cantidad' => $cant, 'subtotal' => $sub];
}

$busqueda = trim((string) get('buscar'));
$resultados = $busqueda !== '' ? $prodModel->listar('activo', $busqueda) : [];

$clientes = (new Cliente())->obtenerTodos();
$metodos = (new MetodosPago())->listarMetodosPagoActivos();
$pausada = $_SESSION['venta_pausada'] ?? null;
$pausasDb = (new VentaPausada())->listar((int) (current_user()['id'] ?? 0), 10);
$ventasHoy = (new Venta())->obtenerTodas(20);

$titulo = 'Punto de Venta';
$subtitulo = 'Escanea el código de barras o busca el producto manualmente para agregarlo a la venta';
require __DIR__ . '/partials/head.php';
?>

<div class="pos-container">


    <?php if ($flashMsg = flash()): ?>
        <div class="alert alert-<?= $flashMsg['tipo'] === 'success' ? 'success' : 'error' ?>">
            <i class="fa-solid <?= $flashMsg['tipo'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
            <?= e($flashMsg['texto']) ?>
        </div>
    <?php endif; ?>

    <!-- Grid superior para balancear el ancho de los paneles (RF 5.1 & RF 5.3) -->
    <div class="pos-grid-top">

        <!-- Panel 1: Registro y Carrito de Venta (Izquierda, 1.35fr) -->
        <section class="content-box panel-pos-venta">
            <div>
                <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px;">
                    <i class="fa-solid fa-cart-shopping" style="color: #e63c82;"></i> Venta en Curso (<?= e(money($total)) ?>)
                </h3>

                <!-- Búsqueda y agregado de productos -->
                <div style="background: #fff8fb; padding: 16px; border-radius: 14px; border: 1px solid #fce4ec; margin-bottom: 20px;">
                    <label style="font-weight: 700; font-size: 13px; color: #4a5568; display: flex; align-items: center; gap: 6px; margin-bottom: 10px;">
                        <i class="fa-solid fa-barcode" style="color: #e63c82;"></i> Agregar producto
                    </label>

                    <form action="<?= e(base_url('index.php')) ?>" method="POST" class="search-form" style="margin-bottom: 10px;">
                        <input type="hidden" name="action" value="pos_add">
                        <?= csrf_field() ?>
                        <input type="text" name="busqueda" placeholder="Código de barras..." autofocus autocomplete="off">
                        <input type="number" name="cantidad" value="1" min="1" class="qty-input" title="Cantidad">
                        <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Agregar</button>
                    </form>

                    <form method="GET" class="search-form" style="margin-bottom: 0;">
                        <input type="text" name="buscar" placeholder="Búsqueda manual por nombre o código..." value="<?= e($busqueda) ?>" autocomplete="off">
                        <button type="submit" class="btn-primary btn-cancel"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
                    </form>
                </div>

                <?php if ($busqueda !== ''): ?>
                    <div style="margin-bottom: 20px;">
                        <h4 style="color:#2b3a55; font-size:14px; margin-bottom:8px; font-weight:700;">
                            <i class="fa-solid fa-magnifying-glass" style="color: #e63c82;"></i> Resultados de búsqueda: "<?= e($busqueda) ?>"
                        </h4>
                        <div class="table-responsive">
                            <table style="min-width: 480px;">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th style="text-align: right;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultados as $r): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e($r['nombre']) ?></strong><br>
                                                <small class="muted"><?= e($r['codigo_barras'] ?? '') ?></small>
                                            </td>
                                            <td><?= e(money($r['precio_venta'])) ?></td>
                                            <td><?= e($r['cantidad_stock']) ?></td>
                                            <td class="actions">
                                                <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                                                    <input type="hidden" name="action" value="pos_add">
                                                    <input type="hidden" name="id_producto" value="<?= e($r['id_producto']) ?>">
                                                    <input type="hidden" name="cantidad" value="1">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="action-btn btn-edit" title="Agregar al carrito"><i class="fa-solid fa-plus"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($resultados)): ?>
                                        <tr>
                                            <td colspan="4" class="muted" style="text-align:center; padding:15px;">Sin resultados para "<?= e($busqueda) ?>".</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Lista de ítems en carrito -->
                <div style="margin-top: 10px;">
                    <label style="font-weight: 700; font-size: 13px; color: #4a5568; display: flex; align-items: center; gap: 6px; margin-bottom: 10px;">
                        <i class="fa-solid fa-basket-shopping" style="color: #e63c82;"></i> Ítems agregados
                    </label>

                    <?php if (empty($lineas)): ?>
                        <div style="text-align:center; padding: 35px 20px; background: #fff8fb; border-radius: 12px; border: 1px dashed #fce4ec; color: #888;">
                            <i class="fa-solid fa-cart-shopping" style="font-size: 28px; color: #f3c6d8; margin-bottom: 8px; display: block;"></i>
                            El carrito está vacío. Escanea el código o busca productos arriba para comenzar la venta.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table style="min-width: 520px;">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>P. unit</th>
                                        <th>Cant.</th>
                                        <th>Subtotal</th>
                                        <th style="text-align: right;">Acciones</th>
                                    </tr>
                                </thead>
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
                                                    <button type="submit" class="action-btn btn-edit" title="Actualizar"><i class="fa-solid fa-check"></i></button>
                                                </form>
                                            </td>
                                            <td style="font-weight: 700; color: #e63c82;"><?= e(money($l['subtotal'])) ?></td>
                                            <td class="actions">
                                                <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                                                    <input type="hidden" name="action" value="pos_remove">
                                                    <input type="hidden" name="id_producto" value="<?= e($p['id_producto']) ?>">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="action-btn btn-delete" title="Quitar producto"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:18px; flex-wrap:wrap; gap:10px;">
                            <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form" onsubmit="return confirm('¿Vaciar todo el carrito?');">
                                <input type="hidden" name="action" value="pos_clear">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn-secondary"><i class="fa-solid fa-trash-arrow-up"></i> Vaciar carrito</button>
                            </form>
                            <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form" style="display:flex; gap:8px; flex-wrap:wrap;">
                                <input type="hidden" name="action" value="pos_pause">
                                <?= csrf_field() ?>
                                <input type="text" name="etiqueta" placeholder="Etiqueta (opcional)" maxlength="60" style="padding:8px 12px; border:1.5px solid #f3c6d8; border-radius:8px; font-size:13px;">
                                <button type="submit" class="btn-secondary"><i class="fa-solid fa-pause"></i> Pausar venta</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($pausasDb)): ?>
                    <div style="margin-top:25px; border-top:1px solid #fce4ec; padding-top:16px;">
                        <h4 style="color:#2b3a55; font-size:14px; margin-bottom:12px; font-weight:700;"><i class="fa-solid fa-pause" style="color:#e63c82;"></i> Ventas pausadas (<?= count($pausasDb) ?>/10)</h4>
                        <div class="table-responsive">
                            <table style="min-width: 480px;">
                                <thead>
                                    <tr>
                                        <th>Etiqueta</th>
                                        <th>Cliente</th>
                                        <th>Ítems</th>
                                        <th>Hora</th>
                                        <th style="text-align: right;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pausasDb as $pz):
                                        $nItems = count(VentaPausada::decodificarItems((string) $pz['items'])); ?>
                                        <tr>
                                            <td><strong><?= e($pz['etiqueta']) ?></strong></td>
                                            <td><?= e($pz['cliente'] ?? '—') ?></td>
                                            <td><?= e($nItems) ?></td>
                                            <td><?= e(substr($pz['creada_en'], 11, 5)) ?></td>
                                            <td class="actions">
                                                <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
                                                    <input type="hidden" name="action" value="pos_resume">
                                                    <input type="hidden" name="id_pausada" value="<?= e($pz['id']) ?>">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn-secondary" style="padding:4px 10px; font-size:12px;">Recuperar</button>
                                                </form>
                                                <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form" onsubmit="return confirm('¿Descartar esta pausa?');">
                                                    <input type="hidden" name="action" value="pos_pausada_delete">
                                                    <input type="hidden" name="id_pausada" value="<?= e($pz['id']) ?>">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="action-btn btn-delete" title="Descartar"><i class="fa-solid fa-xmark"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </section>

        <!-- Panel 2: Cobro y Finalización (Derecha, 1fr) -->
        <section class="content-box panel-pos-cobro">
            <div>
                <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px;">
                    <i class="fa-solid fa-cash-register" style="color: #e63c82;"></i> Cobro y Finalización
                </h3>

                <form action="<?= e(base_url('index.php')) ?>" method="POST" id="checkoutForm">
                    <input type="hidden" name="action" value="checkout">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label><i class="fa-solid fa-user"></i> Cliente (opcional)</label>
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
                        <label><i class="fa-solid fa-credit-card"></i> Método de pago *</label>
                        <select name="id_metodo_pago" required>
                            <option value="">-- Selecciona método --</option>
                            <?php foreach ($metodos as $m): ?>
                                <option value="<?= e($m['id_metodo_pago']) ?>">
                                    <?= e($m['nombre_metodo']) ?><?= !empty($m['empresa']) ? ' (' . e($m['empresa']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fa-solid fa-money-bill-transfer"></i> Pagado *</label>
                        <select name="pagado" id="pagadoSelect" required>
                            <option value="si" selected>Sí (Pagado)</option>
                            <option value="no">No (Pendiente de pago / Fiado)</option>
                        </select>
                    </div>

                    <div class="totals-box">
                        <div class="totals-row total-destacado">
                            <span>Total a pagar:</span>
                            <span><?= e(money($total)) ?></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fa-solid fa-hand-holding-dollar"></i> Valor recibido ($) *</label>
                        <input type="number" name="valor_recibido" id="valorRecibido" min="0" step="0.01" required data-total="<?= e($total) ?>" placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label><i class="fa-solid fa-coins"></i> Cambio / Vueltas</label>
                        <input type="text" id="cambio" value="<?= e(money(0)) ?>" disabled style="font-weight:bold; color:#2e7d32; background:#e8f5e9;">
                    </div>

                    <button type="submit" class="btn-primary btn-block" <?= empty($lineas) ? 'disabled style="opacity:0.6; cursor:not-allowed;"' : '' ?>>
                        <i class="fa-solid fa-check"></i> Finalizar Venta
                    </button>
                </form>

                <details class="quick-client">
                    <summary><i class="fa-solid fa-user-plus"></i> Registrar cliente rápido</summary>
                    <form action="<?= e(base_url('index.php')) ?>" method="POST">
                        <input type="hidden" name="action" value="cliente_rapido">
                        <?= csrf_field() ?>
                        <input type="text" name="nombre" placeholder="Nombre completo *" required maxlength="150">
                        <input type="email" name="correo" placeholder="Correo electrónico (opcional)" maxlength="120">
                        <button type="submit" class="btn-primary" style="width:100%; margin-top:8px;">Guardar y seleccionar</button>
                    </form>
                </details>
            </div>
        </section>

    </div>

    <!-- Panel 3: Historial de Ventas Recientes (Full-Width, espejo de Compras) -->
    <section class="content-box" style="margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-clock-rotate-left" style="color: #e63c82;"></i> Ventas Recientes de Hoy
            </h3>
            <span style="font-size: 13px; color: #888888; font-weight: 600;">
                Total: <?= count($ventasHoy) ?> ventas registradas
            </span>
        </div>

        <div class="table-responsive">
            <table class="tabla-ventas">
                <thead>
                    <tr>
                        <th>Recibo</th>
                        <th>Fecha / Hora</th>
                        <th>Cliente</th>
                        <th>Método de Pago</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ventasHoy)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; color:#888; padding: 25px;">
                                <i class="fa-solid fa-receipt" style="font-size: 24px; color: #f3c6d8; display: block; margin-bottom: 8px;"></i>
                                No hay ventas registradas hoy.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ventasHoy as $v): ?>
                            <tr>
                                <td style="font-weight: 700; color: #2b3a55;">
                                    <?= e($v['numero_recibo'] ?? ('#' . $v['id_venta'])) ?>
                                </td>
                                <td><?= e($v['fecha'] ?? '') ?></td>
                                <td style="font-weight: 600;"><?= e($v['cliente'] ?? 'Consumidor final') ?></td>
                                <td>
                                    <?php 
                                        $metodoTxt = !empty($v['nombre_metodo']) ? ucfirst($v['nombre_metodo']) : (!empty($v['empresa']) ? ucfirst($v['empresa']) : 'Efectivo');
                                        if (!empty($v['metodo_empresa']) && strtolower($v['metodo_empresa']) !== strtolower($v['nombre_metodo'])) {
                                            $metodoTxt .= ' (' . e($v['metodo_empresa']) . ')';
                                        }
                                        echo e($metodoTxt);
                                    ?>
                                </td>
                                <td style="font-weight: 700; color: #e63c82;"><?= e(money($v['total'])) ?></td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                        <span class="badge-estado <?= ($v['estado'] === 'completada') ? 'badge-completada' : 'badge-anulada' ?>">
                                            <i class="fa-solid <?= ($v['estado'] === 'completada') ? 'fa-check' : 'fa-ban' ?>"></i>
                                            <?= e(ucfirst($v['estado'])) ?>
                                        </span>
                                        <span class="badge-estado <?= (($v['pagado'] ?? 'si') === 'si') ? 'badge-completada' : 'badge-nopagado' ?>" style="font-size: 11px; padding: 2px 8px;">
                                            <i class="fa-solid <?= (($v['pagado'] ?? 'si') === 'si') ? 'fa-circle-check' : 'fa-clock' ?>"></i>
                                            <?= (($v['pagado'] ?? 'si') === 'si') ? 'Pagado' : 'No pagado' ?>
                                        </span>
                                    </div>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <div class="acciones-venta">
                                        <a class="btn-action-edit" href="<?= e(base_url('view/recibo.php?id=' . $v['id_venta'])) ?>" title="Ver e imprimir recibo">
                                            <i class="fa-solid fa-receipt"></i> Recibo
                                        </a>
                                        <?php if ($v['estado'] === 'completada'): ?>
                                            <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form"
                                                  onsubmit="const m = prompt('Motivo de la anulación (obligatorio):'); if (!m) return false; this.motivo.value = m; return true;">
                                                <input type="hidden" name="action" value="venta_anular">
                                                <input type="hidden" name="id_venta" value="<?= e($v['id_venta']) ?>">
                                                <input type="hidden" name="motivo" value="">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn-action-cancel" title="Anular venta">
                                                    <i class="fa-solid fa-ban"></i> Anular
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
    const recibido = document.getElementById('valorRecibido');
    const cambio = document.getElementById('cambio');
    const fmt = n => '$ ' + n.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    if (recibido && cambio) {
        recibido.addEventListener('input', () => {
            const total = parseFloat(recibido.dataset.total) || 0;
            const val = parseFloat(recibido.value) || 0;
            cambio.value = fmt(Math.max(0, val - total));
        });
    }

    const pagadoSelect = document.getElementById('pagadoSelect');
    if (pagadoSelect && recibido) {
        pagadoSelect.addEventListener('change', function () {
            const total = parseFloat(recibido.dataset.total) || 0;
            if (this.value === 'no') {
                if (!recibido.value || parseFloat(recibido.value) === total) {
                    recibido.value = '0';
                    cambio.value = fmt(0);
                }
            } else {
                if (recibido.value === '0' || recibido.value === '0.00' || !recibido.value) {
                    recibido.value = total > 0 ? total : '';
                    if (total > 0) cambio.value = fmt(0);
                }
            }
        });
    }

    const clienteSelect = document.getElementById('clienteSelect');
    if (clienteSelect) {
        clienteSelect.addEventListener('change', function () {
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = '<?= e(base_url('index.php')) ?>';
            f.innerHTML = '<input type="hidden" name="action" value="pos_cliente">' +
                '<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">' +
                '<input type="hidden" name="id_cliente" value="' + this.value + '">';
            document.body.appendChild(f);
            f.submit();
        });
    }
</script>

<?php require __DIR__ . '/partials/foot.php'; ?>
