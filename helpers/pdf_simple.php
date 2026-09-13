<?php
declare(strict_types=1);

/**
 * Generador PDF mínimo (RF 5.6) sin dependencias externas.
 * Solo texto con Helvetica; convierte UTF-8 a WinAnsi aproximado.
 */

function pdf_texto(string $s): string
{
    $s = (string) @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
    $s = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $s);
    return $s;
}

/** Envía un PDF con una línea por elemento y termina la ejecución. */
function pdf_descargar(array $lineas, string $nombreArchivo): void
{
    $y = 800;
    $content = "BT /F1 11 Tf 36 {$y} Td 14 TL\n";
    $n = count($lineas);
    foreach ($lineas as $i => $ln) {
        $content .= '(' . pdf_texto((string) $ln) . ') Tj' . ($i < $n - 1 ? ' T*' : '') . "\n";
    }
    $content .= "ET";
    $len = strlen($content);

    $obj = [];
    $obj[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $obj[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $obj[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
    $obj[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $obj[5] = "<< /Length {$len} >>\nstream\n{$content}\nendstream";

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($obj as $num => $body) {
        $offsets[$num] = strlen($pdf);
        $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
    }
    $xrefPos = strlen($pdf);
    $max = max(array_keys($obj));
    $pdf .= "xref\n0 " . ($max + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $max; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . ($max + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $nombreArchivo) . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}
