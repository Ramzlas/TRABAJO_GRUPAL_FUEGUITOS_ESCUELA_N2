<?php
require_once __DIR__ . '/includes/layout.php';
require_login(); // los invitados solo pueden ver el inicio

$pdo = db();

/* ---------- Filtros (GET) ---------- */
$q      = trim((string)($_GET['q'] ?? ''));
$cat    = trim((string)($_GET['cat'] ?? ''));
$disp   = isset($_GET['disp']);
$orden  = (string)($_GET['orden'] ?? 'title');
$orders = [
    'title'     => 'titulo ASC',
    'author'    => 'autor ASC',
    'year-asc'  => 'anio ASC, titulo ASC',
    'year-desc' => 'anio DESC, titulo ASC',
];
if (!isset($orders[$orden])) {
    $orden = 'title';
}

$where  = [];
$params = [];
if ($q !== '') {
    $where[] = '(titulo LIKE :q1 OR autor LIKE :q2 OR categoria LIKE :q3)';
    $like = '%' . $q . '%';
    $params[':q1'] = $params[':q2'] = $params[':q3'] = $like;
}
if ($cat !== '') {
    $where[] = 'categoria = :cat';
    $params[':cat'] = $cat;
}
if ($disp) {
    $where[] = 'disponible = 1';
}

$sql = 'SELECT * FROM libros' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY ' . $orders[$orden];
$st = $pdo->prepare($sql);
$st->execute($params);
$libros = $st->fetchAll();

$total      = (int)$pdo->query('SELECT COUNT(*) FROM libros')->fetchColumn();
$categorias = $pdo->query('SELECT categoria, COUNT(*) AS n FROM libros GROUP BY categoria ORDER BY categoria')->fetchAll();
$hayFiltros = ($q !== '' || $cat !== '' || $disp);

/** Arma la URL del catálogo conservando los filtros actuales */
function cat_url(array $override = []): string
{
    global $q, $cat, $disp, $orden;
    $base = ['q' => $q, 'cat' => $cat, 'disp' => $disp ? '1' : '', 'orden' => $orden];
    $p = array_filter(array_merge($base, $override), fn($v) => $v !== '' && $v !== null);
    return 'catalogo.php' . ($p ? '?' . http_build_query($p) : '');
}

render_header('Catálogo | Fueguitos', 'catalogo',
    'Catálogo de la biblioteca Fueguitos: buscá por título, autor o categoría y fijate qué libros están disponibles.');
?>
  <main>
    <section class="catalog-hero page-width" aria-labelledby="catalog-title">
      <p class="kicker"><span></span> Biblioteca Fueguitos</p>
      <h1 id="catalog-title">Catálogo de <em>libros.</em></h1>
      <p class="lead">Buscá por título o autor, elegí una categoría y explorá nuestros ejemplares.</p>
    </section>

    <section class="toolbar" aria-label="Buscar y filtrar libros">
      <div class="page-width toolbar-inner">
        <form method="get" action="catalogo.php" class="toolbar-row" id="filtros">
          <?php if ($cat !== ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>

          <div class="search-box">
            <span class="search-icon" aria-hidden="true">🔍</span>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por título, autor o tema…" autocomplete="off">
            <?php if ($q !== ''): ?>
              <a class="search-clear" href="<?= e(cat_url(['q' => ''])) ?>" aria-label="Borrar búsqueda">×</a>
            <?php endif; ?>
          </div>

          <label class="toggle">
            <input type="checkbox" name="disp" value="1" <?= $disp ? 'checked' : '' ?> onchange="this.form.submit()">
            <span class="toggle-track" aria-hidden="true"></span>
            Solo disponibles
          </label>

          <label class="sort-box">
            Ordenar
            <select name="orden" onchange="this.form.submit()">
              <option value="title"     <?= $orden === 'title' ? 'selected' : '' ?>>Título (A–Z)</option>
              <option value="author"    <?= $orden === 'author' ? 'selected' : '' ?>>Autor (A–Z)</option>
              <option value="year-asc"  <?= $orden === 'year-asc' ? 'selected' : '' ?>>Más antiguos primero</option>
              <option value="year-desc" <?= $orden === 'year-desc' ? 'selected' : '' ?>>Más recientes primero</option>
            </select>
          </label>
          <noscript><button type="submit" class="btn btn-dark btn-sm">Buscar</button></noscript>
        </form>

        <div class="chips" role="group" aria-label="Filtrar por categoría">
          <a class="chip" href="<?= e(cat_url(['cat' => ''])) ?>" aria-pressed="<?= $cat === '' ? 'true' : 'false' ?>">Todas <small><?= $total ?></small></a>
          <?php foreach ($categorias as $c): ?>
            <a class="chip" href="<?= e(cat_url(['cat' => $c['categoria']])) ?>"
               aria-pressed="<?= $cat === $c['categoria'] ? 'true' : 'false' ?>"><?= e($c['categoria']) ?> <small><?= (int)$c['n'] ?></small></a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="results page-width" aria-labelledby="results-title">
      <div class="results-head">
        <p id="results-title" role="status">Mostrando <strong><?= count($libros) ?></strong> de <?= $total ?> libros</p>
        <?php if ($hayFiltros): ?>
          <a class="reset-btn" href="catalogo.php">Limpiar filtros</a>
        <?php endif; ?>
      </div>

      <?php if (!$libros): ?>
        <p class="empty-state">No encontramos libros con esos filtros.</p>
      <?php else: ?>
      <ul class="catalog-grid">
        <?php foreach ($libros as $b): ?>
        <li>
          <article class="book-card">
            <div class="book-cover-box">
              <?php if ($b['portada']): ?>
                <img src="uploads/<?= e($b['portada']) ?>" alt="Portada de <?= e($b['titulo']) ?>" loading="lazy">
              <?php endif; ?>
            </div>
            <div class="card-meta">
              <p class="book-category"><?= e($b['categoria']) ?></p>
              <?php if ($b['disponible']): ?>
                <span class="status status-ok">Disponible</span>
              <?php else: ?>
                <span class="status status-out">Prestado</span>
              <?php endif; ?>
            </div>
            <h3><?= e($b['titulo']) ?></h3>
            <p class="book-byline"><?= e($b['autor']) ?><?= $b['anio'] ? ' · ' . (int)$b['anio'] : '' ?></p>
          </article>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </section>
  </main>

<?php render_footer('index.php', 'Volver al inicio ↗');
