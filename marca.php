<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

include "conexion.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$marca = trim((string) ($_GET['marca'] ?? ''));
$count = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $count += (int) $qty;
    }
}

$productos = [];
if ($conexion && $marca !== '') {
    $res = pg_query_params($conexion, "SELECT * FROM productos WHERE marca = \$1 ORDER BY id DESC", [$marca]);
    if ($res) {
        while ($row = pg_fetch_assoc($res)) {
            $productos[] = $row;
        }
    } else {
        error_log('Error marca: ' . pg_last_error($conexion));
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($marca ?: 'Marca') ?> | TecnoMovil MX</title>
  <link rel="icon" type="image/png" href="IMG/favicon.png?v=1">
  <link rel="stylesheet" href="CSS/styles.css?v=10">
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
  </div>
</header>

<div class="container page-shell">
  <section class="ofertas">
    <div class="section-heading">
      <p class="section-eyebrow">Marca</p>
      <h2><?= htmlspecialchars($marca) ?></h2>
    </div>
    <div class="grid">
      <?php if (empty($productos)) { ?>
        <p class="empty-state">No hay productos para esta marca.</p>
      <?php } else {
        foreach ($productos as $p) {
          $rawImage  = (string) ($p['imagen'] ?? '');
          $imagePath = $rawImage !== '' ? preg_replace('/^img\//i', 'IMG/', $rawImage) : 'IMG/tecno.png';
          $img       = htmlspecialchars($imagePath);
          $id        = (int) $p['id'];
      ?>
        <div class="card">
          <div class="img-box">
            <a href="producto.php?id=<?= $id ?>"><img src="<?= $img ?>" alt="<?= htmlspecialchars($p['nombre']) ?>"></a>
          </div>
          <div class="card-content">
            <h3><?= htmlspecialchars($p['nombre']) ?></h3>
            <p class="precio">$<?= number_format((float)$p['precio'], 2) ?></p>
            <div class="card-actions">
              <button class="btn primary add-to-cart" data-id="<?= $id ?>" type="button">Agregar al carrito</button>
              <a href="producto.php?id=<?= $id ?>" class="btn ghost ver">Ver</a>
            </div>
          </div>
        </div>
      <?php } } ?>
    </div>
  </section>
</div>

<footer>
  <img src="IMG/TecnoMovil.png" alt="TecnoMovil MX">
  <p>&copy; 2025 TecnoMovil MX - Todos los derechos reservados.</p>
</footer>

<div id="loginModal" class="modal" style="display:none;">
  <div class="modal-content login-box">
    <button class="close" id="closeLogin" aria-label="Cerrar">x</button>
    <h2 class="login-title">Acceso Administrador</h2>
    <div id="loginError" class="form-error" style="display:none;"></div>
    <form id="loginForm">
      <input name="usuario" placeholder="Usuario" required>
      <input name="password" type="password" placeholder="Contraseña" required>
      <button type="submit" class="btn primary" style="width:100%; margin-top:12px;">Entrar</button>
    </form>
  </div>
</div>

<?php include "includes/chatbot_boot.php"; ?>
<script src="js/main.js?v=6"></script>
</body>
</html>