<?php
// controller/InformeController.php
require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../helpers/auth_guard.php';
require_once __DIR__ . '/../model/informes.php';
require_once __DIR__ . '/../libs/fpdf.php';

class PDFReporte extends FPDF
{
    private string $tituloInforme = '';
    private string $rangoFechas = '';

    public function configurar(string $tituloInforme, string $rangoFechas)
    {
        $this->tituloInforme = $tituloInforme;
        $this->rangoFechas = $rangoFechas;
    }

    public function Header()
    {
        $logoPath = __DIR__ . '/../public/logo.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 15, 12, 18);
        }

        $this->SetX(38);
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(230, 60, 130);
        $this->Cell(0, 7, utf8_decode('TENTACIONES MARLLY'), 0, 1);

        $this->SetX(38);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, utf8_decode('Mini Tienda de Consumo Diario - Sistema de Gestión TeMa'), 0, 1);

        $this->SetX(38);
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(43, 58, 85);
        $this->Cell(0, 6, utf8_decode($this->tituloInforme), 0, 1);

        $this->SetX(38);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, utf8_decode('Período: ' . $this->rangoFechas . ' | Generado: ' . date('Y-m-d H:i:s')), 0, 1);

        $this->Ln(4);
        $this->SetDrawColor(243, 198, 216);
        $this->SetLineWidth(0.5);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(6);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetDrawColor(230, 230, 230);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(140, 140, 140);
        $this->Cell(90, 10, utf8_decode('Tentaciones Marlly © ' . date('Y')), 0, 0, 'L');
        $this->Cell(90, 10, utf8_decode('Página ') . $this->PageNo() . ' / {nb}', 0, 0, 'R');
    }
}

class InformeController
{
    private Informes $informesModel;

    public function __construct()
    {
        verificarRol(['administrador']);
        $this->informesModel = new Informes();
    }

    private function obtenerDatos(string $tipo, string $inicio, string $fin): array
    {
        switch ($tipo) {
            case 'ganancia_categoria':
                return [
                    'titulo' => 'Informe de Ganancia Real por Categoría (RF 2.1)',
                    'datos'  => $this->informesModel->gananciaPorCategoria($inicio, $fin),
                ];
            case 'rotacion_alta':
                return [
                    'titulo' => 'Informe de Mayor Rotación / Top Ventas (RF 2.2)',
                    'datos'  => $this->informesModel->productosMasVendidos($inicio, $fin, 50),
                ];
            case 'rotacion_baja':
                return [
                    'titulo' => 'Informe de Baja Rotación / Sin Movimiento (RF 2.2)',
                    'datos'  => $this->informesModel->productosBajaRotacion($inicio, $fin),
                ];
            case 'ganancia_producto':
            default:
                return [
                    'titulo' => 'Informe de Ganancia Real por Producto (RF 2.1)',
                    'datos'  => $this->informesModel->gananciaPorProducto($inicio, $fin),
                ];
        }
    }

