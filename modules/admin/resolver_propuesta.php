<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['admin']);

$datos = json_decode(file_get_contents('php://input'), true);
$idTaller     = (int) ($datos['id_taller'] ?? 0);
$estado       = $datos['estado'] ?? '';
$observaciones = trim($datos['observaciones'] ?? '');

if ($idTaller <= 0 || !in_array($estado, ['aprobado', 'rechazado'], true)) {
    responderJSON(['ok' => false, 'mensaje' => 'Datos inválidos.'], 400);
}

try {
    $pdo = obtenerConexion();
    $stmt = $pdo->prepare(
        'UPDATE talleres SET estado = :estado, observaciones_admin = :obs WHERE id = :id'
    );
    $stmt->execute([
        'estado' => $estado,
        'obs'    => $observaciones !== '' ? $observaciones : null,
        'id'     => $idTaller,
    ]);

    responderJSON(['ok' => true, 'mensaje' => 'Propuesta actualizada correctamente.']);
} catch (PDOException $e) {
    responderJSON(['ok' => false, 'mensaje' => 'Error al actualizar la propuesta.'], 500);
}
