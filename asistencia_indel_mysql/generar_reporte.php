<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_login();

// ---- Valores por defecto del formulario ----
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

// ---- Estadísticas rápidas ----
$estudiantes_activos = $conn->query(
    "SELECT COUNT(*) AS total FROM estudiantes WHERE estado = 'Activo'"
)->fetch()['total'];

$stmtProm = $conn->prepare(
    "SELECT
        SUM(CASE WHEN estado IN ('Presente','Justificado') THEN 1 ELSE 0 END) AS asistidos,
        COUNT(*) AS total
     FROM asistencia
     WHERE fecha BETWEEN :desde AND :hasta"
);
$stmtProm->execute(['desde' => $desde, 'hasta' => $hasta]);
$prom = $stmtProm->fetch();
$asistencia_promedio = ($prom['total'] > 0)
    ? round(($prom['asistidos'] / $prom['total']) * 100)
    : 0;

$reportes_mes = $conn->query(
    "SELECT COUNT(*) AS total FROM reportes_generados
     WHERE MONTH(fecha_generacion) = MONTH(NOW())
       AND YEAR(fecha_generacion)  = YEAR(NOW())"
)->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Generar reporte — Asistencia INDEL</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="topbar">
  <div class="brand">
    <div class="logo">&#127891;</div>
    Asistencia INDEL
  </div>
  <div class="user-info">
    <span><?= htmlspecialchars($_SESSION['nombre_completo']) ?> · <?= htmlspecialchars($_SESSION['nombre_rol']) ?></span>
    <a href="registrar_asistencia.php">Asistencia</a>
    <a href="logout.php">Cerrar sesión</a>
  </div>
</div>

<div class="page">
  <div class="card">
    <div class="card-header">
      <div>
        <h2>Generar reporte</h2>
        <p>Instituto Nacional Cantón Lourdes</p>
      </div>
    </div>

    <form method="post" action="process_reporte.php">
      <div class="field-row">
        <div class="field">
          <label for="tipo_reporte">Tipo de reporte</label>
          <select id="tipo_reporte" name="tipo_reporte">
            <option value="asistencia">Asistencia mensual</option>
            <option value="servicio_social">Horas de servicio social</option>
            <option value="meritos_demeritos">Méritos y deméritos</option>
          </select>
        </div>
        <div class="field">
          <label for="formato">Formato de exportación</label>
          <select id="formato" name="formato">
            <option value="PDF">PDF</option>
            <option value="Excel">Excel</option>
          </select>
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="desde">Desde</label>
          <input type="date" id="desde" name="desde" value="<?= htmlspecialchars($desde) ?>">
        </div>
        <div class="field">
          <label for="hasta">Hasta</label>
          <input type="date" id="hasta" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
        </div>
      </div>

      <div class="stats-row">
        <div class="stat-box">
          <div class="stat-label">Estudiantes activos</div>
          <div class="stat-value"><?= (int) $estudiantes_activos ?></div>
        </div>
        <div class="stat-box">
          <div class="stat-label">Asistencia promedio</div>
          <div class="stat-value"><?= $asistencia_promedio ?>%</div>
        </div>
        <div class="stat-box">
          <div class="stat-label">Reportes este mes</div>
          <div class="stat-value"><?= (int) $reportes_mes ?></div>
        </div>
      </div>

      <div class="actions-row">
        <button type="submit" class="btn btn-dark">&#8681; Generar reporte</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
