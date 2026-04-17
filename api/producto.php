<?php
header('Content-Type: application/json');
include "../conexion.php";

if (!isset($_GET['id'])) {
    echo json_encode(["error" => "Falta id"]);
    exit;
}

$id = $_GET['id'];

$res = pg_query($conexion, "SELECT * FROM productos WHERE id = $id");

if ($p = pg_fetch_assoc($res)) {
    echo json_encode($p);
} else {
    echo json_encode(["error" => "Producto no encontrado"]);
}
