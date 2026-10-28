<?php
require_once __DIR__ . '/includes/layout.php';

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$user  = null;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = db()->prepare('SELECT id FROM usuarios WHERE reset_token = ? AND reset_expira > NOW()');
    $st->execute([hash('sha256', $token)]);
    $user = $st->fetch();
}

if (!$user) {
    flash('danger', 'El enlace de recuperación no es válido o ya venció.');
    redirect('login.php?tab=recovery');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $p1 = (string)($_POST['password'] ?? '');
    $p2 = (string)($_POST['password2'] ?? '');

    if (strlen($p1) < 8) {
        flash('danger', 'La contraseña debe tener al menos 8 caracteres.');
    } elseif ($p1 !== $p2) {
        flash('danger', 'Las contraseñas no coinciden.');
    } else {
        $up = db()->prepare('UPDATE usuarios SET password_hash = ?, reset_token = NULL, reset_expira = NULL WHERE id = ?');
        $up->execute([password_hash($p1, PASSWORD_DEFAULT), $user['id']]);
        flash('success', 'Contraseña actualizada. Ya podés iniciar sesión.');
        redirect('login.php');
    }
    redirect('reset.php?token=' . $token);
}

render_header('Nueva contraseña | Fueguitos', 'login');
?>
  <main class="page-width">
    <div class="auth-container">
      <h2 class="auth-title">Nueva contraseña</h2>
      <form method="post" action="reset.php">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-group">
          <label for="p1">Nueva contraseña (mín. 8 caracteres)</label>
          <input type="password" id="p1" name="password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label for="p2">Repetir contraseña</label>
          <input type="password" id="p2" name="password2" required minlength="8" autocomplete="new-password">
        </div>
        <button type="submit" class="auth-submit">Guardar contraseña</button>
      </form>
    </div>
  </main>
<?php render_footer();
