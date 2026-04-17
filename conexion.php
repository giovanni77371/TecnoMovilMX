<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';

$host = getenv('PGHOST') ?: 'localhost';
$db   = getenv('PGDATABASE') ?: 'tecnomovil';
$user = getenv('PGUSER') ?: 'postgres';
$pass = getenv('PGPASSWORD') ?: '';
$port = getenv('PGPORT') ?: '5432';

$connectionString = sprintf(
    'host=%s port=%s dbname=%s user=%s password=%s',
    $host,
    $port,
    $db,
    $user,
    $pass
);

$conexion = pg_connect($connectionString);
if (!$conexion) {
    die('Error en conexion a la base de datos.');
}
?>
