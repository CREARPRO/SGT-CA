<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['admin']);

$rutaBase = '../../';
$pdo = obtenerConexion();

$stmt = $pdo->query(
    "SELECT t.*, u.nombre_completo AS tallerista
     FROM talleres t
     JOIN usuarios u ON u.id = t.id_tallerista_principal
     WHERE t.estado = 'pendiente'
     ORDER BY t.creado_en ASC"
);
$propuestas = $stmt->fetchAll();

$tituloPagina = 'Validación de Propuestas · SGT-CA';
require_once __DIR__ . '/../../includes/header.php';
?>
<h1>Validación de Propuestas</h1>
<p class="subtitulo">Revisa a detalle los requisitos, materiales y horarios antes de aprobar o rechazar.</p>
<div id="zona-alertas"></div>

<?php if (empty($propuestas)): ?>
  <div class="alerta alerta-info">No hay propuestas pendientes de revisión.</div>
<?php endif; ?>

<?php foreach ($propuestas as $p): ?>
  <div class="tarjeta-form mt-2" id="fila-taller-<?php echo $p['id']; ?>" style="max-width:100%;">
    <div class="flex-entre">
      <h3><?php echo htmlspecialchars($p['titulo']); ?>
        <span class="badge <?php echo $p['tipo'] === 'gratis' ? 'badge-gratis' : 'badge-pago'; ?>">
          <?php echo $p['tipo'] === 'gratis' ? 'Gratuito' : 'Costo: $' . number_format($p['costo'], 2); ?>
        </span>
      </h3>
      <span>Tallerista: <strong><?php echo htmlspecialchars($p['tallerista']); ?></strong></span>
    </div>

    <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($p['descripcion'])); ?></p>
    <div class="fila-2">
      <p><strong>Conocimientos previos:</strong><br><?php echo nl2br(htmlspecialchars($p['requisitos'] ?: 'Ninguno')); ?></p>
      <p><strong>Materiales requeridos:</strong><br><?php echo nl2br(htmlspecialchars($p['materiales'] ?: 'Ninguno')); ?></p>
    </div>
    <div class="fila-2">
      <p><strong>Público objetivo:</strong> <?php echo htmlspecialchars($p['publico']); ?></p>
      <p><strong>Duración:</strong> <?php echo htmlspecialchars($p['duracion_horas']); ?> hrs</p>
    </div>
    <div class="fila-2">
      <p><strong>Días propuestos:</strong> <?php echo htmlspecialchars($p['dias'] ?: 'Por definir'); ?></p>
      <p><strong>Aula propuesta:</strong> <?php echo htmlspecialchars($p['salon'] ?: 'Por definir'); ?></p>
    </div>
    <p><strong>Cupo máximo:</strong> <?php echo (int) $p['cupo_max']; ?></p>
    <p><strong>Personal de apoyo:</strong> <?php echo htmlspecialchars($p['apoyos'] ?: 'N/A'); ?></p>
    <?php if ($p['flyer_url']): ?>
      <p><strong>Flyer:</strong><br><img src="<?php echo htmlspecialchars($p['flyer_url']); ?>" style="max-width:220px;border-radius:8px;"></p>
    <?php endif; ?>

    <div class="mt-2">
      <button class="btn btn-exito" onclick="resolverPropuesta(<?php echo $p['id']; ?>, 'aprobado', document.getElementById('fila-taller-<?php echo $p['id']; ?>'))">Aprobar</button>
      <button class="btn btn-peligro" onclick="resolverPropuesta(<?php echo $p['id']; ?>, 'rechazado', document.getElementById('fila-taller-<?php echo $p['id']; ?>'))">Rechazar</button>
    </div>
  </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
