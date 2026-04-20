CREATE TABLE IF NOT EXISTS productos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100),
    marca VARCHAR(50),
    precio NUMERIC(10,2),
    descripcion TEXT,
    imagen VARCHAR(255)
);

ALTER TABLE productos
    ADD COLUMN IF NOT EXISTS caracteristicas TEXT;

UPDATE productos
SET
    precio = 14999.00,
    descripcion = 'Pantalla OLED Super Retina de 6.1 pulgadas, gran rendimiento y camara dual ideal para foto y video diario.',
    imagen = 'IMG/iphone13.png',
    caracteristicas = '128GB | 5G | Camara dual 12MP | Chip A15 Bionic'
WHERE nombre = 'iPhone 13' AND marca = 'iPhone';

UPDATE productos
SET
    precio = 12499.00,
    descripcion = 'Equipo premium con pantalla Dynamic AMOLED de 120Hz, potencia fluida y excelente experiencia multimedia.',
    imagen = 'IMG/s21.png',
    caracteristicas = '128GB | 120Hz | Triple camara | Exynos/Snapdragon'
WHERE nombre = 'Samsung Galaxy S21' AND marca = 'Samsung';

UPDATE productos
SET
    precio = 8999.00,
    descripcion = 'Celular equilibrado para uso diario con buen diseno, bateria durable y carga rapida para no detenerte.',
    imagen = 'IMG/oppo_reno8.png',
    caracteristicas = '256GB | Carga rapida | Camara 50MP | Bateria de larga duracion'
WHERE nombre = 'OPPO Reno8' AND marca = 'OPPO';

INSERT INTO productos (nombre, marca, precio, descripcion, imagen, caracteristicas)
SELECT
    'iPhone 13',
    'iPhone',
    14999.00,
    'Pantalla OLED Super Retina de 6.1 pulgadas, gran rendimiento y camara dual ideal para foto y video diario.',
    'IMG/iphone13.png',
    '128GB | 5G | Camara dual 12MP | Chip A15 Bionic'
WHERE NOT EXISTS (
    SELECT 1 FROM productos WHERE nombre = 'iPhone 13' AND marca = 'iPhone'
);

INSERT INTO productos (nombre, marca, precio, descripcion, imagen, caracteristicas)
SELECT
    'Samsung Galaxy S21',
    'Samsung',
    12499.00,
    'Equipo premium con pantalla Dynamic AMOLED de 120Hz, potencia fluida y excelente experiencia multimedia.',
    'IMG/s21.png',
    '128GB | 120Hz | Triple camara | Exynos/Snapdragon'
WHERE NOT EXISTS (
    SELECT 1 FROM productos WHERE nombre = 'Samsung Galaxy S21' AND marca = 'Samsung'
);

INSERT INTO productos (nombre, marca, precio, descripcion, imagen, caracteristicas)
SELECT
    'OPPO Reno8',
    'OPPO',
    8999.00,
    'Celular equilibrado para uso diario con buen diseno, bateria durable y carga rapida para no detenerte.',
    'IMG/oppo_reno8.png',
    '256GB | Carga rapida | Camara 50MP | Bateria de larga duracion'
WHERE NOT EXISTS (
    SELECT 1 FROM productos WHERE nombre = 'OPPO Reno8' AND marca = 'OPPO'
);
