<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }
include "conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $marca = $_POST['marca'];
    $precio = $_POST['precio'];
    $descripcion = $_POST['descripcion'];
    $caracteristicas = $_POST['caracteristicas'];
    $img_path = 'img/default.png';

    if (!empty($_FILES['imagen']['name'])) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $target = 'uploads/' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target)) $img_path = $target;
    }

    $q = "INSERT INTO productos (nombre, marca, precio, descripcion, caracteristicas, imagen) VALUES ($1,$2,$3,$4,$5,$6)";
    pg_query_params($conexion, $q, array($nombre,$marca,$precio,$descripcion,$caracteristicas,$img_path));
    header('Location: panel.php'); exit;
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Agregar</title><link rel="icon" type="image/png" href="IMG/favicon.png?v=1"><link rel="stylesheet" href="css/styles.css"></head>
<body>
<div class="container" style="margin-top:30px;">
  <h2>Agregar Producto</h2>
  <form method="post" enctype="multipart/form-data">
    <label>Nombre</label><input name="nombre" required style="width:100%;padding:8px;margin-bottom:8px;"><br>
    <label>Marca</label>
<select name="marca" required style="width:100%;padding:8px;margin-bottom:8px;">
  <option value="">Seleccionar marca</option>
  <option value="iPhone">iPhone</option>
  <option value="Samsung">Samsung</option>
  <option value="Motorola">Motorola</option>
  <option value="Xiaomi">Xiaomi</option>
  <option value="OPPO">OPPO</option>
</select><br>

    <label>Precio</label><input type="number" step="0.01" name="precio" required style="width:100%;padding:8px;margin-bottom:8px;"><br>
    <label>DescripciÃ³n</label><textarea name="descripcion" style="width:100%;padding:8px;margin-bottom:8px;"></textarea><br>
    <label>CaracterÃ­sticas</label><textarea name="caracteristicas" style="width:100%;padding:8px;margin-bottom:8px;"></textarea><br>
    <label>Imagen</label><input type="file" name="imagen" style="margin-bottom:12px;"><br>
    <button type="submit" style="padding:10px 16px;background:#032f6b;color:#fff;border:none;border-radius:6px;">Guardar</button>
  </form>
  <p><a href="panel.php">â† Volver</a></p>
</div>
</body></html>


