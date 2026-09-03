<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_rol(['Administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: secciones.php");
    exit;
}

$accion = $_POST['accion'] ?? '';

// ---------------------------------------------------------------
// Eliminar (solo si no tiene estudiantes asignados)
// ---------------------------------------------------------------
if ($accion === 'eliminar') {
    $id = (int) ($_POST['id_seccion'] ?? 0);

    $chk = $conn->prepare("SELECT COUNT(*) AS total FROM estudiantes WHERE id_seccion = :id");
    $chk->execute(['id' => $id]);
    if ((int) $chk->fetch()['total'] > 0) {
        header("Location: secciones.php?error=tiene_estudiantes");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM secciones WHERE id_seccion = :id");
    $stmt->execute(['id' => $id]);

    header("Location: secciones.php?msg=eliminada");
    exit;
}

// ---------------------------------------------------------------
// Crear / actualizar — campos comunes
// ---------------------------------------------------------------
$nombre = trim($_POST['nombre'] ?? '');
$grado  = trim($_POST['grado'] ?? '');

if ($nombre === '' || $grado === '') {
    header("Location: secciones.php?error=campos");
    exit;
}

if ($accion === 'crear') {
    $chk = $conn->prepare("SELECT id_seccion FROM secciones WHERE nombre = :nombre AND grado = :grado");
    $chk->execute(['nombre' => $nombre, 'grado' => $grado]);
    if ($chk->fetch()) {
        header("Location: secciones.php?error=duplicada");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO secciones (nombre, grado) VALUES (:nombre, :grado)");
    $stmt->execute(['nombre' => $nombre, 'grado' => $grado]);

    header("Location: secciones.php?msg=creada");
    exit;
}

if ($accion === 'actualizar') {
    $id = (int) ($_POST['id_seccion'] ?? 0);

    $chk = $conn->prepare(
        "SELECT id_seccion FROM secciones WHERE nombre = :nombre AND grado = :grado AND id_seccion != :id"
    );
    $chk->execute(['nombre' => $nombre, 'grado' => $grado, 'id' => $id]);
    if ($chk->fetch()) {
        header("Location: secciones.php?error=duplicada");
        exit;
    }

    $stmt = $conn->prepare("UPDATE secciones SET nombre = :nombre, grado = :grado WHERE id_seccion = :id");
    $stmt->execute(['nombre' => $nombre, 'grado' => $grado, 'id' => $id]);

    header("Location: secciones.php?msg=actualizada");
    exit;
}

header("Location: secciones.php");
exit;
