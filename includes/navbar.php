<?php
/**
 * includes/navbar.php
 * Barra superior común a todas las pantallas internas del sistema.
 *
 * Uso en cada página:
 *   $activo = 'asistencia';           // clave de la pantalla actual (o '' si ninguna aplica)
 *   require 'includes/navbar.php';
 *
 * La clave $activo se usa únicamente para no mostrar el enlace hacia la
 * propia pantalla en la que ya está el usuario.
 */

$activo = $activo ?? '';

$nav_items = [
    ['key' => 'asistencia',  'href' => 'registrar_asistencia.php', 'label' => 'Asistencia'],
    ['key' => 'meritos',     'href' => 'meritos_demeritos.php',    'label' => 'Méritos'],
    ['key' => 'servicio',    'href' => 'servicio_social.php',      'label' => 'Servicio social'],
    ['key' => 'reportes',    'href' => 'generar_reporte.php',      'label' => 'Reportes'],
];

$nav_admin = [
    ['key' => 'secciones',   'href' => 'secciones.php',   'label' => 'Secciones'],
    ['key' => 'estudiantes', 'href' => 'estudiantes.php', 'label' => 'Estudiantes'],
    ['key' => 'usuarios',    'href' => 'usuarios.php',    'label' => 'Usuarios'],
];
?>
<div class="topbar">
  <div class="brand">
    <div class="logo">&#127891;</div>
    Asistencia INDEL
  </div>
  <div class="user-info">
    <span><?= htmlspecialchars($_SESSION['nombre_completo']) ?> · <?= htmlspecialchars($_SESSION['nombre_rol']) ?></span>
    <?php foreach ($nav_items as $item): ?>
      <?php if ($item['key'] !== $activo): ?>
        <a href="<?= $item['href'] ?>"><?= htmlspecialchars($item['label']) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($_SESSION['nombre_rol'] === 'Administrador'): ?>
      <?php foreach ($nav_admin as $item): ?>
        <?php if ($item['key'] !== $activo): ?>
          <a href="<?= $item['href'] ?>"><?= htmlspecialchars($item['label']) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
    <a href="logout.php">Cerrar sesión</a>
  </div>
</div>
