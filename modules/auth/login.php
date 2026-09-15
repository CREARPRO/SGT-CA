<?php
require_once __DIR__ . '/../../includes/session.php';

$rutaBase = '../../';
$error = '';

if (estaAutenticado()) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo   = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($correo === '' || $password === '') {
        $error = 'Ingresa tu correo y contraseña.';
    } else {
        try {
            $pdo = obtenerConexion();
            $stmt = $pdo->prepare('SELECT id, nombre_completo, correo, password_hash, rol, activo FROM usuarios WHERE correo = :correo LIMIT 1');
            $stmt->execute(['correo' => $correo]);
            $usuario = $stmt->fetch();

            if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
                $error = 'Credenciales incorrectas.';
            } elseif ((int) $usuario['activo'] !== 1) {
                $error = 'Tu cuenta se encuentra desactivada. Contacta al administrador.';
            } else {
                $_SESSION['usuario_id']      = (int) $usuario['id'];
                $_SESSION['nombre_completo']  = $usuario['nombre_completo'];
                $_SESSION['correo']           = $usuario['correo'];
                $_SESSION['rol']              = $usuario['rol'];

                header('Location: ../../index.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Error al iniciar sesión. Intenta nuevamente.';
        }
    }
}

$tituloPagina = 'Iniciar sesión · SGT-CA';
require_once __DIR__ . '/../../includes/header.php';
?>
<h1>Iniciar sesión</h1>
<p class="subtitulo">Accede con tu correo y contraseña registrados.</p>

<?php if ($error): ?><div class="alerta alerta-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<form class="tarjeta-form" method="POST">
  <div class="campo">
    <label for="correo">Correo</label>
    <input type="email" id="correo" name="correo" required value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>">
  </div>
  <div class="campo">
    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" required>
  </div>
  <button type="submit" class="btn">Entrar</button>
  <p class="mt-2">¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
