<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['admin']);

$rutaBase = '../../';
$pdo = obtenerConexion();

$stmt = $pdo->query(
    "SELECT t.id, t.titulo, t.salon, t.cupo_max,
            (SELECT COUNT(*) FROM inscripciones i WHERE i.id_taller = t.id AND i.estado = 'inscrito') AS inscritos
     FROM talleres t
     WHERE t.estado = 'aprobado'
     ORDER BY t.titulo ASC"
);
$talleres = $stmt->fetchAll();

$tituloPagina = 'Monitor de Inscritos · SGT-CA';
require_once __DIR__ . '/../../includes/header.php';
?>
<h1>Monitor de Inscritos en Tiempo Real</h1>
<p class="subtitulo">Cantidad de alumnos registrados por taller, actualizada automáticamente cada pocos segundos.</p>

<div class="tabla-wrap">
<table class="tabla" id="tabla-monitor">
  <thead>
    <tr><th>Taller</th><th>Aula</th><th>Cupo máximo</th><th>Inscritos</th></tr>
  </thead>
  <tbody>
    <?php foreach ($talleres as $t): ?>
      <tr>
        <td><?php echo htmlspecialchars($t['titulo']); ?></td>
        <td><?php echo htmlspecialchars($t['salon'] ?: '—'); ?></td>
        <td><?php echo (int) $t['cupo_max']; ?></td>
        <td id="inscritos-<?php echo $t['id']; ?>"><?php echo (int) $t['inscritos'] . ' / ' . (int) $t['cupo_max']; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<script>iniciarMonitorTiempoReal(8000);</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
