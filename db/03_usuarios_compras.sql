CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS compras (
    id SERIAL PRIMARY KEY,
    usuario_id INT REFERENCES usuarios(id),
    producto_id INT REFERENCES productos(id),
    cantidad INT NOT NULL DEFAULT 1,
    total NUMERIC(10,2) NOT NULL DEFAULT 0,
    pedido_ref VARCHAR(60),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO usuarios (username, email, password)
SELECT
    'demo',
    'demo@tecnomovilmx.com',
    '$2y$10$dswWPXuZMZfjc67D3JbqwuGXkF3tiJUt.ThnCPNChqxncmzlniNgC'
WHERE NOT EXISTS (
    SELECT 1 FROM usuarios WHERE LOWER(username) = 'demo'
);
