<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
http_response_code(200);

require_once __DIR__ . '/conexion.php';

if (!$conexion) {
    echo json_encode(['error' => 'No se pudo conectar a la base de datos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Listar productos
    $query = 'SELECT * FROM productos ORDER BY id DESC';
    $result = @pg_query($conexion, $query);

    if (!$result) {
        $error = pg_last_error($conexion) ?: 'Error desconocido al consultar productos.';
        echo json_encode(['error' => $error], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $productos = [];
    while ($row = pg_fetch_assoc($result)) {
        $productos[] = $row;
    }
    echo json_encode($productos, JSON_UNESCAPED_UNICODE);

} elseif ($method === 'POST') {
    // Agregar producto nuevo
    $nombre      = $_POST['nombre']      ?? null;
    $marca       = $_POST['marca']       ?? null;
    $precio      = $_POST['precio']      ?? null;
    $descripcion = $_POST['descripcion'] ?? null;
    $imagen      = $_POST['imagen']      ?? null;

    if ($nombre && $marca && $precio && $descripcion && $imagen) {
        $insert = pg_query_params(
            $conexion,
            "INSERT INTO productos (nombre, marca, precio, descripcion, imagen) VALUES ($1, $2, $3, $4, $5) RETURNING id",
            [$nombre, $marca, $precio, $descripcion, $imagen]
        );

        if ($insert) {
            $newId = pg_fetch_result($insert, 0, 'id');
            echo json_encode(["success" => true, "message" => "Producto agregado", "id" => $newId]);
        } else {
            echo json_encode(["success" => false, "error" => "No se pudo insertar"]);
        }
    } else {
        echo json_encode(["error" => "Faltan campos obligatorios"]);
    }

} elseif ($method === 'DELETE') {
    // Borrar producto por id
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $delete = pg_query_params($conexion, "DELETE FROM productos WHERE id = $1", [$id]);

        if ($delete) {
            echo json_encode(["success" => true, "message" => "Producto eliminado"]);
        } else {
            echo json_encode(["success" => false, "error" => "No se pudo eliminar"]);
        }
    } else {
        echo json_encode(["error" => "Falta parámetro id"]);
    }
}
?>
