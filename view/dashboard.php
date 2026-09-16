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

// Asegurar datos de muestra si la base de datos no tiene ventas registradas
$informesModel->asegurarDatosDemostracion();

// Rango de fechas para informes (por defecto mes en curso)
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipoInforme = $_GET['tipo_informe'] ?? 'ganancia_producto';

// Métricas ejecutivas consolidadas del negocio
$metricas = $informesModel->obtenerMetricasEjecutivas($fechaInicio, $fechaFin);

// Datos para gráficas interactivas (Chart.js)
$tendencia = $informesModel->obtenerTendenciaVentas($fechaInicio, $fechaFin);
$distribucionCategorias = $informesModel->obtenerDistribucionCategorias($fechaInicio, $fechaFin);
$metodosPago = $informesModel->obtenerVentasPorMetodoPago($fechaInicio, $fechaFin);
$topProductos = $informesModel->productosMasVendidos($fechaInicio, $fechaFin, 5);

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

// Fechas clave para presets rápidos
$hoy = date('Y-m-d');
$hace7dias = date('Y-m-d', strtotime('-6 days'));
$inicioMes = date('Y-m-01');
$inicioAno = date('Y-01-01');

$esPresetHoy = ($fechaInicio === $hoy && $fechaFin === $hoy);
$esPreset7Dias = ($fechaInicio === $hace7dias && $fechaFin === $hoy);
$esPresetMes = ($fechaInicio === $inicioMes && $fechaFin === $hoy);
$esPresetAno = ($fechaInicio === $inicioAno && $fechaFin === $hoy);

$titulo = 'Dashboard';
$subtitulo = 'Métricas en tiempo real, gráficas de rendimiento e informes de negocio';
require __DIR__ . '/partials/head.php';
?>

