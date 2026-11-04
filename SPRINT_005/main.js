// Carrusel de la página de inicio: avanza solo cada 5 segundos.
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('booksCarousel');
  if (el && window.bootstrap) {
    var carousel = new bootstrap.Carousel(el, { interval: false, ride: false, wrap: true });
    var timer = null;

    function start() {
      stop();
      timer = setInterval(function () { carousel.next(); }, 2500);
    }
    function stop() {
      if (timer) { clearInterval(timer); timer = null; }
    }

    // Pausa al pasar el mouse o enfocar, para poder leer tranquilo
    el.addEventListener('mouseenter', stop);
    el.addEventListener('mouseleave', start);
    el.addEventListener('focusin', stop);
    el.addEventListener('focusout', start);

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduce) start();
  }

  // Pestañas de login / registro / recuperar
  var tabs = document.querySelectorAll('.auth-tab');
  if (tabs.length) {
    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () { switchTab(tab.dataset.tab); });
    });
    document.querySelectorAll('[data-goto]').forEach(function (a) {
      a.addEventListener('click', function (ev) { ev.preventDefault(); switchTab(a.dataset.goto); });
    });
  }
});

function switchTab(tabId) {
  document.querySelectorAll('.auth-form').forEach(function (f) { f.classList.remove('active'); });
  document.querySelectorAll('.auth-tab').forEach(function (t) {
    var on = t.dataset.tab === tabId;
    t.classList.toggle('active', on);
    t.setAttribute('aria-selected', on ? 'true' : 'false');
  });
  var form = document.getElementById(tabId);
  if (form) form.classList.add('active');
}
