<?php
require_once __DIR__ . '/includes/layout.php';
require_admin();

$pdo = db();
$me  = current_user();

/** Guarda la portada subida y devuelve el nombre del archivo (o null) */
function guardar_portada(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('La portada no se pudo subir (máximo 2 MB).');
    }
    $info = @getimagesize($file['tmp_name']);
    $ext  = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
    if (!$ext) {
        throw new RuntimeException('La portada debe ser JPG, PNG o WEBP.');
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        throw new RuntimeException('No se pudo guardar la portada.');
    }
    return $name;
}

function borrar_portada(?string $name): void
{
    if ($name && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $name)) {
        @unlink(UPLOAD_DIR . $name);
    }
}

/* ---------- Acciones (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $tab    = 'libros';

    try {
        switch ($action) {
            case 'add_book':
                $titulo = trim($_POST['titulo'] ?? '');
                $autor  = trim($_POST['autor'] ?? '');
                $cat    = trim($_POST['categoria'] ?? '');
                $anio   = ($_POST['anio'] ?? '') !== '' ? (int)$_POST['anio'] : null;
                if ($titulo === '' || $autor === '' || $cat === '') {
                    throw new RuntimeException('Título, autor y categoría son obligatorios.');
                }
                if ($anio !== null && ($anio < -3000 || $anio > (int)date('Y'))) {
                    throw new RuntimeException('El año no es válido.');
                }
                $portada = guardar_portada($_FILES['portada'] ?? []);
                $st = $pdo->prepare('INSERT INTO libros (titulo, autor, categoria, anio, disponible, portada) VALUES (?,?,?,?,?,?)');
                $st->execute([$titulo, $autor, $cat, $anio, isset($_POST['disponible']) ? 1 : 0, $portada]);
                flash('success', 'Libro agregado.');
                break;

            case 'toggle_book':
                $pdo->prepare('UPDATE libros SET disponible = 1 - disponible WHERE id = ?')->execute([(int)$_POST['id']]);
                flash('success', 'Estado actualizado.');
                break;

            case 'delete_book':
                $id = (int)$_POST['id'];
                $st = $pdo->prepare('SELECT portada FROM libros WHERE id = ?');
                $st->execute([$id]);
                $row = $st->fetch();
                $pdo->prepare('DELETE FROM libros WHERE id = ?')->execute([$id]);
                if ($row) borrar_portada($row['portada']);
                flash('success', 'Libro eliminado.');
                break;

            case 'add_user':
                $tab    = 'usuarios';
                $nombre = trim($_POST['nombre'] ?? '');
                $email  = strtolower(trim($_POST['email'] ?? ''));
                $pass   = (string)($_POST['password'] ?? '');
                $rol    = ($_POST['rol'] ?? '') === 'admin' ? 'admin' : 'usuario';
                if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
                    throw new RuntimeException('Revisá nombre, correo y contraseña (mín. 8 caracteres).');
                }
                $st = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
                $st->execute([$email]);
                if ($st->fetch()) {
                    throw new RuntimeException('Ya existe un usuario con ese correo.');
                }
                $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?,?,?,?)')
                    ->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT), $rol]);
                flash('success', 'Usuario creado.');
                break;

            case 'delete_user':
                $tab = 'usuarios';
                $id  = (int)$_POST['id'];
                if ($id === $me['id']) {
                    throw new RuntimeException('No podés eliminar tu propia cuenta.');
                }
                $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
                flash('success', 'Usuario eliminado.');
                break;
        }
    } catch (RuntimeException $ex) {
        flash('danger', e($ex->getMessage()));
    }
    redirect('admin.php?tab=' . $tab);
}

$tab = ($_GET['tab'] ?? 'libros') === 'usuarios' ? 'usuarios' : 'libros';

$libros     = $pdo->query('SELECT * FROM libros ORDER BY id DESC')->fetchAll();
$usuarios   = $pdo->query('SELECT id, nombre, email, rol, creado FROM usuarios ORDER BY id')->fetchAll();
$categorias = $pdo->query('SELECT DISTINCT categoria FROM libros ORDER BY categoria')->fetchAll(PDO::FETCH_COLUMN);

render_header('Administración | Fueguitos', 'admin');
?>
  <main class="page-width admin-page">
    <p class="kicker"><span></span> Panel de administración</p>
    <h1 class="admin-title">Gestión de <em>biblioteca.</em></h1>

    <ul class="nav nav-tabs admin-tabs" role="tablist">
      <li class="nav-item"><a class="nav-link<?= $tab === 'libros' ? ' active' : '' ?>" href="admin.php?tab=libros">Libros (<?= count($libros) ?>)</a></li>
      <li class="nav-item"><a class="nav-link<?= $tab === 'usuarios' ? ' active' : '' ?>" href="admin.php?tab=usuarios">Usuarios (<?= count($usuarios) ?>)</a></li>
    </ul>

<?php if ($tab === 'libros'): ?>
    <section class="admin-card">
      <h2 class="admin-h2">Agregar libro</h2>
      <form method="post" action="admin.php" enctype="multipart/form-data" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_book">
        <div class="col-md-6">
          <label class="form-label" for="titulo">Título</label>
          <input class="form-control" id="titulo" name="titulo" required maxlength="200">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="autor">Autor</label>
          <input class="form-control" id="autor" name="autor" required maxlength="160">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="categoria">Categoría</label>
          <input class="form-control" id="categoria" name="categoria" list="cats" required maxlength="60">
          <datalist id="cats">
            <?php foreach ($categorias as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-md-2">
          <label class="form-label" for="anio">Año</label>
          <input class="form-control" id="anio" name="anio" type="number" min="-3000" max="<?= date('Y') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="portada">Portada (JPG/PNG/WEBP, máx. 2 MB)</label>
          <input class="form-control" id="portada" name="portada" type="file" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="disponible" name="disponible" checked>
            <label class="form-check-label" for="disponible">Disponible</label>
          </div>
        </div>
        <div class="col-12"><button class="btn-fueguitos" type="submit">Agregar libro</button></div>
      </form>
    </section>

    <section class="admin-card">
      <h2 class="admin-h2">Libros cargados</h2>
      <div class="table-responsive">
        <table class="table align-middle admin-table">
          <thead><tr><th>Título</th><th>Autor</th><th>Categoría</th><th>Año</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
          <?php foreach ($libros as $b): ?>
            <tr>
              <td><?= e($b['titulo']) ?></td>
              <td><?= e($b['autor']) ?></td>
              <td><?= e($b['categoria']) ?></td>
              <td><?= $b['anio'] !== null ? (int)$b['anio'] : '—' ?></td>
              <td>
                <span class="status <?= $b['disponible'] ? 'status-ok' : 'status-out' ?>"><?= $b['disponible'] ? 'Disponible' : 'Prestado' ?></span>
              </td>
              <td class="text-end text-nowrap">
                <form method="post" action="admin.php" class="d-inline">
                  <?= csrf_field() ?><input type="hidden" name="action" value="toggle_book"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                  <button class="btn-mini" type="submit">Cambiar estado</button>
                </form>
                <form method="post" action="admin.php" class="d-inline" onsubmit="return confirm('¿Eliminar este libro?');">
                  <?= csrf_field() ?><input type="hidden" name="action" value="delete_book"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                  <button class="btn-mini btn-mini-danger" type="submit">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$libros): ?><tr><td colspan="6" class="text-center text-muted">No hay libros.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

<?php else: ?>
    <section class="admin-card">
      <h2 class="admin-h2">Agregar usuario</h2>
      <form method="post" action="admin.php" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_user">
        <div class="col-md-4">
          <label class="form-label" for="u-nombre">Nombre y apellido</label>
          <input class="form-control" id="u-nombre" name="nombre" required maxlength="120">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="u-email">Correo electrónico</label>
          <input class="form-control" id="u-email" name="email" type="email" required>
        </div>
        <div class="col-md-2">
          <label class="form-label" for="u-pass">Contraseña</label>
          <input class="form-control" id="u-pass" name="password" type="password" minlength="8" required autocomplete="new-password">
        </div>
        <div class="col-md-2">
          <label class="form-label" for="u-rol">Rol</label>
          <select class="form-select" id="u-rol" name="rol">
            <option value="usuario">Usuario</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="col-12"><button class="btn-fueguitos" type="submit">Crear usuario</button></div>
      </form>
    </section>

    <section class="admin-card">
      <h2 class="admin-h2">Usuarios registrados</h2>
      <div class="table-responsive">
        <table class="table align-middle admin-table">
          <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Alta</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
          <?php foreach ($usuarios as $u): ?>
            <tr>
              <td><?= e($u['nombre']) ?><?= $u['id'] === $me['id'] ? ' <small class="text-muted">(vos)</small>' : '' ?></td>
              <td><?= e($u['email']) ?></td>
              <td><?= e($u['rol']) ?></td>
              <td><?= e(date('d/m/Y', strtotime($u['creado']))) ?></td>
              <td class="text-end">
                <?php if ($u['id'] !== $me['id']): ?>
                <form method="post" action="admin.php" class="d-inline" onsubmit="return confirm('¿Eliminar este usuario?');">
                  <?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button class="btn-mini btn-mini-danger" type="submit">Eliminar</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
<?php endif; ?>
  </main>

<?php render_footer('catalogo.php', 'Ver catálogo ↗');
