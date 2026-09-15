<?php
/**
 * includes/session.php
 * Arranque de sesión y utilidades de control de acceso basado en roles.
 * Debe incluirse en TODOS los controladores protegidos.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/** Devuelve true si hay una sesión de usuario activa. */
function estaAutenticado(): bool
{
    return isset($_SESSION['usuario_id'], $_SESSION['rol']);
}

/** Obliga a que exista sesión activa; si no, redirige al login. */
function requerirSesion(): void
{
    if (!estaAutenticado()) {
        header('Location: /modules/auth/login.php');
        exit;
    }
}

/**
 * Obliga a que el usuario en sesión tenga uno de los roles permitidos.
 * @param string[] $rolesPermitidos
 */
function requerirRol(array $rolesPermitidos): void
{
    requerirSesion();
    if (!in_array($_SESSION['rol'], $rolesPermitidos, true)) {
        http_response_code(403);
        die('Acceso denegado: tu rol no tiene permisos para ver esta sección.');
    }
}

/** Helper para responder JSON en endpoints AJAX. */
function responderJSON($datos, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}
