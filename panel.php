<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }
include "conexion.php";
$res = pg_query($conexion, "SELECT * FROM productos ORDER BY id DESC");
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Panel de Administración</title>
<link rel="icon" type="image/png" href="IMG/favicon.png?v=1">
<link rel="stylesheet" href="CSS/styles.css">
</head>

<div id="deleteModal" class="modal">
  <div class="modal-content" style="text-align:center;">
    <h3>¿Estas seguro de eliminar este producto?</h3>
    <p style="margin-bottom:16px;">Esta acción no se puede deshacer.</p>
    <div style="display:flex; gap:12px; justify-content:center;">
      <button id="confirmDelete" class="btn danger">Eliminar</button>
      <button id="cancelDelete" class="btn ghost">Cancelar</button>
    </div>
  </div>
</div>


<body>
<header class="admin-header">
    <h1>Panel de Administración</h1>
    <div>
        <span> <?=htmlspecialchars($_SESSION['admin'])?></span>
        <a class="logout" href="logout.php">Cerrar sesión</a>
    </div>
</header>

<div class="admin-container">

    <a href="agregar.php" class="btn-primary">+ Agregar Producto</a>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Marca</th>
                <th>Precio</th>
                <th>Imagen</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
        <?php while($p = pg_fetch_assoc($res)) { ?>
            <tr>
                <td><?=$p['id']?></td>
                <td><?=htmlspecialchars($p['nombre'])?></td>
                <td><?=htmlspecialchars($p['marca'])?></td>
                <td>$<?=number_format($p['precio'],2)?></td>
                <td><img src="<?=htmlspecialchars($p['imagen'])?>" class="thumb"></td>
                <td>
                    <a class="btn-edit" href="editar.php?id=<?=$p['id']?>">Editar</a>
                    <a class="btn-delete" href="eliminar.php?id=<?=$p['id']?>" data-id="<?=$p['id']?>" class="delete-link">Eliminar</a>

                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

</div>

</body>
</html>


