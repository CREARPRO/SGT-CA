<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['tallerista']);

$datos = json_decode(file_get_contents('php://input'), true);
$idInscripcion = (int) ($datos['id_inscripcion'] ?? 0);
$asistio = (int) ($datos['asistio'] ?? 0);

if ($idInscripcion <= 0) {
    responderJSON(['ok' => false, 'mensaje' => 'Inscripción inválida.'], 400);
}

try {
    $pdo = obtenerConexion();

    // Verificar que la inscripción pertenece a un taller del tallerista en sesión
    $stmt = $pdo->prepare(
        "SELECT i.id FROM inscripciones i
         JOIN talleres t ON t.id = i.id_taller
         WHERE i.id = :id AND t.id_tallerista_principal = :uid"
    );
    $stmt->execute(['id' => $idInscripcion, 'uid' => $_SESSION['usuario_id']]);

    if (!$stmt->fetch()) {
        responderJSON(['ok' => false, 'mensaje' => 'No tienes permisos sobre esta inscripción.'], 403);
    }

    $stmt = $pdo->prepare('UPDATE inscripciones SET asistencia_validada = :asistio WHERE id = :id');
    $stmt->execute(['asistio' => $asistio, 'id' => $idInscripcion]);

    responderJSON(['ok' => true]);
} catch (PDOException $e) {
    responderJSON(['ok' => false, 'mensaje' => 'Error al guardar la asistencia.'], 500);
}
