<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['alumno']);

$datos = json_decode(file_get_contents('php://input'), true);
$idInscripcion = (int) ($datos['id_inscripcion'] ?? 0);
$idAlumno = $_SESSION['usuario_id'];

if ($idInscripcion <= 0) {
    responderJSON(['ok' => false, 'mensaje' => 'Inscripción inválida.'], 400);
}

try {
    $pdo = obtenerConexion();
    $stmt = $pdo->prepare(
        "UPDATE inscripciones SET estado='cancelado', fecha_cancelacion = NOW()
         WHERE id = :id AND id_alumno = :alumno AND estado = 'inscrito'"
    );
    $stmt->execute(['id' => $idInscripcion, 'alumno' => $idAlumno]);

    if ($stmt->rowCount() === 0) {
        responderJSON(['ok' => false, 'mensaje' => 'No se encontró la inscripción o ya estaba cancelada.'], 404);
    }

    responderJSON(['ok' => true, 'mensaje' => 'Inscripción cancelada. El cupo ha sido liberado.']);
} catch (PDOException $e) {
    responderJSON(['ok' => false, 'mensaje' => 'Error al cancelar la inscripción.'], 500);
}
