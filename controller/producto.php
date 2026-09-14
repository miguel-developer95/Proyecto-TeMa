<?php
// controller/producto.php
require_once __DIR__ . '/../model/producto.php';

class ProductoController {
    private Producto $model;

    public function __construct($db = null) {
        $this->model = new Producto();
    }

    // Listar inventario
    public function index() {
        $productos = $this->model->listarTodos();
        require_once __DIR__ . '/../view/producto.php';
    }

    // Procesar modificación (RF 3.2)
    public function editar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_producto   = (int) ($_POST['id_producto'] ?? $_POST['id'] ?? 0);
            $precio_compra = (float) ($_POST['precio_compra'] ?? 0);
            $precio_venta  = (float) ($_POST['precio_venta'] ?? 0);
            $estado        = $_POST['estado'] ?? 'activo';
            $descripcion   = $_POST['descripcion'] ?? null;
            $id_usuario    = (int) ($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 1);

            if ($id_producto > 0 && $this->model->modificar($id_producto, $precio_compra, $precio_venta, $estado, $descripcion, $id_usuario)) {
                header('Location: /Proyecto-TeMa/view/producto.php?msg=actualizado');
            } else {
                header('Location: /Proyecto-TeMa/view/producto.php?error=fallo');
            }
            exit();
        }
    }

    // Descontinuar / Eliminación Lógica (RF 3.3)
    public function descontinuar() {
        $id_producto = (int) ($_GET['id'] ?? $_GET['id_producto'] ?? 0);
        $id_usuario  = (int) ($_SESSION['user']['id'] ?? $_SESSION['user']['id_usuario'] ?? 1);

        if ($id_producto > 0 && $this->model->descontinuar($id_producto, $id_usuario)) {
            header('Location: /Proyecto-TeMa/view/producto.php?msg=descontinuado');
        } else {
            header('Location: /Proyecto-TeMa/view/producto.php?error=fallo');
        }
        exit();
    }

    // Registrar nuevo producto (RF 3.1)
    public function registrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'codigo_barras'  => trim($_POST['codigo_barras'] ?? ''),
                'nombre'         => trim($_POST['nombre'] ?? ''),
                'descripcion'    => trim($_POST['descripcion'] ?? ''),
                'categoria'      => trim($_POST['categoria'] ?? ''),
                'precio_compra'  => (float) ($_POST['precio_compra'] ?? 0),
                'precio_venta'   => (float) ($_POST['precio_venta'] ?? 0),
                'cantidad_stock' => (int) ($_POST['cantidad_stock'] ?? 0),
                'stock_minimo'   => (int) ($_POST['stock_minimo'] ?? 5),
                'id_proveedor'   => !empty($_POST['id_proveedor']) ? (int) $_POST['id_proveedor'] : null,
            ];

            if (!empty($datos['nombre']) && $this->model->registrar($datos)) {
                header('Location: /Proyecto-TeMa/view/producto.php?msg=registrado');
            } else {
                header('Location: /Proyecto-TeMa/view/producto.php?error=fallo_registro');
            }
            exit();
        }
    }
}