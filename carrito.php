<?php
session_start();
include "conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $id = intval($_POST['id'] ?? 0);
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = 0;
        }
        $_SESSION['cart'][$id] += 1;
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'remove') {
        $id = intval($_POST['id'] ?? 0);
        if (isset($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$id]);
        }
        header('Location: carrito.php');
        exit;
    }
}

$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0.0;
$count = 0;

foreach ($cart as $id => $qty) {
    $res = pg_query_params($conexion, "SELECT * FROM productos WHERE id = $1", [$id]);
    if ($p = pg_fetch_assoc($res)) {
        $p['qty'] = $qty;
        $p['subtotal'] = $qty * (float) $p['precio'];
        $total += $p['subtotal'];
        $count += $qty;
        $items[] = $p;
    }
}

$checkoutError = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Carrito | TecnoMovil MX</title>
  <link rel="icon" type="image/png" href="IMG/favicon.png?v=1">
  <link rel="stylesheet" href="CSS/styles.css?v=10">
</head>
<body>

<header class="site-header compact-header">
  <div class="logo"><img src="img/tecno.png" alt="TecnoMovil MX"></div>

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
    <a href="carrito.php" class="icon-btn cart-link is-active" aria-label="Carrito">
      <svg viewBox="0 0 24 24" class="cart-icon" aria-hidden="true">
        <path d="M7 4H3v2h2.2l1.7 8.4A2 2 0 0 0 8.86 16H18v-2H8.86l-.3-1.5h9.57a2 2 0 0 0 1.95-1.55L21 6H7.42L7 4Zm2 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" fill="currentColor"/>
      </svg><span id="cartCount"><?= $count ?></span>
    </a>
  </div>
</header>

<div class="container page-shell cart-page">
  <div class="cart-main">
    <div class="section-heading">
      <p class="section-eyebrow">Resumen</p>
      <h2>Tu carrito</h2>
    </div>

    <?php if ($checkoutError !== '') { ?>
      <div class="alert alert-error"><?= htmlspecialchars($checkoutError) ?></div>
    <?php } ?>

    <?php if (empty($items)) { ?>
      <div class="empty-card">
        <h3>Tu carrito esta vacio</h3>
        <p>Agrega algunos equipos para continuar con la simulacion de pago.</p>
        <a href="index.php" class="btn primary">Explorar catalogo</a>
      </div>
    <?php } else { ?>
      <div class="cart-table-card">
        <table class="cart-table">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Precio</th>
              <th>Cantidad</th>
              <th>Subtotal</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it) {
              $img = htmlspecialchars($it['imagen'] ?: 'img/default.png');
            ?>
              <tr>
                <td>
                  <div class="cart-product-cell">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($it['nombre']) ?>">
                    <div>
                      <strong><?= htmlspecialchars($it['nombre']) ?></strong>
                      <p><?= htmlspecialchars($it['marca']) ?></p>
                    </div>
                  </div>
                </td>
                <td>$<?= number_format($it['precio'], 2) ?></td>
                <td><?= (int) $it['qty'] ?></td>
                <td>$<?= number_format($it['subtotal'], 2) ?></td>
                <td>
                  <form method="post">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
                    <button type="submit" class="btn danger">Eliminar</button>
                  </form>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } ?>
  </div>

  <aside class="cart-summary">
    <div class="summary-card">
      <h3>Pago</h3>

      <div class="summary-line">
        <span>Productos</span>
        <strong><?= $count ?></strong>
      </div>
      <div class="summary-line summary-total">
        <span>Total</span>
        <strong>$<?= number_format($total, 2) ?></strong>
      </div>

      <?php if (!empty($items)) { ?>
        <form action="stripe_checkout.php" method="post">
          <button type="submit" class="btn primary pay-btn">Pagar</button>
        </form>
      <?php } else { ?>
        <button type="button" class="btn primary pay-btn" disabled>Pagar</button>
      <?php } ?>
    </div>
  </aside>
</div>

<?php include "includes/chatbot_boot.php"; ?>
<script src="js/main.js?v=6"></script>
</body>
</html>

