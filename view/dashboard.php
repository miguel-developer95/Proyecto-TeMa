<?php
// view/dashboard.php
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../helpers/auth_guard.php';
verificarRol(['administrador']);

require_once __DIR__ . '/../model/informes.php';
$informesModel = new Informes();
$metricas = $informesModel->obtenerMetricasDashboard();
$user = $_SESSION['user'];

// Rango de fechas para informes (por defecto mes en curso)
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipoInforme = $_GET['tipo_informe'] ?? 'ganancia_producto';

// Consultas según el informe seleccionado
$datosInforme = [];
if ($tipoInforme === 'ganancia_producto') {
    $datosInforme = $informesModel->gananciaPorProducto($fechaInicio, $fechaFin);
} elseif ($tipoInforme === 'ganancia_categoria') {
    $datosInforme = $informesModel->gananciaPorCategoria($fechaInicio, $fechaFin);
} elseif ($tipoInforme === 'rotacion_alta') {
    $datosInforme = $informesModel->productosMasVendidos($fechaInicio, $fechaFin, 15);
} elseif ($tipoInforme === 'rotacion_baja') {
    $datosInforme = $informesModel->productosBajaRotacion($fechaInicio, $fechaFin);
}
$titulo = 'Dashboard';
$subtitulo = 'Resumen de actividades, indicadores del negocio e informes';
require __DIR__ . '/partials/head.php';
?>

