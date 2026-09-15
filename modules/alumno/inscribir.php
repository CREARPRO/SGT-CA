<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['alumno']);

$datos = json_decode(file_get_contents('php://input'), true);
$idTaller = (int) ($datos['id_taller'] ?? 0);
$idAlumno = $_SESSION['usuario_id'];

if ($idTaller <= 0) {
    responderJSON(['ok' => false, 'mensaje' => 'Taller inválido.'], 400);
}

$pdo = obtenerConexion();

try {
    $pdo->beginTransaction();

    // Bloqueo de fila (FOR UPDATE) para evitar condiciones de carrera (overbooking)
    // en hosting compartido con múltiples solicitudes concurrentes.
    $stmt = $pdo->prepare(
        "SELECT id, cupo_max, estado, link_whatsapp,
                (SELECT COUNT(*) FROM inscripciones i WHERE i.id_taller = talleres.id AND i.estado = 'inscrito') AS inscritos
         FROM talleres WHERE id = :id FOR UPDATE"
    );
    $stmt->execute(['id' => $idTaller]);
    $taller = $stmt->fetch();

    if (!$taller || $taller['estado'] !== 'aprobado') {
        $pdo->rollBack();
        responderJSON(['ok' => false, 'mensaje' => 'El taller no está disponible para inscripción.'], 404);
    }

    if ((int) $taller['inscritos'] >= (int) $taller['cupo_max']) {
        $pdo->rollBack();
        responderJSON(['ok' => false, 'mensaje' => 'Lo sentimos, el cupo se ha llenado.'], 409);
    }

    // Verificar si ya existe un registro previo (posible reinscripción tras cancelación)
    $stmt = $pdo->prepare('SELECT id, estado FROM inscripciones WHERE id_alumno = :a AND id_taller = :t');
    $stmt->execute(['a' => $idAlumno, 't' => $idTaller]);
    $existente = $stmt->fetch();

    if ($existente && $existente['estado'] === 'inscrito') {
        $pdo->rollBack();
        responderJSON(['ok' => false, 'mensaje' => 'Ya estás inscrito en este taller.'], 409);
    } elseif ($existente) {
        $stmt = $pdo->prepare("UPDATE inscripciones SET estado='inscrito', asistencia_validada=0, fecha_inscripcion=NOW(), fecha_cancelacion=NULL WHERE id = :id");
        $stmt->execute(['id' => $existente['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO inscripciones (id_alumno, id_taller, estado) VALUES (:a, :t, "inscrito")');
        $stmt->execute(['a' => $idAlumno, 't' => $idTaller]);
    }

    $pdo->commit();

    $cuposRestantes = (int) $taller['cupo_max'] - ((int) $taller['inscritos'] + 1);

    responderJSON([
        'ok' => true,
        'mensaje' => '¡Inscripción exitosa! Ya puedes acceder al grupo de WhatsApp.',
        'cupos_disponibles' => max(0, $cuposRestantes),
        'link_whatsapp' => $taller['link_whatsapp'],
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    responderJSON(['ok' => false, 'mensaje' => 'Error al procesar la inscripción.'], 500);
}
