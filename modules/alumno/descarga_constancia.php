<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['alumno']);
require_once __DIR__ . '/../../helpers/pdf_generator.php';

$idInscripcion = (int) ($_GET['id_inscripcion'] ?? 0);
$idAlumno = $_SESSION['usuario_id'];
$pdo = obtenerConexion();

$stmt = $pdo->prepare(
    "SELECT i.asistencia_validada, t.titulo, u.nombre_completo
     FROM inscripciones i
     JOIN talleres t ON t.id = i.id_taller
     JOIN usuarios u ON u.id = i.id_alumno
     WHERE i.id = :id AND i.id_alumno = :alumno AND i.estado = 'inscrito'"
);
$stmt->execute(['id' => $idInscripcion, 'alumno' => $idAlumno]);
$registro = $stmt->fetch();

if (!$registro) {
    http_response_code(404);
    die('Inscripción no encontrada.');
}
if (!(int) $registro['asistencia_validada']) {
    http_response_code(403);
    die('Tu asistencia aún no ha sido validada por el tallerista.');
}

$stmt = $pdo->prepare("SELECT * FROM certificados WHERE tipo = 'alumno' LIMIT 1");
$stmt->execute();
$plantilla = $stmt->fetch();

if (!$plantilla) {
    http_response_code(500);
    die('El administrador aún no ha configurado la plantilla de constancias.');
}
if (strtotime($plantilla['fecha_liberacion']) > time()) {
    http_response_code(403);
    die('Las constancias estarán disponibles a partir del ' . htmlspecialchars($plantilla['fecha_liberacion']) . '.');
}

generarConstanciaPDF(
    'alumno',
    $plantilla,
    ['nombre' => $registro['nombre_completo'], 'titulo_taller' => $registro['titulo'], 'rol' => 'participante'],
    dirname(__DIR__, 2)
);
