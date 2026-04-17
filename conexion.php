<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';

$host = getenv('PGHOST') ?: 'dpg-d7gtqvd8nd3s73e98j5g-a';
$db   = getenv('PGDATABASE') ?: 'tecnomovil_db';
$user = getenv('PGUSER') ?: 'tecnomovil_db_user';
$pass = getenv('PGPASSWORD') ?: 'pAtOhb5iWt0vJj42ktx6fhop4bsBGHAj';
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
