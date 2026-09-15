<?php
/**
 * config/database.php
 * Conexión centralizada a MySQL/MariaDB mediante PDO.
 * Ajusta estas constantes con los datos proporcionados por tu panel
 * de InfinityFree (Vista general del hosting -> Detalles de la cuenta MySQL).
 */

// ---------------------------------------------------------------------------
// CREDENCIALES DE CONEXIÓN (editar antes de subir a producción)
// ---------------------------------------------------------------------------
define('DB_HOST', 'sql110.infinityfree.com');   // Host MySQL de InfinityFree
define('DB_NAME', 'if0_42382194_base_de_datos_sgt');        // Nombre de la base de datos
define('DB_USER', 'if0_42382194');              // Usuario MySQL
define('DB_PASS', 'GeMkgjvTuYbsgB');          // Password MySQL

/**
 * Devuelve una instancia PDO reutilizable (patrón singleton simple).
 * Lanza excepciones PDOException en caso de error, capturadas por el
 * controlador que invoque la conexión.
 */
function obtenerConexion(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
    } catch (PDOException $e) {
        http_response_code(500);
        // Muestra el error real de PDO para depuración. ¡No usar en producción!
        die('Error de conexión a la base de datos: ' . $e->getMessage());
    }

    return $pdo;
}
