/**
 * assets/js/main.js
 * JavaScript Vanilla: gestión asíncrona (Fetch API), modales, validación
 * de formularios y contadores de cupo en tiempo real.
 */

/* ---------------------------------------------------------------------
 * Utilidades de Modal genéricas
 * ------------------------------------------------------------------- */
function abrirModal(idModal) {
  const modal = document.getElementById(idModal);
  if (modal) modal.classList.add('activo');
}
function cerrarModal(idModal) {
  const modal = document.getElementById(idModal);
  if (modal) modal.classList.remove('activo');
}
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('overlay-modal')) {
    e.target.classList.remove('activo');
  }
});

/* ---------------------------------------------------------------------
 * Validación de correo Gmail personal (registro)
 * Refuerza en cliente la regla de negocio validada tambien en el backend.
 * ------------------------------------------------------------------- */
function validarCorreoGmail(input) {
  const regex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
  const mensajeEl = document.getElementById('mensaje-correo');
  if (!regex.test(input.value.trim())) {
    if (mensajeEl) {
      mensajeEl.textContent = 'Por favor, introduce tu correo de Gmail personal (no institucional)';
      mensajeEl.style.color = '#dc2626';
    }
    input.setCustomValidity('Correo inválido');
    return false;
  }
  if (mensajeEl) { mensajeEl.textContent = ''; }
  input.setCustomValidity('');
  return true;
}

/* ---------------------------------------------------------------------
 * Validación de peso/formato de imágenes (flyers, plantillas, etc.)
 * Límitado en InfinityFree: 2MB, formatos JPG/PNG/WEBP.
 * ------------------------------------------------------------------- */
function validarImagen(input, maxMB = 2) {
  const mensajeEl = document.getElementById('mensaje-' + input.id);
  if (!input.files || !input.files[0]) return true;

  const archivo = input.files[0];
  const tiposValidos = ['image/jpeg', 'image/png', 'image/webp'];
  const maxBytes = maxMB * 1024 * 1024;

  if (!tiposValidos.includes(archivo.type)) {
    if (mensajeEl) mensajeEl.textContent = 'Formato no permitido. Usa JPG, PNG o WEBP.';
    input.value = '';
    return false;
  }
  if (archivo.size > maxBytes) {
    if (mensajeEl) mensajeEl.textContent = `El archivo supera el límite de ${maxMB}MB.`;
    input.value = '';
    return false;
  }
  if (mensajeEl) mensajeEl.textContent = '';
  return true;
}

/* ---------------------------------------------------------------------
 * Catálogo de talleres: inscripción vía AJAX (Fetch API)
 * ------------------------------------------------------------------- */
async function inscribirseTaller(idTaller, btn) {
  btn.disabled = true;
  const textoOriginal = btn.textContent;
  btn.textContent = 'Procesando...';

  try {
    const resp = await fetch('/modules/alumno/inscribir.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_taller: idTaller })
    });
    const data = await resp.json();

    if (data.ok) {
      const contador = document.getElementById('cupo-' + idTaller);
      if (contador) {
        contador.textContent = data.cupos_disponibles + ' cupos disponibles';
        contador.className = data.cupos_disponibles > 0 ? 'cupo-disponible' : 'cupo-lleno';
      }
      const enlaceWa = document.getElementById('whatsapp-' + idTaller);
      if (enlaceWa && data.link_whatsapp) {
        enlaceWa.href = data.link_whatsapp;
        enlaceWa.style.display = 'inline-block';
      }
      btn.textContent = 'Inscrito ✔';
      mostrarAlerta('exito', data.mensaje || 'Inscripción realizada con éxito.');
    } else {
      btn.disabled = false;
      btn.textContent = textoOriginal;
      mostrarAlerta('error', data.mensaje || 'No fue posible completar la inscripción.');
    }
  } catch (err) {
    btn.disabled = false;
    btn.textContent = textoOriginal;
    mostrarAlerta('error', 'Error de red. Intenta nuevamente.');
  }
}

