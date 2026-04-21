<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/productos_catalogo.php';
require_once __DIR__ . '/includes/usuarios.php';
require_once __DIR__ . '/includes/compras.php';
require_once __DIR__ . '/includes/envios.php';

usuarios_start_session();
usuarios_ensure_table($conexion);
usuarios_require_login();

$usuarioActual = usuarios_current();
$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0.0;
$count = 0;

foreach ($cart as $id => $qty) {
    $res = @pg_query_params($conexion, 'SELECT * FROM productos WHERE id = $1', [(int) $id]);
    if ($res && ($producto = pg_fetch_assoc($res))) {
        $cantidad = (int) $qty;
        $producto['qty'] = $cantidad;
        $producto['subtotal'] = $cantidad * (float) ($producto['precio'] ?? 0);
        $total += (float) $producto['subtotal'];
        $count += $cantidad;
        $items[] = $producto;
    }
}

$error = '';
$success = $_SESSION['pago_success'] ?? null;
unset($_SESSION['pago_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($items) && $usuarioActual) {
    $cardNumber = preg_replace('/\D+/', '', (string) ($_POST['card_number'] ?? ''));
    $cardName = trim((string) ($_POST['card_name'] ?? ''));
    $expiry = trim((string) ($_POST['expiry'] ?? ''));
    $cvc = trim((string) ($_POST['cvc'] ?? ''));

    if ($cardNumber === '' || $cardName === '' || $expiry === '' || $cvc === '') {
        $error = 'Completa los datos de la tarjeta para continuar.';
    } else {
        $brand = str_starts_with($cardNumber, '4') ? 'Visa' : 'Mastercard';
        $compra = compras_registrar_carrito($conexion, (int) $usuarioActual['id'], $cart);

        if ($compra['ok'] ?? false) {
            $envio = null;
            try {
                $envio = envios_create_for_pedido($conexion, (string) $compra['pedido_ref']);
            } catch (Throwable $exception) {
                error_log('No se pudo crear el envio despues del pago: ' . $exception->getMessage());
            }

            $_SESSION['cart'] = [];
            $_SESSION['pago_success'] = [
                'brand' => $brand,
                'pedido_ref' => $compra['pedido_ref'],
                'total' => $compra['total'],
                'productos' => $compra['productos'],
                'tracking' => $envio['tracking_number'] ?? '',
            ];
            header('Location: pago.php?ok=1');
            exit;
        }

        $error = (string) ($compra['msg'] ?? 'No se pudo registrar la compra.');
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Pago | TecnoMovil MX</title>
  <link rel="icon" type="image/png" href="IMG/favicon.png?v=1">
  <link rel="stylesheet" href="CSS/styles.css?v=14">
</head>
<body>
<header class="site-header compact-header">
  <div class="logo"><img src="IMG/tecno.png" alt="TecnoMovil MX"></div>

  <nav class="main-nav">
    <a href="index.php">Inicio</a>
    <a href="marca.php?marca=iPhone">iPhone</a>
    <a href="marca.php?marca=Samsung">Samsung</a>
    <a href="marca.php?marca=Motorola">Motorola</a>
    <a href="marca.php?marca=Xiaomi">Xiaomi</a>
    <a href="marca.php?marca=OPPO">OPPO</a>
    <a href="tracking.php">Tracking</a>
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

<div class="container page-shell cart-page">
  <div class="cart-main">
    <div class="section-heading">
      <p class="section-eyebrow">Checkout</p>
      <h2>Simulador de pago</h2>
    </div>

    <?php if ($success) { ?>
      <div class="summary-card purchase-success">
        <h3>Pago exitoso</h3>
        <p>La compra se proceso correctamente como <?= htmlspecialchars((string) $success['brand']) ?>.</p>
        <p><strong>Referencia:</strong> <?= htmlspecialchars((string) $success['pedido_ref']) ?></p>
        <p><strong>Productos:</strong> <?= htmlspecialchars(implode(', ', (array) ($success['productos'] ?? []))) ?></p>
        <p><strong>Total:</strong> $<?= number_format((float) ($success['total'] ?? 0), 2) ?></p>
        <?php if (!empty($success['tracking'])) { ?>
          <p><strong>Tracking:</strong> <a href="tracking.php?tracking_number=<?= urlencode((string) $success['tracking']) ?>"><?= htmlspecialchars((string) $success['tracking']) ?></a></p>
        <?php } ?>
        <div class="product-actions">
          <a href="index.php" class="btn primary">Volver al catalogo</a>
          <?php if (!empty($success['tracking'])) { ?>
            <a href="tracking.php?tracking_number=<?= urlencode((string) $success['tracking']) ?>" class="btn ghost">Ver seguimiento</a>
          <?php } ?>
        </div>
      </div>
    <?php } elseif (empty($items)) { ?>
      <div class="empty-card">
        <h3>No hay productos para pagar</h3>
        <p>Agrega celulares al carrito antes de continuar.</p>
        <a href="index.php" class="btn primary">Explorar catalogo</a>
      </div>
    <?php } else { ?>
      <?php if ($error !== '') { ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php } ?>

      <div class="payment-layout">
        <form method="post" class="summary-card payment-form">
          <h3>Metodo de pago</h3>
          <p>El simulador acepta cualquier tarjeta y procesa el pago como Visa o Mastercard.</p>

          <label for="card_number">Numero de tarjeta</label>
          <input id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required>

          <div class="payment-grid">
            <div>
              <label for="expiry">MM / AA</label>
              <input id="expiry" name="expiry" placeholder="12 / 29" required>
            </div>
            <div>
              <label for="cvc">CVC</label>
              <input id="cvc" name="cvc" placeholder="123" required>
            </div>
          </div>

          <label for="card_name">Nombre del titular</label>
          <input id="card_name" name="card_name" placeholder="Nombre completo" required>

          <button type="submit" class="btn primary pay-btn">Pagar ahora</button>
        </form>

        <aside class="summary-card">
          <h3>Resumen de compra</h3>
          <?php foreach ($items as $item) { ?>
            <div class="summary-line">
              <span><?= htmlspecialchars((string) $item['nombre']) ?> x<?= (int) $item['qty'] ?></span>
              <strong>$<?= number_format((float) $item['subtotal'], 2) ?></strong>
            </div>
          <?php } ?>
          <div class="summary-line summary-total">
            <span>Total</span>
            <strong>$<?= number_format($total, 2) ?></strong>
          </div>
        </aside>
      </div>
    <?php } ?>
  </div>
</div>

<?php include "includes/admin_login_modal.php"; ?>
<?php include "includes/chatbot_boot.php"; ?>
<script src="js/main.js?v=9"></script>
</body>
</html>
