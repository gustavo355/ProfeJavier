<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_rol(['Administrador']);

// ---- Secciones para los combos ----
$secciones = $conn->query(
    "SELECT id_seccion, nombre, grado FROM secciones ORDER BY grado, nombre"
)->fetchAll();

// ---- ¿Editando un estudiante existente? ----
$editando = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare(
        "SELECT id_estudiante, carnet, nombre_completo, id_seccion, estado
         FROM estudiantes WHERE id_estudiante = :id"
    );
    $stmt->execute(['id' => (int) $_GET['editar']]);
    $editando = $stmt->fetch();
}

// ---- Filtros del listado ----
$id_seccion_filtro = $_GET['id_seccion'] ?? '';
$estado_filtro     = $_GET['estado'] ?? '';
$busqueda          = trim($_GET['busqueda'] ?? '');

$sql = "SELECT e.id_estudiante, e.carnet, e.nombre_completo, e.estado,
               s.nombre AS seccion_nombre, s.grado AS seccion_grado
        FROM estudiantes e
        INNER JOIN secciones s ON s.id_seccion = e.id_seccion
        WHERE 1=1";
$params = [];

if ($id_seccion_filtro !== '') {
    $sql .= " AND e.id_seccion = :id_seccion";
    $params['id_seccion'] = (int) $id_seccion_filtro;
}
if ($estado_filtro !== '') {
    $sql .= " AND e.estado = :estado";
    $params['estado'] = $estado_filtro;
}
if ($busqueda !== '') {
    $sql .= " AND (e.carnet LIKE :busqueda1 OR e.nombre_completo LIKE :busqueda2)";
    $params['busqueda1'] = "%{$busqueda}%";
    $params['busqueda2'] = "%{$busqueda}%";
}
$sql .= " ORDER BY s.grado, s.nombre, e.nombre_completo";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$estudiantes = $stmt->fetchAll();

$mensaje = $_GET['msg'] ?? '';
$error   = $_GET['error'] ?? '';

