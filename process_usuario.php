<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_rol(['Administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: usuarios.php");
    exit;
}

$accion = $_POST['accion'] ?? '';

// ---------------------------------------------------------------
// Activar / desactivar
// ---------------------------------------------------------------
if ($accion === 'cambiar_estado') {
    $id     = (int) ($_POST['id_usuario'] ?? 0);
    $activo = (int) ($_POST['activo'] ?? 1);

    if ($id === (int) $_SESSION['id_usuario'] && $activo === 0) {
        header("Location: usuarios.php?error=auto_desactivar");
        exit;
    }

    $stmt = $conn->prepare("UPDATE usuarios SET activo = :activo WHERE id_usuario = :id");
    $stmt->execute(['activo' => $activo, 'id' => $id]);

    header("Location: usuarios.php?msg=" . ($activo ? 'activado' : 'desactivado'));
    exit;
}

// ---------------------------------------------------------------
// Crear / actualizar — campos comunes
// ---------------------------------------------------------------
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$correo          = trim($_POST['correo'] ?? '');
$usuario          = trim($_POST['usuario'] ?? '');
$id_rol           = (int) ($_POST['id_rol'] ?? 0);
$contrasena       = $_POST['contrasena'] ?? '';

if ($nombre_completo === '' || $correo === '' || $usuario === '' || $id_rol === 0) {
    header("Location: usuarios.php?error=campos");
    exit;
}

if ($accion === 'crear') {
    if ($contrasena === '') {
        header("Location: usuarios.php?error=campos");
        exit;
    }

    $chk = $conn->prepare("SELECT id_usuario FROM usuarios WHERE usuario = :usuario");
    $chk->execute(['usuario' => $usuario]);
    if ($chk->fetch()) {
        header("Location: usuarios.php?error=usuario_existe");
        exit;
    }

    $chk = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo = :correo");
    $chk->execute(['correo' => $correo]);
    if ($chk->fetch()) {
        header("Location: usuarios.php?error=correo_existe");
        exit;
    }

    $hash = password_hash($contrasena, PASSWORD_BCRYPT);

    $stmt = $conn->prepare(
        "INSERT INTO usuarios (nombre_completo, correo, usuario, contrasena_hash, id_rol, activo)
         VALUES (:nombre, :correo, :usuario, :hash, :id_rol, 1)"
    );
    $stmt->execute([
        'nombre'  => $nombre_completo,
        'correo'  => $correo,
        'usuario' => $usuario,
        'hash'    => $hash,
        'id_rol'  => $id_rol,
    ]);

    header("Location: usuarios.php?msg=creado");
    exit;
}

if ($accion === 'actualizar') {
    $id = (int) ($_POST['id_usuario'] ?? 0);

    $chk = $conn->prepare("SELECT id_usuario FROM usuarios WHERE usuario = :usuario AND id_usuario != :id");
    $chk->execute(['usuario' => $usuario, 'id' => $id]);
    if ($chk->fetch()) {
        header("Location: usuarios.php?error=usuario_existe");
        exit;
    }

    $chk = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo = :correo AND id_usuario != :id");
    $chk->execute(['correo' => $correo, 'id' => $id]);
    if ($chk->fetch()) {
        header("Location: usuarios.php?error=correo_existe");
        exit;
    }

    if ($contrasena !== '') {
        $hash = password_hash($contrasena, PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
            "UPDATE usuarios
             SET nombre_completo = :nombre, correo = :correo, usuario = :usuario,
                 id_rol = :id_rol, contrasena_hash = :hash
             WHERE id_usuario = :id"
        );
        $stmt->execute([
            'nombre'  => $nombre_completo,
            'correo'  => $correo,
            'usuario' => $usuario,
            'id_rol'  => $id_rol,
            'hash'    => $hash,
            'id'      => $id,
        ]);
    } else {
        $stmt = $conn->prepare(
            "UPDATE usuarios
             SET nombre_completo = :nombre, correo = :correo, usuario = :usuario, id_rol = :id_rol
             WHERE id_usuario = :id"
        );
        $stmt->execute([
            'nombre'  => $nombre_completo,
            'correo'  => $correo,
            'usuario' => $usuario,
            'id_rol'  => $id_rol,
            'id'      => $id,
        ]);
    }

    // Si el admin se edita a sí mismo, refrescamos el nombre de sesión
    if ($id === (int) $_SESSION['id_usuario']) {
        $_SESSION['nombre_completo'] = $nombre_completo;
    }

    header("Location: usuarios.php?msg=actualizado");
    exit;
}

header("Location: usuarios.php");
exit;
