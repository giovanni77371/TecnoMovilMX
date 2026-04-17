CREATE TABLE IF NOT EXISTS envios (
    id SERIAL PRIMARY KEY,
    pedido_id VARCHAR(120) NOT NULL UNIQUE,
    tracking_number VARCHAR(40) NOT NULL UNIQUE,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'enviado', 'en camino', 'entregado')),
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_envios_tracking_number ON envios (tracking_number);
CREATE INDEX IF NOT EXISTS idx_envios_pedido_id ON envios (pedido_id);
