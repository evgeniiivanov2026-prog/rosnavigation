<?php
declare(strict_types=1);
/** @var array<string, mixed> $page */
$nav = (string) ($page['nav'] ?? '');
$year = date('Y');
$hideFooter = !empty($page['bare']);
?>
<?php if (!$hideFooter): ?>
<footer class="site-footer" id="contacts">
  <div class="container footer__grid">
    <div>
      <a class="logo" href="/" aria-label="РосНавигация, на главную">
        <?= logo_mark() ?>
        <span class="logo__text">
          <span class="logo__word">РосНавигация</span>
          <span class="logo__sub">На 15-м</span>
        </span>
      </a>
      <p class="footer__lead">Всероссийская сеть взаимовыручки дальнобойщиков. Своих не бросаем.</p>
    </div>
    <nav aria-label="Разделы">
      <p class="footer__title">На линии</p>
      <a href="/sos">Выйти на связь</a>
      <a href="/map">Карта помощи</a>
      <a href="/channel">Эфир</a>
      <a href="/partners">Партнёры</a>
      <a href="/auth">Занять место в колонне</a>
      <a href="<?= e(APP_FORUM) ?>">Форум</a>
    </nav>
    <div>
      <p class="footer__title">Контакты</p>
      <a href="tel:112">112 — если угроза жизни</a>
      <a href="mailto:<?= e(APP_EMAIL) ?>"><?= e(APP_EMAIL) ?></a>
      <a href="/#rules">Короткие правила</a>
      <button class="text-btn" type="button" data-install hidden>Установить приложение</button>
    </div>
  </div>
  <div class="container footer__bar">
    <p>© <?= e($year) ?> РосНавигация. Сигнал на сайте не заменяет скорую, ГИБДД и 112.</p>
    <p>Сделано с уважением к 15-му каналу.</p>
  </div>
</footer>
<?php endif; ?>

<nav class="dock" aria-label="Короткий путь">
  <a href="/map"<?= $nav === 'map' ? ' aria-current="page"' : '' ?>><?= icon('pin', 'icon') ?><span>Карта</span></a>
  <a href="/channel"<?= $nav === 'channel' ? ' aria-current="page"' : '' ?>><?= icon('radio', 'icon') ?><span>Эфир</span></a>
  <a class="dock__sos" href="/sos"<?= $nav === 'sos' ? ' aria-current="page"' : '' ?>><span>SOS</span></a>
  <a href="/partners"<?= $nav === 'partners' ? ' aria-current="page"' : '' ?>><?= icon('wrench', 'icon') ?><span>Сервис</span></a>
  <a href="/profile"<?= $nav === 'profile' ? ' aria-current="page"' : '' ?>><?= icon('user', 'icon') ?><span>Я</span></a>
</nav>

<div class="toasts" id="toasts" aria-live="polite"></div>
<?php if (!empty($page['leaflet'])): ?>
<script src="/assets/vendor/leaflet/leaflet.js"></script>
<?php endif; ?>
<script type="module" src="/js/main.js"></script>
</body>
</html>
