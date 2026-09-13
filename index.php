<?php
declare(strict_types=1);

/**
 * Front controller: recibe todas las acciones POST/GET de formularios
 * y las despacha al controlador correspondiente.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controller/UsuarioController.php';
require_once __DIR__ . '/controller/ProductoController.php';
require_once __DIR__ . '/controller/CompraController.php';
require_once __DIR__ . '/controller/VentaController.php';
require_once __DIR__ . '/controller/InformeController.php';

$VENDEDORES = ['vendedor', 'cajero', 'administrador'];
$ADMINS = ['administrador'];

/** action => [controller, method, rolesPermitidos|null, soloPOST] */
$routes = [
    // Auth (públicas)
    'register' => [UsuarioController::class, 'registrar', null, true],
    'login' => [UsuarioController::class, 'login', null, true],
    'logout' => [UsuarioController::class, 'logout', null, false],
    'solicitar_reset' => [UsuarioController::class, 'solicitarReset', null, true],
    'restablecer' => [UsuarioController::class, 'restablecer', null, true],
    // Usuarios (admin)
    'save_user' => [UsuarioController::class, 'guardar', $ADMINS, true],
    'user_estado' => [UsuarioController::class, 'estado', $ADMINS, true],
    // Inventario (admin)
    'producto_guardar' => [ProductoController::class, 'guardar', $ADMINS, true],
    'producto_actualizar' => [ProductoController::class, 'actualizar', $ADMINS, true],
    'producto_estado' => [ProductoController::class, 'estado', $ADMINS, true],
    // Proveedores y compras (admin)
    'proveedor_guardar' => [CompraController::class, 'proveedorGuardar', $ADMINS, true],
    'proveedor_estado' => [CompraController::class, 'proveedorEstado', $ADMINS, true],
    'compra_item_add' => [CompraController::class, 'compraItemAdd', $ADMINS, true],
    'compra_item_remove' => [CompraController::class, 'compraItemRemove', $ADMINS, true],
    'compra_guardar' => [CompraController::class, 'compraGuardar', $ADMINS, true],
    'compra_actualizar' => [CompraController::class, 'compraActualizar', $ADMINS, true],
    'compra_anular' => [CompraController::class, 'compraAnular', $ADMINS, true],
    // POS (vendedores y admin)
    'pos_add' => [VentaController::class, 'posAdd', $VENDEDORES, true],
    'pos_qty' => [VentaController::class, 'posSetQty', $VENDEDORES, true],
    'pos_remove' => [VentaController::class, 'posRemove', $VENDEDORES, true],
    'pos_clear' => [VentaController::class, 'posClear', $VENDEDORES, true],
    'pos_pause' => [VentaController::class, 'posPause', $VENDEDORES, true],
    'pos_resume' => [VentaController::class, 'posResume', $VENDEDORES, true],
    'pos_pausada_delete' => [VentaController::class, 'posPausadaDelete', $VENDEDORES, true],
    'pos_cliente' => [VentaController::class, 'posCliente', $VENDEDORES, true],
    'cliente_rapido' => [VentaController::class, 'clienteRapido', $VENDEDORES, true],
    'checkout' => [VentaController::class, 'checkout', $VENDEDORES, true],
    'venta_anular' => [VentaController::class, 'anular', $VENDEDORES, true],
    // Informes (admin)
    'informe_guardar' => [InformeController::class, 'guardar', $ADMINS, true],
];

$action = (string) ($_REQUEST['action'] ?? '');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($action === '' || !isset($routes[$action])) {
    // Sin acción: home según sesión y rol (RF 1.4)
    if (is_logged_in()) {
        redirect_by_role();
    }
    redirect('view/login.php');
}

[$controllerClass, $controllerMethod, $roles, $onlyPost] = $routes[$action];

if ($roles !== null) {
    require_role($roles);
}
if ($onlyPost) {
    if ($method !== 'POST') {
        show_error(405, 'Método no permitido.');
    }
    csrf_check();
}

$controller = new $controllerClass();
$controller->$controllerMethod();
