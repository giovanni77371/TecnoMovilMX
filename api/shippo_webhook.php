<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
include dirname(__DIR__) . "/conexion.php";
include dirname(__DIR__) . "/includes/envios.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Metodo no permitido.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '{}', true);
$trackingNumber = strtoupper(trim((string) ($payload['tracking_number'] ?? '')));
$estado = trim((string) ($payload['estado'] ?? ''));
$permitidos = ['pendiente', 'enviado', 'en camino', 'entregado'];

if ($trackingNumber === '' || !in_array($estado, $permitidos, true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'tracking_number o estado invalido.']);
    exit;
}

$envio = envios_find_by_tracking($conexion, $trackingNumber);
if (!$envio) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Envio no encontrado.']);
    exit;
}

envios_update_status($conexion, (int) $envio['id'], $estado);
$updated = envios_find_by_tracking($conexion, $trackingNumber);

echo json_encode(['ok' => true, 'envio' => $updated], JSON_UNESCAPED_UNICODE);
