<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_login();

// ---- Secciones para el combo ----
$secciones = $conn->query(
    "SELECT id_seccion, nombre, grado FROM secciones ORDER BY grado, nombre"
)->fetchAll();

$id_seccion = isset($_GET['id_seccion']) && $_GET['id_seccion'] !== ''
    ? (int) $_GET['id_seccion']
    : ($secciones[0]['id_seccion'] ?? 0);

$busqueda = trim($_GET['busqueda'] ?? '');

// ---- Estudiantes de la sección (para el formulario de registro) ----
// Nota: cuando hay texto de búsqueda, se busca en TODAS las secciones,
// no solo en la seleccionada en el combo.
$sqlEst = "SELECT e.id_estudiante, e.carnet, e.nombre_completo,
                  s.nombre AS seccion_nombre, s.grado AS seccion_grado
           FROM estudiantes e
           INNER JOIN secciones s ON s.id_seccion = e.id_seccion
           WHERE e.estado = 'Activo'";
$paramsEst = [];
if ($busqueda !== '') {
    $sqlEst .= " AND (e.carnet LIKE :busqueda1 OR e.nombre_completo LIKE :busqueda2)";
    $paramsEst['busqueda1'] = "%{$busqueda}%";
    $paramsEst['busqueda2'] = "%{$busqueda}%";
} else {
    $sqlEst .= " AND e.id_seccion = :id_seccion";
    $paramsEst['id_seccion'] = $id_seccion;
}
$sqlEst .= " ORDER BY e.nombre_completo";
$stmtEst = $conn->prepare($sqlEst);
$stmtEst->execute($paramsEst);
$estudiantes = $stmtEst->fetchAll();

// ---- Acumulado de horas por estudiante de la sección seleccionada ----
// (antes esta consulta ignoraba la búsqueda por completo; ahora también
// la respeta, y busca en todas las secciones si hay texto de búsqueda)
$sqlAcum = "SELECT e.id_estudiante, e.carnet, e.nombre_completo,
                   s.nombre AS seccion_nombre, s.grado AS seccion_grado,
                   COALESCE(SUM(sv.horas), 0) AS total_horas
            FROM estudiantes e
            INNER JOIN secciones s ON s.id_seccion = e.id_seccion
            LEFT JOIN servicio_social sv ON sv.id_estudiante = e.id_estudiante
            WHERE e.estado = 'Activo'";
$paramsAcum = [];
if ($busqueda !== '') {
    $sqlAcum .= " AND (e.carnet LIKE :busqueda1 OR e.nombre_completo LIKE :busqueda2)";
    $paramsAcum['busqueda1'] = "%{$busqueda}%";
    $paramsAcum['busqueda2'] = "%{$busqueda}%";
} else {
    $sqlAcum .= " AND e.id_seccion = :id_seccion";
    $paramsAcum['id_seccion'] = $id_seccion;
}
$sqlAcum .= " GROUP BY e.id_estudiante, e.carnet, e.nombre_completo, s.nombre, s.grado
            ORDER BY e.nombre_completo";
$stmtAcum = $conn->prepare($sqlAcum);
$stmtAcum->execute($paramsAcum);
$acumulado = $stmtAcum->fetchAll();

const HORAS_META = 50; // meta institucional de horas de servicio social

// ---- Últimos registros (historial reciente) ----
$historial = $conn->query(
    "SELECT s.id_servicio, e.carnet, e.nombre_completo, s.horas, s.fecha
     FROM servicio_social s
     INNER JOIN estudiantes e ON e.id_estudiante = s.id_estudiante
     ORDER BY s.fecha DESC, s.id_servicio DESC
     LIMIT 20"
)->fetchAll();