<!-- Librería moderna Chart.js para visualización de métricas de negocio -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
    /* Presets y barra de filtros */
    .filter-wrapper {
        background: #ffffff;
        border-radius: 20px;
        padding: 20px 24px;
        margin-bottom: 25px;
        border: 1px solid #fce4ec;
        box-shadow: 0 4px 20px rgba(230, 60, 130, 0.08);
    }

    .filter-presets {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 15px;
        padding-bottom: 14px;
        border-bottom: 1px solid #fce4ec;
    }

    .preset-btn {
        background: #ffffff;
        border: 1.5px solid #f3c6d8;
        color: #555555;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .preset-btn:hover {
        background: #fff0f6;
        color: #e63c82;
        border-color: #e63c82;
    }

    .preset-btn.active {
        background: #e63c82;
        color: #ffffff;
        border-color: #c22b68;
        box-shadow: 0 3px 10px rgba(230, 60, 130, 0.3);
    }

    .filter-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
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
        color: #555555;
    }

    .filter-inputs input[type="date"] {
        padding: 8px 12px;
        border: 1.5px solid #f3c6d8;
        border-radius: 10px;
        font-size: 13px;
        outline: none;
        color: #333333;
        font-family: inherit;
    }

    .filter-inputs input[type="date"]:focus {
        border-color: #e63c82;
    }

    .filter-btn {
        background: #e63c82;
        color: #ffffff;
        border: none;
        padding: 8px 18px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 3px 10px rgba(230, 60, 130, 0.25);
    }

    .filter-btn:hover {
        background: #c22b68;
        transform: translateY(-2px);
    }

    @media (max-width: 640px) {
        .filter-presets {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 10px;
            flex-wrap: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-inputs {
            flex-direction: column;
            align-items: stretch;
            width: 100%;
        }

        .filter-inputs input[type="date"] {
            width: 100%;
            box-sizing: border-box;
        }

        .filter-btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* Gráficas Grid */
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 22px;
        margin-top: 20px;
    }

    @media (max-width: 1024px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }
    }

    .chart-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(230, 60, 130, 0.08);
        border: 1px solid #fce4ec;
        display: flex;
        flex-direction: column;
        min-width: 0;
        overflow: hidden;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .chart-card:hover {
        box-shadow: 0 8px 25px rgba(230, 60, 130, 0.12);
    }

    .chart-card.full-width {
        grid-column: 1 / -1;
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 18px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .chart-title {
        color: #2b3a55;
        font-size: 16px;
        font-weight: 700;
        margin: 0 0 4px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chart-subtitle {
        color: #888888;
        font-size: 12.5px;
        margin: 0;
    }

    .chart-canvas-container {
        position: relative;
        width: 100%;
        min-width: 0;
        flex: 1;
        height: 260px;
    }

    .chart-canvas-container.chart-tall {
        height: 290px;
    }

    @media (max-width: 768px) {
        .chart-card {
            padding: 16px 14px;
            border-radius: 16px;
        }

        .chart-header {
            margin-bottom: 12px;
        }

        .chart-title {
            font-size: 15px;
        }

        .chart-subtitle {
            font-size: 11.5px;
        }

        .chart-canvas-container {
            height: 240px;
        }

        .chart-canvas-container.chart-tall {
            height: 250px;
        }
    }

    @media (max-width: 480px) {
        .chart-canvas-container {
            height: 230px;
        }

        .chart-canvas-container.chart-tall {
            height: 235px;
        }
    }

    /* Pestañas de informes */
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
        font-size: 13.5px;
        font-weight: 600;
        color: #555555;
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
        white-space: nowrap;
    }

    .tabla-reporte td {
        padding: 12px 14px;
        border-bottom: 1px solid #f5e3ec;
        color: #333333;
        white-space: nowrap;
    }

    .tabla-reporte tr:hover {
        background-color: #fff9fc;
    }

    /* Cuadros pequeños gris claro sin emoticones */
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 14px;
        margin-bottom: 25px;
    }
    @media (max-width: 1380px) {
        .cards-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    @media (max-width: 768px) {
        .cards-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 480px) {
        .cards-grid {
            grid-template-columns: 1fr;
        }
    }

    .card {
        background: #ffffff;
        padding: 16px 18px;
        border-radius: 18px;
        box-shadow: 0 4px 18px rgba(230, 60, 130, 0.07);
        border: 1px solid #fce4ec;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(230, 60, 130, 0.12);
    }

    .card-info {
        flex: 1;
        min-width: 0;
    }

    .card-info h4 {
        font-size: 11px;
        color: #888888;
        margin: 0 0 5px 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
    }

    .card-value {
        font-size: 20px;
        font-weight: 800;
        color: #2b3a55;
        line-height: 1.2;
        margin-bottom: 2px;
        display: block;
    }

    .kpi-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.2;
        padding: 3px 8px;
        border-radius: 6px;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        text-transform: none;
        letter-spacing: 0;
        margin-top: 5px;
        white-space: nowrap;
    }

    .kpi-badge:hover {
        background: #e2e8f0;
    }

    .card-icon {
        width: 38px;
        height: 38px;
        min-width: 38px;
        border-radius: 10px;
        background: #fdf0f6;
        color: #e63c82;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .badge-kpi {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 5px;
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
        <!-- Filtro Rápido de Fechas Unificado -->
        <div class="filter-wrapper">
            <div class="filter-presets">
                <span style="font-size: 13px; font-weight: 700; color: #555; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-clock-rotate-left" style="color: #e63c82;"></i> Período Rápido:
                </span>
                <a href="?fecha_inicio=<?= $hoy ?>&fecha_fin=<?= $hoy ?>&tipo_informe=<?= urlencode($tipoInforme) ?>"
                   class="preset-btn <?= $esPresetHoy ? 'active' : '' ?>">
                   <i class="fa-regular fa-sun"></i> Hoy
                </a>
                <a href="?fecha_inicio=<?= $hace7dias ?>&fecha_fin=<?= $hoy ?>&tipo_informe=<?= urlencode($tipoInforme) ?>"
                   class="preset-btn <?= $esPreset7Dias ? 'active' : '' ?>">
                   <i class="fa-regular fa-calendar-days"></i> Últimos 7 días
                </a>
                <a href="?fecha_inicio=<?= $inicioMes ?>&fecha_fin=<?= $hoy ?>&tipo_informe=<?= urlencode($tipoInforme) ?>"
                   class="preset-btn <?= $esPresetMes ? 'active' : '' ?>">
                   <i class="fa-regular fa-calendar"></i> Este Mes
                </a>
                <a href="?fecha_inicio=<?= $inicioAno ?>&fecha_fin=<?= $hoy ?>&tipo_informe=<?= urlencode($tipoInforme) ?>"
                   class="preset-btn <?= $esPresetAno ? 'active' : '' ?>">
                   <i class="fa-solid fa-calendar-check"></i> Todo el Año
                </a>
            </div>

            <form method="GET" class="filter-bar">
                <input type="hidden" name="tipo_informe" value="<?= htmlspecialchars($tipoInforme) ?>">
                <div class="filter-inputs">
                    <label><i class="fa-regular fa-calendar-alt"></i> Desde:</label>
                    <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fechaInicio) ?>" required>
                    <label>Hasta:</label>
                    <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fechaFin) ?>" required>
                    <button type="submit" class="filter-btn">
                        <i class="fa-solid fa-filter"></i> Actualizar
                    </button>
                </div>
                <div style="font-size: 13px; color: #777;">
                    Analizando métricas del <strong><?= htmlspecialchars($fechaInicio) ?></strong> al <strong><?= htmlspecialchars($fechaFin) ?></strong>
                </div>
            </form>
        </div>

        <!-- Tarjetas de Información Ejecutivas (KPIs de Negocio) -->
        <section class="cards-grid">
            <!-- 1. Ventas de Hoy -->
            <div class="card">
                <div class="card-info">
                    <h4>Ventas de Hoy</h4>
                    <span class="card-value">$ <?= number_format($metricas['ventas_hoy'], 0, ',', '.') ?></span>
                </div>
                <div class="card-icon"><i class="fa-solid fa-calendar-day"></i></div>
            </div>

            <!-- 2. Ingresos del Período -->
            <div class="card">
                <div class="card-info">
                    <h4>Ingresos Período</h4>
                    <span class="card-value">$ <?= number_format($metricas['ingresos_periodo'], 0, ',', '.') ?></span>
                </div>
                <div class="card-icon"><i class="fa-solid fa-sack-dollar"></i></div>
            </div>

            <!-- 3. Ganancia Real Estimada -->
            <div class="card">
                <div class="card-info">
                    <h4>Ganancia Bruta Real</h4>
                    <span class="card-value" style="color: #475569;">$ <?= number_format($metricas['ganancia_real'], 0, ',', '.') ?></span> 
                </div>
                <div class="card-icon" style="background: #e8f5e9; color: #000000;"><i class="fa-solid fa-coins"></i></div>
            </div>

            <!-- 4. Ticket Promedio -->
            <div class="card">
                <div class="card-info">
                    <h4>Ticket Promedio</h4>
                    <span class="card-value">$ <?= number_format($metricas['ticket_promedio'], 0, ',', '.') ?></span>
                </div>
                <div class="card-icon" style="background: #f1f5f9; color: #000000;"><i class="fa-solid fa-receipt"></i></div>
            </div>

            <!-- 5. Valoración del Inventario -->
            <div class="card">
                <div class="card-info">
                    <h4>Valor Inventario</h4>
                    <span class="card-value">$ <?= number_format($metricas['valor_inventario_costo'], 0, ',', '.') ?></span>
                </div>
                <div class="card-icon" style="background: #f8fafc; color: #000000;"><i class="fa-solid fa-boxes-stacked"></i></div>
            </div>

            <!-- 6. Alertas de Stock Crítico -->
            <div class="card">
                <div class="card-info">
                    <h4>Alertas de Stock</h4>
                    <span class="card-value" style="<?= $metricas['alertas_stock'] > 0 ? 'color: #c62828;' : 'color: #2e7d32;'; ?>">
                        <?= $metricas['alertas_stock'] ?>
                    </span>
                    <?php if ($metricas['alertas_stock'] > 0): ?>
                    <?php else: ?>
                        <span class="kpi-badge">Stock óptimo</span>
                    <?php endif; ?>
                </div>
                <div class="card-icon" style="<?= $metricas['alertas_stock'] > 0 ? 'background: #fef2f2; color: #c62828;' : 'background: #f0fdf4; color: #2e7d32;'; ?>">
                    <i class="fa-solid <?= $metricas['alertas_stock'] > 0 ? 'fa-triangle-exclamation' : 'fa-box-open' ?>"></i>
                </div>
            </div>
        </section>

        <!-- Sección de Gráficas de Inteligencia de Negocio -->
        <section class="content-box" style="margin-top: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="color: #2b3a55; margin-bottom: 4px;">
                        <i class="fa-solid fa-chart-pie" style="color: #e63c82;"></i> Inteligencia de Negocio & Gráficas Interactivas
                    </h3>
                    <p style="color: #888888; font-size: 13px; margin: 0;">
                        Visualización gráfica de la facturación, rentabilidad, rotación de productos y canales de pago.
                    </p>
                </div>
                <span class="badge-kpi badge-positive" style="font-size: 12px; padding: 6px 14px;">
                    <i class="fa-solid fa-bolt"></i> Datos Dinámicos
                </span>
            </div>

            <div class="charts-grid">
                <!-- Gráfica 1: Tendencia Diaria de Ventas (Ancho completo) -->
                <div class="chart-card full-width">
                    <div class="chart-header">
                        <div>
                            <h4 class="chart-title"><i class="fa-solid fa-chart-line" style="color: #e63c82;"></i> Tendencia Diaria de Ventas</h4>
                            <p class="chart-subtitle">Evolución de ingresos diarios y transacciones en el rango de fechas</p>
                        </div>
                        <div style="font-weight: 800; color: #e63c82; font-size: 16px;">
                            $ <?= number_format($metricas['ingresos_periodo'], 0, ',', '.') ?>
                        </div>
                    </div>
                    <div class="chart-canvas-container chart-tall">
                        <canvas id="chartTendenciaVentas"></canvas>
                    </div>
                </div>

                <!-- Gráfica 2: Desglose Financiero -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h4 class="chart-title"><i class="fa-solid fa-scale-balanced" style="color: #2b3a55;"></i> Balance Financiero</h4>
                            <p class="chart-subtitle">Ingresos Totales vs. Costo de Mercancía vs. Ganancia Real</p>
                        </div>
                    </div>
                    <div class="chart-canvas-container">
                        <canvas id="chartFinanciero"></canvas>
                    </div>
                </div>

                <!-- Gráfica 3: Ventas por Categoría -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h4 class="chart-title"><i class="fa-solid fa-layer-group" style="color: #e63c82;"></i> Ventas por Categoría</h4>
                            <p class="chart-subtitle">Participación de ingresos según línea de producto</p>
                        </div>
                    </div>
                    <div class="chart-canvas-container">
                        <canvas id="chartCategorias"></canvas>
                    </div>
                </div>

                <!-- Gráfica 4: Top 5 Productos Más Vendidos -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h4 class="chart-title"><i class="fa-solid fa-trophy" style="color: #ff9800;"></i> Top 5 Productos Más Vendidos</h4>
                            <p class="chart-subtitle">Productos de mayor rotación (unidades vendidas)</p>
                        </div>
                    </div>
                    <div class="chart-canvas-container">
                        <canvas id="chartTopProductos"></canvas>
                    </div>
                </div>

                <!-- Gráfica 5: Métodos de Pago -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h4 class="chart-title"><i class="fa-solid fa-credit-card" style="color: #2e7d32;"></i> Métodos de Pago</h4>
                            <p class="chart-subtitle">Distribución entre Efectivo y Transferencias Digitales</p>
                        </div>
                    </div>
                    <div class="chart-canvas-container">
                        <canvas id="chartMetodosPago"></canvas>
                    </div>
                </div>
            </div>
        </section>


        <!-- Módulo de Informes de Administración (RF 2.1 & RF 2.2) -->
        <section class="content-box" style="margin-top: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h3 style="color: #2b3a55; margin-bottom: 4px;">Informes de Desempeño y Rotación</h3>
                    <p style="color: #888; font-size: 13px;">Analiza la ganancia real y el comportamiento de ventas de los productos.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="/Proyecto-TeMa/index.php?action=exportar_informe&tipo=<?= urlencode($tipoInforme) ?>&formato=pdf&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>"
                       target="_blank"
                       style="background: #e63c82; color: #fff; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(230,60,130,0.25);">
                        <i class="fa-solid fa-file-pdf"></i> Exportar PDF
                    </a>
                    <a href="/Proyecto-TeMa/index.php?action=exportar_informe&tipo=<?= urlencode($tipoInforme) ?>&formato=csv&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>"
                       style="background: #2e7d32; color: #fff; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(46,125,50,0.25);">
                        <i class="fa-solid fa-file-csv"></i> Exportar CSV
                    </a>
                </div>
            </div>

            <!-- Pestañas de Selección de Informe -->
            <div class="reportes-tabs">
                <a href="?tipo_informe=ganancia_producto&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>"
                   class="report-tab-btn <?= $tipoInforme === 'ganancia_producto' ? 'active' : '' ?>">
                    <i class="fa-solid fa-coins"></i> Ganancia Real por Producto
                </a>
                <a href="?tipo_informe=ganancia_categoria&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>"
                   class="report-tab-btn <?= $tipoInforme === 'ganancia_categoria' ? 'active' : '' ?>">
                    <i class="fa-solid fa-layer-group"></i> Ganancia Real por Categoría
                </a>
                <a href="?tipo_informe=rotacion_alta&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>"
                   class="report-tab-btn <?= $tipoInforme === 'rotacion_alta' ? 'active' : '' ?>">
                    <i class="fa-solid fa-fire"></i> Mayor Rotación / Top Ventas
                </a>
                <a href="?tipo_informe=rotacion_baja&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>"
                   class="report-tab-btn <?= $tipoInforme === 'rotacion_baja' ? 'active' : '' ?>">
                    <i class="fa-solid fa-box-archive"></i> Baja Rotación / Sin Movimiento
                </a>
            </div>

            <div style="font-size: 13px; color: #777; margin-bottom: 15px;">
                Mostrando datos del período: <strong><?= htmlspecialchars($fechaInicio) ?></strong> al <strong><?= htmlspecialchars($fechaFin) ?></strong>
            </div>

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

