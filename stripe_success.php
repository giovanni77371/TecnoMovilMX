<?php
session_start();
include "stripe_config.php";
include "conexion.php";
include "includes/envios.php";

$sessionId = trim($_GET['session_id'] ?? '');
$paid = false;
$message = 'No se pudo validar el pago.';
$trackingNumber = '';

if ($sessionId !== '' && stripe_is_ready()) {
    $response = stripe_api_request('GET', 'checkout/sessions/' . rawurlencode($sessionId));
    if ($response['ok']) {
        $sessionData = $response['data'];
        if (($sessionData['payment_status'] ?? '') === 'paid') {
            $paid = true;
            $message = 'Tu pago de prueba fue confirmado correctamente.';
            $envio = envios_create_for_pedido($conexion, $sessionId);
            $trackingNumber = (string) ($envio['tracking_number'] ?? '');
            $_SESSION['cart'] = [];
        } else {
            $message = 'La sesion existe, pero el pago todavia no aparece como completado.';
        }
    } else {
        $message = $response['message'];
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Pago exitoso | TecnoMovil MX</title>
  <link rel="icon" type="image/png" href="IMG/favicon.png?v=1">
  <link rel="stylesheet" href="CSS/styles.css?v=10">
</head>
<body>
  <div class="container">
    <div class="status-card">
      <div class="status-badge <?= $paid ? 'success' : 'error' ?>"><?= $paid ? 'OK' : '!' ?></div>
      <p class="section-eyebrow">Stripe Checkout</p>
      <h1><?= $paid ? 'Pago exitoso' : 'Pago no confirmado' ?></h1>
      <p><?= htmlspecialchars($message) ?></p>
      <?php if ($trackingNumber !== '') { ?>
        <p class="status-meta">Tracking asignado: <strong><?= htmlspecialchars($trackingNumber) ?></strong></p>
      <?php } ?>
      <?php if ($sessionId !== '') { ?>
        <p class="status-meta">Sesion: <?= htmlspecialchars($sessionId) ?></p>
      <?php } ?>
      <div class="product-actions" style="justify-content:center; margin-top:20px;">
        <a href="index.php" class="btn primary">Volver al inicio</a>
        <a href="carrito.php" class="btn ghost">Ir al carrito</a>
        <?php if ($trackingNumber !== '') { ?>
          <a href="tracking.php?tracking_number=<?= urlencode($trackingNumber) ?>" class="btn ghost">Ver tracking</a>
        <?php } ?>
      </div>
    </div>
  </div>
  <?php include "includes/chatbot_boot.php"; ?>
</body>
</html>


