<?php
require_once __DIR__ . '/includes/session.php';

// Enrutamiento automático según rol si ya hay sesión activa
if (estaAutenticado()) {
    switch ($_SESSION['rol']) {
        case 'admin':
            header('Location: modules/admin/index.php');
            exit;
        case 'tallerista':
            header('Location: modules/tallerista/index.php');
            exit;
        case 'alumno':
            header('Location: modules/alumno/catalogo.php');
            exit;
    }
}

$rutaBase = '';
$tituloPagina = 'SGT-CA · Sistema de Gestión de Talleres';
require_once __DIR__ . '/includes/header.php';
?>
<div class="text-centro mt-3">
  <h1>Sistema Integrado de Gestión de Talleres y Certificación Automatizada</h1>
  <p class="subtitulo">Plataforma centralizada para administradores, talleristas y alumnos durante la semana de aniversario institucional.</p>

  <div class="grid-talleres mt-3">
    <div class="tarjeta-taller"><div class="tarjeta-taller-body">
      <h3>¿Eres alumno o interesado?</h3>
      <p>Explora el catálogo de talleres, inscríbete y descarga tu constancia.</p>
      <a href="modules/auth/register.php" class="btn">Crear cuenta</a>
    </div></div>
    <div class="tarjeta-taller"><div class="tarjeta-taller-body">
      <h3>¿Eres tallerista?</h3>
      <p>Registra tu propuesta, gestiona tu lista de asistencia y habilita constancias.</p>
      <a href="modules/auth/register.php" class="btn">Crear cuenta</a>
    </div></div>
    <div class="tarjeta-taller"><div class="tarjeta-taller-body">
      <h3>¿Ya tienes cuenta?</h3>
      <p>Ingresa con tu correo y contraseña registrados.</p>
      <a href="modules/auth/login.php" class="btn btn-secundario">Iniciar sesión</a>
    </div></div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
