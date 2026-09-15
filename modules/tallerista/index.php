<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['tallerista']);

$rutaBase = '../../';
$pdo = obtenerConexion();

$stmt = $pdo->prepare(
    "SELECT t.*,
        (SELECT COUNT(*) FROM inscripciones i WHERE i.id_taller = t.id AND i.estado='inscrito') AS inscritos
     FROM talleres t
     WHERE t.id_tallerista_principal = :id
     ORDER BY t.creado_en DESC"
);
$stmt->execute(['id' => $_SESSION['usuario_id']]);
$misTalleres = $stmt->fetchAll();

$tituloPagina = 'Mis Talleres · SGT-CA';
require_once __DIR__ . '/../../includes/header.php';

$etiquetasEstado = [
    'pendiente' => ['texto' => 'Pendiente de revisión', 'color' => '#d97706'],
    'aprobado'  => ['texto' => 'Aprobado', 'color' => '#16a34a'],
    'rechazado' => ['texto' => 'Rechazado', 'color' => '#dc2626'],
];
?>
<div class="flex-entre">
  <h1>Mis Propuestas de Talleres</h1>
  <a href="propuesta_form.php" class="btn">+ Nueva propuesta</a>
</div>
<p class="subtitulo">Autogestiona tus talleres: crea, edita y controla la asistencia y constancias de tus alumnos.</p>

<?php if (empty($misTalleres)): ?>
  <div class="alerta alerta-info">Aún no tienes propuestas registradas. Crea la primera.</div>
<?php endif; ?>

<div class="grid-talleres">
  <?php foreach ($misTalleres as $t): $et = $etiquetasEstado[$t['estado']]; ?>
    <div class="tarjeta-taller">
      <?php if ($t['flyer_url']): ?><img src="<?php echo $rutaBase . htmlspecialchars($t['flyer_url']); ?>"><?php endif; ?>
      <div class="tarjeta-taller-body">
        <h3><?php echo htmlspecialchars($t['titulo']); ?></h3>
        <span class="badge" style="background:<?php echo $et['color']; ?>22;color:<?php echo $et['color']; ?>;">
          <?php echo $et['texto']; ?>
        </span>
        <?php if ($t['estado'] === 'rechazado' && $t['observaciones_admin']): ?>
          <p><small><strong>Observaciones:</strong> <?php echo htmlspecialchars($t['observaciones_admin']); ?></small></p>
        <?php endif; ?>
        <p><small>Inscritos: <?php echo (int)$t['inscritos']; ?> / <?php echo (int)$t['cupo_max']; ?></small></p>
        <div class="flex-entre">
          <a href="propuesta_form.php?id=<?php echo $t['id']; ?>">Editar</a>
          <?php if ($t['estado'] === 'aprobado'): ?>
            <a href="asistencia.php?id_taller=<?php echo $t['id']; ?>">Control de asistencia</a>
            <a href="descarga_constancia.php?id_taller=<?php echo $t['id']; ?>">Mi constancia</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
