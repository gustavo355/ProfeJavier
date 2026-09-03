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
$sql = "SELECT e.id_estudiante, e.carnet, e.nombre_completo,
               a.estado
        FROM estudiantes e
        LEFT JOIN asistencia a
               ON a.id_estudiante = e.id_estudiante AND a.fecha = :fecha
        WHERE e.id_seccion = :id_seccion
          AND e.estado = 'Activo'";

$params = ['fecha' => $fecha_hoy, 'id_seccion' => $id_seccion];

if ($busqueda !== '') {
    $sql .= " AND (e.carnet LIKE :busqueda OR e.nombre_completo LIKE :busqueda)";
    $params['busqueda'] = "%{$busqueda}%";
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

<div class="topbar">
  <div class="brand">
    <div class="logo">&#127891;</div>
    Asistencia INDEL
  </div>
  <div class="user-info">
    <span><?= htmlspecialchars($_SESSION['nombre_completo']) ?> · <?= htmlspecialchars($_SESSION['nombre_rol']) ?></span>
    <a href="generar_reporte.php">Reportes</a>
    <a href="logout.php">Cerrar sesión</a>
  </div>
</div>

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
            <th style="text-align:right;">Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($estudiantes)): ?>
            <tr><td colspan="3" style="text-align:center; color:#6b7280; padding:24px;">
              No hay estudiantes activos para esta sección o búsqueda.
            </td></tr>
          <?php else: ?>
            <?php foreach ($estudiantes as $e): ?>
              <?php $estado_actual = $e['estado'] ?: 'Presente'; ?>
              <tr>
                <td><?= htmlspecialchars($e['carnet']) ?></td>
                <td><?= htmlspecialchars($e['nombre_completo']) ?></td>
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
