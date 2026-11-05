<?php
declare(strict_types=1);
/** @var string $page */
/** @var bool $compact */
/** @var string $boot */
?>
  </main>
  <?php if (!$compact): ?>
  <footer class="site-footer">
    <div class="container footer__grid">
      <div>
        <a class="logo" href="/" aria-label="РосНавигация, на главную"><?= logo_html(false) ?></a>
        <p class="footer__lead">Всероссийская сеть взаимовыручки. Ты не один на трассе.</p>
      </div>
      <nav aria-label="Разделы">
        <p class="footer__title">Разделы</p>
        <a href="/sos.php">Выйти на связь</a>
        <a href="/map.php">Карта помощи</a>
        <a href="/partners.php">Партнёры</a>
        <a href="/channel.php">Эфир</a>
        <a href="/auth.php">Занять место в колонне</a>
      </nav>
      <div>
        <p class="footer__title">Контакты</p>
        <a href="mailto:<?= e(APP_EMAIL) ?>"><?= e(APP_EMAIL) ?></a>
        <a href="<?= e(APP_FORUM) ?>">Форум рейсов</a>
        <p class="fine">На телефоне откройте меню браузера и выберите «На экран домой» — сайт встанет как программа и откроется без адреса.</p>
      </div>
    </div>
    <div class="container footer__bar">
      <p>© <span id="year"><?= date('Y') ?></span> РосНавигация. Не служба спасения: если жизнь под угрозой — 112.</p>
      <p>Сделано с уважением к 15-му каналу.</p>
    </div>
    <?php if (repo_mode() !== 'mysql'): ?>
      <div class="container">
        <p class="fine footer__mode">Эфир пока хранится в файле на сервере. Карточки с пометкой «образец» — не сводка и не выезд.</p>
      </div>
    <?php endif; ?>
  </footer>
  <?php endif; ?>
  <nav class="dock" aria-label="Короткий путь">
    <a href="/map.php"<?= $page === 'map' ? ' aria-current="page"' : '' ?>>
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6.5 9 4.5l6 2 5-2v13l-5 2-6-2-5 2v-13Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 4.5v13M15 6.5v13" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
      <span>Карта</span>
    </a>
    <a class="dock__sos" href="/sos.php"<?= $page === 'sos' ? ' aria-current="page"' : '' ?>>
      <span>SOS</span>
    </a>
    <a href="/channel.php"<?= $page === 'channel' ? ' aria-current="page"' : '' ?>>
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 9.5a7 7 0 0 1 14 0c0 4.2 1.2 6 1.2 6H3.8S5 13.7 5 9.5Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M9.5 18.5a2.5 2.5 0 0 0 5 0" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      <span>Эфир</span>
    </a>
    <a href="<?= $page === 'profile' || !empty(current_user()) ? '/profile.php' : '/auth.php' ?>"<?= $page === 'profile' || $page === 'auth' ? ' aria-current="page"' : '' ?>>
      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M5.5 19.2c1.4-2.6 3.6-3.7 6.5-3.7s5.1 1.1 6.5 3.7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      <span>Кабина</span>
    </a>
  </nav>
  <div class="toasts" id="toasts" aria-live="polite"></div>
  <?= $boot ?>
  <script type="module" src="<?= e(asset('/js/main.js')) ?>"></script>
  <?php if ($page === 'map'): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <?php endif; ?>
</body>
</html>
