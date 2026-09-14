<?php
// view/compra.php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user'])) {
    header("Location: /Proyecto-TeMa/view/login.php");
    exit();
}
$rolActual = strtolower($_SESSION['user']['rol'] ?? '');
if ($rolActual !== 'administrador') {
    header("Location: /Proyecto-TeMa/view/pos.php");
    exit();
}

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
    $accion = $_POST['accion'] ?? '';

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
    .form-row {
        display: flex;
        gap: 12px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }

    .form-row input,
    .form-row select {
        flex: 1;
        min-width: 140px;
        padding: 10px 12px;
        border: 2px solid #f3c6d8;
        border-radius: 8px;
        font-size: 14px;
    }

    .form-row input:focus,
    .form-row select:focus {
        outline: none;
        border-color: #e63c82;
    }

    #items-container .form-row {
        align-items: center;
    }

    table.tabla-compras {
        width: 100%;
        border-collapse: collapse;
    }

    table.tabla-compras th,
    table.tabla-compras td {
        text-align: left;
        padding: 10px 12px;
        border-bottom: 1px solid #f3d4e2;
        font-size: 14px;
    }

    table.tabla-compras th {
        color: #888;
        font-weight: 600;
        background: #fdf2f7;
    }

    .badge-estado {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-registrada {
        background: #e5f7e5;
        color: #2e7d32;
    }

    .badge-anulada {
        background: #fdeaea;
        color: #c41313;
    }

    .alerta {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .alerta-exito {
        background: #e5f7e5;
        color: #2e7d32;
    }

    .alerta-error {
        background: #fdeaea;
        color: #c41313;
    }

    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.4);
        z-index: 999;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.activo {
        display: flex;
    }

    .modal-box {
        background: #fff;
        border-radius: 20px;
        padding: 25px;
        width: 420px;
        max-width: 90%;
    }

    .modal-box h3 {
        margin-bottom: 15px;
        color: #333;
    }

    .modal-box label {
        display: block;
        font-size: 13px;
        color: #888;
        margin-bottom: 4px;
        margin-top: 10px;
    }

    .modal-box select {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid #f3c6d8;
        border-radius: 8px;
        font-size: 14px;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
</style>

        <?php if ($mensaje): ?>
            <div class="alerta alerta-exito"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alerta alerta-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- RF 4.2 — Registrar proveedor -->
        <section class="content-box">
            <h3>Registrar Proveedor</h3>
            <form method="POST">
                <input type="hidden" name="accion" value="registrar_proveedor">
                <div class="form-row">
                    <input type="text" name="nom_proveedor" placeholder="Nombre del proveedor" required>
                    <input type="text" name="nit" placeholder="NIT" required>
                    <input type="text" name="telefono" placeholder="Teléfono">
                    <input type="email" name="correo_electronico" placeholder="Correo electrónico">
                    <input type="text" name="direccion" placeholder="Dirección">
                </div>
                <button type="submit" class="btn-pink">Guardar proveedor</button>
            </form>
        </section>

        <!-- RF 4.1 — Registrar compra -->
        <section class="content-box">
            <h3>Registrar Compra</h3>
            <form method="POST" id="form-compra">
                <input type="hidden" name="accion" value="registrar_compra">
                <div class="form-row">
                    <select name="id_proveedor" required>
                        <option value="">-- Selecciona proveedor --</option>
                        <?php foreach ($proveedores as $p): ?>
                            <option value="<?php echo $p['id_proveedor']; ?>">
                                <?php echo htmlspecialchars($p['nom_proveedor']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="metodo_pago" required>
                        <option value="">-- Método de pago --</option>
                        <?php foreach ($metodosPago as $mp): ?>
                            <option value="<?php echo $mp['id_pago']; ?>">
                                <?php echo htmlspecialchars($mp['tipo_pago']); ?>
                                <?php echo $mp['nombre_empresa'] ? ' (' . htmlspecialchars($mp['nombre_empresa']) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="items-container">
                    <div class="form-row">
                        <input type="text" name="codigo_barras[]" placeholder="Código de barras" required>
                        <input type="number" name="cantidad_producto[]" placeholder="Cantidad" min="1" required>
                        <input type="number" name="precio_unitario[]" placeholder="Precio unitario" step="0.01" min="0" required>
                    </div>
                </div>

                <button type="button" class="btn-secondary" onclick="agregarLinea()">+ Agregar producto</button>
                <br><br>
                <button type="submit" class="btn-pink">Registrar compra</button>
            </form>
        </section>

        <!-- RF 4.3 — Historial de compras -->
        <section class="content-box">
            <h3>Historial de Compras</h3>
            <table class="tabla-compras">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Método de pago</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historial)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; color:#888;">Aún no hay compras registradas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historial as $c): ?>
                            <tr>
                                <td>#<?php echo $c['id_compra']; ?></td>
                                <td><?php echo htmlspecialchars($c['fecha_hora']); ?></td>
                                <td><?php echo htmlspecialchars($c['nom_proveedor'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($c['tipo_pago'] ?? '—'); ?><?php echo !empty($c['nombre_empresa']) ? ' (' . htmlspecialchars($c['nombre_empresa']) . ')' : ''; ?></td>
                                <td>$ <?php echo number_format($c['total_compra'], 2); ?></td>
                                <td>
                                    <span class="badge-estado badge-<?php echo $c['estado'] === 'anulada' ? 'anulada' : 'registrada'; ?>">
                                        <?php echo htmlspecialchars($c['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($c['estado'] !== 'anulada'): ?>
                                        <button type="button" class="btn-secondary" onclick="abrirModalEditar(<?php echo htmlspecialchars(json_encode($c)); ?>)">Editar</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Anular esta compra?');">
                                            <input type="hidden" name="accion" value="anular_compra">
                                            <input type="hidden" name="id_compra" value="<?php echo $c['id_compra']; ?>">
                                            <button type="submit" class="btn-secondary">Anular</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- Modal de edición de compra (RF 4.4) -->
    <div class="modal-overlay" id="modal-editar">
        <div class="modal-box">
            <h3>Editar Compra</h3>
            <form method="POST" id="form-editar-compra">
                <input type="hidden" name="accion" value="modificar_compra">
                <input type="hidden" name="id_compra" id="edit-id-compra">

                <label>Proveedor</label>
                <select name="id_proveedor" id="edit-id-proveedor" required>
                    <?php foreach ($proveedores as $p): ?>
                        <option value="<?php echo $p['id_proveedor']; ?>">
                            <?php echo htmlspecialchars($p['nom_proveedor']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Método de pago</label>
                <select name="metodo_pago" id="edit-metodo-pago" required>
                    <?php foreach ($metodosPago as $mp): ?>
                        <option value="<?php echo $mp['id_pago']; ?>">
                            <?php echo htmlspecialchars($mp['tipo_pago']); ?>
                            <?php echo $mp['nombre_empresa'] ? ' (' . htmlspecialchars($mp['nombre_empresa']) . ')' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Estado</label>
                <select name="estado" id="edit-estado" required>
                    <option value="Pendiente">Pendiente</option>
                    <option value="registrada">Registrada</option>
                    <option value="anulada">Anulada</option>
                </select>

                <div class="modal-actions">
                    <button type="submit" class="btn-pink">Guardar cambios</button>
                    <button type="button" class="btn-secondary" onclick="cerrarModalEditar()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function agregarLinea() {
            const contenedor = document.getElementById('items-container');
            const fila = document.createElement('div');
            fila.className = 'form-row';
            fila.innerHTML = `
                <input type="text" name="codigo_barras[]" placeholder="Código de barras" required>
                <input type="number" name="cantidad_producto[]" placeholder="Cantidad" min="1" required>
                <input type="number" name="precio_unitario[]" placeholder="Precio unitario" step="0.01" min="0" required>
            `;
            contenedor.appendChild(fila);
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