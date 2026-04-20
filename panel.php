<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/productos_catalogo.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$productos = obtenerProductosCatalogo($conexion);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Panel de Administracion</title>
  <link rel="icon" type="image/png" href="IMG/favicon.png?v=1">
  <link rel="stylesheet" href="CSS/styles.css?v=11">
</head>
<body>

<div id="deleteModal" class="modal">
  <div class="modal-content" style="text-align:center;">
    <h3>Estas seguro de eliminar este producto?</h3>
    <p style="margin-bottom:16px;">Esta accion no se puede deshacer.</p>
    <div style="display:flex; gap:12px; justify-content:center;">
      <button id="confirmDelete" class="btn danger">Eliminar</button>
      <button id="cancelDelete" class="btn ghost">Cancelar</button>
    </div>
  </div>
</div>

<header class="admin-header">
  <h1>Panel de Administracion</h1>
  <div>
    <span><?= htmlspecialchars((string) $_SESSION['admin']) ?></span>
    <a class="logout" href="logout.php">Cerrar sesion</a>
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
      <?php if (empty($productos)) { ?>
        <tr>
          <td colspan="6" class="empty-state">No hay productos disponibles.</td>
        </tr>
      <?php } else {
          foreach ($productos as $producto) {
              $id = (int) ($producto['id'] ?? 0);
              $nombre = htmlspecialchars((string) ($producto['nombre'] ?? 'Producto'));
              $marca = htmlspecialchars((string) ($producto['marca'] ?? ''));
              $precio = number_format((float) ($producto['precio'] ?? 0), 2);
              $imagen = htmlspecialchars(normalizarImagenProducto($producto['imagen'] ?? null));
      ?>
        <tr>
          <td><?= $id ?></td>
          <td><?= $nombre ?></td>
          <td><?= $marca ?></td>
          <td>$<?= $precio ?></td>
          <td><img src="<?= $imagen ?>" alt="<?= $nombre ?>" class="thumb"></td>
          <td>
            <a class="btn-edit" href="editar.php?id=<?= $id ?>">Editar</a>
            <a class="btn-delete delete-link" href="eliminar.php?id=<?= $id ?>" data-id="<?= $id ?>">Eliminar</a>
          </td>
        </tr>
      <?php
          }
      } ?>
    </tbody>
  </table>
</div>

</body>
</html>
