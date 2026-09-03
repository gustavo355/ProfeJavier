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

// ---- Últimos registros (historial reciente) ----
$historial = $conn->query(
    "SELECT m.id_registro, e.carnet, e.nombre_completo, m.tipo, m.motivo, m.fecha
     FROM meritos_demeritos m
     INNER JOIN estudiantes e ON e.id_estudiante = m.id_estudiante
     ORDER BY m.fecha DESC, m.id_registro DESC
     LIMIT 20"
)->fetchAll();

$mensaje = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Méritos y deméritos — Asistencia INDEL</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  .badge-merito   { background:#dcfce7; color:#15803d; font-size:11.5px; font-weight:600; padding:3px 9px; border-radius:20px; }
  .badge-demerito { background:#fee2e2; color:#b91c1c; font-size:11.5px; font-weight:600; padding:3px 9px; border-radius:20px; }
  textarea {
    width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:9px;
    background:#fff; font-size:13.5px; color:var(--text); font-family:inherit; resize:vertical; min-height:70px;
  }
  textarea:focus { outline:none; border-color:var(--blue); box-shadow:0 0 0 3px rgba(37,99,235,.12); }
</style>
</head>
<body>

<?php $activo = 'meritos'; require 'includes/navbar.php'; ?>

<div class="page">

  <div class="card">
    <div class="card-header">
      <div>
        <h2>Registrar mérito o demérito</h2>
        <p>Instituto Nacional Cantón Lourdes</p>
      </div>
      <span class="badge-date"><?= date('d/m/Y') ?></span>
    </div>

    <?php if ($mensaje === 'guardado'): ?>
      <div class="alert alert-success">Registro guardado correctamente.</div>
    <?php endif; ?>

    <form method="get" action="meritos_demeritos.php">
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

    <form method="post" action="process_merito.php">
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
          <label for="tipo">Tipo</label>
          <select id="tipo" name="tipo" required>
            <option value="Mérito">Mérito</option>
            <option value="Demérito">Demérito</option>
          </select>
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="fecha">Fecha</label>
          <input type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="field"></div>
      </div>

      <div class="field">
        <label for="motivo">Motivo</label>
        <textarea id="motivo" name="motivo" placeholder="Describe el motivo del registro..." required></textarea>
      </div>

      <div class="actions-row">
        <button type="submit" class="btn btn-blue">&#10003; Guardar registro</button>
      </div>
    </form>
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
          <th>Tipo</th>
          <th>Motivo</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($historial)): ?>
          <tr><td colspan="5" style="text-align:center; color:#6b7280; padding:24px;">
            Aún no hay registros de méritos ni deméritos.
          </td></tr>
        <?php else: ?>
          <?php foreach ($historial as $h): ?>
            <tr>
              <td><?= htmlspecialchars($h['carnet']) ?></td>
              <td><?= htmlspecialchars($h['nombre_completo']) ?></td>
              <td>
                <span class="<?= $h['tipo'] === 'Mérito' ? 'badge-merito' : 'badge-demerito' ?>">
                  <?= htmlspecialchars($h['tipo']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($h['motivo']) ?></td>
              <td><?= date('d/m/Y', strtotime($h['fecha'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

</body>
</html>
