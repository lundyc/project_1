<?php
// pdf_helper.php - Dompdf wrapper for PDF output
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;

function pdf_output(string $html, string $filename): void {
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dompdf->output();
}
