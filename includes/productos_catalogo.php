<?php
declare(strict_types=1);

if (!function_exists('bootstrapProductosCatalogo')) {
    function bootstrapProductosCatalogo($conexion): void
    {
        static $bootstrapped = false;

        if ($bootstrapped || !$conexion) {
            return;
        }

        $bootstrapped = true;

        $createTableSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS productos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100),
    marca VARCHAR(50),
    precio NUMERIC(10,2),
    descripcion TEXT,
    imagen VARCHAR(255)
)
SQL;

        if (!@pg_query($conexion, $createTableSql)) {
            error_log('Error creando tabla productos: ' . pg_last_error($conexion));
            return;
        }

        if (!@pg_query($conexion, 'ALTER TABLE productos ADD COLUMN IF NOT EXISTS caracteristicas TEXT')) {
            error_log('Error asegurando columna caracteristicas: ' . pg_last_error($conexion));
        }

        $productosBase = [
            [
                'nombre' => 'iPhone 13',
                'marca' => 'iPhone',
                'precio' => '14999.00',
                'descripcion' => 'Pantalla OLED Super Retina de 6.1 pulgadas, gran rendimiento y camara dual ideal para foto y video diario.',
                'imagen' => 'IMG/iphone13.png',
                'caracteristicas' => '128GB | 5G | Camara dual 12MP | Chip A15 Bionic',
            ],
            [
                'nombre' => 'Samsung Galaxy S21',
                'marca' => 'Samsung',
                'precio' => '12499.00',
                'descripcion' => 'Equipo premium con pantalla Dynamic AMOLED de 120Hz, potencia fluida y excelente experiencia multimedia.',
                'imagen' => 'IMG/s21.png',
                'caracteristicas' => '128GB | 120Hz | Triple camara | Exynos/Snapdragon',
            ],
            [
                'nombre' => 'OPPO Reno8',
                'marca' => 'OPPO',
                'precio' => '8999.00',
                'descripcion' => 'Celular equilibrado para uso diario con buen diseno, bateria durable y carga rapida para no detenerte.',
                'imagen' => 'IMG/oppo_reno8.png',
                'caracteristicas' => '256GB | Carga rapida | Camara 50MP | Bateria de larga duracion',
            ],
        ];

        $insertSql = 'INSERT INTO productos (nombre, marca, precio, descripcion, imagen, caracteristicas) VALUES ($1, $2, $3, $4, $5, $6)';

        foreach ($productosBase as $producto) {
            $existsResult = @pg_query_params(
                $conexion,
                'SELECT 1 FROM productos WHERE nombre = $1 AND marca = $2 LIMIT 1',
                [$producto['nombre'], $producto['marca']]
            );

            if (!$existsResult) {
                error_log('Error verificando producto base: ' . pg_last_error($conexion));
                continue;
            }

            if (pg_num_rows($existsResult) > 0) {
                $updateResult = @pg_query_params(
                    $conexion,
                    'UPDATE productos SET precio = $3, descripcion = $4, imagen = $5, caracteristicas = $6 WHERE nombre = $1 AND marca = $2',
                    [
                        $producto['nombre'],
                        $producto['marca'],
                        $producto['precio'],
                        $producto['descripcion'],
                        $producto['imagen'],
                        $producto['caracteristicas'],
                    ]
                );

                if (!$updateResult) {
                    error_log('Error actualizando producto base: ' . pg_last_error($conexion));
                }

                continue;
            }

            $insertResult = @pg_query_params(
                $conexion,
                $insertSql,
                [
                    $producto['nombre'],
                    $producto['marca'],
                    $producto['precio'],
                    $producto['descripcion'],
                    $producto['imagen'],
                    $producto['caracteristicas'],
                ]
            );

            if (!$insertResult) {
                error_log('Error insertando producto base: ' . pg_last_error($conexion));
            }
        }
    }

    function obtenerProductosCatalogo($conexion): array
    {
        bootstrapProductosCatalogo($conexion);

        if (!$conexion) {
            return [];
        }

        $productos = [];
        $result = @pg_query($conexion, 'SELECT * FROM productos ORDER BY id DESC');

        if (!$result) {
            error_log('Error obteniendo productos: ' . pg_last_error($conexion));
            return [];
        }

        while ($row = pg_fetch_assoc($result)) {
            $productos[] = $row;
        }

        return $productos;
    }

    function obtenerProductosPorMarca($conexion, string $marca): array
    {
        bootstrapProductosCatalogo($conexion);

        if (!$conexion || $marca === '') {
            return [];
        }

        $productos = [];
        $result = @pg_query_params($conexion, 'SELECT * FROM productos WHERE marca ILIKE $1 ORDER BY id DESC', [$marca]);

        if (!$result) {
            error_log('Error obteniendo productos por marca: ' . pg_last_error($conexion));
            return [];
        }

        while ($row = pg_fetch_assoc($result)) {
            $productos[] = $row;
        }

        return $productos;
    }

    function normalizarImagenProducto(?string $ruta): string
    {
        $rutaNormalizada = trim((string) $ruta);

        if ($rutaNormalizada === '') {
            return 'IMG/tecno.png';
        }

        return preg_replace('/^img\//i', 'IMG/', $rutaNormalizada) ?: 'IMG/tecno.png';
    }
}
