<?php
// RF 2.1, 2.2: ganancia real y rotación de productos.
require_once __DIR__ . '/../config/config.php';
require_role(['administrador']);

require_once __DIR__ . '/../model/informe.php';
$model = new Informe();

$inicio = (string) (get('inicio') ?: date('Y-m-01'));
$fin = (string) (get('fin') ?: date('Y-m-d'));

$porProducto = $model->gananciaPorProducto($inicio, $fin);
$porCategoria = $model->gananciaPorCategoria($inicio, $fin);
$top = $model->masVendidos($inicio, $fin, 10);
$sinRotacion = $model->bajaRotacion($inicio, $fin);
$historialInformes = $model->historial();

$totalGanancia = 0.0;
foreach ($porProducto as $row) {
    $totalGanancia += (float) $row['ganancia_real'];
}

$titulo = 'Informes';
require __DIR__ . '/partials/head.php';
?>

<h2>Informes</h2>
<p class="muted">Ganancia real y rotación de productos.</p>

<div class="crud-card">
    <form method="GET" class="form-grid">
        <div class="form-group">
            <label>Desde</label>
            <input type="date" name="inicio" value="<?= e($inicio) ?>" required>
        </div>
        <div class="form-group">
            <label>Hasta</label>
            <input type="date" name="fin" value="<?= e($fin) ?>" required>
        </div>
        <div class="btn-container">
            <button type="submit" class="btn-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </div>
    </form>
</div>

<div class="cards-grid">
    <div class="card">
        <div class="card-info">
            <h4>Ganancia Real del Periodo</h4>
            <span><?= e(money($totalGanancia)) ?></span>
            <small><?= e($inicio) ?> al <?= e($fin) ?></small>
        </div>
        <div class="card-icon"><i class="fa-solid fa-chart-line"></i></div>
    </div>
    <div class="card">
        <div class="card-info">
            <h4>Productos sin Rotación</h4>
            <span><?= e(count($sinRotacion)) ?></span>
            <small>sin ventas en el periodo</small>
        </div>
        <div class="card-icon"><i class="fa-solid fa-box"></i></div>
    </div>
</div>

<div class="crud-card">
    <h3>Ganancia por Producto (precio venta − precio compra)</h3>
    <div class="table-responsive">
    <table>
        <thead><tr><th>Producto</th><th>Categoría</th><th>Cant. vendida</th><th>Ingresos</th><th>Costo</th><th>Ganancia</th></tr></thead>
        <tbody>
            <?php foreach ($porProducto as $r): ?>
                <tr>
                    <td><strong><?= e($r['nombre']) ?></strong><br><small class="muted"><?= e($r['codigo_barras'] ?? '') ?></small></td>
                    <td><?= e($r['categoria'] ?? '—') ?></td>
                    <td><?= e($r['cantidad_vendida']) ?></td>
                    <td><?= e(money($r['total_ingresos'])) ?></td>
                    <td><?= e(money($r['total_costo'])) ?></td>
                    <td><strong><?= e(money($r['ganancia_real'])) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($porProducto)): ?>
                <tr><td colspan="6" class="muted">Sin ventas en el periodo seleccionado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="grid-2">
    <div class="content-box">
        <h3>Ganancia por Categoría</h3>
        <table>
            <thead><tr><th>Categoría</th><th>Vendida</th><th>Ganancia</th></tr></thead>
            <tbody>
                <?php foreach ($porCategoria as $r): ?>
                    <tr><td><?= e($r['categoria']) ?></td><td><?= e($r['cantidad_vendida']) ?></td><td><strong><?= e(money($r['ganancia_real'])) ?></strong></td></tr>
                <?php endforeach; ?>
                <?php if (empty($porCategoria)): ?>
                    <tr><td colspan="3" class="muted">Sin datos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="content-box">
        <h3>Mayor Rotación (Top 10)</h3>
        <table>
            <thead><tr><th>Producto</th><th>Unidades</th></tr></thead>
            <tbody>
                <?php foreach ($top as $r): ?>
                    <tr><td><?= e($r['nombre']) ?></td><td><?= e($r['unidades_vendidas']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($top)): ?>
                    <tr><td colspan="2" class="muted">Sin datos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="crud-card">
    <h3>Baja Rotación (sin movimiento en el periodo)</h3>
    <div class="table-responsive">
    <table>
        <thead><tr><th>Producto</th><th>Categoría</th><th>Stock actual</th></tr></thead>
        <tbody>
            <?php foreach ($sinRotacion as $r): ?>
                <tr><td><?= e($r['nombre']) ?></td><td><?= e($r['categoria'] ?? '—') ?></td><td><?= e($r['cantidad_stock']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($sinRotacion)): ?>
                <tr><td colspan="3" class="muted">Todos los productos tuvieron movimiento.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
    <form action="<?= e(base_url('index.php')) ?>" method="POST" class="inline-form">
        <input type="hidden" name="action" value="informe_guardar">
        <input type="hidden" name="inicio" value="<?= e($inicio) ?>">
        <input type="hidden" name="fin" value="<?= e($fin) ?>">
        <input type="hidden" name="tipo_informe" value="Ganancias y rotación">
        <input type="hidden" name="descripcion" value="<?= e("Periodo $inicio al $fin. Ganancia total: " . money($totalGanancia) . ". Productos sin rotación: " . count($sinRotacion) . '.') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar este informe</button>
    </form>
</div>

<div class="crud-card">
    <h3>Historial de Informes Generados</h3>
    <table>
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Usuario</th></tr></thead>
        <tbody>
            <?php foreach ($historialInformes as $h): ?>
                <tr><td><?= e($h['fecha_generacion']) ?></td><td><?= e($h['tipo_informe']) ?></td><td><?= e($h['descripcion']) ?></td><td><?= e($h['username'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($historialInformes)): ?>
                <tr><td colspan="4" class="muted">Aún no se han guardado informes.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/partials/foot.php'; ?>
