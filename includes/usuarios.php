<?php
declare(strict_types=1);

if (!function_exists('usuarios_start_session')) {
    function usuarios_start_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    function usuarios_ensure_table($conexion): void
    {
        static $ready = false;

        if ($ready || !$conexion) {
            return;
        }

        $ready = true;

        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS usuarios (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        SQL;

        if (!@pg_query($conexion, $sql)) {
            error_log('Error creando tabla usuarios: ' . pg_last_error($conexion));
        }
    }

    function usuarios_current(): ?array
    {
        usuarios_start_session();

        if (!isset($_SESSION['usuario_id'])) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['usuario_id'],
            'username' => (string) ($_SESSION['usuario_username'] ?? ''),
            'email' => (string) ($_SESSION['usuario_email'] ?? ''),
        ];
    }

    function usuarios_login_session(array $user): void
    {
        usuarios_start_session();
        $_SESSION['usuario_id'] = (int) $user['id'];
        $_SESSION['usuario_username'] = (string) $user['username'];
        $_SESSION['usuario_email'] = (string) $user['email'];
    }

    function usuarios_clear_session(): void
    {
        usuarios_start_session();
        unset($_SESSION['usuario_id'], $_SESSION['usuario_username'], $_SESSION['usuario_email'], $_SESSION['after_login']);
    }

    function usuarios_require_login(string $fallback = 'index.php'): void
    {
        usuarios_start_session();

        if (isset($_SESSION['usuario_id'])) {
            return;
        }

        $target = $_SERVER['REQUEST_URI'] ?? $fallback;
        if (!is_string($target) || $target === '') {
            $target = $fallback;
        }

        $_SESSION['after_login'] = $target;
        header('Location: login.php');
        exit;
    }

    function usuarios_consume_after_login(string $default = 'index.php'): string
    {
        usuarios_start_session();

        $target = (string) ($_SESSION['after_login'] ?? $default);
        unset($_SESSION['after_login']);

        if (
            $target === '' ||
            str_contains($target, 'login.php') ||
            str_contains($target, 'registro.php')
        ) {
            return $default;
        }

        return $target;
    }

    function usuarios_register($conexion, string $username, string $email, string $password): array
    {
        usuarios_ensure_table($conexion);

        $username = trim($username);
        $email = trim($email);
        $password = trim($password);

        if ($username === '' || $email === '' || $password === '') {
            return ['ok' => false, 'msg' => 'Completa todos los campos.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Ingresa un correo valido.'];
        }

        if (strlen($password) < 6) {
            return ['ok' => false, 'msg' => 'La contrasena debe tener al menos 6 caracteres.'];
        }

        if (!$conexion) {
            return ['ok' => false, 'msg' => 'No hay conexion con la base de datos.'];
        }

        $exists = @pg_query_params(
            $conexion,
            'SELECT id FROM usuarios WHERE LOWER(username) = LOWER($1) OR LOWER(email) = LOWER($2) LIMIT 1',
            [$username, $email]
        );

        if ($exists && pg_fetch_assoc($exists)) {
            return ['ok' => false, 'msg' => 'Ese usuario o correo ya existe.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = @pg_query_params(
            $conexion,
            'INSERT INTO usuarios (username, email, password) VALUES ($1, $2, $3) RETURNING id, username, email',
            [$username, $email, $hash]
        );

        if (!$insert) {
            error_log('Error registrando usuario: ' . pg_last_error($conexion));
            return ['ok' => false, 'msg' => 'No se pudo crear la cuenta.'];
        }

        $user = pg_fetch_assoc($insert);
        if (!$user) {
            return ['ok' => false, 'msg' => 'No se pudo crear la cuenta.'];
        }

        usuarios_login_session($user);

        return ['ok' => true, 'user' => $user];
    }

    function usuarios_login($conexion, string $identifier, string $password): array
    {
        usuarios_ensure_table($conexion);

        $identifier = trim($identifier);
        $password = trim($password);

        if ($identifier === '' || $password === '') {
            return ['ok' => false, 'msg' => 'Usuario o correo y contrasena son requeridos.'];
        }

        if (!$conexion) {
            return ['ok' => false, 'msg' => 'No hay conexion con la base de datos.'];
        }

        $result = @pg_query_params(
            $conexion,
            'SELECT id, username, email, password FROM usuarios WHERE LOWER(username) = LOWER($1) OR LOWER(email) = LOWER($1) LIMIT 1',
            [$identifier]
        );

        if (!$result) {
            error_log('Error iniciando sesion de usuario: ' . pg_last_error($conexion));
            return ['ok' => false, 'msg' => 'No se pudo iniciar sesion.'];
        }

        $user = pg_fetch_assoc($result);
        if (!$user || !password_verify($password, (string) $user['password'])) {
            return ['ok' => false, 'msg' => 'Credenciales incorrectas.'];
        }

        usuarios_login_session($user);

        return ['ok' => true, 'user' => $user];
    }
}
