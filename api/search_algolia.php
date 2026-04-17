<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

include dirname(__DIR__) . "/conexion.php";

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function normalizeDbHits($result): array
{
    $hits = [];

    while ($row = pg_fetch_assoc($result)) {
        $id = (int) ($row['id'] ?? 0);
        $hits[] = [
            'id' => $id,
            'nombre' => (string) ($row['nombre'] ?? ''),
            'precio' => isset($row['precio']) ? (float) $row['precio'] : 0,
            'imagen' => (string) ($row['imagen'] ?? 'img/default.png'),
            'marca' => (string) ($row['marca'] ?? ''),
            'url' => $id > 0 ? 'producto.php?id=' . $id : '#',
        ];
    }

    return $hits;
}

function searchLocalProducts($conexion, string $query): array
{
    $like = '%' . $query . '%';
    $sql = "
        SELECT id, nombre, precio, imagen, marca
        FROM productos
        WHERE nombre ILIKE $1
           OR marca ILIKE $1
           OR descripcion ILIKE $1
           OR caracteristicas ILIKE $1
        ORDER BY id DESC
        LIMIT 8
    ";

    $result = pg_query_params($conexion, $sql, [$like]);
    if ($result === false) {
        respond([
            'ok' => false,
            'message' => 'No se pudo realizar la busqueda local.'
        ], 500);
    }

    return normalizeDbHits($result);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond([
        'ok' => false,
        'message' => 'Metodo no permitido.'
    ], 405);
}

$query = trim((string) ($_GET['q'] ?? ''));

if ($query === '') {
    respond([
        'ok' => true,
        'query' => '',
        'source' => 'local',
        'hits' => []
    ]);
}

$appId = getenv('ALGOLIA_APP_ID') ?: '';
$searchKey = getenv('ALGOLIA_SEARCH_API_KEY') ?: '';
$indexName = getenv('ALGOLIA_INDEX_NAME') ?: '';
$hasAlgoliaConfig = $appId !== '' && $searchKey !== '' && $indexName !== '';

if (!$hasAlgoliaConfig) {
    respond([
        'ok' => true,
        'query' => $query,
        'source' => 'local',
        'hits' => searchLocalProducts($conexion, $query)
    ]);
}

$endpoint = sprintf(
    'https://%s-dsn.algolia.net/1/indexes/%s/query',
    rawurlencode($appId),
    rawurlencode($indexName)
);

$payload = json_encode([
    'query' => $query,
    'hitsPerPage' => 8,
    'attributesToRetrieve' => ['objectID', 'id', 'nombre', 'precio', 'imagen', 'marca'],
], JSON_UNESCAPED_UNICODE);

if ($payload === false) {
    respond([
        'ok' => true,
        'query' => $query,
        'source' => 'local',
        'hits' => searchLocalProducts($conexion, $query)
    ]);
}

$headers = [
    'Content-Type: application/json',
    'X-Algolia-API-Key: ' . $searchKey,
    'X-Algolia-Application-Id: ' . $appId,
];

$responseBody = false;
$httpCode = 0;

if (function_exists('curl_init')) {
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
    ]);
    $responseBody = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
} else {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $payload,
            'timeout' => 10,
            'ignore_errors' => true,
        ]
    ]);
    $responseBody = @file_get_contents($endpoint, false, $context);
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $httpCode = (int) $matches[1];
    }
}

if ($responseBody === false || $httpCode >= 400 || $httpCode === 0) {
    respond([
        'ok' => true,
        'query' => $query,
        'source' => 'local',
        'hits' => searchLocalProducts($conexion, $query)
    ]);
}

$decoded = json_decode($responseBody, true);
$hits = is_array($decoded['hits'] ?? null) ? $decoded['hits'] : [];

$normalizedHits = array_map(static function (array $hit): array {
    $id = isset($hit['id']) ? (int) $hit['id'] : (isset($hit['objectID']) ? (int) $hit['objectID'] : 0);

    return [
        'id' => $id,
        'nombre' => (string) ($hit['nombre'] ?? ''),
        'precio' => isset($hit['precio']) ? (float) $hit['precio'] : 0,
        'imagen' => (string) ($hit['imagen'] ?? 'img/default.png'),
        'marca' => (string) ($hit['marca'] ?? ''),
        'url' => $id > 0 ? 'producto.php?id=' . $id : '#',
    ];
}, $hits);

respond([
    'ok' => true,
    'query' => $query,
    'source' => 'algolia',
    'hits' => $normalizedHits
]);
