<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['alumno']);

$rutaBase = '../../';
$pdo = obtenerConexion();
$idAlumno = $_SESSION['usuario_id'];

$stmt = $pdo->prepare(
    "SELECT i.id AS id_inscripcion, i.asistencia_validada, i.estado, t.id AS id_taller,
            t.titulo, t.link_whatsapp, t.dias, t.salon
     FROM inscripciones i
     JOIN talleres t ON t.id = i.id_taller
     WHERE i.id_alumno = :id AND i.estado = 'inscrito'
     ORDER BY t.titulo ASC"
);
$stmt->execute(['id' => $idAlumno]);
$inscripciones = $stmt->fetchAll();

// Fecha de liberación configurada por el administrador para constancias de alumno
$stmt = $pdo->prepare("SELECT fecha_liberacion FROM certificados WHERE tipo = 'alumno' LIMIT 1");
$stmt->execute();
$configCert = $stmt->fetch();
$fechaLiberada = $configCert && strtotime($configCert['fecha_liberacion']) <= time();

$tituloPagina = 'Mis Inscripciones · SGT-CA';
require_once __DIR__ . '/../../includes/header.php';
?>
<h1>Mis Inscripciones</h1>
<p class="subtitulo">Consulta tus talleres inscritos, cancela tu lugar o descarga tu constancia una vez habilitada.</p>
<div id="zona-alertas"></div>

<?php if (empty($inscripciones)): ?>
  <div class="alerta alerta-info">Aún no te has inscrito a ningún taller. <a href="catalogo.php">Explora el catálogo</a>.</div>
<?php else: ?>
<div class="tabla-wrap">
<table class="tabla">
  <thead>
    <tr><th>Taller</th><th>Aula / Días</th><th>WhatsApp</th><th>Constancia</th><th>Acción</th></tr>
  </thead>
  <tbody>
    <?php foreach ($inscripciones as $i): ?>
      <tr id="fila-<?php echo $i['id_inscripcion']; ?>">
        <td><?php echo htmlspecialchars($i['titulo']); ?></td>
        <td><?php echo htmlspecialchars(($i['salon'] ?: '—') . ' / ' . ($i['dias'] ?: '—')); ?></td>
        <td><a href="<?php echo htmlspecialchars($i['link_whatsapp']); ?>" target="_blank">Abrir grupo</a></td>
        <td>
          <?php if ($i['asistencia_validada'] && $fechaLiberada): ?>
            <a class="btn btn-exito" style="padding:6px 12px;" href="descarga_constancia.php?id_inscripcion=<?php echo $i['id_inscripcion']; ?>">Descargar PDF</a>
          <?php elseif ($i['asistencia_validada']): ?>
            <small>Disponible próximamente</small>
          <?php else: ?>
            <small>Pendiente de validar asistencia</small>
          <?php endif; ?>
        </td>
        <td>
          <button class="btn btn-peligro" style="padding:6px 12px;"
            onclick="cancelarInscripcion(<?php echo $i['id_inscripcion']; ?>, document.getElementById('fila-<?php echo $i['id_inscripcion']; ?>'))">
            Cancelar
          </button>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
