<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_rol(['Administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: estudiantes.php");
    exit;
}

$accion = $_POST['accion'] ?? '';

// ---------------------------------------------------------------
// Activar / desactivar
// ---------------------------------------------------------------
if ($accion === 'cambiar_estado') {
    $id     = (int) ($_POST['id_estudiante'] ?? 0);
    $estado = ($_POST['estado'] ?? '') === 'Activo' ? 'Activo' : 'Inactivo';

    $stmt = $conn->prepare("UPDATE estudiantes SET estado = :estado WHERE id_estudiante = :id");
    $stmt->execute(['estado' => $estado, 'id' => $id]);

    header("Location: estudiantes.php?msg=" . ($estado === 'Activo' ? 'activado' : 'desactivado'));
    exit;
}

// ---------------------------------------------------------------
// Crear / actualizar — campos comunes
// ---------------------------------------------------------------
$carnet          = trim($_POST['carnet'] ?? '');
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$id_seccion      = (int) ($_POST['id_seccion'] ?? 0);
$estado          = ($_POST['estado'] ?? 'Activo') === 'Inactivo' ? 'Inactivo' : 'Activo';

if ($carnet === '' || $nombre_completo === '' || $id_seccion === 0) {
    header("Location: estudiantes.php?error=campos");
    exit;
}

$chkSeccion = $conn->prepare("SELECT id_seccion FROM secciones WHERE id_seccion = :id");
$chkSeccion->execute(['id' => $id_seccion]);
if (!$chkSeccion->fetch()) {
    header("Location: estudiantes.php?error=seccion_invalida");
    exit;
}

if ($accion === 'crear') {
    $chk = $conn->prepare("SELECT id_estudiante FROM estudiantes WHERE carnet = :carnet");
    $chk->execute(['carnet' => $carnet]);
    if ($chk->fetch()) {
        header("Location: estudiantes.php?error=carnet_existe");
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO estudiantes (carnet, nombre_completo, id_seccion, estado)
         VALUES (:carnet, :nombre, :id_seccion, 'Activo')"
    );
    $stmt->execute([
        'carnet'     => $carnet,
        'nombre'     => $nombre_completo,
        'id_seccion' => $id_seccion,
    ]);

    header("Location: estudiantes.php?msg=creado");
    exit;
}

if ($accion === 'actualizar') {
    $id = (int) ($_POST['id_estudiante'] ?? 0);

    $chk = $conn->prepare("SELECT id_estudiante FROM estudiantes WHERE carnet = :carnet AND id_estudiante != :id");
    $chk->execute(['carnet' => $carnet, 'id' => $id]);
    if ($chk->fetch()) {
        header("Location: estudiantes.php?error=carnet_existe");
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE estudiantes
         SET carnet = :carnet, nombre_completo = :nombre, id_seccion = :id_seccion, estado = :estado
         WHERE id_estudiante = :id"
    );
    $stmt->execute([
        'carnet'     => $carnet,
        'nombre'     => $nombre_completo,
        'id_seccion' => $id_seccion,
        'estado'     => $estado,
        'id'         => $id,
    ]);

    header("Location: estudiantes.php?msg=actualizado");
    exit;
}

header("Location: estudiantes.php");
exit;
