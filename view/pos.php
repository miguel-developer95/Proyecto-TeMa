<?php
require_once __DIR__ . '/../helpers/auth_guard.php';
verificarRol(['vendedor', 'cajero', 'administrador']);

$user = $_SESSION['user'] ?? ['username' => 'invitado', 'rol' => '?'];
$diag = ['db_ok' => false, 'db_msg' => '', 'tablas' => [], 'productos' => [], 'productos_msg' => ''];

// Solo lectura: nunca escribe ni borra nada.
try {
    require_once __DIR__ . '/../config/connection.php';
    // Shim por si algun modelo pide conexion.php / Conexion
    if (is_file(__DIR__ . '/../config/conexion.php')) { require_once __DIR__ . '/../config/conexion.php'; }
    $conn = (new Connection())->conn;
    if ($conn) {
        $diag['db_ok'] = true;
        $diag['db_msg'] = 'Conexion OK';
        try { $diag['tablas'] = $conn->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN); } catch (Throwable $t) {}
        // Intenta tabla producto o productos (el original mezclaba nombres)
        $candidatas = ['producto', 'productos', 'PRODUCTOS'];
        foreach ($candidatas as $t) {
            if (in_array($t, $diag['tablas'])) {
                try {
                    $stmt = $conn->query("SELECT * FROM `$t` LIMIT 20");
                    $diag['productos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $diag['productos_msg'] = "Leyendo de tabla `$t` (" . count($diag['productos']) . " filas)";
                    break;
                } catch (Throwable $t2) { $diag['productos_msg'] = 'Error leyendo ' . $t . ': ' . $t2->getMessage(); }
            }
        }
        if (empty($diag['productos']) && $diag['productos_msg'] === '') {
            $diag['productos_msg'] = 'No se encontro tabla de productos (buscado: producto/productos). Revisa la BD.';
        }
    }
} catch (Throwable $e) {
    $diag['db_ok'] = false;
    $diag['db_msg'] = 'Sin conexion: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - Tentaciones Marlly</title>
    <link rel="stylesheet" href="/Proyecto-TeMa/public/styles/index.css">
</head>
<body style="font-family:sans-serif; padding:20px;">
    <h1>Punto de Venta (POS)</h1>
    <p>Hola, <strong><?php echo htmlspecialchars($user['username'] ?? ''); ?></strong> (rol: <?php echo htmlspecialchars($user['rol'] ?? ''); ?>)</p>
    <p>
        <a href="/Proyecto-TeMa/view/dashboard.php">Ir al Dashboard</a> |
        <a href="/Proyecto-TeMa/index.php?action=logout">Cerrar sesion</a>
    </p>
    <hr>
    <h2>Estado del sistema (solo lectura)</h2>
    <p>BD: <?php echo $diag['db_ok'] ? 'OK - ' . htmlspecialchars($diag['db_msg']) : 'FALLO - ' . htmlspecialchars($diag['db_msg']); ?></p>
    <?php if (!$diag['db_ok']): ?>
        <p>Pasos: 1) Abre XAMPP y enciende Apache + MySQL. 2) Crea la BD <code>tentaciones_marlly</code> si no existe. 3) Recarga esta pagina.</p>
    <?php else: ?>
        <p>Tablas: <?php echo htmlspecialchars(implode(', ', $diag['tablas'])); ?></p>
        <p><?php echo htmlspecialchars($diag['productos_msg']); ?></p>
        <?php if (!empty($diag['productos'])): ?>
        <table border="1" cellpadding="6" cellspacing="0">
            <tr><?php foreach (array_keys($diag['productos'][0]) as $c): ?><th><?php echo htmlspecialchars($c); ?></th><?php endforeach; ?></tr>
            <?php foreach ($diag['productos'] as $row): ?><tr><?php foreach ($row as $v): ?><td><?php echo htmlspecialchars((string)$v); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        </table>
        <?php endif; ?>
        <p style="color:#666">Nota: esta vista ya no dice "hello POS". La venta completa (carrito/cobro) esta en Proyecto-TeMa-test. Si quieres, la portamos aqui en esta misma rama.</p>
    <?php endif; ?>
    <script src="/Proyecto-TeMa/assets/js/session-timeout.js"></script>
</body>
</html>
