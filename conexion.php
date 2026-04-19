<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';

$conexion = null;

/**
 * Obtiene la primera variable de entorno disponible.
 */
function env_first(array $keys, string $default = ''): string
{
    foreach ($keys as $key) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }

    return $default;
}

$databaseUrl = getenv('DATABASE_URL');
$connectionParts = [
    'host' => '',
    'port' => '',
    'dbname' => '',
    'user' => '',
    'password' => '',
    'sslmode' => '',
];

if ($databaseUrl !== false && $databaseUrl !== '') {
    $parsed = parse_url($databaseUrl);

    if ($parsed !== false) {
        $connectionParts['host'] = $parsed['host'] ?? '';
        $connectionParts['port'] = isset($parsed['port']) ? (string) $parsed['port'] : '5432';
        $connectionParts['dbname'] = isset($parsed['path']) ? ltrim($parsed['path'], '/') : '';
        $connectionParts['user'] = $parsed['user'] ?? '';
        $connectionParts['password'] = $parsed['pass'] ?? '';

        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
            $connectionParts['sslmode'] = (string) ($queryParams['sslmode'] ?? '');
        }
    }
}

if ($connectionParts['host'] === '') {
    $connectionParts['host'] = env_first(['DB_HOST', 'PGHOST'], '');
    $connectionParts['port'] = env_first(['DB_PORT', 'PGPORT'], '5432');
    $connectionParts['dbname'] = env_first(['DB_NAME', 'PGDATABASE'], '');
    $connectionParts['user'] = env_first(['DB_USER', 'PGUSER'], '');
    $connectionParts['password'] = env_first(['DB_PASSWORD', 'PGPASSWORD'], '');
    $connectionParts['sslmode'] = env_first(['DB_SSLMODE', 'PGSSLMODE'], '');
}

$connectionParams = [];

foreach (['host', 'port', 'dbname', 'user', 'password', 'sslmode'] as $key) {
    if ($connectionParts[$key] !== '') {
        $connectionParams[] = $key . '=' . $connectionParts[$key];
    }
}

if (!empty($connectionParams)) {
    $conexion = @pg_connect(implode(' ', $connectionParams));
}

if (!$conexion) {
    error_log('Error de conexion PostgreSQL en Render.');
    $conexion = null;
}
?>
