<?php

class Informes {
    private $conn;

    // Constructor: recibe la conexión a la BD
    public function __construct($servername, $username, $password, $dbname) {
        $this->conn = new mysqli($servername, $username, $password, $dbname);
        if ($this->conn->connect_error) {
            die("Error de conexión: " . $this->conn->connect_error);
        }
    }

    // Informe RF 2.1: Ganancia real por producto
    public function getGananciaPorProducto() {
        $sql = "
            SELECT p.nombre, 
                   SUM(v.cantidad) AS total_vendido,
                   p.precio_venta, 
                   p.precio_compra,
                   (p.precio_venta - p.precio_compra) * SUM(v.cantidad) AS ganancia_real
            FROM ventas v
            INNER JOIN productos p ON v.id_producto = p.id
            GROUP BY p.id
        ";
        return $this->conn->query($sql);
    }

    // Informe RF 2.2: Productos por rotación
    public function getProductosPorRotacion() {
        $sql = "
            SELECT p.nombre, SUM(v.cantidad) AS total_vendido
            FROM ventas v
            INNER JOIN productos p ON v.id_producto = p.id
            GROUP BY p.id
            ORDER BY total_vendido DESC
        ";
        return $this->conn->query($sql);
    }

    // Cerrar conexión
    public function cerrarConexion() {
        $this->conn->close();
    }
}

// ------------------- Uso de la clase -------------------

// Crear objeto de la clase
$informes = new Informes("localhost", "root", "", "proyecto_tema");

// Obtener resultados
$resultGanancia = $informes->getGananciaPorProducto();
$resultRotacion = $informes->getProductosPorRotacion();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Informes del Sistema</h1>

    <h2>Ganancia Real por Producto</h2>
    <table>
        <tr>
            <th>Producto</th>
            <th>Total Vendido</th>
            <th>Precio Venta</th>
            <th>Precio Compra</th>
            <th>Ganancia Real</th>
        </tr>
        <?php
        if ($resultGanancia->num_rows > 0) {
            while($row = $resultGanancia->fetch_assoc()) {
                echo "<tr>
                        <td>".$row["nombre"]."</td>
                        <td>".$row["total_vendido"]."</td>
                        <td>".$row["precio_venta"]."</td>
                        <td>".$row["precio_compra"]."</td>
                        <td>".$row["ganancia_real"]."</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No hay datos</td></tr>";
        }
        ?>
    </table>

    <h2>Productos por Rotación</h2>
    <table>
        <tr>
            <th>Producto</th>
            <th>Total Vendido</th>
        </tr>
        <?php
        if ($resultRotacion->num_rows > 0) {
            while($row = $resultRotacion->fetch_assoc()) {
                echo "<tr>
                        <td>".$row["nombre"]."</td>
                        <td>".$row["total_vendido"]."</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='2'>No hay datos</td></tr>";
        }
        ?>
    </table>
</body>
</html>

<?php
$informes->cerrarConexion();
?>

