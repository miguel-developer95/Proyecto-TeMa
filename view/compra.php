<?php
// view/compra.php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../helpers/auth_guard.php';
require_once __DIR__ . '/../helpers/csrf.php';
verificarRol(['administrador']);

require_once __DIR__ . '/../controller/CompraController.php';
require_once __DIR__ . '/../model/proveedores.php';

$controller = new CompraController();
$proveedorModel = new Proveedores();
$user = $_SESSION['user'];
$id_usuario = (int) ($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 0);

$mensaje = null;
$error = null;

// --- Manejo de acciones (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $_SESSION['flash'] = ['tipo' => 'error', 'texto' => 'Sesión inválida o expirada. Intenta de nuevo.'];
        header('Location: /Proyecto-TeMa/view/compra.php');
        exit();
    }

    $accion = $_POST['accion'] ?? '';
    // ... resto del código existente

    if ($accion === 'registrar_proveedor') {
        $resultado = $controller->registrarProveedor([
            'nom_proveedor'      => trim($_POST['nom_proveedor'] ?? ''),
            'nit'                => trim($_POST['nit'] ?? ''),
            'direccion'          => trim($_POST['direccion'] ?? ''),
            'telefono'           => trim($_POST['telefono'] ?? ''),
            'correo_electronico' => trim($_POST['correo_electronico'] ?? ''),
        ]);
        $_SESSION['flash'] = $resultado['success']
            ? ['tipo' => 'exito', 'texto' => 'Proveedor registrado correctamente.']
            : ['tipo' => 'error', 'texto' => $resultado['error']];
    }

    if ($accion === 'registrar_compra') {
        $codigos = $_POST['codigo_barras'] ?? [];
        $cantidades = $_POST['cantidad_producto'] ?? [];
        $precios = $_POST['precio_unitario'] ?? [];

        $items = [];
        foreach ($codigos as $i => $codigo) {
            if (trim($codigo) === '') continue;
            $items[] = [
                'codigo_barras'     => trim($codigo),
                'cantidad_producto' => (int) $cantidades[$i],
                'precio_unitario'   => (float) $precios[$i],
            ];
        }

        $resultado = $controller->registrarCompra([
            'id_proveedor' => (int) ($_POST['id_proveedor'] ?? 0),
            'metodo_pago'  => $_POST['metodo_pago'] ?? '',
            'items'        => $items,
        ]);

        $_SESSION['flash'] = $resultado['success']
            ? ['tipo' => 'exito', 'texto' => "Compra #{$resultado['id_compra']} registrada correctamente."]
            : ['tipo' => 'error', 'texto' => $resultado['error']];
    }

    if ($accion === 'anular_compra') {
        $resultado = $controller->anularCompra((int) $_POST['id_compra'], $id_usuario);
        $_SESSION['flash'] = $resultado['success']
            ? ['tipo' => 'exito', 'texto' => 'Compra anulada.']
            : ['tipo' => 'error', 'texto' => $resultado['error']];
    }

    if ($accion === 'modificar_compra') {
        $resultado = $controller->modificarCompra(
            (int) $_POST['id_compra'],
            [
                'metodo_pago'  => (int) $_POST['metodo_pago'],
                'estado'       => $_POST['estado'],
                'id_proveedor' => (int) $_POST['id_proveedor'],
            ],
            $id_usuario
        );
        $_SESSION['flash'] = $resultado['success']
            ? ['tipo' => 'exito', 'texto' => 'Compra actualizada correctamente.']
            : ['tipo' => 'error', 'texto' => $resultado['error']];
    }

    // Patrón Post/Redirect/Get: evita que un refresh (F5 / Ctrl+F5) reenvíe
    // el mismo formulario y duplique la compra.
    header('Location: /Proyecto-TeMa/view/compra.php');
    exit();
}

