<!-- view/producto.php -->
<?php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../helpers/auth_guard.php';
verificarRol(['administrador']);
require_once __DIR__ . '/../helpers/funciones.php';
require_once __DIR__ . '/../model/producto.php';
require_once __DIR__ . '/../model/proveedores.php';

$productoModel = new Producto();
$productos = $productoModel->listarTodos();
$proveedores = (new Proveedores())->listarProveedores('activo');
$alertaBajoStockTotal = count(array_filter($productos, fn($p) => !empty($p['alerta_stock']) && ($p['estado'] ?? '') === 'activo'));

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

$titulo = 'Inventario';
$subtitulo = 'Gestión de catálogo de productos, existencias y alertas de stock';
require __DIR__ . '/partials/head.php';
?>

<div class="container" style="max-width: 1400px; margin: 0 auto;">
    <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <button class="btn-primary" onclick="toggleFormNuevo()" style="cursor: pointer;">
            <i class="fa-solid fa-plus"></i> Registrar Nuevo Producto
        </button>
    </div>

    <?php if ($alertaBajoStockTotal > 0): ?>
        <div style="background:#fff3e0; color:#e65100; padding:12px 16px; border-radius:10px; margin-bottom:15px; border-left: 4px solid #f57c00; font-size:14px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <strong>Aviso de Reabastecimiento (RF 3.5):</strong> Hay <strong><?= $alertaBajoStockTotal ?></strong> producto(s) activo(s) con stock igual o inferior a su stock mínimo configurado.
        </div>
    <?php endif; ?>

    <?php if ($msg === 'actualizado'): ?>
        <div class="alert alert-success" style="background:#e8f5e9; color:#2e7d32; padding:12px; border-radius:10px; margin-bottom:15px;">
            <i class="fa-solid fa-circle-check"></i> Producto actualizado correctamente.
        </div>
    <?php elseif ($msg === 'descontinuado'): ?>
        <div class="alert alert-success" style="background:#e8f5e9; color:#2e7d32; padding:12px; border-radius:10px; margin-bottom:15px;">
            <i class="fa-solid fa-circle-check"></i> Producto descontinuado (marcado como inactivo).
        </div>
    <?php elseif ($msg === 'registrado'): ?>
        <div class="alert alert-success" style="background:#e8f5e9; color:#2e7d32; padding:12px; border-radius:10px; margin-bottom:15px;">
            <i class="fa-solid fa-circle-check"></i> Producto registrado con éxito en el inventario.
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-error" style="background:#ffebee; color:#c62828; padding:12px; border-radius:10px; margin-bottom:15px;">
            <i class="fa-solid fa-triangle-exclamation"></i> Ocurrió un error al procesar la operación.
        </div>
    <?php endif; ?>

    <!-- Formulario colapsable para registrar producto (RF 3.1) -->
    <div id="formNuevoProducto" style="display: none; background: #fff; border-radius: 20px; padding: 25px; box-shadow: 0 8px 30px rgba(230, 60, 130, 0.12); margin-bottom: 25px; border: 1px solid #fce4ec;">
        <h3 style="color: #2b3a55; margin-bottom: 15px; font-size: 18px;"><i class="fa-solid fa-box-open" style="color:#e63c82;"></i> Nuevo Producto</h3>
        <form action="/Proyecto-TeMa/index.php" method="POST">
            <input type="hidden" name="action" value="registrar_producto">
            <?= csrf_field() ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">Código de Barras *</label>
                    <input type="text" name="codigo_barras" required placeholder="Ej: 770123456789" style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">Nombre del Producto *</label>
                    <input type="text" name="nombre" required placeholder="Ej: Galletas Festival Chocolate" style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">Categoría</label>
                    <input type="text" name="categoria" placeholder="Ej: Galletas y Dulces" style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">Precio de Compra ($) *</label>
                    <input type="number" step="0.01" min="0" name="precio_compra" required placeholder="0.00" style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">Precio de Venta ($) *</label>
                    <input type="number" step="0.01" min="0" name="precio_venta" required placeholder="0.00" style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">Stock Inicial *</label>
                    <input type="number" min="0" name="cantidad_stock" required value="0" style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">Stock Mínimo (Alerta) *</label>
                    <input type="number" min="1" name="stock_minimo" required value="5" style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">Proveedor (Opcional)</label>
                    <select name="id_proveedor" style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                        <option value="">-- Sin proveedor asignado --</option>
                        <?php foreach ($proveedores as $prov): ?>
                            <option value="<?= e($prov['id_proveedor']) ?>"><?= e($prov['nombre_razon_social'] ?? $prov['nom_proveedor']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">Descripción</label>
                <textarea name="descripcion" rows="2" placeholder="Detalles o especificaciones del producto..." style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box; font-family: inherit;"></textarea>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Guardar Producto</button>
                <button type="button" class="btn btn-danger" onclick="toggleFormNuevo()" style="padding: 10px 20px;">Cancelar</button>
            </div>
        </form>
    </div>

    <!-- Tabla de Inventario -->
    <div class="table-responsive-wrapper">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Código de Barras</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>P. Compra</th>
                    <th>P. Venta</th>
                    <th>Stock Disponible</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productos)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #888; padding: 25px;">No hay productos registrados en el inventario.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($productos as $p): ?>
                        <tr class="<?= ($p['alerta_stock'] && $p['estado'] === 'activo') ? 'table-danger' : '' ?>">
                            <td><strong><?= htmlspecialchars($p['codigo_barras'] ?? '—') ?></strong></td>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= htmlspecialchars($p['categoria'] ?? '—') ?></td>
                            <td>$<?= number_format($p['precio_compra'], 2) ?></td>
                            <td>$<?= number_format($p['precio_venta'], 2) ?></td>
                            <td>
                                <?= $p['cantidad_stock'] ?>
                                <?php if ($p['alerta_stock'] && $p['estado'] === 'activo'): ?>
                                    <span class="badge bg-warning text-dark" style="margin-left: 6px;">⚠️ Stock Agotándose</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $p['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= ucfirst($p['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <!-- Editar datos (RF 3.2) -->
                                <button class="btn btn-sm btn-primary" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($p)) ?>)">Modificar</button>
                                
                                <!-- RF 3.3: Descontinuar / Eliminación Lógica -->
                                <?php if ($p['estado'] === 'activo'): ?>
                                    <a href="/Proyecto-TeMa/index.php?action=descontinuar_producto&id=<?= $p['id_producto'] ?>" 
                                        class="btn btn-sm btn-danger" 
                                        onclick="return confirm('¿Está seguro de descontinuar este producto?')">
                                        Descontinuar
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Modificar Producto (RF 3.2) -->
<div id="modalEditarProducto" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 20px; padding: 25px; width: 480px; max-width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
        <h3 style="color: #2b3a55; margin-bottom: 15px; border-bottom: 1px solid #fce4ec; padding-bottom: 10px;">
            <i class="fa-solid fa-pen-to-square" style="color: #e63c82;"></i> Modificar Producto
        </h3>
        <form action="/Proyecto-TeMa/index.php" method="POST">
            <input type="hidden" name="action" value="modificar_producto">
            <input type="hidden" name="id_producto" id="edit_id_producto">
            <?= csrf_field() ?>

            <div style="margin-bottom: 12px;">
                <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">Producto</label>
                <input type="text" id="edit_nombre_display" disabled style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; box-sizing: border-box;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">P. Compra ($) *</label>
                    <input type="number" step="0.01" min="0" name="precio_compra" id="edit_precio_compra" required style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">P. Venta ($) *</label>
                    <input type="number" step="0.01" min="0" name="precio_venta" id="edit_precio_venta" required style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">Estado</label>
                <select name="estado" id="edit_estado" style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo (Descontinuado)</option>
                </select>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="font-size: 13px; color: #555; display: block; margin-bottom: 4px;">Descripción</label>
                <textarea name="descripcion" id="edit_descripcion" rows="2" style="width: 100%; padding: 10px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box; font-family: inherit;"></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModalEditar()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleFormNuevo() {
        const f = document.getElementById('formNuevoProducto');
        f.style.display = (f.style.display === 'none' || f.style.display === '') ? 'block' : 'none';
    }

    function abrirModalEditar(p) {
        document.getElementById('edit_id_producto').value = p.id_producto;
        document.getElementById('edit_nombre_display').value = p.nombre;
        document.getElementById('edit_precio_compra').value = p.precio_compra;
        document.getElementById('edit_precio_venta').value = p.precio_venta;
        document.getElementById('edit_estado').value = p.estado;
        document.getElementById('edit_descripcion').value = p.descripcion || '';
        document.getElementById('modalEditarProducto').style.display = 'flex';
    }

    function cerrarModalEditar() {
        document.getElementById('modalEditarProducto').style.display = 'none';
    }
</script>

<?php require __DIR__ . '/partials/foot.php'; ?>