<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: generar_reporte.php");
    exit;
}

$tipos_validos    = ['asistencia', 'servicio_social', 'meritos_demeritos'];
$formatos_validos = ['PDF', 'Excel'];

$tipo_reporte = in_array($_POST['tipo_reporte'] ?? '', $tipos_validos, true) ? $_POST['tipo_reporte'] : 'asistencia';
$formato      = in_array($_POST['formato'] ?? '', $formatos_validos, true) ? $_POST['formato'] : 'PDF';
$desde        = $_POST['desde'] ?? date('Y-m-01');
$hasta        = $_POST['hasta'] ?? date('Y-m-d');

$etiquetas = [
    'asistencia'        => 'Asistencia mensual',
    'servicio_social'    => 'Horas de servicio social',
    'meritos_demeritos'  => 'Méritos y deméritos',
];

// ---- Obtener los datos según el tipo de reporte ----
function obtener_datos_reporte(PDO $conn, string $tipo, string $desde, string $hasta): array {
    switch ($tipo) {
        case 'servicio_social':
            $sql = "SELECT e.carnet, e.nombre_completo, SUM(s.horas) AS total_horas
                    FROM servicio_social s
                    INNER JOIN estudiantes e ON e.id_estudiante = s.id_estudiante
                    WHERE s.fecha BETWEEN :desde AND :hasta
                    GROUP BY e.carnet, e.nombre_completo
                    ORDER BY e.nombre_completo";
            $columnas = ['Carnet', 'Estudiante', 'Total horas'];
            break;

        case 'meritos_demeritos':
            $sql = "SELECT e.carnet, e.nombre_completo, m.tipo, m.motivo, m.fecha
                    FROM meritos_demeritos m
                    INNER JOIN estudiantes e ON e.id_estudiante = m.id_estudiante
                    WHERE m.fecha BETWEEN :desde AND :hasta
                    ORDER BY m.fecha DESC";
            $columnas = ['Carnet', 'Estudiante', 'Tipo', 'Motivo', 'Fecha'];
            break;

        case 'asistencia':
        default:
            $sql = "SELECT e.carnet, e.nombre_completo,
                        SUM(CASE WHEN a.estado = 'Presente'    THEN 1 ELSE 0 END) AS presentes,
                        SUM(CASE WHEN a.estado = 'Ausente'     THEN 1 ELSE 0 END) AS ausentes,
                        SUM(CASE WHEN a.estado = 'Tardanza'    THEN 1 ELSE 0 END) AS tardanzas,
                        SUM(CASE WHEN a.estado = 'Justificado' THEN 1 ELSE 0 END) AS justificados
                    FROM estudiantes e
                    LEFT JOIN asistencia a
                           ON a.id_estudiante = e.id_estudiante
                          AND a.fecha BETWEEN :desde AND :hasta
                    WHERE e.estado = 'Activo'
                    GROUP BY e.carnet, e.nombre_completo
                    ORDER BY e.nombre_completo";
            $columnas = ['Carnet', 'Estudiante', 'Presentes', 'Ausentes', 'Tardanzas', 'Justificados'];
            break;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
    return ['columnas' => $columnas, 'filas' => $stmt->fetchAll(PDO::FETCH_NUM)];
}

$reporte = obtener_datos_reporte($conn, $tipo_reporte, $desde, $hasta);

// ---- Registrar el reporte generado (RF-07) ----
$parametros = "Rango: {$desde} a {$hasta}";
$stmtLog = $conn->prepare(
    "INSERT INTO reportes_generados (tipo_reporte, formato, id_usuario_generador, parametros)
     VALUES (:tipo, :formato, :usuario, :parametros)"
);
$stmtLog->execute([
    'tipo'       => $etiquetas[$tipo_reporte],
    'formato'    => $formato,
    'usuario'    => $_SESSION['id_usuario'],
    'parametros' => $parametros,
]);

// ---- Exportar ----
if ($formato === 'Excel') {
    $nombre_archivo = $tipo_reporte . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // BOM para que Excel muestre bien los acentos
    fputcsv($out, $reporte['columnas']);
    foreach ($reporte['filas'] as $fila) {
        fputcsv($out, $fila);
    }
    fclose($out);
    exit;
}

// ---- Formato PDF: vista imprimible (el usuario usa "Imprimir > Guardar como PDF") ----
$_SESSION['reporte_temp'] = [
    'titulo'    => $etiquetas[$tipo_reporte],
    'desde'     => $desde,
    'hasta'     => $hasta,
    'columnas'  => $reporte['columnas'],
    'filas'     => $reporte['filas'],
];
header("Location: report_print.php");
exit;
