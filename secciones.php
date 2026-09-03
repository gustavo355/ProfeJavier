<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_rol(['Administrador']);

// ---- ¿Editando una sección existente? ----
$editando = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT id_seccion, nombre, grado FROM secciones WHERE id_seccion = :id");
    $stmt->execute(['id' => (int) $_GET['editar']]);
    $editando = $stmt->fetch();
}

// ---- Listado de secciones con cantidad de estudiantes ----
$secciones = $conn->query(
    "SELECT s.id_seccion, s.nombre, s.grado,
            COUNT(e.id_estudiante) AS total_estudiantes
     FROM secciones s
     LEFT JOIN estudiantes e ON e.id_seccion = s.id_seccion
     GROUP BY s.id_seccion, s.nombre, s.grado
     ORDER BY s.grado, s.nombre"
)->fetchAll();

$mensaje = $_GET['msg'] ?? '';
$error   = $_GET['error'] ?? '';

$mensajes = [
    'creada'      => 'Sección creada correctamente.',
    'actualizada' => 'Sección actualizada correctamente.',
    'eliminada'   => 'Sección eliminada correctamente.',
];
$errores = [
    'campos'          => 'Completa todos los campos requeridos.',
    'duplicada'       => 'Ya existe una sección con ese nombre y grado.',
    'tiene_estudiantes' => 'No se puede eliminar: la sección tiene estudiantes asignados.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Secciones — Asistencia INDEL</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  .badge-count { background:#eef2ff; color:#4338ca; font-size:11.5px; font-weight:600; padding:3px 9px; border-radius:20px; white-space:nowrap; }
  .row-actions { display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
  .row-actions a, .row-actions button {
    font-size:12px; font-weight:600; padding:5px 10px; border-radius:7px;
    text-decoration:none; border:1px solid var(--border); background:#fff; color:var(--text); cursor:pointer;
  }
  .row-actions a:hover, .row-actions button:hover { background: var(--bg); }
  .row-actions .danger { color:#b91c1c; border-color:#fecaca; }
</style>
</head>
<body>

<?php $activo = 'secciones'; require 'includes/navbar.php'; ?>

<div class="page">

  <div class="card">
    <div class="card-header">
      <div>
        <h2><?= $editando ? 'Editar sección' : 'Nueva sección' ?></h2>
        <p>Las secciones agrupan a los estudiantes por grado.</p>
      </div>
    </div>

    <?php if ($mensaje && isset($mensajes[$mensaje])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($mensajes[$mensaje]) ?></div>
    <?php endif; ?>
    <?php if ($error && isset($errores[$error])): ?>
      <div class="alert alert-error"><?= htmlspecialchars($errores[$error]) ?></div>
    <?php endif; ?>

    <form method="post" action="process_seccion.php">
      <input type="hidden" name="accion" value="<?= $editando ? 'actualizar' : 'crear' ?>">
      <?php if ($editando): ?>
        <input type="hidden" name="id_seccion" value="<?= $editando['id_seccion'] ?>">
      <?php endif; ?>

      <div class="field-row">
        <div class="field">
          <label for="grado">Grado</label>
          <input type="text" id="grado" name="grado" required placeholder="Ej: 1er Año"
                 value="<?= htmlspecialchars($editando['grado'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="nombre">Sección</label>
          <input type="text" id="nombre" name="nombre" required placeholder="Ej: A"
                 value="<?= htmlspecialchars($editando['nombre'] ?? '') ?>">
        </div>
      </div>

      <div class="actions-row">
        <?php if ($editando): ?>
          <a href="secciones.php" class="btn btn-outline">Cancelar</a>
        <?php endif; ?>
        <button type="submit" class="btn btn-blue">
          <?= $editando ? '✓ Guardar cambios' : '+ Crear sección' ?>
        </button>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2>Secciones registradas</h2>
        <p><?= count($secciones) ?> secciones</p>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Grado</th>
          <th>Sección</th>
          <th>Estudiantes</th>
          <th style="text-align:right;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($secciones)): ?>
          <tr><td colspan="4" style="text-align:center; color:#6b7280; padding:24px;">
            Aún no hay secciones registradas.
          </td></tr>
        <?php else: ?>
          <?php foreach ($secciones as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['grado']) ?></td>
              <td><?= htmlspecialchars($s['nombre']) ?></td>
              <td><span class="badge-count"><?= (int) $s['total_estudiantes'] ?></span></td>
              <td>
                <div class="row-actions">
                  <a href="secciones.php?editar=<?= $s['id_seccion'] ?>">Editar</a>
                  <?php if ((int) $s['total_estudiantes'] === 0): ?>
                    <form method="post" action="process_seccion.php" style="display:inline;"
                          onsubmit="return confirm('¿Eliminar esta sección?');">
                      <input type="hidden" name="accion" value="eliminar">
                      <input type="hidden" name="id_seccion" value="<?= $s['id_seccion'] ?>">
                      <button type="submit" class="danger">Eliminar</button>
                    </form>
                  <?php endif; ?>
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
