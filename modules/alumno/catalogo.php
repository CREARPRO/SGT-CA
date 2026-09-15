<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['alumno']);

/** Trunca texto de forma segura, con o sin la extensión mbstring disponible. */
function truncarTexto(string $texto, int $limite): string
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($texto) > $limite ? mb_substr($texto, 0, $limite) . '…' : $texto;
    }
    return strlen($texto) > $limite ? substr($texto, 0, $limite) . '…' : $texto;
}

$rutaBase = '../../';
$pdo = obtenerConexion();
$idAlumno = $_SESSION['usuario_id'];

$stmt = $pdo->prepare(
    "SELECT t.*, u.nombre_completo AS tallerista,
        (SELECT COUNT(*) FROM inscripciones i WHERE i.id_taller = t.id AND i.estado='inscrito') AS inscritos,
        (SELECT COUNT(*) FROM inscripciones i2 WHERE i2.id_taller = t.id AND i2.id_alumno = :uid AND i2.estado='inscrito') AS ya_inscrito
     FROM talleres t
     JOIN usuarios u ON u.id = t.id_tallerista_principal
     WHERE t.estado = 'aprobado'
     ORDER BY t.titulo ASC"
);
$stmt->execute(['uid' => $idAlumno]);
$talleres = $stmt->fetchAll();

$tituloPagina = 'Catálogo de Talleres · SGT-CA';
require_once __DIR__ . '/../../includes/header.php';
?>
<h1>Catálogo de Talleres</h1>
<p class="subtitulo">Cupos disponibles actualizados en tiempo real. Al inscribirte, se revelará el enlace de WhatsApp del grupo.</p>
<div id="zona-alertas"></div>

<div class="grid-talleres">
  <?php foreach ($talleres as $t):
        $disponibles = max(0, (int)$t['cupo_max'] - (int)$t['inscritos']);
        $yaInscrito = (int) $t['ya_inscrito'] > 0;
  ?>
    <div class="tarjeta-taller">
      <img src="<?php echo $t['flyer_url'] ? $rutaBase . htmlspecialchars($t['flyer_url']) : $rutaBase . 'assets/img/placeholder.jpg'; ?>" onerror="this.style.display='none'">
      <div class="tarjeta-taller-body">
        <h3><?php echo htmlspecialchars($t['titulo']); ?></h3>
        <span class="badge <?php echo $t['tipo'] === 'gratis' ? 'badge-gratis' : 'badge-pago'; ?>">
          <?php echo $t['tipo'] === 'gratis' ? 'Gratuito' : 'Costo: $' . number_format($t['costo'], 2); ?>
        </span>
        <p><small>Impartido por <?php echo htmlspecialchars($t['tallerista']); ?></small></p>
        <p><small><?php echo htmlspecialchars(truncarTexto($t['descripcion'], 110)); ?></small></p>
        <p><small>Duración: <?php echo htmlspecialchars($t['duracion_horas']); ?> hrs · <?php echo htmlspecialchars($t['dias'] ?: 'Días por definir'); ?></small></p>
        <p id="cupo-<?php echo $t['id']; ?>" class="<?php echo $disponibles > 0 ? 'cupo-disponible' : 'cupo-lleno'; ?>">
          <?php echo $disponibles; ?> cupos disponibles
        </p>

        <?php if ($yaInscrito): ?>
          <a id="whatsapp-<?php echo $t['id']; ?>" class="enlace-whatsapp" href="<?php echo htmlspecialchars($t['link_whatsapp']); ?>" target="_blank">Ir al grupo de WhatsApp</a>
        <?php else: ?>
          <a id="whatsapp-<?php echo $t['id']; ?>" class="enlace-whatsapp" style="display:none;" target="_blank">Ir al grupo de WhatsApp</a>
          <button class="btn" <?php echo $disponibles <= 0 ? 'disabled' : ''; ?>
            onclick="inscribirseTaller(<?php echo $t['id']; ?>, this)">
            <?php echo $disponibles <= 0 ? 'Sin cupo' : 'Inscribirse'; ?>
          </button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
