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

    <!-- Barra de Filtros, Búsqueda y Paginación (Requisito 3.2) -->
    <div style="background: #fff; border-radius: 12px; padding: 15px 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); border: 1px solid #fce4ec; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between;">
        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; flex: 1; min-width: 300px;">
            <div style="position: relative; flex: 1; min-width: 220px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999;"></i>
                <input type="text" id="filtroTexto" placeholder="Buscar por código o nombre..." style="width: 100%; padding: 9px 12px 9px 36px; border: 1.5px solid #f3c6d8; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
            </div>
            <div>
                <select id="filtroCategoria" style="padding: 9px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; font-size: 14px; outline: none; background: #fff; cursor: pointer;">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <select id="filtroEstado" style="padding: 9px 12px; border: 1.5px solid #f3c6d8; border-radius: 8px; font-size: 14px; outline: none; background: #fff; cursor: pointer;">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activos</option>
                    <option value="inactivo">Inactivos</option>
                </select>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #666;">
            <label>Filas por página:</label>
            <select id="filasPorPagina" style="padding: 6px 10px; border: 1.5px solid #f3c6d8; border-radius: 6px; font-size: 13px; outline: none; background: #fff; cursor: pointer;">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="1000">Todos</option>
            </select>
        </div>
    </div>

    <!-- Tabla de Inventario -->
    <div class="table-responsive-wrapper">
        <table class="table table-bordered align-middle" id="tablaProductos">
            <thead>
                <tr>
                    <th style="cursor:pointer;" onclick="ordenarTabla(0)">Código de Barras <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                    <th style="cursor:pointer;" onclick="ordenarTabla(1)">Nombre <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                    <th style="cursor:pointer;" onclick="ordenarTabla(2)">Categoría <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                    <th style="cursor:pointer;" onclick="ordenarTabla(3)">P. Compra <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                    <th style="cursor:pointer;" onclick="ordenarTabla(4)">P. Venta <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                    <th style="cursor:pointer;" onclick="ordenarTabla(5)">Stock Disponible <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                    <th style="cursor:pointer;" onclick="ordenarTabla(6)">Estado <i class="fa-solid fa-sort" style="color:#aaa; font-size:11px;"></i></th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyProductos">
                <?php if (empty($productos)): ?>
                    <tr id="filaSinDatos">
                        <td colspan="8" style="text-align: center; color: #888; padding: 25px;">No hay productos registrados en el inventario.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($productos as $p): ?>
                        <tr class="fila-producto <?= ($p['alerta_stock'] && $p['estado'] === 'activo') ? 'table-danger' : '' ?>"
                            data-codigo="<?= htmlspecialchars(strtolower($p['codigo_barras'] ?? '')) ?>"
                            data-nombre="<?= htmlspecialchars(strtolower($p['nombre'] ?? '')) ?>"
                            data-categoria="<?= htmlspecialchars(strtolower($p['categoria'] ?? '')) ?>"
                            data-estado="<?= htmlspecialchars(strtolower($p['estado'] ?? '')) ?>">
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

    <!-- Paginador -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; flex-wrap: wrap; gap: 10px; font-size: 13px; color: #666;">
        <div id="infoPaginacion">Mostrando productos</div>
        <div id="controlesPaginacion" style="display: flex; gap: 5px;"></div>
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
                btnAnt.textContent = '« Ant';
                btnAnt.disabled = paginaActual === 1;
                btnAnt.onclick = () => { paginaActual--; filtrarYPaginar(); };
                contenedorBotones.appendChild(btnAnt);

                for (let p = 1; p <= totalPaginas; p++) {
                    const btnP = document.createElement('button');
                    btnP.type = 'button';
                    btnP.className = `btn btn-sm ${p === paginaActual ? 'btn-primary' : 'btn-secondary'}`;
                    btnP.textContent = p;
                    btnP.onclick = () => { paginaActual = p; filtrarYPaginar(); };
                    contenedorBotones.appendChild(btnP);
                }

                const btnSig = document.createElement('button');
                btnSig.type = 'button';
                btnSig.className = 'btn btn-sm btn-secondary';
                btnSig.textContent = 'Sig »';
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