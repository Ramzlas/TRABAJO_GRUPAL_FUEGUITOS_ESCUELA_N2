<?php
require_once __DIR__ . '/includes/layout.php';

if (current_user()) {
    redirect(is_admin() ? 'admin.php' : 'catalogo.php');
}

$tab = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $pdo = db();

    if ($action === 'login') {
        $tab   = 'login';
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');

        $st = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();

        if ($u && password_verify($pass, $u['password_hash'])) {
            login_user($u);
            redirect($u['rol'] === 'admin' ? 'admin.php' : 'catalogo.php');
        }
        flash('danger', 'Correo o contraseña incorrectos.');
        redirect('login.php');
    }

    if ($action === 'register') {
        $tab    = 'register';
        $nombre = trim($_POST['nombre'] ?? '');
        $email  = strtolower(trim($_POST['email'] ?? ''));
        $pass   = (string)($_POST['password'] ?? '');

        if ($nombre === '' || mb_strlen($nombre) > 120) {
            flash('danger', 'Ingresá tu nombre y apellido.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'El correo electrónico no es válido.');
        } elseif (strlen($pass) < 8) {
            flash('danger', 'La contraseña debe tener al menos 8 caracteres.');
        } else {
            $st = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
            $st->execute([$email]);
            if ($st->fetch()) {
                flash('danger', 'Ya existe una cuenta con ese correo.');
            } else {
                $ins = $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, "usuario")');
                $ins->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT)]);
                login_user([
                    'id' => (int)$pdo->lastInsertId(), 'nombre' => $nombre,
                    'email' => $email, 'rol' => 'usuario',
                ]);
                flash('success', '¡Cuenta creada! Bienvenido/a.');
                redirect('catalogo.php');
            }
        }
        redirect('login.php?tab=register');
    }

    if ($action === 'recovery') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $st = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();

        $msg = 'Si el correo está registrado, vas a recibir las instrucciones para recuperar tu contraseña.';
        if ($u) {
            $token = bin2hex(random_bytes(32));
            $up = $pdo->prepare('UPDATE usuarios SET reset_token = ?, reset_expira = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?');
            $up->execute([hash('sha256', $token), $u['id']]);

            if (DEV_MODE) {
                // Sin servidor de mail en XAMPP: mostramos el enlace para poder probar.
                $msg .= '<br><small>Modo local: <a href="reset.php?token=' . $token . '">restablecer contraseña</a> (válido 1 hora).</small>';
            }
            // En producción: enviar $token por mail con mail() o PHPMailer.
        }
        flash('info', $msg);
        redirect('login.php?tab=recovery');
    }
}

if (isset($_GET['tab']) && in_array($_GET['tab'], ['login', 'register', 'recovery'], true)) {
    $tab = $_GET['tab'];
}

render_header('Acceso | Fueguitos', 'login', 'Acceso, registro y recuperación de cuenta en la biblioteca Fueguitos.');
?>
  <main class="page-width">
    <div class="auth-container">

      <div class="auth-tabs" role="tablist">
        <button class="auth-tab<?= $tab === 'login' ? ' active' : '' ?>" data-tab="login" type="button" role="tab">Iniciar Sesión</button>
        <button class="auth-tab<?= $tab === 'register' ? ' active' : '' ?>" data-tab="register" type="button" role="tab">Registro</button>
        <button class="auth-tab<?= $tab === 'recovery' ? ' active' : '' ?>" data-tab="recovery" type="button" role="tab">Recuperar</button>
      </div>

      <form id="login" class="auth-form<?= $tab === 'login' ? ' active' : '' ?>" method="post" action="login.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="login">
        <div class="form-group">
          <label for="login-email">Correo electrónico</label>
          <input type="email" id="login-email" name="email" required placeholder="tu@email.com" autocomplete="email">
        </div>
        <div class="form-group">
          <label for="login-pass">Contraseña</label>
          <input type="password" id="login-pass" name="password" required placeholder="••••••••" autocomplete="current-password">
        </div>
        <button type="submit" class="auth-submit">Ingresar</button>
        <p class="auth-help">¿Olvidaste tu contraseña? <a href="#" data-goto="recovery">Recuperala aquí</a></p>
      </form>

      <form id="register" class="auth-form<?= $tab === 'register' ? ' active' : '' ?>" method="post" action="login.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="register">
        <div class="form-group">
          <label for="reg-name">Nombre y apellido</label>
          <input type="text" id="reg-name" name="nombre" required maxlength="120" placeholder="Juan Pérez" autocomplete="name">
        </div>
        <div class="form-group">
          <label for="reg-email">Correo electrónico</label>
          <input type="email" id="reg-email" name="email" required placeholder="tu@email.com" autocomplete="email">
        </div>
        <div class="form-group">
          <label for="reg-pass">Crear contraseña (mín. 8 caracteres)</label>
          <input type="password" id="reg-pass" name="password" required minlength="8" placeholder="••••••••" autocomplete="new-password">
        </div>
        <button type="submit" class="auth-submit">Registrarse</button>
      </form>

      <form id="recovery" class="auth-form<?= $tab === 'recovery' ? ' active' : '' ?>" method="post" action="login.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="recovery">
        <div class="form-group">
          <label for="rec-email">Correo electrónico de recuperación</label>
          <input type="email" id="rec-email" name="email" required placeholder="tu@email.com" autocomplete="email">
        </div>
        <button type="submit" class="auth-submit">Enviar instrucciones</button>
        <p class="auth-help">¿Recordaste tu contraseña? <a href="#" data-goto="login">Volver al ingreso</a></p>
      </form>

    </div>
  </main>

<?php render_footer('index.php', 'Volver al inicio ↗');