$mensaje = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Servicio social — Asistencia INDEL</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  .progress-track {
    background:#e5e7eb; border-radius:20px; height:8px; width:100%; overflow:hidden;
  }
  .progress-fill { background: var(--blue); height:100%; border-radius:20px; }
  .progress-fill.completo { background:#15803d; }
  .horas-row { display:flex; align-items:center; gap:10px; }
  .horas-row span { font-size:12.5px; font-weight:600; color:var(--text-soft); white-space:nowrap; }
</style>
</head>
<body>

<?php $activo = 'servicio'; require 'includes/navbar.php'; ?>

<div class="page">

  <div class="card">
    <div class="card-header">
      <div>
        <h2>Registrar horas de servicio social</h2>
        <p>Instituto Nacional Cantón Lourdes</p>
      </div>
      <span class="badge-date"><?= date('d/m/Y') ?></span>
    </div>

    <?php if ($mensaje === 'guardado'): ?>
      <div class="alert alert-success">Horas guardadas correctamente.</div>
    <?php endif; ?>

    <form method="get" action="servicio_social.php">
      <div class="toolbar">
        <div class="field">
          <label for="id_seccion">Sección</label>
          <select id="id_seccion" name="id_seccion" onchange="this.form.submit()">
            <?php foreach ($secciones as $s): ?>
              <option value="<?= $s['id_seccion'] ?>" <?= $s['id_seccion'] == $id_seccion ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['grado'] . ' ' . $s['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field search-box">
          <label for="busqueda">Buscar estudiante</label>
          <span class="icon">&#128269;</span>
          <input type="search" id="busqueda" name="busqueda" placeholder="Buscar por carnet o nombre..."
                 value="<?= htmlspecialchars($busqueda) ?>">
        </div>
        <div class="field" style="flex:0 0 auto;">
          <label>&nbsp;</label>
          <button type="submit" class="btn btn-blue">&#128269; Buscar</button>
        </div>
      </div>
    </form>

    <form method="post" action="process_servicio.php">
      <div class="field-row">
        <div class="field">
          <label for="id_estudiante">Estudiante</label>
          <select id="id_estudiante" name="id_estudiante" required>
            <option value="">Selecciona un estudiante...</option>
            <?php foreach ($estudiantes as $i => $e): ?>
              <option value="<?= $e['id_estudiante'] ?>" <?= ($busqueda !== '' && $i === 0) ? 'selected' : '' ?>>
                <?= htmlspecialchars($e['carnet'] . ' — ' . $e['nombre_completo']) ?><?php if ($busqueda !== ''): ?> (<?= htmlspecialchars($e['seccion_grado'] . ' ' . $e['seccion_nombre']) ?>)<?php endif; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="horas">Horas</label>
          <input type="number" id="horas" name="horas" min="0.5" max="24" step="0.5" required>
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="fecha">Fecha</label>
          <input type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="field"></div>
      </div>

      <div class="actions-row">
        <button type="submit" class="btn btn-blue">&#10003; Guardar horas</button>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2>Acumulado por estudiante</h2>
        <p>Meta institucional: <?= HORAS_META ?> horas</p>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Carnet</th>
          <th>Estudiante</th>
          <?php if ($busqueda !== ''): ?><th>Sección</th><?php endif; ?>
          <th style="width:220px;">Progreso</th>
          <th style="text-align:right;">Horas</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($acumulado)): ?>
          <tr><td colspan="<?= $busqueda !== '' ? 5 : 4 ?>" style="text-align:center; color:#6b7280; padding:24px;">
            No hay estudiantes activos para esta sección o búsqueda.
          </td></tr>
        <?php else: ?>
          <?php foreach ($acumulado as $a): ?>
            <?php
              $pct = min(100, round(($a['total_horas'] / HORAS_META) * 100));
              $completo = $a['total_horas'] >= HORAS_META;
            ?>
            <tr>
              <td><?= htmlspecialchars($a['carnet']) ?></td>
              <td><?= htmlspecialchars($a['nombre_completo']) ?></td>
              <?php if ($busqueda !== ''): ?>
                <td><?= htmlspecialchars($a['seccion_grado'] . ' ' . $a['seccion_nombre']) ?></td>
              <?php endif; ?>
              <td>
                <div class="horas-row">
                  <div class="progress-track">
                    <div class="progress-fill <?= $completo ? 'completo' : '' ?>" style="width:<?= $pct ?>%"></div>
                  </div>
                  <span><?= $pct ?>%</span>
                </div>
              </td>
              <td style="text-align:right; font-weight:600;">
                <?= rtrim(rtrim(number_format($a['total_horas'], 1), '0'), '.') ?> / <?= HORAS_META ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2>Historial reciente</h2>
        <p>Últimos 20 registros</p>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Carnet</th>
          <th>Estudiante</th>
          <th style="text-align:right;">Horas</th>
          <th style="text-align:right;">Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($historial)): ?>
          <tr><td colspan="4" style="text-align:center; color:#6b7280; padding:24px;">
            Aún no hay horas registradas.
          </td></tr>
        <?php else: ?>
          <?php foreach ($historial as $h): ?>
            <tr>
              <td><?= htmlspecialchars($h['carnet']) ?></td>
              <td><?= htmlspecialchars($h['nombre_completo']) ?></td>
              <td style="text-align:right;"><?= rtrim(rtrim(number_format($h['horas'], 1), '0'), '.') ?></td>
              <td style="text-align:right;"><?= date('d/m/Y', strtotime($h['fecha'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

</body>
</html>
