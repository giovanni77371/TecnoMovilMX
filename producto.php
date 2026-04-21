<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/productos_catalogo.php';
require_once __DIR__ . '/includes/usuarios.php';

bootstrapProductosCatalogo($conexion);
usuarios_start_session();
usuarios_ensure_table($conexion);
usuarios_require_login();

$id = intval($_GET['id'] ?? 0);
$res = @pg_query_params($conexion, 'SELECT * FROM productos WHERE id = $1', [$id]);
if (!$res) {
    error_log('Error detalle producto: ' . pg_last_error($conexion));
    echo 'Producto no encontrado';
    exit;
}

$p = pg_fetch_assoc($res);
if (!$p) {
    echo "Producto no encontrado";
    exit;
}

$count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $count += (int) $qty;
    }
}

$usuarioActual = usuarios_current();
$nombre = htmlspecialchars((string) ($p['nombre'] ?? 'Producto'));
$marca = htmlspecialchars((string) ($p['marca'] ?? ''));
$precio = is_numeric((string) ($p['precio'] ?? null)) ? number_format((float) $p['precio'], 2) : '0.00';
$descripcion = trim((string) ($p['descripcion'] ?? ''));
$caracteristicas = trim((string) ($p['caracteristicas'] ?? ''));
$imagePath = normalizarImagenProducto($p['imagen'] ?? null);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= $nombre ?></title>
  <link rel="icon" type="image/png" href="IMG/favicon.png?v=1">
  <link rel="stylesheet" href="CSS/styles.css?v=14">
</head>
<body>

<header class="site-header">
  <div class="logo"><img src="IMG/tecno.png" alt="TecnoMovil MX"></div>

  <nav class="main-nav">
    <a href="index.php">Inicio</a>
    <a href="marca.php?marca=iPhone">iPhone</a>
    <a href="marca.php?marca=Samsung">Samsung</a>
    <a href="marca.php?marca=Motorola">Motorola</a>
    <a href="marca.php?marca=Xiaomi">Xiaomi</a>
    <a href="marca.php?marca=OPPO">OPPO</a>
    <a href="tracking.php">Tracking</a>

    <form class="header-search" action="search.php" method="get" autocomplete="off">
      <button type="button" class="search-toggle" aria-label="Abrir busqueda">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M10.5 4a6.5 6.5 0 1 0 4.03 11.6l4.44 4.44 1.41-1.41-4.44-4.44A6.5 6.5 0 0 0 10.5 4Zm0 2a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z" fill="currentColor"/>
        </svg>
      </button>
      <div class="search-popover">
        <input type="search" name="q" placeholder="Buscar celular..." aria-label="Buscar productos">
      </div>
    </form>
  </nav>

  <div class="icons">
    <a href="carrito.php" class="icon-btn cart-link" aria-label="Carrito">
      <svg viewBox="0 0 24 24" class="cart-icon" aria-hidden="true">
        <path d="M7 4H3v2h2.2l1.7 8.4A2 2 0 0 0 8.86 16H18v-2H8.86l-.3-1.5h9.57a2 2 0 0 0 1.95-1.55L21 6H7.42L7 4Zm2 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" fill="currentColor"/>
      </svg><span id="cartCount"><?= $count ?></span>
    </a>
    <a href="#" class="icon-btn" id="openLogin" aria-label="Admin">Admin</a>
    <?php include "includes/user_menu.php"; ?>
  </div>
</header>

<div class="container page-shell product-layout">
  <div class="product-media">
    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= $nombre ?>">
  </div>

  <div class="product-info">
    <p class="section-eyebrow"><?= $marca ?></p>
    <h1><?= $nombre ?></h1>
    <h2 class="product-price">$<?= $precio ?></h2>
    <p class="product-copy"><?= nl2br(htmlspecialchars($descripcion !== '' ? $descripcion : 'Descripcion no disponible por el momento.')) ?></p>

    <div class="product-specs">
      <h3>Caracteristicas</h3>
      <p class="product-copy"><?= nl2br(htmlspecialchars($caracteristicas !== '' ? $caracteristicas : 'Caracteristicas no disponibles por el momento.')) ?></p>
    </div>

    <div class="product-actions">
      <button class="btn primary add-to-cart" data-id="<?= (int) $p['id'] ?>" type="button">Agregar al carrito</button>
      <a href="carrito.php" class="btn ghost">Ver carrito</a>
    </div>
  </div>
</div>

<?php include "includes/admin_login_modal.php"; ?>
<?php include "includes/chatbot_boot.php"; ?>
<script src="js/main.js?v=9"></script>
</body>
</html>