/* ---------------------------------------------------------------------
 * Cancelar inscripción vía AJAX
 * ------------------------------------------------------------------- */
async function cancelarInscripcion(idInscripcion, filaEl) {
  if (!confirm('¿Deseas cancelar esta inscripción? El cupo se liberará de inmediato.')) return;

  try {
    const resp = await fetch('/modules/alumno/cancelar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_inscripcion: idInscripcion })
    });
    const data = await resp.json();
    if (data.ok) {
      if (filaEl) filaEl.remove();
      mostrarAlerta('exito', 'Inscripción cancelada correctamente.');
    } else {
      mostrarAlerta('error', data.mensaje || 'No se pudo cancelar la inscripción.');
    }
  } catch (err) {
    mostrarAlerta('error', 'Error de red. Intenta nuevamente.');
  }
}

/* ---------------------------------------------------------------------
 * Admin: aprobar / rechazar propuestas vía AJAX
 * ------------------------------------------------------------------- */
async function resolverPropuesta(idTaller, accion, filaEl) {
  let observaciones = '';
  if (accion === 'rechazado') {
    observaciones = prompt('Escribe las observaciones técnicas para el rechazo:') || '';
    if (observaciones.trim() === '') return;
  }
  try {
    const resp = await fetch('/modules/admin/resolver_propuesta.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_taller: idTaller, estado: accion, observaciones })
    });
    const data = await resp.json();
    if (data.ok) {
      mostrarAlerta('exito', 'Propuesta actualizada.');
      if (filaEl) filaEl.remove();
    } else {
      mostrarAlerta('error', data.mensaje || 'No se pudo actualizar la propuesta.');
    }
  } catch (err) {
    mostrarAlerta('error', 'Error de red. Intenta nuevamente.');
  }
}

/* ---------------------------------------------------------------------
 * Tallerista: marcar asistencia vía AJAX (checkbox individual)
 * ------------------------------------------------------------------- */
async function marcarAsistencia(idInscripcion, checkboxEl) {
  try {
    const resp = await fetch('/modules/tallerista/guardar_asistencia.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_inscripcion: idInscripcion, asistio: checkboxEl.checked ? 1 : 0 })
    });
    const data = await resp.json();
    if (!data.ok) {
      checkboxEl.checked = !checkboxEl.checked;
      mostrarAlerta('error', data.mensaje || 'No se pudo guardar la asistencia.');
    }
  } catch (err) {
    checkboxEl.checked = !checkboxEl.checked;
    mostrarAlerta('error', 'Error de red al guardar asistencia.');
  }
}

/* ---------------------------------------------------------------------
 * Monitor de inscritos en tiempo real (polling ligero)
 * ------------------------------------------------------------------- */
function iniciarMonitorTiempoReal(intervaloMs = 8000) {
  const contenedor = document.getElementById('tabla-monitor');
  if (!contenedor) return;

  async function actualizar() {
    try {
      const resp = await fetch('/modules/admin/monitor_datos.php');
      const data = await resp.json();
      if (data.ok) {
        data.talleres.forEach((t) => {
          const celda = document.getElementById('inscritos-' + t.id);
          if (celda) celda.textContent = `${t.inscritos} / ${t.cupo_max}`;
        });
      }
    } catch (err) { /* silencioso: reintenta en el siguiente ciclo */ }
  }
  actualizar();
  setInterval(actualizar, intervaloMs);
}

/* ---------------------------------------------------------------------
 * Alertas flotantes simples
 * ------------------------------------------------------------------- */
function mostrarAlerta(tipo, mensaje) {
  const contenedor = document.getElementById('zona-alertas') || document.body;
  const div = document.createElement('div');
  div.className = 'alerta alerta-' + (tipo === 'exito' ? 'exito' : 'error');
  div.textContent = mensaje;
  contenedor.prepend(div);
  setTimeout(() => div.remove(), 5000);
}
