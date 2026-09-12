<!-- view/producto.php -->
<div class="container mt-4">
    <h2>Inventario de Productos</h2>

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
            <?php foreach ($productos as $p): ?>
                <!-- RF 3.5 / RI 3.5: Alerta si el stock es inferior o igual al límite mínimo -->
                <tr class="<?= ($p['alerta_stock'] && $p['estado'] === 'activo') ? 'table-danger' : '' ?>">
                    <td><?= htmlspecialchars($p['codigo_barras']) ?></td>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['categoria']) ?></td>
                    <td>$<?= number_format($p['precio_compra'], 2) ?></td>
                    <td>$<?= number_format($p['precio_venta'], 2) ?></td>
                    <td>
                        <?= $p['stock'] ?>
                        <?php if ($p['alerta_stock'] && $p['estado'] === 'activo'): ?>
                            <span class="badge bg-warning text-dark">⚠️ Stock Agotándose</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $p['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= ucfirst($p['estado']) ?>
                        </span>
                    </td>
                    <td>
                        <!-- Editar datos (RF 3.2) -->
                        <button class="btn btn-sm btn-primary" onclick="abrirModalEditar(<?= $p['id'] ?>)">Modificar</button>
                        
                        <!-- RF 3.3: Descontinuar / Eliminación Lógica -->
                        <?php if ($p['estado'] === 'activo'): ?>
                            <a href="index.php?action=descontinuar_producto&id=<?= $p['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('¿Está seguro de descontinuar este producto?')">
                               Descontinuar
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>