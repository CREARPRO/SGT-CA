-- ============================================================================
-- SGT-CA: Sistema Integrado de Gestión de Talleres y Certificación Automatizada
-- Esquema de Base de Datos - MySQL / MariaDB
-- Compatible con InfinityFree (motor InnoDB, charset utf8mb4)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Tabla: usuarios
-- Almacén central de cuentas de acceso para los tres roles del sistema.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_completo` VARCHAR(150) NOT NULL,
  `correo` VARCHAR(150) NOT NULL,
  `matricula` VARCHAR(50) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol` ENUM('admin','tallerista','alumno') NOT NULL DEFAULT 'alumno',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_correo` (`correo`),
  UNIQUE KEY `uq_usuarios_matricula` (`matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: talleres
-- Propuestas de talleres registradas por los talleristas.
-- El link de WhatsApp permanece oculto en la capa de aplicación hasta que
-- el alumno se inscribe exitosamente (regla de negocio, no de BD).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `talleres` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tallerista_principal` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(180) NOT NULL,
  `apoyos` TEXT DEFAULT NULL,
  `flyer_url` VARCHAR(255) DEFAULT NULL,
  `descripcion` TEXT NOT NULL,
  `requisitos` TEXT DEFAULT NULL,
  `materiales` TEXT DEFAULT NULL,
  `tipo` ENUM('pago','gratis') NOT NULL DEFAULT 'gratis',
  `costo` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `publico` ENUM('general','carrera','universidad') NOT NULL DEFAULT 'general',
  `duracion_horas` DECIMAL(4,1) NOT NULL DEFAULT 1,
  `dias` VARCHAR(120) DEFAULT NULL,
  `salon` VARCHAR(80) DEFAULT NULL,
  `cupo_max` INT UNSIGNED NOT NULL DEFAULT 20,
  `link_whatsapp` VARCHAR(255) DEFAULT NULL,
  `estado` ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `observaciones_admin` TEXT DEFAULT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_talleres_tallerista` (`id_tallerista_principal`),
  KEY `idx_talleres_estado` (`estado`),
  CONSTRAINT `fk_talleres_tallerista` FOREIGN KEY (`id_tallerista_principal`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: inscripciones
-- Entidad pivote alumno-taller. Controla el estado del cupo y determina
-- el derecho a constancia mediante 'asistencia_validada'.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inscripciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_alumno` INT UNSIGNED NOT NULL,
  `id_taller` INT UNSIGNED NOT NULL,
  `estado` ENUM('inscrito','cancelado') NOT NULL DEFAULT 'inscrito',
  `asistencia_validada` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_inscripcion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_cancelacion` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_alumno_taller` (`id_alumno`,`id_taller`),
  KEY `fk_inscripciones_taller` (`id_taller`),
  CONSTRAINT `fk_inscripciones_alumno` FOREIGN KEY (`id_alumno`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inscripciones_taller` FOREIGN KEY (`id_taller`)
    REFERENCES `talleres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: certificados
-- Configuración centralizada de plantillas de certificación (una por tipo).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `certificados` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo` ENUM('alumno','tallerista') NOT NULL,
  `imagen_fondo` VARCHAR(255) NOT NULL,
  `texto_agradecimiento` TEXT NOT NULL,
  `coordenadas_json` TEXT DEFAULT NULL,
  `fecha_liberacion` DATE NOT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_certificados_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- Datos semilla (opcional) - Usuario administrador por defecto
-- Contraseña: Admin123!  (cambiar inmediatamente después de la instalación)
-- Hash generado con password_hash('Admin123!', PASSWORD_DEFAULT)
-- ----------------------------------------------------------------------------
INSERT INTO `usuarios` (`nombre_completo`, `correo`, `matricula`, `password_hash`, `rol`)
VALUES ('Administrador General', 'admin@sgtca.local', 'ADMIN-001',
'$2y$10$WwwxSjupW..FvwKYzOY5oO8VOATTnMEPv/F8AaGgWZhcccJMneN/y', 'admin')
ON DUPLICATE KEY UPDATE correo=correo;
