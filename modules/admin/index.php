<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['admin']);

$rutaBase = '../../';
$pdo = obtenerConexion();

$totalTalleres    = $pdo->query("SELECT COUNT(*) c FROM talleres")->fetch()['c'];
$pendientes       = $pdo->query("SELECT COUNT(*) c FROM talleres WHERE estado='pendiente'")->fetch()['c'];
$aprobados        = $pdo->query("SELECT COUNT(*) c FROM talleres WHERE estado='aprobado'")->fetch()['c'];
$totalInscripciones = $pdo->query("SELECT COUNT(*) c FROM inscripciones WHERE estado='inscrito'")->fetch()['c'];

$tituloPagina = 'Panel de Administración · SGT-CA';
require_once __DIR__ . '/../../includes/header.php';
?>
<h1>Mesa de Control · Administrador</h1>
<p class="subtitulo">Supervisión general y configuración técnica del sistema.</p>

<div class="grid-talleres">
  <div class="tarjeta-taller"><div class="tarjeta-taller-body">
    <h3>Talleres totales</h3><p style="font-size:2rem;margin:0;"><?php echo (int)$totalTalleres; ?></p>
  </div></div>
  <div class="tarjeta-taller"><div class="tarjeta-taller-body">
    <h3>Propuestas pendientes</h3><p style="font-size:2rem;margin:0;color:#d97706;"><?php echo (int)$pendientes; ?></p>
    <a href="propuestas.php">Revisar propuestas →</a>
  </div></div>
  <div class="tarjeta-taller"><div class="tarjeta-taller-body">
    <h3>Talleres aprobados</h3><p style="font-size:2rem;margin:0;color:#16a34a;"><?php echo (int)$aprobados; ?></p>
  </div></div>
  <div class="tarjeta-taller"><div class="tarjeta-taller-body">
    <h3>Inscripciones activas</h3><p style="font-size:2rem;margin:0;"><?php echo (int)$totalInscripciones; ?></p>
    <a href="monitor.php">Ver monitor en tiempo real →</a>
  </div></div>
</div>

<h2>Accesos rápidos</h2>
<ul>
  <li><a href="propuestas.php">Validación de propuestas de talleres</a></li>
  <li><a href="monitor.php">Monitor de inscritos por taller</a></li>
  <li><a href="certificados.php">Generador de plantillas de certificados</a></li>
</ul>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