// Recupera el mensaje guardado en sesión (si lo hay) tras la redirección.
if (isset($_SESSION['flash'])) {
    $mensaje = $_SESSION['flash']['tipo'] === 'exito' ? $_SESSION['flash']['texto'] : null;
    $error = $_SESSION['flash']['tipo'] === 'error' ? $_SESSION['flash']['texto'] : null;
    unset($_SESSION['flash']);
}

$proveedores = $proveedorModel->listarProveedores();
$historial = $controller->listarHistorial();

// Métodos de pago activos
require_once __DIR__ . '/../model/metodos_pago.php';
$metodosPago = (new MetodosPago())->listarMetodosPagoActivos();
$titulo = 'Compras';
$subtitulo = 'Registro de compras, proveedores e historial de abastecimiento';
require __DIR__ . '/partials/head.php';
?>

<style>
    /* Asegurar pie de página pegado abajo */
    .main-content {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .app-footer {
        margin-top: auto !important;
    }

    /* Grid superior para balancear el ancho de los paneles */
    .compras-grid-top {
        display: grid;
        grid-template-columns: 1.35fr 1fr;
        gap: 25px;
        margin-bottom: 25px;
        align-items: stretch;
    }

    @media (max-width: 1100px) {
        .compras-grid-top {
            grid-template-columns: 1fr;
        }
    }

    .panel-compra, .panel-proveedor {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        margin-bottom: 0 !important;
    }

    /* Grupos de formularios elegantes y responsivos */
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 14px;
    }

    .form-group label {
        font-size: 13px;
        font-weight: 700;
        color: #4a5568;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    @media (max-width: 600px) {
        .form-row-2 {
            grid-template-columns: 1fr;
        }
    }

    .form-group input,
    .form-group select,
    .item-field input {
        padding: 10px 12px;
        border: 1.5px solid #f3c6d8;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        width: 100%;
        box-sizing: border-box;
        color: #333333;
        background: #ffffff;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .item-field input:focus {
        outline: none;
        border-color: #e63c82;
        box-shadow: 0 0 0 3px rgba(230, 60, 130, 0.15);
    }

    /* Sección de Ítems / Productos de la Compra */
    .items-section-header {
        margin-top: 10px;
        margin-bottom: 8px;
        padding-top: 12px;
        border-top: 1px dashed #fce4ec;
    }

    .item-compra-row {
        display: flex;
        gap: 10px;
        align-items: flex-end;
        margin-bottom: 10px;
        background: #fff8fb;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid #fce4ec;
        transition: background 0.2s;
    }

    .item-compra-row:hover {
        background: #fff0f6;
    }

    .item-sublabel {
        display: block;
        font-size: 11.5px;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .btn-remove-row {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fca5a5;
        padding: 9px 12px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        height: 40px;
    }

    .btn-remove-row:hover {
        background: #dc2626;
        color: #ffffff;
    }

    .compra-actions-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 18px;
        padding-top: 14px;
        border-top: 1px solid #fce4ec;
        flex-wrap: wrap;
        gap: 10px;
    }

    /* Tabla de Historial Responsiva */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        border-radius: 14px;
        border: 1px solid #f3d4e2;
        box-shadow: 0 2px 8px rgba(230, 60, 130, 0.04);
        margin-top: 12px;
    }

    table.tabla-compras {
        width: 100%;
        border-collapse: collapse;
        background: #ffffff;
        font-size: 13.5px;
    }

    table.tabla-compras th {
        background: #fdf2f7;
        color: #2b3a55;
        font-weight: 700;
        padding: 13px 15px;
        border-bottom: 2px solid #f3c6d8;
        text-align: left;
        white-space: nowrap;
    }

    table.tabla-compras td {
        padding: 13px 15px;
        border-bottom: 1px solid #f9e8f0;
        color: #333333;
        white-space: nowrap;
    }

    @media (max-width: 600px) {
        .item-compra-row {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        .item-compra-row .item-field {
            flex: 1 1 100% !important;
            width: 100%;
        }

        .item-compra-row .item-action {
            display: flex;
            justify-content: flex-end;
            margin-top: 4px;
        }

        .item-compra-row .btn-remove-row {
            width: 100%;
        }

        .compra-actions-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .compra-actions-bar button,
        .compra-actions-bar div {
            width: 100%;
            text-align: center;
        }
    }

    table.tabla-compras tbody tr:hover {
        background-color: #fff9fc;
    }

    .badge-estado {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
        text-transform: capitalize;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .badge-registrada {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .badge-anulada {
        background: #fef2f2;
        color: #c62828;
    }

    .badge-pendiente {
        background: #fff8e1;
        color: #f57f17;
    }

    .acciones-compra {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .btn-action-edit,
    .btn-action-cancel {
        padding: 5px 9px;
        border-radius: 7px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        line-height: 1;
        white-space: nowrap;
        transition: all 0.2s;
    }

    .btn-action-edit {
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }

    .btn-action-edit:hover {
        background: #0284c7;
        color: #ffffff;
    }

    .btn-action-cancel {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .btn-action-cancel:hover {
        background: #dc2626;
        color: #ffffff;
    }

    .alerta {
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alerta-exito {
        background: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #c8e6c9;
    }

    .alerta-error {
        background: #fef2f2;
        color: #c62828;
        border: 1px solid #ffcdd2;
    }

    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(2px);
    }

    .modal-overlay.activo {
        display: flex;
    }

    .modal-box {
        background: #fff;
        border-radius: 20px;
        padding: 28px;
        width: 440px;
        max-width: 90%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        border: 1px solid #fce4ec;
    }

    .modal-box h3 {
        margin-top: 0;
        margin-bottom: 18px;
        color: #2b3a55;
        font-size: 18px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 22px;
    }
</style>

        <?php if ($mensaje): ?>
            <div class="alerta alerta-exito">
                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alerta alerta-error">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Grid de Contenedores y Paneles de Entrada -->
        <div class="compras-grid-top">

            <!-- Panel 1: Registrar Compra (RF 4.1) -->
            <section class="content-box panel-compra">
                <div>
                    <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px;">
                        <i class="fa-solid fa-cart-shopping" style="color: #e63c82;"></i> Registrar Compra
                    </h3>
                    <form method="POST" id="form-compra">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="registrar_compra">
                        
                        <div class="form-row-2">
                            <div class="form-group">
                                <label><i class="fa-solid fa-truck"></i> Proveedor *</label>
                                <select name="id_proveedor" required>
                                    <option value="">-- Selecciona proveedor --</option>
                                    <?php foreach ($proveedores as $p): ?>
                                        <option value="<?php echo $p['id_proveedor']; ?>">
                                            <?php echo htmlspecialchars($p['nom_proveedor']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-credit-card"></i> Método de Pago *</label>
                                <select name="metodo_pago" required>
                                    <option value="">-- Método de pago --</option>
                                    <?php foreach ($metodosPago as $mp): ?>
                                        <option value="<?php echo $mp['id_pago']; ?>">
                                            <?php echo htmlspecialchars($mp['tipo_pago']); ?>
                                            <?php echo !empty($mp['nombre_empresa']) ? ' (' . htmlspecialchars($mp['nombre_empresa']) . ')' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="items-section-header">
                            <label style="font-weight: 700; font-size: 13.5px; color: #2b3a55; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-boxes-stacked" style="color: #e63c82;"></i> Productos de la Compra
                            </label>
                        </div>

                        <div id="items-container">
                            <div class="item-compra-row">
                                <div class="item-field" style="flex: 2;">
                                    <label class="item-sublabel">Código de barras</label>
                                    <input type="text" name="codigo_barras[]" placeholder="Ej: 770123456789" required>
                                </div>
                                <div class="item-field" style="flex: 1;">
                                    <label class="item-sublabel">Cantidad</label>
                                    <input type="number" name="cantidad_producto[]" placeholder="Cant." min="1" value="1" required>
                                </div>
                                <div class="item-field" style="flex: 1.3;">
                                    <label class="item-sublabel">Precio Unitario ($)</label>
                                    <input type="number" name="precio_unitario[]" placeholder="Precio" step="0.01" min="0" required>
                                </div>
                                <div class="item-action">
                                    <button type="button" class="btn-remove-row" onclick="eliminarLinea(this)" title="Eliminar ítem">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="compra-actions-bar">
                            <button type="button" class="btn-secondary" onclick="agregarLinea()">
                                <i class="fa-solid fa-plus"></i> Agregar producto
                            </button>
                            <button type="submit" class="btn-pink">
                                <i class="fa-solid fa-cart-arrow-down"></i> Registrar compra
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Panel 2: Registrar Proveedor (RF 4.2) -->
            <section class="content-box panel-proveedor">
                <div>
                    <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px;">
                        <i class="fa-solid fa-truck-field" style="color: #e63c82;"></i> Registrar Proveedor
                    </h3>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="registrar_proveedor">
                        
                        <div class="form-group">
                            <label><i class="fa-solid fa-building"></i> Nombre del Proveedor *</label>
                            <input type="text" name="nom_proveedor" placeholder="Nombre o Razón Social" required>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label><i class="fa-solid fa-id-card"></i> NIT *</label>
                                <input type="text" name="nit" placeholder="Ej: 900.123.456-7" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-phone"></i> Teléfono</label>
                                <input type="text" name="telefono" placeholder="Ej: 300 123 4567">
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label><i class="fa-solid fa-envelope"></i> Correo Electrónico</label>
                                <input type="email" name="correo_electronico" placeholder="proveedor@correo.com">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-location-dot"></i> Dirección</label>
                                <input type="text" name="direccion" placeholder="Calle / Carrera #">
                            </div>
                        </div>

                        <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #fce4ec;">
                            <button type="submit" class="btn-pink" style="width: 100%; justify-content: center;">
                                <i class="fa-solid fa-user-plus"></i> Guardar Proveedor
                            </button>
                        </div>
                    </form>
                </div>
            </section>

        </div>

        <!-- Panel 3: Historial de Compras (RF 4.3) -->
        <section class="content-box" style="margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-clock-rotate-left" style="color: #e63c82;"></i> Historial de Compras
                </h3>
                <span style="font-size: 13px; color: #888888; font-weight: 600;">
                    Total: <?= count($historial) ?> compras registradas
                </span>
            </div>

            <div class="table-responsive">
                <table class="tabla-compras">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Método de pago</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historial)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; color:#888; padding: 25px;">
                                    <i class="fa-solid fa-box-open" style="font-size: 24px; color: #f3c6d8; display: block; margin-bottom: 8px;"></i>
                                    Aún no hay compras registradas en el sistema.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historial as $c): ?>
                                <tr>
                                    <td style="font-weight: 700; color: #2b3a55;">#<?php echo $c['id_compra']; ?></td>
                                    <td><?php echo htmlspecialchars($c['fecha_hora']); ?></td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($c['nom_proveedor'] ?? '—'); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($c['tipo_pago'] ?? '—'); ?>
                                        <?php echo !empty($c['nombre_empresa']) ? ' <span style="font-size:12px; color:#888;">(' . htmlspecialchars($c['nombre_empresa']) . ')</span>' : ''; ?>
                                    </td>
                                    <td style="font-weight: 800; color: #e63c82;">$ <?php echo number_format($c['total_compra'], 2); ?></td>
                                    <td>
                                        <span class="badge-estado badge-<?php echo strtolower($c['estado']) === 'anulada' ? 'anulada' : (strtolower($c['estado']) === 'pendiente' ? 'pendiente' : 'registrada'); ?>">
                                            <i class="fa-solid <?= strtolower($c['estado']) === 'anulada' ? 'fa-ban' : (strtolower($c['estado']) === 'pendiente' ? 'fa-clock' : 'fa-check') ?>"></i>
                                            <?php echo htmlspecialchars($c['estado']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <?php if (strtolower($c['estado']) !== 'anulada'): ?>
                                            <div class="acciones-compra">
                                                <button type="button" class="btn-action-edit" onclick="abrirModalEditar(<?php echo htmlspecialchars(json_encode($c)); ?>)" title="Editar">
                                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                                </button>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de anular esta compra? Se revertirá el stock ingresado.');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="accion" value="anular_compra">
                                                    <input type="hidden" name="id_compra" value="<?php echo $c['id_compra']; ?>">
                                                    <button type="submit" class="btn-action-cancel" title="Anular">
                                                        <i class="fa-solid fa-xmark"></i> Anular
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #aaa; font-size: 12px; font-style: italic;">Sin acciones</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    <!-- Modal de edición de compra (RF 4.4) -->
    <div class="modal-overlay" id="modal-editar">
        <div class="modal-box">
            <h3><i class="fa-solid fa-pen-to-square" style="color: #e63c82;"></i> Editar Compra</h3>
            <form method="POST" id="form-editar-compra">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="modificar_compra">
                <input type="hidden" name="id_compra" id="edit-id-compra">

                <div class="form-group">
                    <label><i class="fa-solid fa-truck"></i> Proveedor</label>
                    <select name="id_proveedor" id="edit-id-proveedor" required>
                        <?php foreach ($proveedores as $p): ?>
                            <option value="<?php echo $p['id_proveedor']; ?>">
                                <?php echo htmlspecialchars($p['nom_proveedor']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-credit-card"></i> Método de Pago</label>
                    <select name="metodo_pago" id="edit-metodo-pago" required>
                        <?php foreach ($metodosPago as $mp): ?>
                            <option value="<?php echo $mp['id_pago']; ?>">
                                <?php echo htmlspecialchars($mp['tipo_pago']); ?>
                                <?php echo !empty($mp['nombre_empresa']) ? ' (' . htmlspecialchars($mp['nombre_empresa']) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-tag"></i> Estado</label>
                    <select name="estado" id="edit-estado" required>
                        <option value="Pendiente">Pendiente</option>
                        <option value="registrada">Registrada</option>
                        <option value="anulada">Anulada</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-pink">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                    </button>
                    <button type="button" class="btn-secondary" onclick="cerrarModalEditar()">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function agregarLinea() {
            const contenedor = document.getElementById('items-container');
            const fila = document.createElement('div');
            fila.className = 'item-compra-row';
            fila.innerHTML = `
                <div class="item-field" style="flex: 2;">
                    <label class="item-sublabel">Código de barras</label>
                    <input type="text" name="codigo_barras[]" placeholder="Ej: 770123456789" required>
                </div>
                <div class="item-field" style="flex: 1;">
                    <label class="item-sublabel">Cantidad</label>
                    <input type="number" name="cantidad_producto[]" placeholder="Cant." min="1" value="1" required>
                </div>
                <div class="item-field" style="flex: 1.3;">
                    <label class="item-sublabel">Precio Unitario ($)</label>
                    <input type="number" name="precio_unitario[]" placeholder="Precio" step="0.01" min="0" required>
                </div>
                <div class="item-action">
                    <button type="button" class="btn-remove-row" onclick="eliminarLinea(this)" title="Eliminar ítem">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            `;
            contenedor.appendChild(fila);
        }

        function eliminarLinea(btn) {
            const contenedor = document.getElementById('items-container');
            if (contenedor.children.length > 1) {
                btn.closest('.item-compra-row').remove();
            } else {
                alert('Debe haber al menos un producto en la compra.');
            }
        }

        function abrirModalEditar(compra) {
            document.getElementById('edit-id-compra').value = compra.id_compra;
            document.getElementById('edit-id-proveedor').value = compra.id_proveedor;
            document.getElementById('edit-metodo-pago').value = compra.metodo_pago;
            document.getElementById('edit-estado').value = compra.estado;
            document.getElementById('modal-editar').classList.add('activo');
        }

        function cerrarModalEditar() {
            document.getElementById('modal-editar').classList.remove('activo');
        }
    </script>
    <?php require __DIR__ . '/partials/foot.php'; ?>