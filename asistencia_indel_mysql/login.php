<?php
require_once "includes/auth.php";
require_once "config.php";

// Si ya inició sesión, lo mandamos directo al sistema
if (!empty($_SESSION['id_usuario'])) {
    header("Location: registrar_asistencia.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario    = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($usuario === '' || $contrasena === '') {
        $error = "Ingresa tu usuario y contraseña.";
    } else {
        $stmt = $conn->prepare(
            "SELECT u.id_usuario, u.nombre_completo, u.contrasena_hash, u.activo, r.nombre_rol
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE u.usuario = :usuario"
        );
        $stmt->execute(['usuario' => $usuario]);
        $row = $stmt->fetch();

        if (!$row) {
            $error = "Usuario o contraseña incorrectos.";
        } elseif (!$row['activo']) {
            $error = "Este usuario está inactivo. Contacta al administrador.";
        } elseif (!password_verify($contrasena, $row['contrasena_hash'])) {
            $error = "Usuario o contraseña incorrectos.";
        } else {
            $_SESSION['id_usuario']      = $row['id_usuario'];
            $_SESSION['nombre_completo'] = $row['nombre_completo'];
            $_SESSION['nombre_rol']      = $row['nombre_rol'];
            header("Location: registrar_asistencia.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Asistencia INDEL — Iniciar sesión</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-wrap">
  <div class="card login-card">
    <div class="logo">&#127891;</div>
    <h1>Asistencia INDEL</h1>
    <p class="sub">Instituto Nacional Cantón Lourdes</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <div class="field">
        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario" autocomplete="username" required>
      </div>
      <div class="field">
        <label for="contrasena">Contraseña</label>
        <input type="password" id="contrasena" name="contrasena" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-dark btn-block">Iniciar sesión</button>
    </form>

    <a class="foot-link" href="mailto:soporte@indel.edu.sv">¿Olvidó sus credenciales? Contacte a soporte</a>
  </div>
</div>

</body>
</html>
