<?php
require_once __DIR__ . '/../../includes/session.php';
requerirRol(['admin']);

$rutaBase = '../../';
$pdo = obtenerConexion();
$error = '';
$exito = '';

// Límite acordado para InfinityFree
define('PESO_MAX_BYTES', 2 * 1024 * 1024);
define('DIR_TEMPLATES', __DIR__ . '/../../assets/templates_pdf/');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo               = $_POST['tipo'] ?? '';
    $textoAgradecimiento = trim($_POST['texto_agradecimiento'] ?? '');
    $fechaLiberacion    = $_POST['fecha_liberacion'] ?? '';
    // Coordenadas del bloque de texto (nombre del beneficiario) sobre la plantilla, en mm.
    $coordX = (float) ($_POST['coord_x'] ?? 105);
    $coordY = (float) ($_POST['coord_y'] ?? 90);

    if (!in_array($tipo, ['alumno', 'tallerista'], true) || $textoAgradecimiento === '' || $fechaLiberacion === '') {
        $error = 'Completa todos los campos obligatorios.';
    } else {
        $rutaImagen = null;

        if (!empty($_FILES['imagen_fondo']['name'])) {
            $archivo = $_FILES['imagen_fondo'];
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            $tiposValidos = ['jpg', 'jpeg', 'png', 'webp'];

            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                $error = 'Error al subir el archivo.';
            } elseif (!in_array($extension, $tiposValidos, true)) {
                $error = 'Formato no permitido. Usa JPG, PNG o WEBP.';
            } elseif ($archivo['size'] > PESO_MAX_BYTES) {
                $error = 'La imagen supera el límite de 2MB permitido en InfinityFree.';
            } else {
                if (!is_dir(DIR_TEMPLATES)) mkdir(DIR_TEMPLATES, 0755, true);
                $nombreArchivo = 'plantilla_' . $tipo . '_' . time() . '.' . $extension;
                if (move_uploaded_file($archivo['tmp_name'], DIR_TEMPLATES . $nombreArchivo)) {
                    $rutaImagen = 'assets/templates_pdf/' . $nombreArchivo;
                } else {
                    $error = 'No se pudo guardar la imagen en el servidor.';
                }
            }
        }

        if ($error === '') {
            try {
                // upsert manual porque 'tipo' es UNIQUE
                $stmt = $pdo->prepare('SELECT id, imagen_fondo FROM certificados WHERE tipo = :tipo LIMIT 1');
                $stmt->execute(['tipo' => $tipo]);
                $existente = $stmt->fetch();

                $imagenFinal = $rutaImagen ?: ($existente['imagen_fondo'] ?? null);
                $coordenadasJson = json_encode(['x' => $coordX, 'y' => $coordY]);

                if ($existente) {
                    $stmt = $pdo->prepare(
                        'UPDATE certificados SET imagen_fondo=:img, texto_agradecimiento=:texto,
                         coordenadas_json=:coord, fecha_liberacion=:fecha WHERE id=:id'
                    );
                    $stmt->execute([
                        'img' => $imagenFinal, 'texto' => $textoAgradecimiento,
                        'coord' => $coordenadasJson, 'fecha' => $fechaLiberacion, 'id' => $existente['id'],
                    ]);
                } else {
                    if (!$imagenFinal) {
                        $error = 'Debes subir una imagen de fondo para una plantilla nueva.';
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO certificados (tipo, imagen_fondo, texto_agradecimiento, coordenadas_json, fecha_liberacion)
                             VALUES (:tipo, :img, :texto, :coord, :fecha)'
                        );
                        $stmt->execute([
                            'tipo' => $tipo, 'img' => $imagenFinal, 'texto' => $textoAgradecimiento,
                            'coord' => $coordenadasJson, 'fecha' => $fechaLiberacion,
                        ]);
                    }
                }
                if ($error === '') $exito = 'Plantilla de certificado guardada correctamente.';
            } catch (PDOException $e) {
                $error = 'Error al guardar la plantilla.';
            }
        }
    }
}

$plantillas = $pdo->query('SELECT * FROM certificados')->fetchAll();
$mapaPlantillas = [];
foreach ($plantillas as $pl) { $mapaPlantillas[$pl['tipo']] = $pl; }

$tituloPagina = 'Plantillas de Certificados · SGT-CA';
require_once __DIR__ . '/../../includes/header.php';
?>
<h1>Generador de Plantillas de Certificados</h1>
<p class="subtitulo">Sube la imagen de fondo institucional, define el texto de agradecimiento y la fecha de liberación automatizada.</p>

<?php if ($error): ?><div class="alerta alerta-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($exito): ?><div class="alerta alerta-exito"><?php echo htmlspecialchars($exito); ?></div><?php endif; ?>

<form class="tarjeta-form" method="POST" enctype="multipart/form-data" style="max-width:640px;">
  <div class="campo">
    <label for="tipo">Tipo de constancia</label>
    <select id="tipo" name="tipo" required>
      <option value="alumno">Alumno (por concluir el taller)</option>
      <option value="tallerista">Tallerista (por impartir el taller)</option>
    </select>
  </div>

  <div class="campo">
    <label for="imagen_fondo">Imagen de fondo (JPG/PNG/WEBP, máx. 2MB)</label>
    <input type="file" id="imagen_fondo" name="imagen_fondo" accept=".jpg,.jpeg,.png,.webp"
           onchange="validarImagen(this, 2)">
    <small id="mensaje-imagen_fondo" style="color:#dc2626;"></small>
  </div>

  <div class="campo">
    <label for="texto_agradecimiento">Texto de agradecimiento</label>
    <textarea id="texto_agradecimiento" name="texto_agradecimiento" required
      placeholder="Ej: Por su valiosa participación como {rol} en el taller {titulo}..."></textarea>
    <small>Puedes usar {nombre}, {titulo} y {rol} como marcadores; el motor de renderizado los sustituirá.</small>
  </div>

  <div class="fila-2">
    <div class="campo">
      <label for="coord_x">Posición X del texto (mm)</label>
      <input type="number" id="coord_x" name="coord_x" step="0.5" value="105">
    </div>
    <div class="campo">
      <label for="coord_y">Posición Y del texto (mm)</label>
      <input type="number" id="coord_y" name="coord_y" step="0.5" value="90">
    </div>
  </div>

  <div class="campo">
    <label for="fecha_liberacion">Fecha de liberación automatizada</label>
    <input type="date" id="fecha_liberacion" name="fecha_liberacion" required>
  </div>

  <button type="submit" class="btn">Guardar plantilla</button>
</form>

<h2>Plantillas configuradas</h2>
<div class="grid-talleres">
  <?php foreach (['alumno', 'tallerista'] as $t): ?>
    <div class="tarjeta-taller">
      <?php if (!empty($mapaPlantillas[$t]['imagen_fondo'])): ?>
        <img src="<?php echo $rutaBase . htmlspecialchars($mapaPlantillas[$t]['imagen_fondo']); ?>">
      <?php endif; ?>
      <div class="tarjeta-taller-body">
        <h3>Plantilla: <?php echo ucfirst($t); ?></h3>
        <?php if (isset($mapaPlantillas[$t])): ?>
          <p><small>Libera: <?php echo htmlspecialchars($mapaPlantillas[$t]['fecha_liberacion']); ?></small></p>
        <?php else: ?>
          <p><small>Sin configurar</small></p>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
