<?php
declare(strict_types=1);

function envios_ensure_table($conexion): void
{
    static $created = false;
    if ($created) {
        return;
    }

    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS envios (
        id SERIAL PRIMARY KEY,
        pedido_id VARCHAR(120) NOT NULL UNIQUE,
        tracking_number VARCHAR(40) NOT NULL UNIQUE,
        estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'
            CHECK (estado IN ('pendiente', 'enviado', 'en camino', 'entregado')),
        fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
    SQL;

    pg_query($conexion, $sql);
    $created = true;
}

function envios_generate_tracking_number(): string
{
    return 'SHIPPO' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function envios_find_by_tracking($conexion, string $trackingNumber): ?array
{
    envios_ensure_table($conexion);
    $result = pg_query_params(
        $conexion,
        'SELECT * FROM envios WHERE tracking_number = $1 LIMIT 1',
        [$trackingNumber]
    );

    if ($result === false) {
        return null;
    }

    $envio = pg_fetch_assoc($result);
    return $envio ?: null;
}

function envios_find_by_pedido($conexion, string $pedidoId): ?array
{
    envios_ensure_table($conexion);
    $result = pg_query_params(
        $conexion,
        'SELECT * FROM envios WHERE pedido_id = $1 LIMIT 1',
        [$pedidoId]
    );

    if ($result === false) {
        return null;
    }

    $envio = pg_fetch_assoc($result);
    return $envio ?: null;
}

function envios_progress_index(string $estado): int
{
    $steps = ['pendiente', 'enviado', 'en camino', 'entregado'];
    $index = array_search($estado, $steps, true);
    return $index === false ? 0 : (int) $index;
}

function envios_next_status(array $envio): string
{
    $current = $envio['estado'] ?? 'pendiente';
    $updatedAt = strtotime((string) ($envio['fecha_actualizacion'] ?? 'now')) ?: time();
    $elapsedHours = (time() - $updatedAt) / 3600;

    if ($current === 'entregado') {
        return 'entregado';
    }

    if ($elapsedHours >= 72) {
        return 'entregado';
    }

    if ($elapsedHours >= 24) {
        return 'en camino';
    }

    if ($elapsedHours >= 1) {
        return 'enviado';
    }

    return $current;
}

function envios_update_status($conexion, int $id, string $estado): bool
{
    envios_ensure_table($conexion);
    $result = pg_query_params(
        $conexion,
        'UPDATE envios SET estado = $1, fecha_actualizacion = NOW() WHERE id = $2',
        [$estado, $id]
    );

    return $result !== false;
}

function envios_simulate_progress($conexion, array $envio): array
{
    $nextStatus = envios_next_status($envio);
    if (($envio['estado'] ?? 'pendiente') !== $nextStatus) {
        envios_update_status($conexion, (int) $envio['id'], $nextStatus);
        $envio['estado'] = $nextStatus;
        $envio['fecha_actualizacion'] = date('Y-m-d H:i:s');
    }

    return $envio;
}

function envios_create_for_pedido($conexion, string $pedidoId): array
{
    envios_ensure_table($conexion);

    $existing = envios_find_by_pedido($conexion, $pedidoId);
    if ($existing) {
        return $existing;
    }

    do {
        $trackingNumber = envios_generate_tracking_number();
        $exists = envios_find_by_tracking($conexion, $trackingNumber);
    } while ($exists !== null);

    $result = pg_query_params(
        $conexion,
        'INSERT INTO envios (pedido_id, tracking_number, estado, fecha_actualizacion) VALUES ($1, $2, $3, NOW()) RETURNING *',
        [$pedidoId, $trackingNumber, 'pendiente']
    );

    $envio = $result ? pg_fetch_assoc($result) : null;
    if (!$envio) {
        throw new RuntimeException('No se pudo crear el envio simulado.');
    }

    return $envio;
}
