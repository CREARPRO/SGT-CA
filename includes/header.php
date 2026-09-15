<?php
/**
 * includes/header.php
 * Cabecera HTML común. Espera opcionalmente $tituloPagina definido antes de incluirse.
 */
$tituloPagina = $tituloPagina ?? 'SGT-CA · Sistema de Talleres';
// Ruta base relativa para que assets funcionen desde cualquier profundidad de módulo
$rutaBase = $rutaBase ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($tituloPagina); ?></title>
<link rel="stylesheet" href="<?php echo $rutaBase; ?>assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="contenedor">
