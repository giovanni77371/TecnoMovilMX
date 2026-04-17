<?php
session_start();
include "conexion.php";
include "stripe_config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: carrito.php');
    exit;
}

if (!stripe_is_ready()) {
    $_SESSION['checkout_error'] = 'Configura tus claves de prueba de Stripe en stripe_config.php antes de usar el pago.';
    header('Location: carrito.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    $_SESSION['checkout_error'] = 'Tu carrito esta vacio.';
    header('Location: carrito.php');
    exit;
}

$payload = [
    'mode' => 'payment',
    'success_url' => stripe_base_url() . '/stripe_success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => stripe_base_url() . '/stripe_cancel.php',
    'payment_method_types[0]' => 'card',
];

$lineIndex = 0;
foreach ($cart as $id => $qty) {
    $res = pg_query_params($conexion, "SELECT nombre, precio FROM productos WHERE id = $1", [$id]);
    $product = pg_fetch_assoc($res);

    if (!$product) {
        continue;
    }

    $payload["line_items[$lineIndex][price_data][currency]"] = 'mxn';
    $payload["line_items[$lineIndex][price_data][product_data][name]"] = $product['nombre'];
    $payload["line_items[$lineIndex][price_data][unit_amount]"] = (int) round(((float) $product['precio']) * 100);
    $payload["line_items[$lineIndex][quantity]"] = (int) $qty;
    $lineIndex++;
}

if ($lineIndex === 0) {
    $_SESSION['checkout_error'] = 'No se encontraron productos validos para pagar.';
    header('Location: carrito.php');
    exit;
}

$response = stripe_api_request('POST', 'checkout/sessions', $payload);

if (!$response['ok']) {
    $_SESSION['checkout_error'] = 'No fue posible iniciar el pago: ' . $response['message'];
    header('Location: carrito.php');
    exit;
}

$checkoutUrl = $response['data']['url'] ?? '';
if ($checkoutUrl === '') {
    $_SESSION['checkout_error'] = 'Stripe no devolvio una URL de checkout.';
    header('Location: carrito.php');
    exit;
}

header('Location: ' . $checkoutUrl);
exit;
