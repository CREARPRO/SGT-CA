<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['admin']);

try {
    $pdo = obtenerConexion();
    $stmt = $pdo->query(
        "SELECT t.id, t.cupo_max,
                (SELECT COUNT(*) FROM inscripciones i WHERE i.id_taller = t.id AND i.estado = 'inscrito') AS inscritos
         FROM talleres t
         WHERE t.estado = 'aprobado'"
    );
    responderJSON(['ok' => true, 'talleres' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    responderJSON(['ok' => false, 'mensaje' => 'Error al obtener datos.'], 500);
}
