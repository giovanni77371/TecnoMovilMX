<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

include "conexion.php";

if (!$conexion) {
    echo "❌ Sin conexión a la base de datos.";
    exit;
}

// Crear tabla admin si no existe
pg_query($conexion, "
    CREATE TABLE IF NOT EXISTS admin (
        id SERIAL PRIMARY KEY,
        usuario VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    )
");

$usuario       = 'admin';
$passwordPlain = getenv('ADMIN_DEFAULT_PASSWORD') ?: 'admin123';
$hash          = password_hash($passwordPlain, PASSWORD_DEFAULT);

$res = pg_query_params(
    $conexion,
    "INSERT INTO admin (usuario, password) VALUES (\$1, \$2)
     ON CONFLICT (usuario) DO UPDATE SET password = EXCLUDED.password",
    [$usuario, $hash]
);

if ($res) {
    echo "✅ Admin listo. Usuario: <strong>$usuario</strong> | Contraseña: <strong>$passwordPlain</strong>";
} else {
    echo "❌ Error: " . pg_last_error($conexion);
}