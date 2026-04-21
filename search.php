<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/productos_catalogo.php';
require_once __DIR__ . '/includes/usuarios.php';

usuarios_start_session();
usuarios_ensure_table($conexion);
usuarios_require_login();
bootstrapProductosCatalogo($conexion);

$query = trim($_GET['q'] ?? '');
$usuarioActual = usuarios_current();
$count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $count += (int) $qty;
    }
}

$results = false;
if ($query !== '') {
    $like = '%' . $query . '%';
    $sql = "
        SELECT *
        FROM productos
        WHERE nombre ILIKE $1
           OR marca ILIKE $1
           OR descripcion ILIKE $1
           OR caracteristicas ILIKE $1
        ORDER BY id DESC
    ";
    $results = pg_query_params($conexion, $sql, [$like]);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Busqueda | TecnoMovil MX</title>
  <link rel="icon" type="image/png" href="IMG/favicon.png?v=1">
  <link rel="stylesheet" href="CSS/styles.css?v=13">
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

    <form class="header-search is-open" action="search.php" method="get" autocomplete="off">
      <button type="button" class="search-toggle" aria-label="Abrir busqueda">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M10.5 4a6.5 6.5 0 1 0 4.03 11.6l4.44 4.44 1.41-1.41-4.44-4.44A6.5 6.5 0 0 0 10.5 4Zm0 2a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z" fill="currentColor"/>
        </svg>
      </button>
      <div class="search-popover">
        <input type="search" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Buscar celular..." aria-label="Buscar productos">
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
    <a href="login.php" class="icon-btn">Login</a>
    <?php if ($usuarioActual) { ?>
      <span class="icon-btn user-badge"><?= htmlspecialchars($usuarioActual['username']) ?></span>
      <a href="logout.php" class="icon-btn">Salir</a>
    <?php } ?>
  </div>
</header>

<div class="container page-shell">
  <section class="ofertas">
    <div class="section-heading">
      <p class="section-eyebrow">Busqueda</p>
      <h2>Resultados para "<?= htmlspecialchars($query !== '' ? $query : 'tu consulta') ?>"</h2>
    </div>

    <?php if ($query === '') { ?>
      <div class="empty-card">
        <h3>Escribe algo para buscar</h3>
        <p>Puedes buscar por nombre, marca, descripcion o caracteristicas.</p>
      </div>
    <?php } elseif ($results && pg_num_rows($results) > 0) { ?>
      <div class="grid">
        <?php while ($p = pg_fetch_assoc($results)) {
          $img = htmlspecialchars(normalizarImagenProducto($p['imagen'] ?? null));
          $nombre = htmlspecialchars($p['nombre']);
          $precio = number_format($p['precio'], 2);
          $id = (int) $p['id'];
        ?>
          <div class="card">
            <div class="img-box">
              <a href="producto.php?id=<?= $id ?>"><img src="<?= $img ?>" alt="<?= $nombre ?>"></a>
            </div>
            <div class="card-content">
              <h3><?= $nombre ?></h3>
              <p class="precio">$<?= $precio ?></p>
              <div class="card-actions">
                <button class="btn primary add-to-cart" data-id="<?= $id ?>" type="button">Agregar al carrito</button>
                <a href="producto.php?id=<?= $id ?>" class="btn ghost">Ver</a>
              </div>
            </div>
          </div>
        <?php } ?>
      </div>
    <?php } else { ?>
      <div class="empty-card">
        <h3>No encontramos coincidencias</h3>
        <p>Prueba con otro modelo, marca o palabra clave.</p>
      </div>
    <?php } ?>
  </section>
</div>

<?php include "includes/admin_login_modal.php"; ?>
<?php include "includes/chatbot_boot.php"; ?>
<script src="js/main.js?v=8"></script>
</body>
</html>

