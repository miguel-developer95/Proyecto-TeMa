<?php
// controller/producto.php

class ProductoController {
    private $model;

    public function __construct($db) {
        $this->model = new ProductoModel($db);
    }

    // Listar inventario
    public function index() {
        $productos = $this->model->listarTodos();
        require_once 'view/producto.php'; // o la vista que utilices
    }

    // Procesar modificación (RF 3.2)
    public function editar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_producto   = $_POST['id'];
            $precio_compra = $_POST['precio_compra'];
            $precio_venta  = $_POST['precio_venta'];
            $estado        = $_POST['estado'];
            $descripcion   = $_POST['descripcion'];
            $id_usuario    = $_SESSION['usuario_id']; // Usuario responsable

            if ($this->model->modificar($id_producto, $precio_compra, $precio_venta, $estado, $descripcion, $id_usuario)) {
                header('Location: index.php?action=productos&msg=actualizado');
            } else {
                header('Location: index.php?action=productos&error=fallo');
            }
        }
    }

    // Descontinuar / Eliminación Lógica (RF 3.3)
    public function descontinuar() {
        if (isset($_GET['id'])) {
            $id_producto = $_GET['id'];
            $id_usuario  = $_SESSION['usuario_id']; // Usuario responsable

            if ($this->model->descontinuar($id_producto, $id_usuario)) {
                header('Location: index.php?action=productos&msg=descontinuado');
            }
        }
    }
}