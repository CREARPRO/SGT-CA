<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['tallerista']);
require_once __DIR__ . '/../../helpers/pdf_generator.php';

$idTaller = (int) ($_GET['id_taller'] ?? 0);
$pdo = obtenerConexion();

$stmt = $pdo->prepare(
    "SELECT t.titulo, u.nombre_completo
     FROM talleres t
     JOIN usuarios u ON u.id = t.id_tallerista_principal
     WHERE t.id = :id AND t.id_tallerista_principal = :uid AND t.estado = 'aprobado'"
);
$stmt->execute(['id' => $idTaller, 'uid' => $_SESSION['usuario_id']]);
$registro = $stmt->fetch();

if (!$registro) {
    http_response_code(404);
    die('Taller no encontrado o no aprobado.');
}

$stmt = $pdo->prepare("SELECT * FROM certificados WHERE tipo = 'tallerista' LIMIT 1");
$stmt->execute();
$plantilla = $stmt->fetch();

if (!$plantilla) {
    http_response_code(500);
    die('El administrador aún no ha configurado la plantilla de constancias de tallerista.');
}
if (strtotime($plantilla['fecha_liberacion']) > time()) {
    http_response_code(403);
    die('Las constancias estarán disponibles a partir del ' . htmlspecialchars($plantilla['fecha_liberacion']) . '.');
}

generarConstanciaPDF(
    'tallerista',
    $plantilla,
    ['nombre' => $registro['nombre_completo'], 'titulo_taller' => $registro['titulo'], 'rol' => 'tallerista'],
    dirname(__DIR__, 2)
);
