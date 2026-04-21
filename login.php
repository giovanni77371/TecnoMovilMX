<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/usuarios.php';

usuarios_start_session();
usuarios_ensure_table($conexion);

if (usuarios_current()) {
    header('Location: index.php');
    exit;
}

$error = '';
$notice = isset($_GET['registered']) ? 'Cuenta creada correctamente. Ahora inicia sesion.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $password = trim((string) ($_POST['password'] ?? ''));
    $result = usuarios_login($conexion, $identifier, $password);

    if ($result['ok'] ?? false) {
        header('Location: ' . usuarios_consume_after_login('index.php'));
        exit;
    }

    $error = (string) ($result['msg'] ?? 'No se pudo iniciar sesion.');
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Login | TecnoMovil MX</title>
  <link rel="icon" type="image/png" href="IMG/favicon.png?v=1">
  <link rel="stylesheet" href="CSS/styles.css?v=14">
</head>
<body class="auth-page">
  <main class="auth-shell">
    <section class="auth-side">
      <p class="section-eyebrow">TecnoMovil MX</p>
      <h1>Bienvenido a tu tienda de celulares</h1>
      <p>Antes de entrar al catalogo, inicia sesion o crea tu cuenta para guardar tu compra y completar el pago simulado sin contratiempos.</p>
      <div class="auth-side-actions">
        <a href="registro.php" class="btn primary">Crear cuenta</a>
      </div>
    </section>

    <section class="auth-card">
      <h2>Iniciar sesion</h2>
      <p>Usa tu usuario o correo para entrar.</p>

      <?php if ($notice !== '') { ?>
        <div class="alert alert-success"><?= htmlspecialchars($notice) ?></div>
      <?php } ?>

      <?php if ($error !== '') { ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php } ?>

      <form method="post" class="auth-form">
        <label for="identifier">Usuario o correo</label>
        <input id="identifier" name="identifier" required>

        <label for="password">Contrasena</label>
        <input id="password" name="password" type="password" required>

        <button type="submit" class="btn primary">Entrar</button>
      </form>

      <p class="auth-footnote">No tienes cuenta? <a href="registro.php">Registrate aqui</a>.</p>
    </section>
  </main>
</body>
</html>