$mensajes = [
    'creado'      => 'Estudiante creado correctamente.',
    'actualizado' => 'Estudiante actualizado correctamente.',
    'activado'    => 'Estudiante activado.',
    'desactivado' => 'Estudiante desactivado.',
];
$errores = [
    'campos'         => 'Completa todos los campos requeridos.',
    'carnet_existe'  => 'Ese carnet ya está registrado.',
    'seccion_invalida' => 'Selecciona una sección válida.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Estudiantes — Asistencia INDEL</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  .badge-activo   { background:#dcfce7; color:#15803d; font-size:11.5px; font-weight:600; padding:3px 9px; border-radius:20px; }
  .badge-inactivo { background:#fee2e2; color:#b91c1c; font-size:11.5px; font-weight:600; padding:3px 9px; border-radius:20px; }
  .row-actions { display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
  .row-actions a, .row-actions button {
    font-size:12px; font-weight:600; padding:5px 10px; border-radius:7px;
    text-decoration:none; border:1px solid var(--border); background:#fff; color:var(--text); cursor:pointer;
  }
  .row-actions a:hover, .row-actions button:hover { background: var(--bg); }
  .row-actions .danger { color:#b91c1c; border-color:#fecaca; }
  .field-row-3 { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px; }
  @media (max-width: 900px) { .field-row-3 { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<?php $activo = 'estudiantes'; require 'includes/navbar.php'; ?>

<div class="page">

  <div class="card">
    <div class="card-header">
      <div>
        <h2><?= $editando ? 'Editar estudiante' : 'Nuevo estudiante' ?></h2>
        <p>Alta y edición de estudiantes por sección.</p>
      </div>
    </div>

    <?php if ($mensaje && isset($mensajes[$mensaje])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($mensajes[$mensaje]) ?></div>
    <?php endif; ?>
    <?php if ($error && isset($errores[$error])): ?>
      <div class="alert alert-error"><?= htmlspecialchars($errores[$error]) ?></div>
    <?php endif; ?>

    <?php if (empty($secciones)): ?>
      <div class="alert alert-error">
        Primero debes crear al menos una <a href="secciones.php">sección</a> antes de registrar estudiantes.
      </div>
    <?php else: ?>
      <form method="post" action="process_estudiante.php">
        <input type="hidden" name="accion" value="<?= $editando ? 'actualizar' : 'crear' ?>">
        <?php if ($editando): ?>
          <input type="hidden" name="id_estudiante" value="<?= $editando['id_estudiante'] ?>">
        <?php endif; ?>

        <div class="field-row-3">
          <div class="field">
            <label for="carnet">Carnet</label>
            <input type="text" id="carnet" name="carnet" required autocomplete="off"
                   value="<?= htmlspecialchars($editando['carnet'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="nombre_completo">Nombre completo</label>
            <input type="text" id="nombre_completo" name="nombre_completo" required
                   value="<?= htmlspecialchars($editando['nombre_completo'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="id_seccion">Sección</label>
            <select id="id_seccion" name="id_seccion" required>
              <?php foreach ($secciones as $s): ?>
                <option value="<?= $s['id_seccion'] ?>"
                  <?= (isset($editando['id_seccion']) && $editando['id_seccion'] == $s['id_seccion']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['grado'] . ' ' . $s['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <?php if ($editando): ?>
          <div class="field" style="max-width:220px;">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
              <option value="Activo"   <?= $editando['estado'] === 'Activo'   ? 'selected' : '' ?>>Activo</option>
              <option value="Inactivo" <?= $editando['estado'] === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
          </div>
        <?php endif; ?>

        <div class="actions-row">
          <?php if ($editando): ?>
            <a href="estudiantes.php" class="btn btn-outline">Cancelar</a>
          <?php endif; ?>
          <button type="submit" class="btn btn-blue">
            <?= $editando ? '✓ Guardar cambios' : '+ Crear estudiante' ?>
          </button>
        </div>
      </form>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2>Estudiantes registrados</h2>
        <p><?= count($estudiantes) ?> resultados</p>
      </div>
    </div>

    <form method="get" action="estudiantes.php">
      <div class="toolbar">
        <div class="field">
          <label for="id_seccion_f">Sección</label>
          <select id="id_seccion_f" name="id_seccion" onchange="this.form.submit()">
            <option value="">Todas las secciones</option>
            <?php foreach ($secciones as $s): ?>
              <option value="<?= $s['id_seccion'] ?>" <?= (string) $id_seccion_filtro === (string) $s['id_seccion'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['grado'] . ' ' . $s['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="estado_f">Estado</label>
          <select id="estado_f" name="estado" onchange="this.form.submit()">
            <option value="">Todos</option>
            <option value="Activo"   <?= $estado_filtro === 'Activo'   ? 'selected' : '' ?>>Activos</option>
            <option value="Inactivo" <?= $estado_filtro === 'Inactivo' ? 'selected' : '' ?>>Inactivos</option>
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

    <table>
      <thead>
        <tr>
          <th>Carnet</th>
          <th>Nombre</th>
          <th>Sección</th>
          <th>Estado</th>
          <th style="text-align:right;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($estudiantes)): ?>
          <tr><td colspan="5" style="text-align:center; color:#6b7280; padding:24px;">
            No hay estudiantes que coincidan con el filtro.
          </td></tr>
        <?php else: ?>
          <?php foreach ($estudiantes as $e): ?>
            <tr>
              <td><?= htmlspecialchars($e['carnet']) ?></td>
              <td><?= htmlspecialchars($e['nombre_completo']) ?></td>
              <td><?= htmlspecialchars($e['seccion_grado'] . ' ' . $e['seccion_nombre']) ?></td>
              <td>
                <?php if ($e['estado'] === 'Activo'): ?>
                  <span class="badge-activo">Activo</span>
                <?php else: ?>
                  <span class="badge-inactivo">Inactivo</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="row-actions">
                  <a href="estudiantes.php?editar=<?= $e['id_estudiante'] ?>">Editar</a>
                  <form method="post" action="process_estudiante.php" style="display:inline;">
                    <input type="hidden" name="accion" value="cambiar_estado">
                    <input type="hidden" name="id_estudiante" value="<?= $e['id_estudiante'] ?>">
                    <input type="hidden" name="estado" value="<?= $e['estado'] === 'Activo' ? 'Inactivo' : 'Activo' ?>">
                    <button type="submit" class="<?= $e['estado'] === 'Activo' ? 'danger' : '' ?>">
                      <?= $e['estado'] === 'Activo' ? 'Desactivar' : 'Activar' ?>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

</body>
</html>
