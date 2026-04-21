<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/envios.php';
require_once __DIR__ . '/includes/usuarios.php';

usuarios_start_session();
usuarios_ensure_table($conexion);
usuarios_require_login();

$usuarioActual = usuarios_current();

$count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $count += (int) $qty;
    }
}

$trackingNumber = strtoupper(trim($_GET['tracking_number'] ?? ''));
$envio = null;
$error = '';

if ($trackingNumber !== '') {
    $envio = envios_find_by_tracking($conexion, $trackingNumber);
    if ($envio) {
        $envio = envios_simulate_progress($conexion, $envio);
    } else {
        $error = 'No encontramos un envio con ese numero de tracking.';
    }
}

$progressSteps = ['pendiente', 'enviado', 'en camino', 'entregado'];
$progressIndex = $envio ? envios_progress_index($envio['estado']) : -1;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Tracking | TecnoMovil MX</title>
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
    <a href="login.php" class="icon-btn">Login</a>
    <?php if ($usuarioActual) { ?>
      <span class="icon-btn user-badge"><?= htmlspecialchars($usuarioActual['username']) ?></span>
      <a href="logout.php" class="icon-btn">Salir</a>
    <?php } ?>
  </div>
</header>

<div class="container page-shell">
  <section class="status-card tracking-card">
    <p class="section-eyebrow">Shippo Sandbox</p>
    <h1>Rastrea tu envio</h1>
    <p>Ingresa tu codigo tipo <strong>SHIPPO123456</strong> para consultar el estado actual del pedido.</p>

    <form class="tracking-form" method="get" action="tracking.php">
      <input type="search" name="tracking_number" value="<?= htmlspecialchars($trackingNumber) ?>" placeholder="SHIPPO123456" required>
      <button type="submit" class="btn primary">Buscar envio</button>
    </form>

    <?php if ($error !== '') { ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php } ?>

    <?php if ($envio) { ?>
      <div class="tracking-meta">
        <div class="tracking-meta-item">
          <span>Tracking</span>
          <strong class="tracking-value"><?= htmlspecialchars($envio['tracking_number']) ?></strong>
        </div>
        <div class="tracking-meta-item">
          <span>Pedido</span>
          <strong class="tracking-value"><?= htmlspecialchars($envio['pedido_id']) ?></strong>
        </div>
        <div class="tracking-meta-item">
          <span>Estado actual</span>
          <strong class="tracking-value"><?= htmlspecialchars(ucwords($envio['estado'])) ?></strong>
        </div>
      </div>

      <div class="tracking-progress">
        <?php foreach ($progressSteps as $index => $step) { ?>
          <div class="tracking-step <?= $index <= $progressIndex ? 'is-done' : '' ?>">
            <div class="tracking-dot"></div>
            <span><?= htmlspecialchars(ucwords($step)) ?></span>
          </div>
        <?php } ?>
      </div>

      <p class="status-meta">Ultima actualizacion: <?= htmlspecialchars((string) $envio['fecha_actualizacion']) ?></p>
    <?php } ?>
  </section>
</div>

<?php include "includes/admin_login_modal.php"; ?>
<?php include "includes/chatbot_boot.php"; ?>
<script src="js/main.js?v=8"></script>
</body>
</html>

