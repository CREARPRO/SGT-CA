<?php
/**
 * includes/navbar.php
 * Menú de navegación adaptativo según el rol almacenado en sesión.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$rol = $_SESSION['rol'] ?? null;
$nombre = $_SESSION['nombre_completo'] ?? '';
$rutaBase = $rutaBase ?? '';
?>
<header class="navbar">
  <div class="navbar-marca">
    <a href="<?php echo $rutaBase; ?>index.php">SGT&#8209;CA</a>
  </div>
  <nav class="navbar-links">
    <?php if ($rol === 'admin'): ?>
      <a href="<?php echo $rutaBase; ?>modules/admin/index.php">Panel Admin</a>
      <a href="<?php echo $rutaBase; ?>modules/admin/propuestas.php">Propuestas</a>
      <a href="<?php echo $rutaBase; ?>modules/admin/monitor.php">Monitor de Inscritos</a>
      <a href="<?php echo $rutaBase; ?>modules/admin/certificados.php">Plantillas de Constancia</a>
    <?php elseif ($rol === 'tallerista'): ?>
      <a href="<?php echo $rutaBase; ?>modules/tallerista/index.php">Mis Talleres</a>
      <a href="<?php echo $rutaBase; ?>modules/tallerista/propuesta_form.php">Nueva Propuesta</a>
    <?php elseif ($rol === 'alumno'): ?>
      <a href="<?php echo $rutaBase; ?>modules/alumno/catalogo.php">Catálogo</a>
      <a href="<?php echo $rutaBase; ?>modules/alumno/mis_inscripciones.php">Mis Inscripciones</a>
    <?php endif; ?>
  </nav>
  <div class="navbar-usuario">
    <?php if ($rol): ?>
      <span class="navbar-saludo">Hola, <?php echo htmlspecialchars($nombre); ?> (<?php echo htmlspecialchars($rol); ?>)</span>
      <a href="<?php echo $rutaBase; ?>modules/auth/logout.php" class="btn-salir">Salir</a>
    <?php else: ?>
      <a href="<?php echo $rutaBase; ?>modules/auth/login.php">Iniciar sesión</a>
      <a href="<?php echo $rutaBase; ?>modules/auth/register.php">Registrarse</a>
    <?php endif; ?>
  </div>
</header>
