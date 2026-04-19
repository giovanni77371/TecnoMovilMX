<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }
include "conexion.php";

$id = intval($_GET['id'] ?? 0);
$res = pg_query_params($conexion, "SELECT * FROM productos WHERE id=$1", array($id));
if (!$prod = pg_fetch_assoc($res)) { header('Location: panel.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $marca = $_POST['marca'];
    $precio = $_POST['precio'];
    $descripcion = $_POST['descripcion'];
    $caracteristicas = $_POST['caracteristicas'];
    $img_path = $prod['imagen'];

    if (!empty($_FILES['imagen']['name'])) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $target = 'uploads/' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target)) $img_path = $target;
    }

    $update = "UPDATE productos SET nombre=$1, marca=$2, precio=$3, descripcion=$4, caracteristicas=$5, imagen=$6 WHERE id=$7";
    pg_query_params($conexion, $update, array($nombre,$marca,$precio,$descripcion,$caracteristicas,$img_path,$id));
    header('Location: panel.php'); exit;
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Editar</title><link rel="icon" type="image/png" href="IMG/favicon.png?v=1"><link rel="stylesheet" href="css/styles.css"></head>
<body>
<div class="container" style="margin-top:30px;">
  <h2>Editar Producto</h2>
  <form method="post" enctype="multipart/form-data">
    <label>Nombre</label><input name="nombre" required value="<?=htmlspecialchars($prod['nombre'])?>" style="width:100%;padding:8px;margin-bottom:8px;"><br>
    <label>Marca</label><input name="marca" required value="<?=htmlspecialchars($prod['marca'])?>" style="width:100%;padding:8px;margin-bottom:8px;"><br>
    <label>Precio</label><input type="number" step="0.01" name="precio" required value="<?=htmlspecialchars($prod['precio'])?>" style="width:100%;padding:8px;margin-bottom:8px;"><br>
    <label>DescripciÃ³n</label><textarea name="descripcion" style="width:100%;padding:8px;margin-bottom:8px;"><?=htmlspecialchars($prod['descripcion'])?></textarea><br>
    <label>CaracterÃ­sticas</label><textarea name="caracteristicas" style="width:100%;padding:8px;margin-bottom:8px;"><?=htmlspecialchars($prod['caracteristicas'])?></textarea><br>
    <label>Imagen (dejar vacÃ­o para no cambiar)</label><input type="file" name="imagen" style="margin-bottom:12px;"><br>
    <button type="submit" style="padding:10px 16px;background:#032f6b;color:#fff;border:none;border-radius:6px;">Guardar cambios</button>
  </form>
  <p><a href="panel.php">â† Volver</a></p>
</div>
</body></html>


