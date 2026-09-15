<?php
/**
 * helpers/pdf_generator.php
 * Motor de renderizado de constancias "al vuelo" (on-the-fly).
 *
 * Reglas de diseño (obligatorias para InfinityFree):
 * - JAMÁS se escribe el PDF resultante en el disco del servidor.
 * - El binario se transmite directamente al navegador via stream ('I')
 *   con los headers HTTP Content-Type: application/pdf.
 * - Sólo se lee la imagen base de la plantilla (ya almacenada previamente
 *   por el administrador) para usarla como fondo del documento.
 */

require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

/**
 * Genera y transmite (stream) el PDF de una constancia directamente al navegador.
 *
 * @param string $tipo           'alumno' | 'tallerista'
 * @param array  $datosPlantilla Fila de la tabla `certificados` (imagen_fondo, texto_agradecimiento, coordenadas_json)
 * @param array  $datosPersona   ['nombre' => ..., 'titulo_taller' => ..., 'rol' => ...]
 * @param string $rutaAbsolutaProyecto Ruta absoluta al directorio raíz del proyecto (para localizar la imagen de fondo)
 */
function generarConstanciaPDF(string $tipo, array $datosPlantilla, array $datosPersona, string $rutaAbsolutaProyecto): void
{
    $rutaImagen = rtrim($rutaAbsolutaProyecto, '/') . '/' . $datosPlantilla['imagen_fondo'];

    if (!is_file($rutaImagen)) {
        http_response_code(500);
        die('No se encontró la plantilla de certificado configurada por el administrador.');
    }

    // Formato carta apaisado, típico de diplomas/constancias.
    $pdf = new FPDF('L', 'mm', 'Letter');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);

    // Dimensiones de la página en modo Letter horizontal (mm)
    $anchoPagina = $pdf->GetPageWidth();
    $altoPagina  = $pdf->GetPageHeight();

    $extension = strtolower(pathinfo($rutaImagen, PATHINFO_EXTENSION));
    $tipoImagen = in_array($extension, ['jpg', 'jpeg'], true) ? 'JPG' : strtoupper($extension);

    $pdf->Image($rutaImagen, 0, 0, $anchoPagina, $altoPagina, $tipoImagen);

    // Sustitución de marcadores en el texto de agradecimiento
    $texto = str_replace(
        ['{nombre}', '{titulo}', '{rol}'],
        [$datosPersona['nombre'], $datosPersona['titulo_taller'], $datosPersona['rol']],
        $datosPlantilla['texto_agradecimiento']
    );

    $coordenadas = json_decode($datosPlantilla['coordenadas_json'] ?? '{}', true) ?: ['x' => $anchoPagina / 2, 'y' => $altoPagina / 2];

    $pdf->SetFont('Helvetica', 'B', 20);
    $pdf->SetXY(0, (float) $coordenadas['y'] - 12);
    $pdf->SetTextColor(20, 20, 20);
    $pdf->Cell($anchoPagina, 10, iconv('UTF-8', 'windows-1252//IGNORE', $datosPersona['nombre']), 0, 2, 'C');

    $pdf->SetFont('Helvetica', '', 13);
    $pdf->SetXY(20, (float) $coordenadas['y']);
    $pdf->MultiCell($anchoPagina - 40, 7, iconv('UTF-8', 'windows-1252//IGNORE', $texto), 0, 'C');

    // 'I' transmite el PDF inline directamente al navegador (stream binario),
    // sin dejar rastro alguno en el almacenamiento del servidor.
    $pdf->Output('I', 'constancia_' . preg_replace('/\s+/', '_', $datosPersona['nombre']) . '.pdf');
}
