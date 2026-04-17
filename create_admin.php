<?php
declare(strict_types=1);

include "conexion.php";

$usuario = 'admin';
$passwordPlain = getenv('ADMIN_DEFAULT_PASSWORD') ?: 'admin123';
$hash = password_hash($passwordPlain, PASSWORD_DEFAULT);

$res = pg_query_params(
    $conexion,
    "INSERT INTO admin (usuario, password) VALUES ($1, $2) ON CONFLICT (usuario) DO NOTHING",
    [$usuario, $hash]
);

if ($res) {
    echo "Admin creado (o ya existe). Usuario: $usuario";
} else {
    echo "Error al crear admin.";
}
?>