    public function exportarCSV(string $tipo, string $inicio, string $fin): void
    {
        $info = $this->obtenerDatos($tipo, $inicio, $fin);
        $datos = $info['datos'];
        $nombreArchivo = 'informe_' . $tipo . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        if ($tipo === 'ganancia_producto') {
            fputcsv($out, ['Codigo', 'Producto', 'Categoria', 'Unidades Vendidas', 'Total Ingresos ($)', 'Costo Total ($)', 'Ganancia Real ($)'], ';');
            foreach ($datos as $fila) {
                fputcsv($out, [
                    $fila['codigo_barras'] ?? '',
                    $fila['nombre'] ?? '',
                    $fila['categoria'] ?? 'Sin categoría',
                    $fila['cantidad_vendida'] ?? 0,
                    number_format((float)($fila['total_ingresos'] ?? 0), 2, '.', ''),
                    number_format((float)($fila['total_costo'] ?? 0), 2, '.', ''),
                    number_format((float)($fila['ganancia_real'] ?? 0), 2, '.', '')
                ], ';');
            }
        } elseif ($tipo === 'ganancia_categoria') {
            fputcsv($out, ['Categoria', 'Variedad Productos', 'Unidades Vendidas', 'Total Ingresos ($)', 'Costo Total ($)', 'Ganancia Real ($)'], ';');
            foreach ($datos as $fila) {
                fputcsv($out, [
                    $fila['categoria'] ?? 'Sin categoría',
                    $fila['productos_distintos'] ?? 0,
                    $fila['cantidad_vendida'] ?? 0,
                    number_format((float)($fila['total_ingresos'] ?? 0), 2, '.', ''),
                    number_format((float)($fila['total_costo'] ?? 0), 2, '.', ''),
                    number_format((float)($fila['ganancia_real'] ?? 0), 2, '.', '')
                ], ';');
            }
        } elseif ($tipo === 'rotacion_alta') {
            fputcsv($out, ['Ranking', 'Codigo', 'Producto', 'Categoria', 'Stock Disponible', 'Unidades Vendidas', 'Total Recaudado ($)'], ';');
            foreach ($datos as $i => $fila) {
                fputcsv($out, [
                    'Top ' . ($i + 1),
                    $fila['codigo_barras'] ?? '',
                    $fila['nombre'] ?? '',
                    $fila['categoria'] ?? 'Sin categoría',
                    $fila['cantidad_stock'] ?? 0,
                    $fila['unidades_vendidas'] ?? 0,
                    number_format((float)($fila['total_recaudado'] ?? 0), 2, '.', '')
                ], ';');
            }
        } elseif ($tipo === 'rotacion_baja') {
            fputcsv($out, ['Codigo', 'Producto', 'Categoria', 'Precio Venta ($)', 'Stock Estancado', 'Estado Rotacion'], ';');
            foreach ($datos as $fila) {
                fputcsv($out, [
                    $fila['codigo_barras'] ?? '',
                    $fila['nombre'] ?? '',
                    $fila['categoria'] ?? 'Sin categoría',
                    number_format((float)($fila['precio_venta'] ?? 0), 2, '.', ''),
                    $fila['cantidad_stock'] ?? 0,
                    $fila['estado_rotacion'] ?? 'Sin ventas'
                ], ';');
            }
        }

        fclose($out);
        exit();
    }

