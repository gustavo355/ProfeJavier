<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: meritos_demeritos.php");
    exit;
}

$id_estudiante = (int) ($_POST['id_estudiante'] ?? 0);
$tipo          = $_POST['tipo'] ?? '';
$motivo        = trim($_POST['motivo'] ?? '');
$fecha         = $_POST['fecha'] ?? date('Y-m-d');

$tipos_validos = ['Mérito', 'Demérito'];

if ($id_estudiante === 0 || !in_array($tipo, $tipos_validos, true) || $motivo === '') {
    header("Location: meritos_demeritos.php?error=campos");
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO meritos_demeritos (id_estudiante, tipo, motivo, fecha)
     VALUES (:id_estudiante, :tipo, :motivo, :fecha)"
);
$stmt->execute([
    'id_estudiante' => $id_estudiante,
    'tipo'          => $tipo,
    'motivo'        => $motivo,
    'fecha'         => $fecha,
]);

header("Location: meritos_demeritos.php?msg=guardado");
exit;
