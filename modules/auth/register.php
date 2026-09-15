<?php
require_once __DIR__ . '/../../includes/session.php';

$rutaBase = '../../';
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre_completo'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');
    $password  = $_POST['password'] ?? '';
    $rol       = $_POST['rol'] ?? 'alumno';

    // Validación estricta: sólo cuentas @gmail.com, reforzada en backend.
    $esGmailPersonal = (bool) preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $correo);

    if ($nombre === '' || $correo === '' || $password === '') {
        $error = 'Todos los campos obligatorios deben completarse.';
    } elseif (!$esGmailPersonal) {
        $error = 'Por favor, introduce tu correo de Gmail personal (no institucional)';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (!in_array($rol, ['alumno', 'tallerista'], true)) {
        $error = 'Rol inválido.';
    } else {
        try {
            $pdo = obtenerConexion();
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = :correo LIMIT 1');
            $stmt->execute(['correo' => $correo]);
            if ($stmt->fetch()) {
                $error = 'Ya existe una cuenta registrada con ese correo.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO usuarios (nombre_completo, correo, matricula, password_hash, rol)
                     VALUES (:nombre, :correo, :matricula, :hash, :rol)'
                );
                $stmt->execute([
                    'nombre'    => $nombre,
                    'correo'    => $correo,
                    'matricula' => $matricula !== '' ? $matricula : null,
                    'hash'      => $hash,
                    'rol'       => $rol,
                ]);
                $exito = 'Cuenta creada exitosamente. Ya puedes iniciar sesión.';
            }
        } catch (PDOException $e) {
            $error = 'Error al registrar la cuenta. Intenta nuevamente.';
        }
    }
}

$tituloPagina = 'Registro de cuenta · SGT-CA';
require_once __DIR__ . '/../../includes/header.php';
?>
<h1>Crear cuenta</h1>
<p class="subtitulo">Regístrate como alumno/interesado o como tallerista para participar en la semana de talleres.</p>

<?php if ($error): ?><div class="alerta alerta-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($exito): ?><div class="alerta alerta-exito"><?php echo htmlspecialchars($exito); ?> <a href="login.php">Iniciar sesión</a></div><?php endif; ?>

<form class="tarjeta-form" method="POST" novalidate>
  <div class="campo">
    <label for="nombre_completo">Nombre completo</label>
    <input type="text" id="nombre_completo" name="nombre_completo" required
           value="<?php echo htmlspecialchars($_POST['nombre_completo'] ?? ''); ?>">
  </div>

  <div class="campo">
    <label for="correo">Correo (Gmail personal)</label>
    <input type="email" id="correo" name="correo" required placeholder="tucuenta@gmail.com"
           value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>"
           oninput="validarCorreoGmail(this)">
    <small id="mensaje-correo" style="color:#dc2626;"></small>
  </div>

  <div class="campo">
    <label for="matricula">Matrícula (opcional)</label>
    <input type="text" id="matricula" name="matricula" value="<?php echo htmlspecialchars($_POST['matricula'] ?? ''); ?>">
  </div>

  <div class="campo">
    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" minlength="8" required>
    <small>Mínimo 8 caracteres.</small>
  </div>

  <div class="campo">
    <label for="rol">Quiero registrarme como</label>
    <select id="rol" name="rol">
      <option value="alumno" <?php echo (($_POST['rol'] ?? '') === 'alumno') ? 'selected' : ''; ?>>Alumno / Interesado</option>
      <option value="tallerista" <?php echo (($_POST['rol'] ?? '') === 'tallerista') ? 'selected' : ''; ?>>Tallerista</option>
    </select>
  </div>

  <button type="submit" class="btn">Crear cuenta</button>
  <p class="mt-2">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
