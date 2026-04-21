<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = trim((string) ($_POST['usuario'] ?? ''));
$password = trim((string) ($_POST['password'] ?? ''));

if ($usuario === '' || $password === '') {
    echo json_encode(['ok' => false, 'msg' => 'Usuario y contrasena requeridos.']);
    exit;
}

if (!$conexion) {
    echo json_encode(['ok' => false, 'msg' => 'No hay conexion con la base de datos.']);
    exit;
}

$res = @pg_query_params($conexion, 'SELECT * FROM admin WHERE usuario = $1 LIMIT 1', [$usuario]);

if ($res && ($row = pg_fetch_assoc($res)) && password_verify($password, (string) $row['password'])) {
    $_SESSION['admin'] = $usuario;
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Usuario o contrasena incorrectos.']);
