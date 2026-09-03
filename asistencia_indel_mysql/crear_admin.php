<?php
/**
 * crear_admin.php
 * Ejecuta este archivo UNA sola vez desde el navegador para crear el primer
 * usuario administrador (la tabla "usuarios" no trae datos de ejemplo).
 * Bórralo después de usarlo, por seguridad.
 */
require_once "config.php";

$usuario    = "admin";
$contrasena = "admin123";      // cámbiala después de tu primer inicio de sesión
$nombre     = "Administrador General";
$correo     = "admin@indel.edu.sv";

$id_rol = $conn->query(
    "SELECT id_rol FROM roles WHERE nombre_rol = 'Administrador'"
)->fetch()['id_rol'];

$existe = $conn->prepare("SELECT id_usuario FROM usuarios WHERE usuario = :usuario");
$existe->execute(['usuario' => $usuario]);

if ($existe->fetch()) {
    die("El usuario '{$usuario}' ya existe. Puedes borrar este archivo.");
}

$hash = password_hash($contrasena, PASSWORD_BCRYPT);

$stmt = $conn->prepare(
    "INSERT INTO usuarios (nombre_completo, correo, usuario, contrasena_hash, id_rol, activo)
     VALUES (:nombre, :correo, :usuario, :hash, :id_rol, 1)"
);
$stmt->execute([
    'nombre'  => $nombre,
    'correo'  => $correo,
    'usuario' => $usuario,
    'hash'    => $hash,
    'id_rol'  => $id_rol,
]);

echo "Usuario administrador creado correctamente.<br>";
echo "Usuario: <b>{$usuario}</b><br>";
echo "Contraseña: <b>{$contrasena}</b><br><br>";
echo "Inicia sesión en <a href='login.php'>login.php</a> y luego borra este archivo (crear_admin.php).";
