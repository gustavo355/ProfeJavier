<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: registrar_asistencia.php");
    exit;
}

$id_seccion = (int) ($_POST['id_seccion'] ?? 0);
$fecha      = $_POST['fecha'] ?? date('Y-m-d');
$estados    = $_POST['estado'] ?? [];   // [id_estudiante => estado]
$id_docente = $_SESSION['id_usuario'];

$estados_validos = ['Presente', 'Ausente', 'Tardanza', 'Justificado'];

$sqlExiste = "SELECT id_asistencia FROM asistencia WHERE id_estudiante = :id_estudiante AND fecha = :fecha";
$sqlInsert = "INSERT INTO asistencia (id_estudiante, id_docente, fecha, estado)
              VALUES (:id_estudiante, :id_docente, :fecha, :estado)";
$sqlUpdate = "UPDATE asistencia SET estado = :estado, id_docente = :id_docente
              WHERE id_estudiante = :id_estudiante AND fecha = :fecha";

$stmtExiste = $conn->prepare($sqlExiste);
$stmtInsert = $conn->prepare($sqlInsert);
$stmtUpdate = $conn->prepare($sqlUpdate);

$conn->beginTransaction();
try {
    foreach ($estados as $id_estudiante => $estado) {
        $id_estudiante = (int) $id_estudiante;
        if (!in_array($estado, $estados_validos, true)) {
            continue;
        }

        $stmtExiste->execute(['id_estudiante' => $id_estudiante, 'fecha' => $fecha]);
        $existe = $stmtExiste->fetch();

        if ($existe) {
            $stmtUpdate->execute([
                'estado'        => $estado,
                'id_docente'    => $id_docente,
                'id_estudiante' => $id_estudiante,
                'fecha'         => $fecha,
            ]);
        } else {
            $stmtInsert->execute([
                'id_estudiante' => $id_estudiante,
                'id_docente'    => $id_docente,
                'fecha'         => $fecha,
                'estado'        => $estado,
            ]);
        }
    }
    $conn->commit();
} catch (Exception $e) {
    $conn->rollBack();
    die("Error al guardar la asistencia: " . $e->getMessage());
}

header("Location: registrar_asistencia.php?id_seccion={$id_seccion}&msg=guardado");
exit;
