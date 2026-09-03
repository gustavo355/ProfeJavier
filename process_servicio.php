<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: servicio_social.php");
    exit;
}

$id_estudiante = (int) ($_POST['id_estudiante'] ?? 0);
$horas         = (float) ($_POST['horas'] ?? 0);
$fecha         = $_POST['fecha'] ?? date('Y-m-d');

if ($id_estudiante === 0 || $horas <= 0 || $horas > 24) {
    header("Location: servicio_social.php?error=campos");
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO servicio_social (id_estudiante, horas, fecha)
     VALUES (:id_estudiante, :horas, :fecha)"
);
$stmt->execute([
    'id_estudiante' => $id_estudiante,
    'horas'         => $horas,
    'fecha'         => $fecha,
]);

header("Location: servicio_social.php?msg=guardado");
exit;
