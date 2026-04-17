<?php
// login.php
session_start();
include "conexion.php";
header('Content-Type: application/json');

$usuario = $_POST['usuario'] ?? '';
$password = $_POST['password'] ?? '';

if (!$usuario || !$password) {
    echo json_encode(['ok'=>false, 'msg'=>'Usuario y contraseña requeridos']);
    exit;
}

$res = pg_query_params($conexion, "SELECT * FROM admin WHERE usuario = $1", array($usuario));
if ($row = pg_fetch_assoc($res)) {
    if (password_verify($password, $row['password'])) {
        $_SESSION['admin'] = $usuario;
        echo json_encode(['ok'=>true]);
        exit;
    }
}

echo json_encode(['ok'=>false,'msg'=>'Usuario o contraseña incorrectos']);
