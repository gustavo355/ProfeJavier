<?php
/**
 * config.php
 * Conexión a la base de datos "asistencia_indel" (MySQL / phpMyAdmin)
 * Requiere la extensión pdo_mysql habilitada en PHP (viene activada
 * por defecto en XAMPP / WAMP / Laragon).
 */

// ---- Datos de conexión: ajusta según tu instalación ----
$db_host = "localhost";             // Ej: "localhost" o "127.0.0.1"
$db_port = "3306";                  // Puerto de MySQL (3306 por defecto)
$db_name = "asistencia_indel";      // Nombre de la base creada en phpMyAdmin
$db_user = "root";                  // usuario de MySQL (en XAMPP suele ser "root")
$db_pass = "";                      // contraseña de MySQL (en XAMPP suele ir vacía)

try {
    $conn = new PDO(
        "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
