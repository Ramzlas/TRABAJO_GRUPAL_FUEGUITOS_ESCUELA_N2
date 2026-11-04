<?php
require_once __DIR__ . '/../config.php';

/**
 * Cabecera común del sitio.
 * $active: 'inicio' | 'catalogo' | 'login' | 'admin'
 */
function render_header(string $title, string $active = '', string $desc = ''): void
{
    $user = current_user();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($desc ?: 'Biblioteca Fueguitos: lecturas para todos.') ?>">
  <title><?= e($title) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="extra.css">
</head>
<body>
  <header class="site-header">
    <div class="page-width nav-layout">
      <a class="brand" href="index.php" aria-label="Fueguitos, inicio">
        <span class="brand-mark" aria-hidden="true">F</span>
        <span>fueguitos</span>
      </a>
      <nav aria-label="Navegación principal">
        <a href="index.php#donar">Donar</a>
        <a href="catalogo.php"<?= $active === 'catalogo' ? ' aria-current="page"' : '' ?>>Catálogo</a>
        <?php if (is_admin()): ?>
          <a href="admin.php"<?= $active === 'admin' ? ' aria-current="page"' : '' ?>>Admin</a>
        <?php endif; ?>
        <?php if ($user): ?>
          <a class="login-link" href="logout.php">Salir <span aria-hidden="true">↗</span></a>
        <?php else: ?>
          <a class="login-link" href="login.php"<?= $active === 'login' ? ' aria-current="page"' : '' ?>>Iniciar sesión <span aria-hidden="true">↗</span></a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="page-width flash-wrap">
      <?php foreach ($_SESSION['flash'] as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
          <?= $f['msg'] /* los mensajes se arman en el servidor con e() donde corresponde */ ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
      <?php endforeach; unset($_SESSION['flash']); ?>
    </div>
  <?php endif; ?>
<?php
}

function render_footer(string $linkHref = 'index.php', string $linkText = 'Volver al inicio ↗'): void
{
    ?>
  <footer class="site-footer">
    <div class="page-width footer-layout">
      <a class="brand footer-brand" href="index.php">
        <span class="brand-mark" aria-hidden="true">F</span>
        <span>fueguitos</span>
      </a>
      <p>TEXTO DE RELLENO</p>
      <a class="footer-catalog" href="<?= e($linkHref) ?>"><?= e($linkText) ?></a>
    </div>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="main.js"></script>
</body>
</html>
<?php
}
