<?php
declare(strict_types=1);

require_once __DIR__ . '/usuarios.php';
require_once __DIR__ . '/productos_catalogo.php';

if (!function_exists('compras_ensure_table')) {
    function compras_ensure_table($conexion): void
    {
        static $ready = false;

        if ($ready || !$conexion) {
            return;
        }

        $ready = true;

        usuarios_ensure_table($conexion);
        bootstrapProductosCatalogo($conexion);

        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS compras (
            id SERIAL PRIMARY KEY,
            usuario_id INT REFERENCES usuarios(id),
            producto_id INT REFERENCES productos(id),
            cantidad INT NOT NULL DEFAULT 1,
            total NUMERIC(10,2) NOT NULL DEFAULT 0,
            pedido_ref VARCHAR(60),
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        SQL;

        if (!@pg_query($conexion, $sql)) {
            error_log('Error creando tabla compras: ' . pg_last_error($conexion));
            return;
        }

        if (!@pg_query($conexion, 'ALTER TABLE compras ADD COLUMN IF NOT EXISTS pedido_ref VARCHAR(60)')) {
            error_log('Error asegurando columna pedido_ref en compras: ' . pg_last_error($conexion));
        }

        if (!@pg_query($conexion, 'ALTER TABLE compras ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP')) {
            error_log('Error asegurando columna created_at en compras: ' . pg_last_error($conexion));
        }
    }

    function compras_generar_referencia(): string
    {
        return 'COMPRA' . date('YmdHis') . strtoupper(bin2hex(random_bytes(3)));
    }

    function compras_registrar_carrito($conexion, int $usuarioId, array $cart): array
    {
        compras_ensure_table($conexion);

        if (!$conexion || $usuarioId <= 0 || empty($cart)) {
            return ['ok' => false, 'msg' => 'No hay datos suficientes para registrar la compra.'];
        }

        $pedidoRef = compras_generar_referencia();
        $totalCompra = 0.0;
        $productosResumen = [];
        $lineas = [];

        foreach ($cart as $id => $qty) {
            $cantidad = (int) $qty;
            if ($cantidad <= 0) {
                continue;
            }

            $productoResult = @pg_query_params(
                $conexion,
                'SELECT id, nombre, precio FROM productos WHERE id = $1 LIMIT 1',
                [(int) $id]
            );

            if (!$productoResult) {
                error_log('Error consultando producto para compra: ' . pg_last_error($conexion));
                continue;
            }

            $producto = pg_fetch_assoc($productoResult);
            if (!$producto) {
                continue;
            }

            $subtotal = $cantidad * (float) ($producto['precio'] ?? 0);
            $totalCompra += $subtotal;
            $productosResumen[] = (string) $producto['nombre'] . ' x' . $cantidad;
            $lineas[] = [
                'producto_id' => (int) $producto['id'],
                'cantidad' => $cantidad,
                'total' => $subtotal,
            ];
        }

        if (empty($lineas)) {
            return ['ok' => false, 'msg' => 'No se encontraron productos validos para registrar la compra.'];
        }

        @pg_query($conexion, 'BEGIN');

        foreach ($lineas as $linea) {
            $insert = @pg_query_params(
                $conexion,
                'INSERT INTO compras (usuario_id, producto_id, cantidad, total, pedido_ref, created_at) VALUES ($1, $2, $3, $4, $5, NOW())',
                [$usuarioId, $linea['producto_id'], $linea['cantidad'], $linea['total'], $pedidoRef]
            );

            if (!$insert) {
                @pg_query($conexion, 'ROLLBACK');
                error_log('Error registrando linea de compra: ' . pg_last_error($conexion));
                return ['ok' => false, 'msg' => 'No se pudo registrar la compra.'];
            }
        }

        @pg_query($conexion, 'COMMIT');

        return [
            'ok' => true,
            'pedido_ref' => $pedidoRef,
            'total' => $totalCompra,
            'productos' => $productosResumen,
        ];
    }

    function compras_historial_admin($conexion): array
    {
        compras_ensure_table($conexion);

        if (!$conexion) {
            return [];
        }

        $sql = <<<SQL
        SELECT
            referencia,
            username,
            productos,
            total_compra,
            fecha
        FROM (
            SELECT
                COALESCE(NULLIF(c.pedido_ref, ''), 'COMPRA-' || c.id::text) AS referencia,
                u.username,
                STRING_AGG(p.nombre || ' x' || c.cantidad::text, ', ' ORDER BY p.nombre) AS productos,
                SUM(c.total) AS total_compra,
                MAX(c.created_at) AS fecha
            FROM compras c
            JOIN usuarios u ON u.id = c.usuario_id
            JOIN productos p ON p.id = c.producto_id
            GROUP BY COALESCE(NULLIF(c.pedido_ref, ''), 'COMPRA-' || c.id::text), u.username
        ) historial
        ORDER BY fecha DESC
        SQL;

        $result = @pg_query($conexion, $sql);
        if (!$result) {
            error_log('Error consultando historial de compras: ' . pg_last_error($conexion));
            return [];
        }

        $historial = [];
        while ($row = pg_fetch_assoc($result)) {
            $historial[] = $row;
        }

        return $historial;
    }
}
