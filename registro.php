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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = trim((string) ($_POST['password'] ?? ''));
    $confirm = trim((string) ($_POST['confirm_password'] ?? ''));

    if ($password !== $confirm) {
        $error = 'Las contrasenas no coinciden.';
    } else {
        $result = usuarios_register($conexion, $username, $email, $password);
        if ($result['ok'] ?? false) {
            header('Location: index.php');
            exit;
        }

        $error = (string) ($result['msg'] ?? 'No se pudo crear la cuenta.');
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registro | TecnoMovil MX</title>
  <link rel="icon" type="image/png" href="IMG/favicon.png?v=1">
  <link rel="stylesheet" href="CSS/styles.css?v=14">
</head>
<body class="auth-page">
  <main class="auth-shell auth-shell-register">
    <section class="auth-side">
      <p class="section-eyebrow">TecnoMovil MX</p>
      <h1>Crea tu cuenta</h1>
      <p>Registra tu usuario para comprar, dar seguimiento a tus pedidos y mantener un historial de compras dentro de la tienda.</p>
      <div class="auth-side-actions">
        <a href="login.php" class="btn ghost">Ya tengo cuenta</a>
      </div>
    </section>

    <section class="auth-card">
      <h2>Registro</h2>
      <p>Completa tu informacion para empezar.</p>

      <?php if ($error !== '') { ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php } ?>

      <form method="post" class="auth-form">
        <label for="username">Nombre de usuario</label>
        <input id="username" name="username" required>

        <label for="email">Correo</label>
        <input id="email" name="email" type="email" required>

        <label for="password">Contrasena</label>
        <input id="password" name="password" type="password" required>

        <label for="confirm_password">Confirmar contrasena</label>
        <input id="confirm_password" name="confirm_password" type="password" required>

        <button type="submit" class="btn primary">Crear cuenta</button>
      </form>

      <p class="auth-footnote">Ya tienes cuenta? <a href="login.php">Inicia sesion</a>.</p>
    </section>
  </main>
</body>
</html>
