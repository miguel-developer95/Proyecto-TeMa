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
$categorias = array_unique(array_filter(array_map(fn($p) => trim($p['categoria'] ?? ''), $productos)));
sort($categorias);

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

$titulo = 'Inventario';
$subtitulo = 'Gestión de catálogo de productos, existencias y alertas de stock';
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

    .inventario-top-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .producto-form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .form-action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    @media (max-width: 640px) {
        .inventario-top-actions {
            justify-content: stretch;
        }
        .inventario-top-actions button {
            width: 100%;
            justify-content: center;
        }
        .producto-form-grid {
            grid-template-columns: 1fr;
        }
        .form-action-buttons {
            flex-direction: column;
        }
        .form-action-buttons button {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div>
    <div class="inventario-top-actions">
        <button class="btn-primary" onclick="toggleFormNuevo()" style="cursor: pointer;">
            <i class="fa-solid fa-plus"></i> Registrar Nuevo Producto
        </button>
    </div>

    <?php if ($alertaBajoStockTotal > 0): ?>
        <div style="background:#fff3e0; color:#e65100; padding:12px 16px; border-radius:12px; margin-bottom:18px; border-left: 4px solid #f57c00; font-size:14px; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong>Aviso de Reabastecimiento (RF 3.5):</strong> Hay <strong><?= $alertaBajoStockTotal ?></strong> producto(s) activo(s) con stock igual o inferior a su stock mínimo configurado.
            </div>
        </div>
    <?php endif; ?>

    <?php if ($msg === 'actualizado'): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> Producto actualizado correctamente.
        </div>
    <?php elseif ($msg === 'descontinuado'): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> Producto descontinuado (marcado como inactivo).
        </div>
    <?php elseif ($msg === 'registrado'): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> Producto registrado con éxito en el inventario.
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i> Ocurrió un error al procesar la operación.
        </div>
    <?php endif; ?>

    <!-- Formulario colapsable para registrar producto (RF 3.1) -->
    <div id="formNuevoProducto" class="crud-card" style="display: none;">
        <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px;">
            <i class="fa-solid fa-box-open" style="color:#e63c82;"></i> Registrar Nuevo Producto
        </h3>
        <form action="/Proyecto-TeMa/index.php" method="POST">
            <input type="hidden" name="action" value="registrar_producto">
            <?= csrf_field() ?>
            <div class="producto-form-grid">
                <div>
                    <label style="font-size: 13px; font-weight: 700; color: #4a5568; display: block; margin-bottom: 5px;">Código de Barras *</label>
                    <input type="text" name="codigo_barras" required placeholder="Ej: 770123456789" style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 700; color: #4a5568; display: block; margin-bottom: 5px;">Nombre del Producto *</label>
                    <input type="text" name="nombre" required placeholder="Ej: Galletas Festival Chocolate" style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 700; color: #4a5568; display: block; margin-bottom: 5px;">Categoría</label>
                    <input type="text" name="categoria" placeholder="Ej: Galletas y Dulces" style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 700; color: #4a5568; display: block; margin-bottom: 5px;">Precio de Compra ($) *</label>
                    <input type="number" step="0.01" min="0" name="precio_compra" required placeholder="0.00" style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 700; color: #4a5568; display: block; margin-bottom: 5px;">Precio de Venta ($) *</label>
                    <input type="number" step="0.01" min="0" name="precio_venta" required placeholder="0.00" style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 700; color: #4a5568; display: block; margin-bottom: 5px;">Stock Inicial *</label>
                    <input type="number" min="0" name="cantidad_stock" required value="0" style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 700; color: #4a5568; display: block; margin-bottom: 5px;">Stock Mínimo (Alerta) *</label>
                    <input type="number" min="1" name="stock_minimo" required value="5" style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 700; color: #4a5568; display: block; margin-bottom: 5px;">Proveedor (Opcional)</label>
                    <select name="id_proveedor" style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box; background: #fff;">
                        <option value="">-- Sin proveedor asignado --</option>
                        <?php foreach ($proveedores as $prov): ?>
                            <option value="<?= e($prov['id_proveedor']) ?>"><?= e($prov['nombre_razon_social'] ?? $prov['nom_proveedor']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; font-weight: 700; color: #4a5568; display: block; margin-bottom: 5px;">Descripción</label>
                <textarea name="descripcion" rows="2" placeholder="Detalles o especificaciones del producto..." style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box; font-family: inherit;"></textarea>
            </div>
            <div class="form-action-buttons">
                <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">Guardar Producto</button>
                <button type="button" class="btn btn-danger" onclick="toggleFormNuevo()" style="padding: 10px 22px;">Cancelar</button>
            </div>
        </form>
    </div>

    <!-- Barra de Filtros, Búsqueda y Paginación (Requisito 3.2) -->
    <div class="inventario-filtros-bar">
        <div class="filtro-inputs-row">
            <div class="filtro-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="filtroTexto" placeholder="Buscar por código o nombre...">
            </div>
            <div class="filtro-select-group">
                <select id="filtroCategoria">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activos</option>
                    <option value="inactivo">Inactivos</option>
                </select>
            </div>
        </div>
        <div class="filtro-paginacion-size">
            <label for="filasPorPagina">Filas por página:</label>
            <select id="filasPorPagina">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="1000">Todos</option>
            </select>
        </div>
    </div>

    <!-- Tabla de Inventario en Tarjeta -->
    <section class="content-box" style="margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-boxes-stacked" style="color: #e63c82;"></i> Catálogo de Productos
            </h3>
            <span style="font-size: 13px; color: #888888; font-weight: 600;">
                Total: <?= count($productos) ?> productos registrados
            </span>
        </div>

        <div class="table-responsive">
            <table class="tabla-inventario" id="tablaProductos">
                <thead>
                    <tr>
                        <th style="cursor:pointer;" onclick="ordenarTabla(0)">Código de Barras <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                        <th style="cursor:pointer;" onclick="ordenarTabla(1)">Nombre <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                        <th style="cursor:pointer;" onclick="ordenarTabla(2)">Categoría <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                        <th style="cursor:pointer;" onclick="ordenarTabla(3)">P. Compra <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                        <th style="cursor:pointer;" onclick="ordenarTabla(4)">P. Venta <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                        <th style="cursor:pointer;" onclick="ordenarTabla(5)">Stock Disponible <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                        <th style="cursor:pointer;" onclick="ordenarTabla(6)">Estado <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyProductos">
                    <?php if (empty($productos)): ?>
                        <tr id="filaSinDatos">
                            <td colspan="8" style="text-align: center; color: #888; padding: 25px;">
                                <i class="fa-solid fa-box-open" style="font-size: 24px; color: #f3c6d8; display: block; margin-bottom: 8px;"></i>
                                No hay productos registrados en el inventario.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                            <tr class="fila-producto <?= ($p['alerta_stock'] && $p['estado'] === 'activo') ? 'table-danger' : '' ?>"
                                data-codigo="<?= htmlspecialchars(strtolower($p['codigo_barras'] ?? '')) ?>"
                                data-nombre="<?= htmlspecialchars(strtolower($p['nombre'] ?? '')) ?>"
                                data-categoria="<?= htmlspecialchars(strtolower($p['categoria'] ?? '')) ?>"
                                data-estado="<?= htmlspecialchars(strtolower($p['estado'] ?? '')) ?>">
                                <td style="font-weight: 700; color: #2b3a55;"><?= htmlspecialchars($p['codigo_barras'] ?? '—') ?></td>
                                <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                                <td><?= htmlspecialchars($p['categoria'] ?? '—') ?></td>
                                <td>$<?= number_format($p['precio_compra'], 2) ?></td>
                                <td style="font-weight: 700; color: #e63c82;">$<?= number_format($p['precio_venta'], 2) ?></td>
                                <td>
                                    <strong><?= $p['cantidad_stock'] ?></strong>
                                    <?php if ($p['alerta_stock'] && $p['estado'] === 'activo'): ?>
                                        <span class="badge bg-warning text-dark" style="margin-left: 6px;">⚠️ Stock Bajo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $p['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($p['estado']) ?>
                                    </span>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <!-- Editar datos (RF 3.2) -->
                                    <button type="button" class="btn-action-edit" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($p)) ?>)">
                                        <i class="fa-solid fa-pen-to-square"></i> Modificar
                                    </button>
                                    
                                    <!-- RF 3.3: Descontinuar / Eliminación Lógica -->
                                    <?php if ($p['estado'] === 'activo'): ?>
                                        <a href="/Proyecto-TeMa/index.php?action=descontinuar_producto&id=<?= $p['id_producto'] ?>" 
                                            class="btn-action-cancel" 
                                            onclick="return confirm('¿Está seguro de descontinuar este producto?')">
                                            <i class="fa-solid fa-ban"></i> Descontinuar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginador -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 18px; padding-top: 14px; border-top: 1px solid #fce4ec; flex-wrap: wrap; gap: 12px; font-size: 13px; color: #64748b;">
            <div id="infoPaginacion" style="font-weight: 600;">Mostrando productos</div>
            <div id="controlesPaginacion" style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;"></div>
        </div>
    </section>
</div>

<!-- Modal para Modificar Producto (RF 3.2) -->
<div id="modalEditarProducto" class="modal-overlay">
    <div class="modal-box">
        <h3 style="color: #2b3a55; margin-top: 0; margin-bottom: 16px; border-bottom: 1px solid #fce4ec; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-pen-to-square" style="color: #e63c82;"></i> Modificar Producto
        </h3>
        <form action="/Proyecto-TeMa/index.php" method="POST">
            <input type="hidden" name="action" value="modificar_producto">
            <input type="hidden" name="id_producto" id="edit_id_producto">
            <?= csrf_field() ?>

            <div style="margin-bottom: 12px;">
                <label style="font-size: 13px; font-weight: 600; color: #4a5568; display: block; margin-bottom: 4px;">Producto</label>
                <input type="text" id="edit_nombre_display" disabled style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; box-sizing: border-box; color: #555;">
            </div>

            <div class="modal-grid-precios" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="font-size: 13px; font-weight: 600; color: #4a5568; display: block; margin-bottom: 4px;">P. Compra ($) *</label>
                    <input type="number" step="0.01" min="0" name="precio_compra" id="edit_precio_compra" required style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 600; color: #4a5568; display: block; margin-bottom: 4px;">P. Venta ($) *</label>
                    <input type="number" step="0.01" min="0" name="precio_venta" id="edit_precio_venta" required style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="font-size: 13px; font-weight: 600; color: #4a5568; display: block; margin-bottom: 4px;">Estado</label>
                <select name="estado" id="edit_estado" style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box; background: #fff;">
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo (Descontinuado)</option>
                </select>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="font-size: 13px; font-weight: 600; color: #4a5568; display: block; margin-bottom: 4px;">Descripción</label>
                <textarea name="descripcion" id="edit_descripcion" rows="2" style="width: 100%; padding: 10px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; box-sizing: border-box; font-family: inherit;"></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap;">
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
        if (f.style.display === 'block') {
            f.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
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

    // Cerrar modal al hacer clic en el fondo
    document.getElementById('modalEditarProducto')?.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalEditar();
    });

    // === Lógica de Búsqueda, Filtrado, Ordenamiento y Paginación (RF 3.2) ===
    let paginaActual = 1;
    let ordenColumna = -1;
    let ordenAsc = true;

    function filtrarYPaginar() {
        const texto = document.getElementById('filtroTexto') ? document.getElementById('filtroTexto').value.toLowerCase().trim() : '';
        const categoria = document.getElementById('filtroCategoria') ? document.getElementById('filtroCategoria').value.toLowerCase().trim() : '';
        const estado = document.getElementById('filtroEstado') ? document.getElementById('filtroEstado').value.toLowerCase().trim() : '';
        const filasPorPagina = document.getElementById('filasPorPagina') ? parseInt(document.getElementById('filasPorPagina').value, 10) : 10;

        const filas = Array.from(document.querySelectorAll('.fila-producto'));
        const filasVisibles = filas.filter(fila => {
            const cod = fila.dataset.codigo || '';
            const nom = fila.dataset.nombre || '';
            const cat = fila.dataset.categoria || '';
            const est = fila.dataset.estado || '';

            const coincideTexto = !texto || cod.includes(texto) || nom.includes(texto);
            const coincideCat = !categoria || cat === categoria;
            const coincideEst = !estado || est === estado;

            return coincideTexto && coincideCat && coincideEst;
        });

        filas.forEach(f => f.style.display = 'none');

        const totalVisibles = filasVisibles.length;
        const totalPaginas = Math.ceil(totalVisibles / filasPorPagina) || 1;

        if (paginaActual > totalPaginas) paginaActual = totalPaginas;
        if (paginaActual < 1) paginaActual = 1;

        const inicio = (paginaActual - 1) * filasPorPagina;
        const fin = Math.min(inicio + filasPorPagina, totalVisibles);

        for (let i = inicio; i < fin; i++) {
            filasVisibles[i].style.display = '';
        }

        const info = document.getElementById('infoPaginacion');
        if (info) {
            info.textContent = totalVisibles > 0 
                ? `Mostrando ${inicio + 1} a ${fin} de ${totalVisibles} producto(s)` 
                : 'No se encontraron productos coincidentes';
        }

        const contenedorBotones = document.getElementById('controlesPaginacion');
        if (contenedorBotones) {
            contenedorBotones.innerHTML = '';
            if (totalPaginas > 1) {
                const btnAnt = document.createElement('button');
                btnAnt.type = 'button';
                btnAnt.className = 'btn btn-sm btn-secondary';
                btnAnt.innerHTML = '<i class="fa-solid fa-chevron-left"></i> Ant';
                btnAnt.disabled = paginaActual === 1;
                btnAnt.onclick = () => { paginaActual--; filtrarYPaginar(); };
                contenedorBotones.appendChild(btnAnt);

                let startP = Math.max(1, paginaActual - 2);
                let endP = Math.min(totalPaginas, paginaActual + 2);
                if (paginaActual <= 3) {
                    endP = Math.min(5, totalPaginas);
                }
                if (paginaActual >= totalPaginas - 2) {
                    startP = Math.max(1, totalPaginas - 4);
                }

                if (startP > 1) {
                    const btn1 = document.createElement('button');
                    btn1.type = 'button';
                    btn1.className = 'btn btn-sm btn-secondary';
                    btn1.textContent = '1';
                    btn1.onclick = () => { paginaActual = 1; filtrarYPaginar(); };
                    contenedorBotones.appendChild(btn1);

                    if (startP > 2) {
                        const dots = document.createElement('span');
                        dots.textContent = '...';
                        dots.style.padding = '0 4px';
                        dots.style.color = '#888';
                        contenedorBotones.appendChild(dots);
                    }
                }

                for (let p = startP; p <= endP; p++) {
                    const btnP = document.createElement('button');
                    btnP.type = 'button';
                    btnP.className = `btn btn-sm ${p === paginaActual ? 'btn-primary' : 'btn-secondary'}`;
                    btnP.textContent = p;
                    btnP.onclick = () => { paginaActual = p; filtrarYPaginar(); };
                    contenedorBotones.appendChild(btnP);
                }

                if (endP < totalPaginas) {
                    if (endP < totalPaginas - 1) {
                        const dots = document.createElement('span');
                        dots.textContent = '...';
                        dots.style.padding = '0 4px';
                        dots.style.color = '#888';
                        contenedorBotones.appendChild(dots);
                    }

                    const btnUlt = document.createElement('button');
                    btnUlt.type = 'button';
                    btnUlt.className = 'btn btn-sm btn-secondary';
                    btnUlt.textContent = totalPaginas;
                    btnUlt.onclick = () => { paginaActual = totalPaginas; filtrarYPaginar(); };
                    contenedorBotones.appendChild(btnUlt);
                }

                const btnSig = document.createElement('button');
                btnSig.type = 'button';
                btnSig.className = 'btn btn-sm btn-secondary';
                btnSig.innerHTML = 'Sig <i class="fa-solid fa-chevron-right"></i>';
                btnSig.disabled = paginaActual === totalPaginas;
                btnSig.onclick = () => { paginaActual++; filtrarYPaginar(); };
                contenedorBotones.appendChild(btnSig);
            }
        }
    }

    function ordenarTabla(colIdx) {
        const tbody = document.getElementById('tbodyProductos');
        if (!tbody) return;
        const filas = Array.from(tbody.querySelectorAll('.fila-producto'));
        
        if (ordenColumna === colIdx) {
            ordenAsc = !ordenAsc;
        } else {
            ordenColumna = colIdx;
            ordenAsc = true;
        }

        filas.sort((a, b) => {
            let valA = a.cells[colIdx].innerText.trim().replace('$', '').replace(',', '');
            let valB = b.cells[colIdx].innerText.trim().replace('$', '').replace(',', '');

            let numA = parseFloat(valA);
            let numB = parseFloat(valB);

            if (!isNaN(numA) && !isNaN(numB)) {
                return ordenAsc ? numA - numB : numB - numA;
            }
            return ordenAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
        });

        filas.forEach(f => tbody.appendChild(f));
        filtrarYPaginar();
    }

    document.getElementById('filtroTexto')?.addEventListener('input', () => { paginaActual = 1; filtrarYPaginar(); });
    document.getElementById('filtroCategoria')?.addEventListener('change', () => { paginaActual = 1; filtrarYPaginar(); });
    document.getElementById('filtroEstado')?.addEventListener('change', () => { paginaActual = 1; filtrarYPaginar(); });
    document.getElementById('filasPorPagina')?.addEventListener('change', () => { paginaActual = 1; filtrarYPaginar(); });

    filtrarYPaginar();
</script>

<?php require __DIR__ . '/partials/foot.php'; ?>