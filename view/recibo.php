<?php
// RF 5.6: recibo electrónico imprimible de una venta.
require_once __DIR__ . '/../config/config.php';
require_role(['vendedor', 'cajero', 'administrador']);

require_once __DIR__ . '/../model/venta.php';
$model = new Venta();

$venta = $model->obtenerPorId((int) get('id'));
if (!$venta) {
    show_error(404, 'Venta no encontrada.');
}
$detalle = $model->obtenerDetalle((int) $venta['id_venta']);

// RF 5.6: descarga PDF sin dependencias (?formato=pdf)
if (get('formato') === 'pdf') {
    require_once __DIR__ . '/../helpers/pdf_simple.php';
    $lineas = [];
    $lineas[] = 'Tentaciones Marlly - Mini tienda de consumo diario';
    $lineas[] = ($venta['numero_recibo'] ?? ('Venta #' . $venta['id_venta'])) . ' | ' . ($venta['fecha'] ?? '');
    $lineas[] = 'Vendedor: ' . ($venta['vendedor'] ?? '-');
    if (!empty($venta['cliente'])) {
        $lineas[] = 'Cliente: ' . $venta['cliente'];
    }
    $lineas[] = 'Pago: ' . ($venta['nombre_metodo'] ?? '-') . (!empty($venta['empresa_pago']) ? ' (' . $venta['empresa_pago'] . ')' : '');
    $lineas[] = str_repeat('-', 60);
    foreach ($detalle as $d) {
        $lineas[] = $d['nombre_producto'] . ' x' . $d['cantidad'] . ' @ ' . money($d['precio_unitario']) . ' = ' . money($d['subtotal']);
    }
    $lineas[] = str_repeat('-', 60);
    $lineas[] = 'TOTAL: ' . money($venta['total']);
    $lineas[] = 'Recibido: ' . money($venta['valor_recibido']) . ' | Cambio: ' . money($venta['cambio']);
    if ($venta['estado'] === 'anulada') {
        $lineas[] = 'VENTA ANULADA - Motivo: ' . ($venta['motivo_anulacion'] ?? '');
    }
    $lineas[] = 'Gracias por su compra!';
    pdf_descargar($lineas, ($venta['numero_recibo'] ?? ('venta-' . $venta['id_venta'])) . '.pdf');
}

$titulo = 'Recibo ' . ($venta['numero_recibo'] ?? ('#' . $venta['id_venta']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> - Tentaciones Marlly</title>
    <link rel="stylesheet" href="<?= e(base_url('public/styles/app.css')) ?>">
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <h2>Tentaciones Marlly</h2>
            <p>Mini tienda de consumo diario</p>
            <p><strong><?= e($venta['numero_recibo'] ?? ('Venta #' . $venta['id_venta'])) ?></strong></p>
            <p><?= e($venta['fecha']) ?> · Vendedor: <?= e($venta['vendedor']) ?></p>
            <?php if (!empty($venta['cliente'])): ?>
                <p>Cliente: <?= e($venta['cliente']) ?></p>
            <?php endif; ?>
            <p>Pago: <?= e($venta['nombre_metodo'] ?? '—') ?><?= !empty($venta['empresa_pago']) ? ' (' . e($venta['empresa_pago']) . ')' : '' ?></p>
        </div>
        <table class="receipt-table">
            <thead><tr><th>Producto</th><th>Cant.</th><th>P. unit</th><th>Subtotal</th></tr></thead>
            <tbody>
                <?php foreach ($detalle as $d): ?>
                    <tr>
                        <td><?= e($d['nombre_producto']) ?></td>
                        <td><?= e($d['cantidad']) ?></td>
                        <td><?= e(money($d['precio_unitario'])) ?></td>
                        <td><?= e(money($d['subtotal'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="receipt-totals">
            <p><strong>Total: <?= e(money($venta['total'])) ?></strong></p>
            <p>Recibido: <?= e(money($venta['valor_recibido'])) ?> · Cambio: <?= e(money($venta['cambio'])) ?></p>
            <?php if ($venta['estado'] === 'anulada'): ?>
                <p class="flash flash-error">VENTA ANULADA — Motivo: <?= e($venta['motivo_anulacion'] ?? '') ?></p>
            <?php endif; ?>
        </div>
        <p class="muted">¡Gracias por su compra!</p>
        <div class="receipt-actions">
            <button class="btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir</button>
            <a class="btn-primary" href="<?= e(base_url('view/recibo.php?id=' . $venta['id_venta'] . '&formato=pdf')) ?>"><i class="fa-solid fa-file-pdf"></i> Descargar PDF</a>
            <a class="btn-primary btn-cancel" href="<?= e(base_url('view/pos.php')) ?>">Volver al POS</a>
        </div>
    </div>
</body>
</html>
