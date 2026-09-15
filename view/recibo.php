<?php
// view/recibo.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/auth_guard.php';

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../model/venta.php';

$idVenta = (int) ($_GET['id'] ?? 0);
$ventaModel = new Venta();
$recibo = $idVenta > 0 ? $ventaModel->generarRecibo($idVenta) : ['venta' => false, 'detalle' => []];

$venta = $recibo['venta'] ?? false;
$detalle = $recibo['detalle'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Venta - Tentaciones Marlly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #e63c82;
            --primary-dark: #c22b68;
            --secondary: #2b3a55;
            --bg: #fce4ec;
            --card-bg: #ffffff;
            --border: #f1d5e3;
            --text: #333333;
            --text-muted: #777777;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px 15px;
        }

        .receipt-container {
            width: 100%;
            max-width: 440px;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 12px 35px rgba(230, 60, 130, 0.15);
            padding: 30px 25px;
            position: relative;
            border: 1px solid var(--border);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #f0cadc;
            padding-bottom: 18px;
            margin-bottom: 18px;
        }

        .receipt-logo {
            width: 75px;
            height: 75px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .receipt-header h1 {
            font-size: 22px;
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 4px;
        }

        .receipt-header p {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .badge-status {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-completada {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-anulada {
            background-color: #ffebee;
            color: #c62828;
        }

        .receipt-meta {
            font-size: 13px;
            margin-bottom: 18px;
            line-height: 1.6;
            color: #555;
            border-bottom: 1px solid #f9e1ed;
            padding-bottom: 14px;
        }

        .receipt-meta .meta-row {
            display: flex;
            justify-content: space-between;
        }

        .meta-row strong {
            color: var(--secondary);
        }

        .receipt-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .receipt-items th {
            text-align: left;
            padding: 8px 4px;
            border-bottom: 1px solid #ddd;
            color: var(--secondary);
            font-size: 12px;
            text-transform: uppercase;
        }

        .receipt-items td {
            padding: 8px 4px;
            border-bottom: 1px solid #f5f5f5;
        }

        .receipt-items .text-right {
            text-align: right;
        }

        .receipt-items .text-center {
            text-align: center;
        }

        .receipt-totals {
            border-top: 2px dashed #f0cadc;
            padding-top: 14px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            color: #555;
        }

        .totals-row.grand-total {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary);
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #f9e1ed;
        }

        .receipt-footer {
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
            border-top: 1px solid #f9e1ed;
            padding-top: 16px;
            margin-top: 10px;
        }

        .actions-bar {
            width: 100%;
            max-width: 440px;
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.25s ease;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #fff;
            box-shadow: 0 4px 15px rgba(230, 60, 130, 0.35);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #ffffff;
            color: var(--secondary);
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background-color: #f7f7f7;
            border-color: #bbb;
        }

        /* Print Settings */
        @media print {
            body {
                background: none;
                padding: 0;
            }

            .actions-bar {
                display: none !important;
            }

            .receipt-container {
                box-shadow: none;
                border: none;
                max-width: 100%;
                width: 100%;
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <?php if (!$venta): ?>
        <div class="receipt-container" style="text-align: center;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 48px; color: #e63c82; margin-bottom: 15px;"></i>
            <h2 style="color: var(--secondary); margin-bottom: 10px;">Recibo No Encontrado</h2>
            <p style="color: var(--text-muted); margin-bottom: 20px;">El identificador de venta solicitado no existe o fue eliminado.</p>
            <a href="/Proyecto-TeMa/view/pos.php" class="btn btn-primary" style="display: inline-flex;">
                <i class="fa-solid fa-arrow-left"></i> Volver a Ventas
            </a>
        </div>
    <?php else: ?>
        <div class="receipt-container" id="printableReceipt">
            <div class="receipt-header">
                <img src="/Proyecto-TeMa/public/logo.png" alt="Tentaciones Marlly" class="receipt-logo">
                <h1>Tentaciones Marlly</h1>
                <p>MINI TIENDA DE CONSUMO DIARIO</p>
                <p>Razón Social: Marlly Store & Co.</p>
                <p>Tel: +57 300 000 0000 | Pereira, Colombia</p>
                
                <span class="badge-status status-<?= htmlspecialchars($venta['estado'] ?? 'completada') ?>">
                    <?= htmlspecialchars(strtoupper($venta['estado'] ?? 'COMPLETADA')) ?>
                </span>
            </div>

            <div class="receipt-meta">
                <div class="meta-row">
                    <span>Recibo:</span>
                    <strong><?= htmlspecialchars($venta['numero_recibo'] ?? ('REC-' . $venta['id_venta'])) ?></strong>
                </div>
                <div class="meta-row">
                    <span>Fecha:</span>
                    <span><?= htmlspecialchars($venta['fecha'] ?? date('Y-m-d H:i')) ?></span>
                </div>
                <div class="meta-row">
                    <span>Atendido por:</span>
                    <span><?= htmlspecialchars($venta['cajero_nombre'] ?: ($venta['cajero_usuario'] ?: 'Caja Principal')) ?></span>
                </div>
                <div class="meta-row">
                    <span>Cliente:</span>
                    <strong><?= htmlspecialchars($venta['cliente'] ?: 'Consumidor Final') ?></strong>
                </div>
                <?php if (!empty($venta['correo_electronico'])): ?>
                <div class="meta-row">
                    <span>Email:</span>
                    <span><?= htmlspecialchars($venta['correo_electronico']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <table class="receipt-items">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-right">Precio</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detalle)): ?>
                        <tr>
                            <td colspan="4" class="text-center" style="padding: 15px; color: #999;">Sin detalles registrados</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detalle as $item): ?>
                            <?php 
                                $cant = (int) $item['cantidad'];
                                $pu = (float) $item['precio_unitario'];
                                $sub = $cant * $pu;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nombre'] ?? 'Producto') ?></td>
                                <td class="text-center"><?= $cant ?></td>
                                <td class="text-right">$<?= number_format($pu, 2) ?></td>
                                <td class="text-right">$<?= number_format($sub, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="receipt-totals">
                <div class="totals-row">
                    <span>Método de Pago:</span>
                    <strong><?= htmlspecialchars($venta['nombre_metodo'] ?: ($venta['empresa'] ?: 'Efectivo')) ?></strong>
                </div>
                <div class="totals-row">
                    <span>Recibido:</span>
                    <span>$<?= number_format((float)($venta['valor_recibido'] ?? 0), 2) ?></span>
                </div>
                <div class="totals-row">
                    <span>Cambio / Vueltas:</span>
                    <span>$<?= number_format((float)($venta['cambio'] ?? 0), 2) ?></span>
                </div>
                <div class="totals-row grand-total">
                    <span>TOTAL:</span>
                    <span>$<?= number_format((float)($venta['total'] ?? 0), 2) ?></span>
                </div>
            </div>

            <div class="receipt-footer">
                <p>¡Gracias por su compra en Tentaciones Marlly!</p>
                <p>Conserve este recibo para cualquier reclamación o garantía.</p>
            </div>
        </div>

        <div class="actions-bar">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Imprimir
            </button>
            <a href="/Proyecto-TeMa/view/pos.php" class="btn btn-secondary">
                <i class="fa-solid fa-cart-plus"></i> Nueva Venta
            </a>
            <?php if (strtolower($_SESSION['user']['rol'] ?? '') === 'administrador'): ?>
                <a href="/Proyecto-TeMa/view/dashboard.php" class="btn btn-secondary" title="Ir al Panel de Administración">
                    <i class="fa-solid fa-gauge"></i> Dashboard
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <script src="/Proyecto-TeMa/assets/js/session-timeout.js"></script>
</body>
</html>
