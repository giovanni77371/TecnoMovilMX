<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

include "conexion.php";

if (!$conexion) {
    echo "❌ Sin conexión.";
    exit;
}

$sqls = [
    "productos" => "
        CREATE TABLE IF NOT EXISTS productos (
            id SERIAL PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            marca VARCHAR(100),
            precio NUMERIC(10,2),
            descripcion TEXT,
            caracteristicas TEXT,
            imagen VARCHAR(255) DEFAULT 'IMG/tecno.png'
        )
    ",
    "admin" => "
        CREATE TABLE IF NOT EXISTS admin (
            id SERIAL PRIMARY KEY,
            usuario VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL
        )
    ",
    "envios" => "
        CREATE TABLE IF NOT EXISTS envios (
            id SERIAL PRIMARY KEY,
            pedido_id VARCHAR(120) NOT NULL UNIQUE,
            tracking_number VARCHAR(40) NOT NULL UNIQUE,
            estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'
                CHECK (estado IN ('pendiente', 'enviado', 'en camino', 'entregado')),
            fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ",
];

foreach ($sqls as $tabla => $sql) {
    $res = pg_query($conexion, $sql);
    echo $res
        ? "✅ Tabla <strong>$tabla</strong> OK<br>"
        : "❌ Error en <strong>$tabla</strong>: " . pg_last_error($conexion) . "<br>";
}

echo "<br>✅ Listo. <strong>Borra este archivo después.</strong>";