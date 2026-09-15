<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['tallerista']);

$rutaBase = '../../';
$pdo = obtenerConexion();
$error = '';
$idTaller = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$taller = null;

define('PESO_MAX_BYTES_FLYER', 2 * 1024 * 1024);
define('DIR_FLYERS', __DIR__ . '/../../assets/img/');

// Cargar taller existente (sólo si pertenece al tallerista en sesión)
if ($idTaller > 0) {
    $stmt = $pdo->prepare('SELECT * FROM talleres WHERE id = :id AND id_tallerista_principal = :uid');
    $stmt->execute(['id' => $idTaller, 'uid' => $_SESSION['usuario_id']]);
    $taller = $stmt->fetch();
    if (!$taller) { $idTaller = 0; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo         = trim($_POST['titulo'] ?? '');
    $apoyos         = trim($_POST['apoyos'] ?? '');
    $descripcion    = trim($_POST['descripcion'] ?? '');
    $requisitos     = trim($_POST['requisitos'] ?? '');
    $materiales     = trim($_POST['materiales'] ?? '');
    $tipo           = $_POST['tipo'] ?? 'gratis';
    $costo          = (float) ($_POST['costo'] ?? 0);
    $publico        = $_POST['publico'] ?? 'general';
    $duracionHoras  = (float) ($_POST['duracion_horas'] ?? 1);
    $dias           = trim($_POST['dias'] ?? '');
    $salon          = trim($_POST['salon'] ?? '');
    $cupoMax        = (int) ($_POST['cupo_max'] ?? 20);
    $linkWhatsapp   = trim($_POST['link_whatsapp'] ?? '');

    if ($titulo === '' || $descripcion === '' || $cupoMax <= 0 || $linkWhatsapp === '') {
        $error = 'Completa los campos obligatorios: título, descripción, cupo máximo y enlace de WhatsApp.';
    } elseif (!in_array($tipo, ['pago', 'gratis'], true) || !in_array($publico, ['general', 'carrera', 'universidad'], true)) {
        $error = 'Valores inválidos en tipo o público objetivo.';
    } else {
        $flyerUrl = $taller['flyer_url'] ?? null;

        if (!empty($_FILES['flyer']['name'])) {
            $archivo = $_FILES['flyer'];
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            $tiposValidos = ['jpg', 'jpeg', 'png', 'webp'];

            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                $error = 'Error al subir el flyer.';
            } elseif (!in_array($extension, $tiposValidos, true)) {
                $error = 'Formato de flyer no permitido (usa JPG, PNG o WEBP).';
            } elseif ($archivo['size'] > PESO_MAX_BYTES_FLYER) {
                $error = 'El flyer supera el límite de 2MB.';
            } else {
                if (!is_dir(DIR_FLYERS)) mkdir(DIR_FLYERS, 0755, true);
                $nombreArchivo = 'flyer_' . $_SESSION['usuario_id'] . '_' . time() . '.' . $extension;
                if (move_uploaded_file($archivo['tmp_name'], DIR_FLYERS . $nombreArchivo)) {
                    $flyerUrl = 'assets/img/' . $nombreArchivo;
                } else {
                    $error = 'No se pudo guardar el flyer en el servidor.';
                }
            }
        }

        if ($error === '') {
            try {
                if ($idTaller > 0) {
                    $stmt = $pdo->prepare(
                        "UPDATE talleres SET titulo=:titulo, apoyos=:apoyos, flyer_url=:flyer, descripcion=:desc,
                         requisitos=:req, materiales=:mat, tipo=:tipo, costo=:costo, publico=:pub,
                         duracion_horas=:dur, dias=:dias, salon=:salon, cupo_max=:cupo, link_whatsapp=:wa,
                         estado='pendiente', observaciones_admin=NULL
                         WHERE id=:id AND id_tallerista_principal=:uid"
                    );
                    $stmt->execute([
                        'titulo' => $titulo, 'apoyos' => $apoyos, 'flyer' => $flyerUrl, 'desc' => $descripcion,
                        'req' => $requisitos, 'mat' => $materiales, 'tipo' => $tipo, 'costo' => $costo,
                        'pub' => $publico, 'dur' => $duracionHoras, 'dias' => $dias, 'salon' => $salon,
                        'cupo' => $cupoMax, 'wa' => $linkWhatsapp, 'id' => $idTaller, 'uid' => $_SESSION['usuario_id'],
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO talleres (id_tallerista_principal, titulo, apoyos, flyer_url, descripcion,
                         requisitos, materiales, tipo, costo, publico, duracion_horas, dias, salon, cupo_max, link_whatsapp)
                         VALUES (:uid, :titulo, :apoyos, :flyer, :desc, :req, :mat, :tipo, :costo, :pub, :dur, :dias, :salon, :cupo, :wa)"
                    );
                    $stmt->execute([
                        'uid' => $_SESSION['usuario_id'], 'titulo' => $titulo, 'apoyos' => $apoyos, 'flyer' => $flyerUrl,
                        'desc' => $descripcion, 'req' => $requisitos, 'mat' => $materiales, 'tipo' => $tipo,
                        'costo' => $costo, 'pub' => $publico, 'dur' => $duracionHoras, 'dias' => $dias,
                        'salon' => $salon, 'cupo' => $cupoMax, 'wa' => $linkWhatsapp,
                    ]);
                }
                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Error al guardar la propuesta.';
            }
        }
    }
    // Conservar valores para redibujar el formulario en caso de error
    $taller = array_merge($taller ?? [], $_POST);
}

$tituloPagina = ($idTaller > 0 ? 'Editar' : 'Nueva') . ' Propuesta · SGT-CA';
require_once __DIR__ . '/../../includes/header.php';

function val($taller, $campo, $default = '') {
    return htmlspecialchars($taller[$campo] ?? $default);
}
?>
<h1><?php echo $idTaller > 0 ? 'Editar Propuesta' : 'Nueva Propuesta de Taller'; ?></h1>
<p class="subtitulo">Captura toda la información necesaria para tu taller. Al guardar cambios, la propuesta pasa a revisión del administrador.</p>

<?php if ($error): ?><div class="alerta alerta-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<form class="tarjeta-form" method="POST" enctype="multipart/form-data" style="max-width:680px;">
  <input type="hidden" name="id" value="<?php echo $idTaller; ?>">

  <div class="campo">
    <label for="titulo">Título del taller</label>
    <input type="text" id="titulo" name="titulo" required value="<?php echo val($taller, 'titulo'); ?>">
  </div>

  <div class="campo">
    <label for="apoyos">Personal de apoyo</label>
    <input type="text" id="apoyos" name="apoyos" placeholder="Nombres separados por coma" value="<?php echo val($taller, 'apoyos'); ?>">
  </div>

  <div class="campo">
    <label for="flyer">Imagen de flyer descriptivo (JPG/PNG/WEBP, máx. 2MB)</label>
    <input type="file" id="flyer" name="flyer" accept=".jpg,.jpeg,.png,.webp" onchange="validarImagen(this, 2)">
    <small id="mensaje-flyer" style="color:#dc2626;"></small>
    <?php if (!empty($taller['flyer_url'])): ?>
      <img src="<?php echo $rutaBase . htmlspecialchars($taller['flyer_url']); ?>" style="max-width:160px;margin-top:6px;border-radius:6px;">
    <?php endif; ?>
  </div>

  <div class="campo">
    <label for="descripcion">Descripción detallada</label>
    <textarea id="descripcion" name="descripcion" required><?php echo val($taller, 'descripcion'); ?></textarea>
  </div>

  <div class="campo">
    <label for="requisitos">Conocimientos previos obligatorios</label>
    <textarea id="requisitos" name="requisitos"><?php echo val($taller, 'requisitos'); ?></textarea>
  </div>

  <div class="campo">
    <label for="materiales">Lista de materiales requeridos</label>
    <textarea id="materiales" name="materiales"><?php echo val($taller, 'materiales'); ?></textarea>
  </div>

  <div class="fila-2">
    <div class="campo">
      <label for="tipo">Estatus de cobro</label>
      <select id="tipo" name="tipo" onchange="document.getElementById('campo-costo').style.display = this.value==='pago' ? 'block' : 'none';">
        <option value="gratis" <?php echo ($taller['tipo'] ?? '') === 'gratis' ? 'selected' : ''; ?>>Gratuito</option>
        <option value="pago" <?php echo ($taller['tipo'] ?? '') === 'pago' ? 'selected' : ''; ?>>De pago</option>
      </select>
    </div>
    <div class="campo" id="campo-costo" style="display:<?php echo ($taller['tipo'] ?? '') === 'pago' ? 'block' : 'none'; ?>;">
      <label for="costo">Costo (MXN)</label>
      <input type="number" id="costo" name="costo" step="0.01" min="0" value="<?php echo val($taller, 'costo', '0'); ?>">
    </div>
  </div>

  <div class="fila-2">
    <div class="campo">
      <label for="publico">Público objetivo</label>
      <select id="publico" name="publico">
        <option value="general" <?php echo ($taller['publico'] ?? '') === 'general' ? 'selected' : ''; ?>>General</option>
        <option value="carrera" <?php echo ($taller['publico'] ?? '') === 'carrera' ? 'selected' : ''; ?>>Carrera específica</option>
        <option value="universidad" <?php echo ($taller['publico'] ?? '') === 'universidad' ? 'selected' : ''; ?>>Comunidad universitaria</option>
      </select>
    </div>
    <div class="campo">
      <label for="duracion_horas">Duración (horas)</label>
      <input type="number" id="duracion_horas" name="duracion_horas" step="0.5" min="0.5" value="<?php echo val($taller, 'duracion_horas', '1'); ?>">
    </div>
  </div>

  <div class="fila-2">
    <div class="campo">
      <label for="dias">Días asignados</label>
      <input type="text" id="dias" name="dias" placeholder="Lunes y Miércoles" value="<?php echo val($taller, 'dias'); ?>">
    </div>
    <div class="campo">
      <label for="salon">Aula propuesta</label>
      <input type="text" id="salon" name="salon" placeholder="Edificio A - Salón 3" value="<?php echo val($taller, 'salon'); ?>">
    </div>
  </div>

  <div class="campo">
    <label for="cupo_max">Cupo máximo</label>
    <input type="number" id="cupo_max" name="cupo_max" min="1" required value="<?php echo val($taller, 'cupo_max', '20'); ?>">
  </div>

  <div class="campo">
    <label for="link_whatsapp">Enlace al grupo de WhatsApp</label>
    <input type="url" id="link_whatsapp" name="link_whatsapp" required placeholder="https://chat.whatsapp.com/..." value="<?php echo val($taller, 'link_whatsapp'); ?>">
    <small>Este enlace permanece oculto para los alumnos hasta que se inscriban exitosamente.</small>
  </div>

  <button type="submit" class="btn"><?php echo $idTaller > 0 ? 'Guardar cambios' : 'Enviar propuesta a revisión'; ?></button>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
