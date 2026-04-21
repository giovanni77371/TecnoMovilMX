<?php if (!empty($usuarioActual)) { ?>
  <div class="user-menu">
    <button type="button" class="icon-btn user-menu-toggle" aria-expanded="false">
      <?= htmlspecialchars((string) $usuarioActual['username']) ?>
    </button>
    <div class="user-menu-panel">
      <a href="logout.php" class="user-menu-link">Cerrar sesion</a>
    </div>
  </div>
<?php } else { ?>
  <a href="login.php" class="icon-btn">Login</a>
<?php } ?>
