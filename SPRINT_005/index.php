<?php
require_once __DIR__ . '/includes/layout.php';

// Últimos libros para el carrusel (visible para invitados)
$libros = db()->query('SELECT id, titulo, autor, categoria, portada FROM libros ORDER BY creado DESC, id DESC LIMIT 12')->fetchAll();
$slides = array_chunk($libros, 4);
$colores = ['art-olive', 'art-coral', 'art-blue', 'art-yellow'];

render_header('Fueguitos | Lecturas para todos', 'inicio',
    'Conoce Fueguitos, explora libros y descubre cómo apoyar nuestro proyecto de lectura.');
?>
  <main>
    <section class="welcome page-width" aria-labelledby="welcome-title">
      <div class="welcome-copy">
        <p class="kicker"><span></span> Una biblioteca abierta</p>
        <h1 id="welcome-title">Bienvenido a biblioteca<br><em>Fueguitos.</em></h1>
        <p class="welcome-text">TEXTO DE RELLENO</p>
        <a class="primary-link" href="catalogo.php">Explorar el catálogo <span aria-hidden="true">→</span></a>
        <p class="welcome-note">
          <?= current_user() ? 'Hola, ' . e(current_user()['nombre']) . '.' : 'Para ver el catálogo completo necesitás iniciar sesión.' ?>
        </p>
      </div>
      <div class="book-still-life" aria-label="Ilustración de tres libros de colores" role="img">
        <div class="sun-shape"></div>
        <div class="still-book still-book-back"><span>LEER<br>ES<br>VIAJAR</span></div>
        <div class="still-book still-book-middle"><span>HISTORIAS<br>PARA<br>COMPARTIR</span></div>
        <div class="still-book still-book-front"><span>FUEGUITOS<br>DE PAPEL</span></div>
        <span class="spark spark-one" aria-hidden="true">✳</span>
        <span class="spark spark-two" aria-hidden="true">✳</span>
      </div>
    </section>

    <section class="reading-section" aria-labelledby="reading-title">
      <div class="page-width">
        <div class="section-topline">
          <div>
            <p class="kicker">TEXTO DE RELLENO</p>
            <h2 id="reading-title">CARRUSEL</h2>
          </div>
          <a class="text-link" href="catalogo.php">Ver todo el catálogo <span aria-hidden="true">→</span></a>
        </div>

        <?php if (!$slides): ?>
          <p class="text-muted">Todavía no hay libros cargados.</p>
        <?php else: ?>
        <!-- Carrusel Bootstrap; main.js lo hace avanzar solo cada 5 segundos -->
        <div id="booksCarousel" class="carousel slide fueguitos-carousel" aria-roledescription="carrusel" aria-label="Libros destacados">
          <div class="carousel-inner">
            <?php $n = 0; foreach ($slides as $i => $grupo): ?>
              <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                <div class="book-list">
                  <?php foreach ($grupo as $j => $b): $n++; ?>
                    <article class="book-item">
                      <?php if ($b['portada']): ?>
                        <div class="book-art book-art-img" style="background-image:url('uploads/<?= e($b['portada']) ?>')" aria-hidden="true"></div>
                      <?php else: ?>
                        <div class="book-art <?= $colores[$j % 4] ?>" aria-hidden="true">
                          <span><?= str_pad((string)$n, 2, '0', STR_PAD_LEFT) ?></span>
                          <b><?= e($b['titulo']) ?></b>
                          <i></i>
                        </div>
                      <?php endif; ?>
                      <p class="book-category"><?= e($b['categoria']) ?></p>
                      <h3><?= e($b['titulo']) ?></h3>
                      <p class="book-byline"><?= e($b['autor']) ?></p>
                    </article>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if (count($slides) > 1): ?>
          <div class="carousel-controls">
            <button class="carousel-btn" type="button" data-bs-target="#booksCarousel" data-bs-slide="prev" aria-label="Anterior">←</button>
            <div class="carousel-indicators-custom">
              <?php foreach ($slides as $i => $_): ?>
                <button type="button" data-bs-target="#booksCarousel" data-bs-slide-to="<?= $i ?>"
                        class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Diapositiva <?= $i + 1 ?>"
                        <?= $i === 0 ? 'aria-current="true"' : '' ?>></button>
              <?php endforeach; ?>
            </div>
            <button class="carousel-btn" type="button" data-bs-target="#booksCarousel" data-bs-slide="next" aria-label="Siguiente">→</button>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="project-section page-width" aria-labelledby="project-title">
      <div class="project-stamp" aria-hidden="true">TEXTO DE RELLENO<br></div>
      <div class="project-copy">
        <p class="kicker">Nuestro proyecto</p>
        <h2 id="project-title"><br>TEXTO DE RELLENO</h2>
        <p>TEXTO DE RELLENO</p>
        <a class="text-link" href="catalogo.php">Conoce nuestras lecturas <span aria-hidden="true">→</span></a>
      </div>
    </section>

    <section class="donation-section" id="donar" aria-labelledby="donation-title">
      <div class="page-width donation-layout">
        <div>
          <p class="kicker">TEXTO DE RELLENO</p>
          <h2 id="donation-title"><br>TEXTO DE RELLENO.</h2>
        </div>
        <div class="donation-copy">
          <p>TEXTO DE RELLENO</p>
          <a class="primary-link donate-btn" href="<?= e(DONATION_URL) ?>" target="_blank" rel="noopener noreferrer">
            Donar con billetera virtual <span aria-hidden="true">↗</span>
          </a>
          <p class="donation-note">TEXTO DE RELLENO</p>
        </div>
      </div>
    </section>
  </main>

<?php render_footer('catalogo.php', 'Explorar catálogo ↗');
