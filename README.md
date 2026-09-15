# SGT-CA · Sistema de Gestión de Talleres y Certificación Automatizada SeGunTu
<img width="1906" height="880" alt="image" src="https://github.com/user-attachments/assets/7daac401-8b20-4305-9176-f47253afbb94" />

Sistema completo desarrollado en **PHP estructurado + MySQL/MariaDB + HTML5/CSS3/JavaScript Vanilla**,
optimizado para desplegarse en hosting compartido gratuito (InfinityFree).

## Estado del proyecto
**probado de extremo a extremo**

- ✅ Registro y login con validación estricta de Gmail personal (no institucional).
- ✅ Panel de Administrador: validación de propuestas, monitor de inscritos en tiempo real, generador
  de plantillas de certificados.
- ✅ Panel de Tallerista: registro/edición de propuestas con subida de flyer, control de asistencia.
- ✅ Panel de Alumno: catálogo en tiempo real, inscripción con revelación de enlace de WhatsApp,
  cancelación con liberación automática de cupo, descarga de constancia en PDF.
- ✅ Control transaccional **ACID con `SELECT ... FOR UPDATE`** verificado bajo inscripciones
  concurrentes: nunca se rebasa el `cupo_max` de un taller (probado con 3 peticiones simultáneas
  sobre un cupo de 2, resultando exactamente 2 inscripciones aceptadas y 1 rechazada).
  Ver `modules/alumno/inscribir.php`.
- ✅ Generación de PDF **on-the-fly** con FPDF: el certificado se transmite como stream binario
  (`Output('I', ...)`) directamente al navegador, sin escribir ningún archivo en el disco del
  servidor. Ver `helpers/pdf_generator.php`.
- ✅ Validación de peso (2MB) y formato (JPG/PNG/WEBP) de imágenes, tanto en cliente (JS) como en
  servidor (PHP), acorde a las limitaciones de InfinityFree.

## Estructura del proyecto

Jerarquía definida en la propuesta:

```
/
├── assets/{css,js,img,templates_pdf}/
├── config/database.php
├── includes/{header,footer,navbar,session}.php
├── modules/{auth,admin,tallerista,alumno}/
├── helpers/pdf_generator.php
├── vendor/fpdf/                 # Librería FPDF (incluida, no requiere Composer)
├── sql/schema.sql               # Script de creación de la base de datos
└── index.php
```

## Instalación en InfinityFree

1. **Base de datos**
   - Entra a tu panel de InfinityFree → *MySQL Databases* → crea una base de datos.
   - Anota el host (`sqlXXX.infinityfree.com`), nombre de BD, usuario y contraseña.
   - Abre phpMyAdmin desde el panel e importa el archivo `sql/schema.sql` (pestaña *Importar*).
     Esto crea las 4 tablas (`usuarios`, `talleres`, `inscripciones`, `certificados`) y un
     usuario administrador de prueba.

2. **Configurar la conexión**
   - Edita `config/database.php` y reemplaza `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` con los
     datos reales de tu base de datos de InfinityFree.

3. **Subir archivos**
   - Sube **todo el contenido** de esta carpeta (no la carpeta en sí) a `htdocs/` vía FTP
     (FileZilla) o el Administrador de Archivos del panel.
   - Verifica que las carpetas `assets/img/`, `assets/templates_pdf/` tengan permisos de
     escritura (755 o 775) para que las subidas de flyers y plantillas funcionen.

4. **Primer acceso**
   - Visita `https://tudominio.infinityfreeapp.com/`.
   - Usuario administrador semilla:
     - Correo: `ad@sgtca.local`
     - Contraseña: `*****`
   - **Cambia esta contraseña de inmediato** (créate un nuevo admin y desactiva/edita el semilla
     directamente en phpMyAdmin, ya que el sistema no incluye edición de usuarios desde la UI en
     esta primera versión).

5. **Configura las plantillas de certificado**
   - Inicia sesión como admin → *Plantillas de Constancia* → sube la imagen de fondo, define el
     texto de agradecimiento (puedes usar `{nombre}`, `{titulo}`, `{rol}`) y la fecha de
     liberación.

## Notas técnicas importantes

- **Anti-overbooking**: la inscripción usa una transacción con `SELECT ... FOR UPDATE` sobre la
  fila del taller antes de contar los inscritos e insertar, evitando condiciones de carrera en
  hosting compartido.
- **PDF sin almacenamiento**: `helpers/pdf_generator.php` jamás escribe el PDF resultante en
  disco; se transmite directamente al navegador con `Content-Type: application/pdf`.
- **Seguridad de contraseñas**: se usa `password_hash()` / `password_verify()` con
  `PASSWORD_DEFAULT` (bcrypt).
- **Consultas preparadas**: toda interacción con la base de datos usa PDO con sentencias
  preparadas (protección contra inyección SQL).
- **Validación de Gmail**: reforzada tanto en JavaScript (cliente) como en PHP (servidor) para
  evitar bypass.
- Si tu plan de InfinityFree no tiene la extensión `mbstring` habilitada, el sistema sigue
  funcionando (se incluyó una función de respaldo sin dependencias).

## Cuentas de prueba sugeridas para tus propias pruebas

Después de importar el esquema, regístrate desde la interfaz con cualquier correo `@gmail.com`
como alumno o tallerista; el rol `admin` sólo existe mediante el registro semilla o edición
manual en la base de datos (`UPDATE usuarios SET rol='admin' WHERE correo='...'`).

## Créditos
Gaspar Gijon

Sistema Integrado de Gestión de Talleres y Certificación Automatizada (SGT-CA) SeGunTu.
