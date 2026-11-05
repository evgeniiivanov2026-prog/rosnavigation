<?php
declare(strict_types=1);
$me = $me ?? current_user();
$year = date('Y');
?>
  </main>
  <?php if (($pageActive ?? '') !== 'map'): ?>
  <footer class="site-footer">
    <div class="container footer__grid">
      <div>
        <a class="logo" href="/"><?= logo_mark() ?><span class="logo__name">РосНавигация</span></a>
        <p class="footer__lead">Свои на трассе. Своих не бросаем.</p>
        <p class="fine">Сделано с уважением к 15-му каналу.</p>
      </div>
      <nav aria-label="Разделы">
        <p class="footer__title">В эфире</p>
        <a href="/sos">Выйти на связь</a>
        <a href="/map">Карта помощи</a>
        <a href="/partners">Партнёры</a>
        <a href="/channel">Эфир</a>
        <a href="/auth">Встать на приём</a>
      </nav>
      <div>
        <p class="footer__title">Контакты</p>
        <a href="mailto:info@rosnavigation.ru">info@rosnavigation.ru</a>
        <a href="https://forum.rosnavigation.ru" target="_blank" rel="noopener noreferrer">Форум братства</a>
        <button class="text-btn" id="install-app" type="button" hidden>Поставить на экран</button>
      </div>
    </div>
    <div class="container footer__bar">
      <p>© <?= e($year) ?> РосНавигация</p>
      <p>Не служба спасения. Если угроза жизни — 112.</p>
    </div>
  </footer>
  <?php endif; ?>
  <nav class="tabbar" aria-label="Короткий путь">
    <a class="<?= ($pageActive ?? '') === 'map' ? 'is-active' : '' ?>" href="/map"<?= ($pageActive ?? '') === 'map' ? ' aria-current="page"' : '' ?>><?= icon('map') ?><span>Карта</span></a>
    <a class="<?= ($pageActive ?? '') === 'channel' ? 'is-active' : '' ?>" href="/channel"<?= ($pageActive ?? '') === 'channel' ? ' aria-current="page"' : '' ?>><?= icon('radio') ?><span>Эфир</span></a>
    <a class="tabbar__sos" href="/sos"><?= icon('alert') ?><span>SOS</span></a>
    <a class="<?= ($pageActive ?? '') === 'partners' ? 'is-active' : '' ?>" href="/partners"<?= ($pageActive ?? '') === 'partners' ? ' aria-current="page"' : '' ?>><?= icon('wrench') ?><span>Сервис</span></a>
    <a class="<?= ($pageActive ?? '') === 'profile' ? 'is-active' : '' ?>" href="<?= $me ? '/profile' : '/auth' ?>"<?= ($pageActive ?? '') === 'profile' ? ' aria-current="page"' : '' ?>><?= icon('user') ?><span><?= $me ? 'Я' : 'Вход' ?></span></a>
  </nav>
  <?php if (!empty($pageClassic)): ?>
    <script src="<?= e($pageClassic) ?>"></script>
  <?php endif; ?>
  <script type="module" src="/js/main.js"></script>
  <?php if (!empty($pageScript)): ?>
    <script type="module" src="<?= e($pageScript) ?>"></script>
  <?php endif; ?>
</body>
</html>
