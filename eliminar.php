<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }
include "conexion.php";

$id = intval($_GET['id'] ?? 0);
if ($id) {
    // opcional: borrar archivo fisico de uploads si quieres
    $res = pg_query_params($conexion, "SELECT imagen FROM productos WHERE id=$1", array($id));
    if ($row = pg_fetch_assoc($res)) {
        if (!empty($row['imagen']) && strpos($row['imagen'],'uploads/') === 0 && file_exists($row['imagen'])) {
            @unlink($row['imagen']);
        }
    }
    pg_query_params($conexion, "DELETE FROM productos WHERE id=$1", array($id));
}
header('Location: panel.php');
exit;
