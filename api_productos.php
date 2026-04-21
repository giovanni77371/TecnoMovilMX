<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/productos_catalogo.php';

$marca = trim((string) ($_GET['marca'] ?? ''));
$productos = $marca !== '' ? obtenerProductosPorMarca($conexion, $marca) : obtenerProductosCatalogo($conexion);

$payload = array_map(static function (array $producto): array {
    return [
        'id' => (int) ($producto['id'] ?? 0),
        'nombre' => (string) ($producto['nombre'] ?? ''),
        'marca' => (string) ($producto['marca'] ?? ''),
        'precio' => (float) ($producto['precio'] ?? 0),
        'descripcion' => (string) ($producto['descripcion'] ?? ''),
        'imagen' => normalizarImagenProducto($producto['imagen'] ?? null),
    ];
}, $productos);

echo json_encode([
    'ok' => true,
    'count' => count($payload),
    'productos' => $payload,
], JSON_UNESCAPED_UNICODE);
