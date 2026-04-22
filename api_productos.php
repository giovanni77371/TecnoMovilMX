<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
http_response_code(200);

require_once __DIR__ . '/conexion.php';

if (!$conexion) {
    error_log('api_productos.php: no se pudo establecer la conexion a PostgreSQL.');
    echo json_encode([
        'error' => 'No se pudo conectar a la base de datos.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$query = 'SELECT * FROM productos ORDER BY id DESC';
$result = @pg_query($conexion, $query);

if (!$result) {
    $error = pg_last_error($conexion) ?: 'Error desconocido al consultar productos.';
    error_log('api_productos.php: ' . $error);

    echo json_encode([
        'error' => $error,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$productos = [];

while ($row = pg_fetch_assoc($result)) {
    $productos[] = $row;
}

echo json_encode($productos, JSON_UNESCAPED_UNICODE);
