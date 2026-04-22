CREATE TABLE IF NOT EXISTS productos (
  id SERIAL PRIMARY KEY,
  nombre VARCHAR(100),
  marca VARCHAR(50),
  precio NUMERIC(10,2),
  descripcion TEXT,
  imagen VARCHAR(255)
);
