<?php
require_once "includes/auth.php";
requerir_login();

if (empty($_SESSION['reporte_temp'])) {
    header("Location: generar_reporte.php");
    exit;
}
$r = $_SESSION['reporte_temp'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($r['titulo']) ?> — Asistencia INDEL</title>
<style>
  body { font-family: Arial, sans-serif; color: #111827; padding: 30px; }
  h1 { font-size: 18px; margin-bottom: 2px; }
  p.sub { color: #6b7280; margin-top: 0; font-size: 12px; }
  table { width: 100%; border-collapse: collapse; margin-top: 18px; }
  th, td { border: 1px solid #d1d5db; padding: 6px 10px; font-size: 12.5px; text-align: left; }
  th { background: #f3f4f6; }
  .no-print { margin-top: 20px; }
  @media print { .no-print { display: none; } }
</style>
</head>
<body>

<h1>Asistencia INDEL — <?= htmlspecialchars($r['titulo']) ?></h1>
<p class="sub">Instituto Nacional Cantón Lourdes · Del <?= htmlspecialchars($r['desde']) ?> al <?= htmlspecialchars($r['hasta']) ?></p>

<table>
  <thead>
    <tr>
      <?php foreach ($r['columnas'] as $col): ?>
        <th><?= htmlspecialchars($col) ?></th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($r['filas'])): ?>
      <tr><td colspan="<?= count($r['columnas']) ?>">No hay datos para el rango seleccionado.</td></tr>
    <?php else: ?>
      <?php foreach ($r['filas'] as $fila): ?>
        <tr>
          <?php foreach ($fila as $valor): ?>
            <td><?= htmlspecialchars((string) $valor) ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<div class="no-print">
  <button onclick="window.print()">Imprimir / Guardar como PDF</button>
  <a href="generar_reporte.php" style="margin-left:10px;">Volver</a>
</div>

</body>
</html>