    public function exportarPDF(string $tipo, string $inicio, string $fin): void
    {
        $info = $this->obtenerDatos($tipo, $inicio, $fin);
        $datos = $info['datos'];
        $rango = $inicio . ' al ' . $fin;

        $pdf = new PDFReporte('P', 'mm', 'A4');
        $pdf->configurar($info['titulo'], $rango);
        $pdf->AliasNbPages();
        $pdf->AddPage();

        if ($tipo === 'ganancia_producto') {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(245, 227, 236);
            $pdf->SetTextColor(43, 58, 85);
            $pdf->Cell(25, 7, utf8_decode('Código'), 1, 0, 'C', true);
            $pdf->Cell(45, 7, utf8_decode('Producto'), 1, 0, 'L', true);
            $pdf->Cell(30, 7, utf8_decode('Categoría'), 1, 0, 'L', true);
            $pdf->Cell(18, 7, utf8_decode('Vendidos'), 1, 0, 'C', true);
            $pdf->Cell(22, 7, utf8_decode('Ingresos'), 1, 0, 'R', true);
            $pdf->Cell(20, 7, utf8_decode('Costo'), 1, 0, 'R', true);
            $pdf->Cell(20, 7, utf8_decode('Ganancia'), 1, 1, 'R', true);

            $pdf->SetFont('Arial', '', 7.5);
            $pdf->SetTextColor(50, 50, 50);
            $totIng = 0; $totCos = 0; $totGan = 0;

            foreach ($datos as $f) {
                $totIng += (float)($f['total_ingresos'] ?? 0);
                $totCos += (float)($f['total_costo'] ?? 0);
                $totGan += (float)($f['ganancia_real'] ?? 0);

                $pdf->Cell(25, 6, utf8_decode(substr($f['codigo_barras'] ?? '—', 0, 15)), 1, 0, 'C');
                $pdf->Cell(45, 6, utf8_decode(substr($f['nombre'] ?? '', 0, 26)), 1, 0, 'L');
                $pdf->Cell(30, 6, utf8_decode(substr($f['categoria'] ?? 'Sin cat.', 0, 18)), 1, 0, 'L');
                $pdf->Cell(18, 6, $f['cantidad_vendida'] ?? 0, 1, 0, 'C');
                $pdf->Cell(22, 6, '$' . number_format((float)($f['total_ingresos'] ?? 0), 2), 1, 0, 'R');
                $pdf->Cell(20, 6, '$' . number_format((float)($f['total_costo'] ?? 0), 2), 1, 0, 'R');
                $pdf->SetFont('Arial', 'B', 7.5);
                $pdf->SetTextColor(46, 125, 50);
                $pdf->Cell(20, 6, '$' . number_format((float)($f['ganancia_real'] ?? 0), 2), 1, 1, 'R');
                $pdf->SetFont('Arial', '', 7.5);
                $pdf->SetTextColor(50, 50, 50);
            }

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(253, 242, 247);
            $pdf->Cell(118, 7, utf8_decode('TOTALES:'), 1, 0, 'R', true);
            $pdf->Cell(22, 7, '$' . number_format($totIng, 2), 1, 0, 'R', true);
            $pdf->Cell(20, 7, '$' . number_format($totCos, 2), 1, 0, 'R', true);
            $pdf->SetTextColor(46, 125, 50);
            $pdf->Cell(20, 7, '$' . number_format($totGan, 2), 1, 1, 'R', true);

        } elseif ($tipo === 'ganancia_categoria') {
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetFillColor(245, 227, 236);
            $pdf->SetTextColor(43, 58, 85);
            $pdf->Cell(45, 7, utf8_decode('Categoría'), 1, 0, 'L', true);
            $pdf->Cell(25, 7, utf8_decode('Variedad Prod.'), 1, 0, 'C', true);
            $pdf->Cell(25, 7, utf8_decode('Unid. Vendidas'), 1, 0, 'C', true);
            $pdf->Cell(28, 7, utf8_decode('Ingresos ($)'), 1, 0, 'R', true);
            $pdf->Cell(28, 7, utf8_decode('Costo ($)'), 1, 0, 'R', true);
            $pdf->Cell(29, 7, utf8_decode('Ganancia Real ($)'), 1, 1, 'R', true);

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(50, 50, 50);
            $totIng = 0; $totCos = 0; $totGan = 0;

            foreach ($datos as $f) {
                $totIng += (float)($f['total_ingresos'] ?? 0);
                $totCos += (float)($f['total_costo'] ?? 0);
                $totGan += (float)($f['ganancia_real'] ?? 0);

                $pdf->Cell(45, 6, utf8_decode(substr($f['categoria'] ?? '', 0, 25)), 1, 0, 'L');
                $pdf->Cell(25, 6, $f['productos_distintos'] ?? 0, 1, 0, 'C');
                $pdf->Cell(25, 6, $f['cantidad_vendida'] ?? 0, 1, 0, 'C');
                $pdf->Cell(28, 6, '$' . number_format((float)($f['total_ingresos'] ?? 0), 2), 1, 0, 'R');
                $pdf->Cell(28, 6, '$' . number_format((float)($f['total_costo'] ?? 0), 2), 1, 0, 'R');
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetTextColor(46, 125, 50);
                $pdf->Cell(29, 6, '$' . number_format((float)($f['ganancia_real'] ?? 0), 2), 1, 1, 'R');
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetTextColor(50, 50, 50);
            }

            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetFillColor(253, 242, 247);
            $pdf->Cell(95, 7, utf8_decode('TOTALES:'), 1, 0, 'R', true);
            $pdf->Cell(28, 7, '$' . number_format($totIng, 2), 1, 0, 'R', true);
            $pdf->Cell(28, 7, '$' . number_format($totCos, 2), 1, 0, 'R', true);
            $pdf->SetTextColor(46, 125, 50);
            $pdf->Cell(29, 7, '$' . number_format($totGan, 2), 1, 1, 'R', true);

        } elseif ($tipo === 'rotacion_alta') {
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetFillColor(245, 227, 236);
            $pdf->SetTextColor(43, 58, 85);
            $pdf->Cell(20, 7, utf8_decode('Ranking'), 1, 0, 'C', true);
            $pdf->Cell(30, 7, utf8_decode('Código'), 1, 0, 'C', true);
            $pdf->Cell(50, 7, utf8_decode('Producto'), 1, 0, 'L', true);
            $pdf->Cell(30, 7, utf8_decode('Categoría'), 1, 0, 'L', true);
            $pdf->Cell(20, 7, utf8_decode('Stock'), 1, 0, 'C', true);
            $pdf->Cell(30, 7, utf8_decode('Total Recaudado'), 1, 1, 'R', true);

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(50, 50, 50);

            foreach ($datos as $i => $f) {
                $pdf->Cell(20, 6, utf8_decode('Top ' . ($i + 1)), 1, 0, 'C');
                $pdf->Cell(30, 6, utf8_decode($f['codigo_barras'] ?? '—'), 1, 0, 'C');
                $pdf->Cell(50, 6, utf8_decode(substr($f['nombre'] ?? '', 0, 30)), 1, 0, 'L');
                $pdf->Cell(30, 6, utf8_decode(substr($f['categoria'] ?? 'Sin cat.', 0, 18)), 1, 0, 'L');
                $pdf->Cell(20, 6, $f['cantidad_stock'] ?? 0, 1, 0, 'C');
                $pdf->Cell(30, 6, '$' . number_format((float)($f['total_recaudado'] ?? 0), 2), 1, 1, 'R');
            }
        } elseif ($tipo === 'rotacion_baja') {
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetFillColor(245, 227, 236);
            $pdf->SetTextColor(43, 58, 85);
            $pdf->Cell(30, 7, utf8_decode('Código'), 1, 0, 'C', true);
            $pdf->Cell(60, 7, utf8_decode('Producto'), 1, 0, 'L', true);
            $pdf->Cell(35, 7, utf8_decode('Categoría'), 1, 0, 'L', true);
            $pdf->Cell(25, 7, utf8_decode('Precio Venta'), 1, 0, 'R', true);
            $pdf->Cell(30, 7, utf8_decode('Stock Estancado'), 1, 1, 'C', true);

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(50, 50, 50);

            foreach ($datos as $f) {
                $pdf->Cell(30, 6, utf8_decode($f['codigo_barras'] ?? '—'), 1, 0, 'C');
                $pdf->Cell(60, 6, utf8_decode(substr($f['nombre'] ?? '', 0, 36)), 1, 0, 'L');
                $pdf->Cell(35, 6, utf8_decode(substr($f['categoria'] ?? 'Sin cat.', 0, 20)), 1, 0, 'L');
                $pdf->Cell(25, 6, '$' . number_format((float)($f['precio_venta'] ?? 0), 2), 1, 0, 'R');
                $pdf->Cell(30, 6, $f['cantidad_stock'] ?? 0, 1, 1, 'C');
            }
        }

        $nombreArchivo = 'informe_' . $tipo . '_' . date('Ymd_His') . '.pdf';
        $pdf->Output('I', $nombreArchivo);
        exit();
    }
}