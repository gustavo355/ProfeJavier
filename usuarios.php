<?php
require_once "includes/auth.php";
require_once "config.php";
requerir_rol(['Administrador']);

// ---- Roles para el combo ----
$roles = $conn->query("SELECT id_rol, nombre_rol FROM roles ORDER BY nombre_rol")->fetchAll();

// ---- ¿Editando un usuario existente? ----
$editando = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare(
        "SELECT id_usuario, nombre_completo, correo, usuario, id_rol, activo
         FROM usuarios WHERE id_usuario = :id"
    );
    $stmt->execute(['id' => (int) $_GET['editar']]);
    $editando = $stmt->fetch();
}

// ---- Listado de usuarios ----
$usuarios = $conn->query(
    "SELECT u.id_usuario, u.nombre_completo, u.correo, u.usuario, u.activo, r.nombre_rol
     FROM usuarios u
     INNER JOIN roles r ON r.id_rol = u.id_rol
     ORDER BY u.activo DESC, u.nombre_completo"
)->fetchAll();

$mensaje = $_GET['msg'] ?? '';
$error   = $_GET['error'] ?? '';

$mensajes = [
    'creado'       => 'Usuario creado correctamente.',
    'actualizado'  => 'Usuario actualizado correctamente.',
    'activado'     => 'Usuario activado.',
    'desactivado'  => 'Usuario desactivado.',
];
$errores = [
    'usuario_existe'  => 'Ese nombre de usuario ya está en uso.',
    'correo_existe'   => 'Ese correo ya está registrado.',
    'campos'          => 'Completa todos los campos requeridos.',
    'auto_desactivar' => 'No puedes desactivar tu propia cuenta.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Usuarios — Asistencia INDEL</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  .badge-rol { background:#eef2ff; color:#4338ca; font-size:11.5px; font-weight:600; padding:3px 9px; border-radius:20px; white-space:nowrap; }
  .badge-activo   { background:#dcfce7; color:#15803d; font-size:11.5px; font-weight:600; padding:3px 9px; border-radius:20px; }
  .badge-inactivo { background:#fee2e2; color:#b91c1c; font-size:11.5px; font-weight:600; padding:3px 9px; border-radius:20px; }
  .row-actions { display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
  .row-actions a, .row-actions button {
    font-size:12px; font-weight:600; padding:5px 10px; border-radius:7px;
    text-decoration:none; border:1px solid var(--border); background:#fff; color:var(--text); cursor:pointer;
  }
  .row-actions a:hover, .row-actions button:hover { background: var(--bg); }
  .row-actions .danger { color:#b91c1c; border-color:#fecaca; }
  input[type="email"] {
    width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:9px;
    background:#fff; font-size:13.5px; color:var(--text);
  }
  input[type="email"]:focus { outline:none; border-color:var(--blue); box-shadow:0 0 0 3px rgba(37,99,235,.12); }
  .hint { font-size:12px; color:var(--text-soft); margin-top:-10px; margin-bottom:16px; }
</style>
</head>
<body>

<?php $activo = 'usuarios'; require 'includes/navbar.php'; ?>

<div class="page">

  <div class="card">
    <div class="card-header">
      <div>
        <h2><?= $editando ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
        <p>Solo los administradores pueden crear o modificar cuentas.</p>
      </div>
    </div>

    <?php if ($mensaje && isset($mensajes[$mensaje])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($mensajes[$mensaje]) ?></div>
    <?php endif; ?>
    <?php if ($error && isset($errores[$error])): ?>
      <div class="alert alert-error"><?= htmlspecialchars($errores[$error]) ?></div>
    <?php endif; ?>

    <form method="post" action="process_usuario.php">
      <input type="hidden" name="accion" value="<?= $editando ? 'actualizar' : 'crear' ?>">
      <?php if ($editando): ?>
        <input type="hidden" name="id_usuario" value="<?= $editando['id_usuario'] ?>">
      <?php endif; ?>

      <div class="field-row">
        <div class="field">
          <label for="nombre_completo">Nombre completo</label>
          <input type="text" id="nombre_completo" name="nombre_completo" required
                 value="<?= htmlspecialchars($editando['nombre_completo'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="correo">Correo</label>
          <input type="email" id="correo" name="correo" required
                 value="<?= htmlspecialchars($editando['correo'] ?? '') ?>">
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="usuario">Usuario</label>
          <input type="text" id="usuario" name="usuario" required autocomplete="off"
                 value="<?= htmlspecialchars($editando['usuario'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="id_rol">Rol</label>
          <select id="id_rol" name="id_rol" required>
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['id_rol'] ?>"
                <?= (isset($editando['id_rol']) && $editando['id_rol'] == $r['id_rol']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($r['nombre_rol']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field">
        <label for="contrasena"><?= $editando ? 'Nueva contraseña (opcional)' : 'Contraseña' ?></label>
        <input type="password" id="contrasena" name="contrasena" autocomplete="new-password"
               <?= $editando ? '' : 'required' ?>>
      </div>
      <?php if ($editando): ?>
        <p class="hint">Deja este campo vacío para conservar la contraseña actual.</p>
      <?php endif; ?>

      <div class="actions-row">
        <?php if ($editando): ?>
          <a href="usuarios.php" class="btn btn-outline">Cancelar</a>
        <?php endif; ?>
        <button type="submit" class="btn btn-blue">
          <?= $editando ? '✓ Guardar cambios' : '+ Crear usuario' ?>
        </button>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2>Usuarios del sistema</h2>
        <p><?= count($usuarios) ?> registrados</p>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Usuario</th>
          <th>Correo</th>
          <th>Rol</th>
          <th>Estado</th>
          <th style="text-align:right;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
            <td><?= htmlspecialchars($u['usuario']) ?></td>
            <td><?= htmlspecialchars($u['correo']) ?></td>
            <td><span class="badge-rol"><?= htmlspecialchars($u['nombre_rol']) ?></span></td>
            <td>
              <?php if ($u['activo']): ?>
                <span class="badge-activo">Activo</span>
              <?php else: ?>
                <span class="badge-inactivo">Inactivo</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="row-actions">
                <a href="usuarios.php?editar=<?= $u['id_usuario'] ?>">Editar</a>
                <?php if ($u['id_usuario'] != $_SESSION['id_usuario']): ?>
                  <form method="post" action="process_usuario.php" style="display:inline;">
                    <input type="hidden" name="accion" value="cambiar_estado">
                    <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                    <input type="hidden" name="activo" value="<?= $u['activo'] ? 0 : 1 ?>">
                    <button type="submit" class="<?= $u['activo'] ? 'danger' : '' ?>">
                      <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>

</body>
</html>