<style>
    .reportes-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .report-tab-btn {
        padding: 10px 18px;
        border-radius: 12px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        color: #555;
        background: #ffffff;
        border: 1.5px solid #f3c6d8;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .report-tab-btn:hover {
        background: #fff0f6;
        color: #e63c82;
        border-color: #e63c82;
    }

    .report-tab-btn.active {
        background: #e63c82;
        color: #ffffff;
        border-color: #c22b68;
        box-shadow: 0 4px 12px rgba(230, 60, 130, 0.25);
    }

    .filter-bar {
        background: #ffffff;
        border-radius: 16px;
        padding: 18px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 22px;
        border: 1px solid #fce4ec;
        box-shadow: 0 4px 15px rgba(230, 60, 130, 0.05);
        flex-wrap: wrap;
    }

    .filter-inputs {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .filter-inputs label {
        font-size: 13px;
        font-weight: 600;
        color: #555;
    }

    .filter-inputs input[type="date"] {
        padding: 8px 12px;
        border: 1.5px solid #f3c6d8;
        border-radius: 8px;
        font-size: 13px;
        outline: none;
    }

    .filter-btn {
        background: #e63c82;
        color: #fff;
        border: none;
        padding: 9px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .filter-btn:hover {
        background: #c22b68;
    }

    .tabla-reporte {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    .tabla-reporte th {
        text-align: left;
        padding: 12px 14px;
        background: #fdf2f7;
        color: #2b3a55;
        font-weight: 700;
        border-bottom: 2px solid #f3c6d8;
    }

    .tabla-reporte td {
        padding: 12px 14px;
        border-bottom: 1px solid #f5e3ec;
        color: #333;
    }

    .tabla-reporte tr:hover {
        background-color: #fff9fc;
    }

    .badge-kpi {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-positive {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .badge-warning {
        background: #fff3e0;
        color: #e65100;
    }

    .badge-danger {
        background: #ffebee;
        color: #c62828;
    }
</style>

        <!-- Tarjetas de Información Rápidas (KPIs Dinámicos) -->
        <section class="cards-grid">
            <div class="card">
                <div class="card-info">
                    <h4>Ventas de Hoy</h4>
                    <span>$ <?php echo number_format($metricas['ventas_hoy'], 2); ?></span>
                </div>
                <div class="card-icon"><i class="fa-solid fa-dollar-sign"></i></div>
            </div>

            <div class="card">
                <div class="card-info">
                    <h4>Productos Activos</h4>
                    <span><?php echo $metricas['total_productos']; ?></span>
                </div>
                <div class="card-icon"><i class="fa-solid fa-box-open"></i></div>
            </div>

            <div class="card">
                <div class="card-info">
                    <h4>Alertas de Stock</h4>
                    <span style="<?php echo $metricas['alertas_stock'] > 0 ? 'color: #c62828;' : ''; ?>">
                        <?php echo $metricas['alertas_stock']; ?>
                    </span>
                </div>
                <div class="card-icon" style="<?php echo $metricas['alertas_stock'] > 0 ? 'color: #c62828;' : ''; ?>">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>

            <div class="card">
                <div class="card-info">
                    <h4>Usuarios Registrados</h4>
                    <span><?php echo $metricas['total_usuarios']; ?></span>
                </div>
                <div class="card-icon"><i class="fa-solid fa-users"></i></div>
            </div>
        </section>

        <!-- Módulo de Informes de Administración (RF 2.1 & RF 2.2) -->
        <section class="content-box" style="margin-top: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h3 style="color: #2b3a55; margin-bottom: 4px;">Informes de Desempeño y Rotación</h3>
                    <p style="color: #888; font-size: 13px;">Analiza la ganancia real y el comportamiento de ventas de los productos.</p>
                </div>
            </div>

            <!-- Pestañas de Selección de Informe -->
            <div class="reportes-tabs">
                <a href="?tipo_informe=ganancia_producto&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>"
                   class="report-tab-btn <?= $tipoInforme === 'ganancia_producto' ? 'active' : '' ?>">
                    <i class="fa-solid fa-coins"></i> Ganancia Real por Producto (RF 2.1)
                </a>
                <a href="?tipo_informe=ganancia_categoria&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>"
                   class="report-tab-btn <?= $tipoInforme === 'ganancia_categoria' ? 'active' : '' ?>">
                    <i class="fa-solid fa-layer-group"></i> Ganancia Real por Categoría (RF 2.1)
                </a>
                <a href="?tipo_informe=rotacion_alta&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>"
                   class="report-tab-btn <?= $tipoInforme === 'rotacion_alta' ? 'active' : '' ?>">
                    <i class="fa-solid fa-fire"></i> Mayor Rotación / Top Ventas (RF 2.2)
                </a>
                <a href="?tipo_informe=rotacion_baja&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>"
                   class="report-tab-btn <?= $tipoInforme === 'rotacion_baja' ? 'active' : '' ?>">
                    <i class="fa-solid fa-box-archive"></i> Baja Rotación / Sin Movimiento (RF 2.2)
                </a>
            </div>

            <!-- Barra de Filtros de Fecha -->
            <form method="GET" class="filter-bar">
                <input type="hidden" name="tipo_informe" value="<?= htmlspecialchars($tipoInforme) ?>">
                <div class="filter-inputs">
                    <label><i class="fa-regular fa-calendar"></i> Desde:</label>
                    <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fechaInicio) ?>" required>
                    <label>Hasta:</label>
                    <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fechaFin) ?>" required>
                    <button type="submit" class="filter-btn">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                </div>
                <div style="font-size: 13px; color: #777;">
                    Período: <strong><?= htmlspecialchars($fechaInicio) ?></strong> al <strong><?= htmlspecialchars($fechaFin) ?></strong>
                </div>
            </form>

            <!-- Tabla de Resultados según el Informe -->
            <div style="overflow-x: auto; background: #ffffff; border-radius: 16px; border: 1px solid #f5e3ec;">

                <?php if ($tipoInforme === 'ganancia_producto'): ?>
                    <table class="tabla-reporte">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th class="text-center">Unid. Vendidas</th>
                                <th class="text-right">Total Ingresos ($)</th>
                                <th class="text-right">Costo Total ($)</th>
                                <th class="text-right">Ganancia Real ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($datosInforme)): ?>
                                <tr>
                                    <td colspan="7" class="text-center" style="padding: 25px; color: #999;">No hay ventas registradas en el período seleccionado.</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                    $totIngresos = 0;
                                    $totCostos = 0;
                                    $totGanancia = 0;
                                ?>
                                <?php foreach ($datosInforme as $fila): ?>
                                    <?php 
                                        $totIngresos += $fila['total_ingresos'];
                                        $totCostos += $fila['total_costo'];
                                        $totGanancia += $fila['ganancia_real'];
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($fila['codigo_barras'] ?? '—') ?></strong></td>
                                        <td><?= htmlspecialchars($fila['nombre']) ?></td>
                                        <td><?= htmlspecialchars($fila['categoria'] ?? 'Sin categoría') ?></td>
                                        <td class="text-center"><?= $fila['cantidad_vendida'] ?></td>
                                        <td class="text-right">$<?= number_format($fila['total_ingresos'], 2) ?></td>
                                        <td class="text-right" style="color: #777;">$<?= number_format($fila['total_costo'], 2) ?></td>
                                        <td class="text-right" style="font-weight: 700; color: #2e7d32;">
                                            $<?= number_format($fila['ganancia_real'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background: #fdf2f7; font-weight: bold; font-size: 14px;">
                                    <td colspan="4" class="text-right">TOTALES DEL PERÍODO:</td>
                                    <td class="text-right">$<?= number_format($totIngresos, 2) ?></td>
                                    <td class="text-right" style="color:#777;">$<?= number_format($totCostos, 2) ?></td>
                                    <td class="text-right" style="color:#2e7d32;">$<?= number_format($totGanancia, 2) ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                <?php elseif ($tipoInforme === 'ganancia_categoria'): ?>
                    <table class="tabla-reporte">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th class="text-center">Variedad Productos</th>
                                <th class="text-center">Unid. Vendidas</th>
                                <th class="text-right">Total Ingresos ($)</th>
                                <th class="text-right">Costo Total ($)</th>
                                <th class="text-right">Ganancia Real ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($datosInforme)): ?>
                                <tr>
                                    <td colspan="6" class="text-center" style="padding: 25px; color: #999;">No hay ventas registradas en el período seleccionado.</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                    $totIngresos = 0;
                                    $totCostos = 0;
                                    $totGanancia = 0;
                                ?>
                                <?php foreach ($datosInforme as $fila): ?>
                                    <?php 
                                        $totIngresos += $fila['total_ingresos'];
                                        $totCostos += $fila['total_costo'];
                                        $totGanancia += $fila['ganancia_real'];
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($fila['categoria']) ?></strong></td>
                                        <td class="text-center"><?= $fila['productos_distintos'] ?></td>
                                        <td class="text-center"><?= $fila['cantidad_vendida'] ?></td>
                                        <td class="text-right">$<?= number_format($fila['total_ingresos'], 2) ?></td>
                                        <td class="text-right" style="color: #777;">$<?= number_format($fila['total_costo'], 2) ?></td>
                                        <td class="text-right" style="font-weight: 700; color: #2e7d32;">
                                            $<?= number_format($fila['ganancia_real'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background: #fdf2f7; font-weight: bold; font-size: 14px;">
                                    <td colspan="3" class="text-right">TOTALES:</td>
                                    <td class="text-right">$<?= number_format($totIngresos, 2) ?></td>
                                    <td class="text-right" style="color:#777;">$<?= number_format($totCostos, 2) ?></td>
                                    <td class="text-right" style="color:#2e7d32;">$<?= number_format($totGanancia, 2) ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                <?php elseif ($tipoInforme === 'rotacion_alta'): ?>
                    <table class="tabla-reporte">
                        <thead>
                            <tr>
                                <th># Ranking</th>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th class="text-center">Stock Disponible</th>
                                <th class="text-center">Unidades Vendidas</th>
                                <th class="text-right">Total Recaudado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($datosInforme)): ?>
                                <tr>
                                    <td colspan="7" class="text-center" style="padding: 25px; color: #999;">No hay productos con rotación en este rango.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($datosInforme as $i => $fila): ?>
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge-kpi <?= $i === 0 ? 'badge-positive' : 'badge-warning' ?>">
                                                Top <?= $i + 1 ?>
                                            </span>
                                        </td>
                                        <td><strong><?= htmlspecialchars($fila['codigo_barras'] ?? '—') ?></strong></td>
                                        <td><?= htmlspecialchars($fila['nombre']) ?></td>
                                        <td><?= htmlspecialchars($fila['categoria'] ?? 'Sin categoría') ?></td>
                                        <td class="text-center"><?= $fila['cantidad_stock'] ?></td>
                                        <td class="text-center" style="font-weight: 700; color: #e63c82;"><?= $fila['unidades_vendidas'] ?></td>
                                        <td class="text-right">$<?= number_format($fila['total_recaudado'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                <?php elseif ($tipoInforme === 'rotacion_baja'): ?>
                    <table class="tabla-reporte">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Precio Venta</th>
                                <th class="text-center">Stock Estancado</th>
                                <th class="text-center">Estado Rotación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($datosInforme)): ?>
                                <tr>
                                    <td colspan="6" class="text-center" style="padding: 25px; color: #2e7d32;">
                                        ¡Excelente! Todos los productos activos registraron movimiento en este período.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($datosInforme as $fila): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($fila['codigo_barras'] ?? '—') ?></strong></td>
                                        <td><?= htmlspecialchars($fila['nombre']) ?></td>
                                        <td><?= htmlspecialchars($fila['categoria'] ?? 'Sin categoría') ?></td>
                                        <td>$<?= number_format($fila['precio_venta'], 2) ?></td>
                                        <td class="text-center" style="font-weight: 700;"><?= $fila['cantidad_stock'] ?></td>
                                        <td class="text-center">
                                            <span class="badge-kpi badge-danger">Sin Ventas en el Período</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div>
        </section>
    <?php require __DIR__ . '/partials/foot.php'; ?>