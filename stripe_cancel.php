<?php
session_start();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Pago cancelado | TecnoMovil MX</title>
  <link rel="icon" type="image/png" href="IMG/favicon.png?v=1">
  <link rel="stylesheet" href="CSS/styles.css?v=10">
</head>
<body>
  <div class="container">
    <div class="status-card">
      <div class="status-badge error">!</div>
      <p class="section-eyebrow">Stripe Checkout</p>
      <h1>Pago cancelado</h1>
      <p>La simulacion de pago se cancelo antes de completarse. Tu carrito sigue intacto.</p>
      <div class="product-actions" style="justify-content:center; margin-top:20px;">
        <a href="carrito.php" class="btn primary">Volver al carrito</a>
        <a href="index.php" class="btn ghost">Seguir comprando</a>
      </div>
    </div>
  </div>
  <?php include "includes/chatbot_boot.php"; ?>
</body>
</html>


