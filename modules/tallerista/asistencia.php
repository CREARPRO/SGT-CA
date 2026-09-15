<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['tallerista']);

$rutaBase = '../../';
$pdo = obtenerConexion();
$idTaller = (int) ($_GET['id_taller'] ?? 0);

// Verificar que el taller pertenece al tallerista en sesión
$stmt = $pdo->prepare('SELECT * FROM talleres WHERE id = :id AND id_tallerista_principal = :uid');
$stmt->execute(['id' => $idTaller, 'uid' => $_SESSION['usuario_id']]);
$taller = $stmt->fetch();

if (!$taller) {
    http_response_code(404);
    die('Taller no encontrado o no tienes permisos sobre él.');
}

$stmt = $pdo->prepare(
    "SELECT i.id AS id_inscripcion, i.asistencia_validada, u.nombre_completo, u.correo, u.matricula
     FROM inscripciones i
     JOIN usuarios u ON u.id = i.id_alumno
     WHERE i.id_taller = :id AND i.estado = 'inscrito'
     ORDER BY u.nombre_completo ASC"
);
$stmt->execute(['id' => $idTaller]);
$inscritos = $stmt->fetchAll();

$tituloPagina = 'Control de Asistencia · SGT-CA';
require_once __DIR__ . '/../../includes/header.php';
?>
<h1>Control de Asistencia</h1>
<p class="subtitulo">Taller: <strong><?php echo htmlspecialchars($taller['titulo']); ?></strong></p>
<div id="zona-alertas"></div>

<?php if (empty($inscritos)): ?>
  <div class="alerta alerta-info">Todavía no hay alumnos inscritos en este taller.</div>
<?php else: ?>
<div class="tabla-wrap">
<table class="tabla">
  <thead>
    <tr><th>Alumno</th><th>Correo</th><th>Matrícula</th><th>Asistencia confirmada</th></tr>
  </thead>
  <tbody>
    <?php foreach ($inscritos as $i): ?>
      <tr>
        <td><?php echo htmlspecialchars($i['nombre_completo']); ?></td>
        <td><?php echo htmlspecialchars($i['correo']); ?></td>
        <td><?php echo htmlspecialchars($i['matricula'] ?: '—'); ?></td>
        <td>
          <input type="checkbox" onchange="marcarAsistencia(<?php echo $i['id_inscripcion']; ?>, this)"
                 <?php echo $i['asistencia_validada'] ? 'checked' : ''; ?>>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<p class="mt-2"><small>Al marcar la asistencia se habilita automáticamente la descarga de constancia del alumno (sujeto a la fecha de liberación configurada por el administrador).</small></p>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
