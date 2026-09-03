<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_login();

$fecha_hoy = date('Y-m-d');

// ---- Secciones para el combo ----
$secciones = $conn->query(
    "SELECT id_seccion, nombre, grado FROM secciones ORDER BY grado, nombre"
)->fetchAll();

// ---- Sección y búsqueda seleccionadas ----
$id_seccion = isset($_GET['id_seccion']) && $_GET['id_seccion'] !== ''
    ? (int) $_GET['id_seccion']
    : ($secciones[0]['id_seccion'] ?? 0);

$busqueda = trim($_GET['busqueda'] ?? '');

// ---- Estudiantes de la sección + estado de asistencia de hoy (si existe) ----
// Nota: cuando hay texto de búsqueda, se busca en TODAS las secciones
// (no solo en la que está seleccionada en el combo), porque el usuario
// puede no saber a qué sección pertenece el estudiante que busca.
$sql = "SELECT e.id_estudiante, e.carnet, e.nombre_completo,
               s.nombre AS seccion_nombre, s.grado AS seccion_grado,
               a.estado
        FROM estudiantes e
        INNER JOIN secciones s ON s.id_seccion = e.id_seccion
        LEFT JOIN asistencia a
               ON a.id_estudiante = e.id_estudiante AND a.fecha = :fecha
        WHERE e.estado = 'Activo'";

$params = ['fecha' => $fecha_hoy];

if ($busqueda !== '') {
    $sql .= " AND (e.carnet LIKE :busqueda1 OR e.nombre_completo LIKE :busqueda2)";
    $params['busqueda1'] = "%{$busqueda}%";
    $params['busqueda2'] = "%{$busqueda}%";
} else {
    $sql .= " AND e.id_seccion = :id_seccion";
    $params['id_seccion'] = $id_seccion;
}

$sql .= " ORDER BY e.nombre_completo";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$estudiantes = $stmt->fetchAll();

$mensaje = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar asistencia — Asistencia INDEL</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php $activo = 'asistencia'; require 'includes/navbar.php'; ?>

<div class="page">
  <div class="card">
    <div class="card-header">
      <div>
        <h2>Registrar asistencia</h2>
        <p>Instituto Nacional Cantón Lourdes</p>
      </div>
      <span class="badge-date"><?= date('d/m/Y') ?></span>
    </div>

    <?php if ($mensaje === 'guardado'): ?>
      <div class="alert alert-success">Asistencia guardada correctamente.</div>
    <?php endif; ?>

    <form method="get" action="registrar_asistencia.php">
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

    <form method="post" action="process_asistencia.php">
      <input type="hidden" name="id_seccion" value="<?= $id_seccion ?>">
      <input type="hidden" name="fecha" value="<?= $fecha_hoy ?>">

      <table>
        <thead>
          <tr>
            <th>Carnet</th>
            <th>Estudiante</th>
            <?php if ($busqueda !== ''): ?><th>Sección</th><?php endif; ?>
            <th style="text-align:right;">Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($estudiantes)): ?>
            <tr><td colspan="<?= $busqueda !== '' ? 4 : 3 ?>" style="text-align:center; color:#6b7280; padding:24px;">
              No hay estudiantes activos para esta sección o búsqueda.
            </td></tr>
          <?php else: ?>
            <?php foreach ($estudiantes as $e): ?>
              <?php $estado_actual = $e['estado'] ?: 'Presente'; ?>
              <tr>
                <td><?= htmlspecialchars($e['carnet']) ?></td>
                <td><?= htmlspecialchars($e['nombre_completo']) ?></td>
                <?php if ($busqueda !== ''): ?>
                  <td><?= htmlspecialchars($e['seccion_grado'] . ' ' . $e['seccion_nombre']) ?></td>
                <?php endif; ?>
                <td style="text-align:right;">
                  <select name="estado[<?= $e['id_estudiante'] ?>]"
                          class="select-estado estado-<?= $estado_actual ?>"
                          onchange="this.className = 'select-estado estado-' + this.value">
                    <option value="Presente"    <?= $estado_actual === 'Presente'    ? 'selected' : '' ?>>Presente</option>
                    <option value="Ausente"     <?= $estado_actual === 'Ausente'     ? 'selected' : '' ?>>Ausente</option>
                    <option value="Tardanza"    <?= $estado_actual === 'Tardanza'    ? 'selected' : '' ?>>Tardanza</option>
                    <option value="Justificado" <?= $estado_actual === 'Justificado' ? 'selected' : '' ?>>Justificado</option>
                  </select>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <?php if (!empty($estudiantes)): ?>
        <div class="actions-row">
          <button type="submit" class="btn btn-blue">&#10003; Guardar asistencia</button>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

</body>
</html>