<!-- Inicialización de Gráficas Interactivas con Chart.js -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Formateador de moneda colombiana
        const fmtMoney = (val) => '$ ' + Number(val || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 });

        // Paleta oficial Tentaciones Marlly
        const paleta = [
            '#e63c82', // Rosa institucional
            '#2b3a55', // Azul marino elegante
            '#ff65a4', // Rosa claro brillante
            '#2e7d32', // Verde éxito
            '#ff9800', // Naranja cálido
            '#8e24aa', // Púrpura
            '#00acc1', // Cian
            '#f06292'  // Rosa pastel
        ];

        window.dashboardCharts = [];

        // 1. Gráfica de Tendencia de Ventas
        const datosTendencia = <?= json_encode($tendencia, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const ctxTendencia = document.getElementById('chartTendenciaVentas');
        if (ctxTendencia && typeof Chart !== 'undefined') {
            const ctx = ctxTendencia.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, 'rgba(230, 60, 130, 0.35)');
            gradient.addColorStop(1, 'rgba(230, 60, 130, 0.00)');

            const chart1 = new Chart(ctxTendencia, {
                type: 'line',
                data: {
                    labels: datosTendencia.labels || [],
                    datasets: [{
                        label: 'Facturación ($)',
                        data: datosTendencia.valores || [],
                        borderColor: '#e63c82',
                        backgroundColor: gradient,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#e63c82',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const val = context.raw || 0;
                                    const index = context.dataIndex;
                                    const conteo = datosTendencia.conteos ? (datosTendencia.conteos[index] || 0) : 0;
                                    return [
                                        ' Ingresos: ' + fmtMoney(val),
                                        ' Transacciones: ' + conteo + ' venta(s)'
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (v) => fmtMoney(v),
                                font: { size: 11 },
                                maxTicksLimit: 6
                            },
                            grid: { color: 'rgba(230, 60, 130, 0.07)' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11 },
                                maxTicksLimit: (window.innerWidth < 640 ? 6 : 14),
                                maxRotation: 45,
                                minRotation: 0
                            }
                        }
                    }
                }
            });
            window.dashboardCharts.push(chart1);
        }

        // 2. Gráfica de Balance Financiero
        const ctxFinanciero = document.getElementById('chartFinanciero');
        if (ctxFinanciero && typeof Chart !== 'undefined') {
            const chart2 = new Chart(ctxFinanciero, {
                type: 'bar',
                data: {
                    labels: ['Ingresos Totales', 'Costo Mercancía', 'Ganancia Neta'],
                    datasets: [{
                        data: [
                            <?= (float) $metricas['ingresos_periodo'] ?>,
                            <?= (float) $metricas['costo_mercancia'] ?>,
                            <?= (float) $metricas['ganancia_real'] ?>
                        ],
                        backgroundColor: [
                            '#2b3a55', // Azul ingresos
                            '#ff9800', // Naranja costos
                            '#2e7d32'  // Verde ganancia
                        ],
                        borderRadius: 8,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ' ' + ctx.label + ': ' + fmtMoney(ctx.raw)
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (v) => fmtMoney(v),
                                font: { size: 11 },
                                maxTicksLimit: 6
                            },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: window.innerWidth < 480 ? 10 : 11.5, weight: '600' },
                                callback: function(val) {
                                    const lbl = this.getLabelForValue(val) || '';
                                    if (window.innerWidth < 520) {
                                        if (lbl.indexOf('Ingresos') !== -1) return 'Ingresos';
                                        if (lbl.indexOf('Costo') !== -1) return 'Costos';
                                        if (lbl.indexOf('Ganancia') !== -1) return 'Ganancia';
                                    }
                                    return lbl;
                                }
                            }
                        }
                    }
                }
            });
            window.dashboardCharts.push(chart2);
        }

        // 3. Gráfica de Ventas por Categoría
        const datosCategorias = <?= json_encode($distribucionCategorias, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const ctxCategorias = document.getElementById('chartCategorias');
        if (ctxCategorias && typeof Chart !== 'undefined') {
            const chart3 = new Chart(ctxCategorias, {
                type: 'doughnut',
                data: {
                    labels: (datosCategorias.labels && datosCategorias.labels.length) ? datosCategorias.labels : ['Sin ventas'],
                    datasets: [{
                        data: (datosCategorias.valores && datosCategorias.valores.length) ? datosCategorias.valores : [1],
                        backgroundColor: (datosCategorias.valores && datosCategorias.valores.length) ? paleta : ['#e0e0e0'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: window.innerWidth < 480 ? 9 : 12,
                                padding: window.innerWidth < 480 ? 8 : 12,
                                font: { size: window.innerWidth < 480 ? 10 : 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const val = ctx.raw || 0;
                                    const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                    return ' ' + ctx.label + ': ' + fmtMoney(val) + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
            window.dashboardCharts.push(chart3);
        }

        // 4. Gráfica de Top 5 Productos Más Vendidos
        const topProds = <?= json_encode([
            'labels' => array_column($topProductos, 'nombre'),
            'unidades' => array_map('intval', array_column($topProductos, 'unidades_vendidas')),
            'montos' => array_map('floatval', array_column($topProductos, 'total_recaudado'))
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        const ctxTop = document.getElementById('chartTopProductos');
        if (ctxTop && typeof Chart !== 'undefined') {
            const chart4 = new Chart(ctxTop, {
                type: 'bar',
                data: {
                    labels: (topProds.labels && topProds.labels.length) ? topProds.labels : ['Sin datos'],
                    datasets: [{
                        label: 'Unidades Vendidas',
                        data: (topProds.unidades && topProds.unidades.length) ? topProds.unidades : [0],
                        backgroundColor: '#e63c82',
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const idx = ctx.dataIndex;
                                    const recaudado = topProds.montos ? (topProds.montos[idx] || 0) : 0;
                                    return [
                                        ' Unidades: ' + ctx.raw + ' unid.',
                                        ' Recaudado: ' + fmtMoney(recaudado)
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, font: { size: 11 }, maxTicksLimit: 6 },
                            grid: { color: 'rgba(230, 60, 130, 0.06)' }
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
                                font: { size: window.innerWidth < 480 ? 10 : 11 },
                                callback: function(val) {
                                    const raw = this.getLabelForValue(val) || '';
                                    const limit = window.innerWidth < 480 ? 12 : 22;
                                    return raw.length > limit ? raw.slice(0, limit - 1) + '…' : raw;
                                }
                            }
                        }
                    }
                }
            });
            window.dashboardCharts.push(chart4);
        }

        // 5. Gráfica de Métodos de Pago
        const datosMetodos = <?= json_encode($metodosPago, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const ctxMetodos = document.getElementById('chartMetodosPago');
        if (ctxMetodos && typeof Chart !== 'undefined') {
            const chart5 = new Chart(ctxMetodos, {
                type: 'pie',
                data: {
                    labels: (datosMetodos.labels && datosMetodos.labels.length) ? datosMetodos.labels : ['Sin pagos'],
                    datasets: [{
                        data: (datosMetodos.valores && datosMetodos.valores.length) ? datosMetodos.valores : [1],
                        backgroundColor: (datosMetodos.valores && datosMetodos.valores.length) ? [
                            '#2e7d32', // Efectivo
                            '#8e24aa', // Transferencia Nequi
                            '#e63c82', // Transferencia Daviplata
                            '#0284c7', // Bancolombia / Otro
                            '#ff9800'
                        ] : ['#e0e0e0'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: window.innerWidth < 480 ? 9 : 12,
                                padding: window.innerWidth < 480 ? 8 : 12,
                                font: { size: window.innerWidth < 480 ? 10 : 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const idx = ctx.dataIndex;
                                    const val = ctx.raw || 0;
                                    const conteo = datosMetodos.conteos ? (datosMetodos.conteos[idx] || 0) : 0;
                                    return [
                                        ' ' + ctx.label + ': ' + fmtMoney(val),
                                        ' Transacciones: ' + conteo
                                    ];
                                }
                            }
                        }
                    }
                }
            });
            window.dashboardCharts.push(chart5);
        }

        // Redimensionamiento inteligente ante cambio de tamaño de ventana o alternancia de sidebar
        function redimensionarGraficas() {
            if (window.dashboardCharts && window.dashboardCharts.length) {
                window.dashboardCharts.forEach(function(c) {
                    if (c && typeof c.resize === 'function') {
                        c.resize();
                    }
                });
            }
        }
        window.addEventListener('resize', redimensionarGraficas);
        window.addEventListener('sidebarToggle', redimensionarGraficas);
    });
</script>

<?php require __DIR__ . '/partials/foot.php'; ?>
