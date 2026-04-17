<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
include dirname(__DIR__) . "/conexion.php";
include dirname(__DIR__) . "/includes/envios.php";

function chatbot_respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function chatbot_normalize_products($result, int $limit = 3): array
{
    $items = [];
    while (($row = pg_fetch_assoc($result)) && count($items) < $limit) {
        $items[] = [
            'id' => (int) $row['id'],
            'nombre' => (string) $row['nombre'],
            'marca' => (string) ($row['marca'] ?? ''),
            'precio' => (float) ($row['precio'] ?? 0),
            'imagen' => (string) ($row['imagen'] ?? ''),
            'url' => 'producto.php?id=' . (int) $row['id'],
        ];
    }
    return $items;
}

function chatbot_get_message(array $payload): string
{
    $candidates = [
        $payload['message'] ?? null,
        $payload['text'] ?? null,
        $payload['query'] ?? null,
        $payload['payload']['text'] ?? null,
        $payload['conversation']['message'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }
    }

    return '';
}

function chatbot_product_reply(string $message, $conexion): ?array
{
    $normalized = function_exists('mb_strtolower')
        ? mb_strtolower($message, 'UTF-8')
        : strtolower($message);

    if (preg_match('/shippo\d{6}/i', $message, $match)) {
        $trackingNumber = strtoupper($match[0]);
        $envio = envios_find_by_tracking($conexion, $trackingNumber);
        if ($envio) {
            $envio = envios_simulate_progress($conexion, $envio);
            return [
                'reply' => "El envio {$trackingNumber} va en estado {$envio['estado']}.",
                'tracking' => [
                    'tracking_number' => $envio['tracking_number'],
                    'estado' => $envio['estado'],
                    'pedido_id' => $envio['pedido_id'],
                    'url' => 'tracking.php?tracking_number=' . urlencode((string) $envio['tracking_number']),
                ],
            ];
        }

        return ['reply' => "No encontre el tracking {$trackingNumber}."];
    }

    if (preg_match('/(\d{4,6})/', $message, $amountMatch)) {
        $price = (float) $amountMatch[1];
        $result = pg_query_params(
            $conexion,
            "SELECT id, nombre, marca, precio, imagen FROM productos WHERE precio <= $1 ORDER BY precio ASC LIMIT 3",
            [$price]
        );
        $items = $result ? chatbot_normalize_products($result) : [];

        if ($items) {
            return [
                'reply' => "Te recomiendo estas opciones por debajo de {$price} pesos.",
                'products' => $items,
            ];
        }
    }

    if (str_contains($normalized, 'barato') || str_contains($normalized, 'economico') || str_contains($normalized, 'económico')) {
        $result = pg_query($conexion, "SELECT id, nombre, marca, precio, imagen FROM productos ORDER BY precio ASC LIMIT 3");
        return [
            'reply' => 'Estas son las opciones mas accesibles que tengo ahorita.',
            'products' => $result ? chatbot_normalize_products($result) : [],
        ];
    }

    $brands = ['samsung', 'iphone', 'motorola', 'xiaomi', 'oppo'];
    foreach ($brands as $brand) {
        if (str_contains($normalized, $brand)) {
            if ($brand === 'iphone') {
                $displayBrand = 'iPhone';
            } elseif ($brand === 'oppo') {
                $displayBrand = 'OPPO';
            } else {
                $displayBrand = ucfirst($brand);
            }
            $result = pg_query_params(
                $conexion,
                "SELECT id, nombre, marca, precio, imagen FROM productos WHERE marca ILIKE $1 ORDER BY id DESC LIMIT 3",
                [$displayBrand]
            );
            return [
                'reply' => "Te muestro algunas opciones de {$displayBrand}.",
                'products' => $result ? chatbot_normalize_products($result) : [],
            ];
        }
    }

    if (str_contains($normalized, 'gaming') || str_contains($normalized, 'jugar')) {
        $result = pg_query(
            $conexion,
            "SELECT id, nombre, marca, precio, imagen
             FROM productos
             WHERE descripcion ILIKE '%gaming%'
                OR caracteristicas ILIKE '%gaming%'
                OR caracteristicas ILIKE '%snapdragon%'
                OR caracteristicas ILIKE '%ram%'
             ORDER BY precio DESC
             LIMIT 3"
        );

        $items = $result ? chatbot_normalize_products($result) : [];
        if (!$items) {
            $fallback = pg_query($conexion, "SELECT id, nombre, marca, precio, imagen FROM productos ORDER BY precio DESC LIMIT 3");
            $items = $fallback ? chatbot_normalize_products($fallback) : [];
        }

        return [
            'reply' => 'Para gaming te convienen equipos potentes. Estas son mis recomendaciones.',
            'products' => $items,
        ];
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chatbot_respond(['ok' => false, 'message' => 'Metodo no permitido.'], 405);
}

$payload = json_decode(file_get_contents('php://input') ?: '{}', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$message = chatbot_get_message($payload);
if ($message === '') {
    chatbot_respond(['ok' => false, 'message' => 'No se recibio ningun mensaje.'], 422);
}

$answer = chatbot_product_reply($message, $conexion);
if ($answer === null) {
    $answer = [
        'reply' => 'Puedo ayudarte con celulares economicos, marcas como Samsung o con el estado de un tracking SHIPPO123456.',
    ];
}

chatbot_respond([
    'ok' => true,
    'reply' => $answer['reply'],
    'products' => $answer['products'] ?? [],
    'tracking' => $answer['tracking'] ?? null,
    // Respuesta compatible tambien con un webhook sencillo.
    'messages' => [
        ['type' => 'text', 'text' => $answer['reply']]
    ]
]);
