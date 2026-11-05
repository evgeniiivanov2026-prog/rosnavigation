<?php
declare(strict_types=1);
/** @var string $title */
/** @var string $description */
/** @var string $canonical */
/** @var string $page */
/** @var array|null $user */
/** @var string|null $holiday */
/** @var array|null $flash */
/** @var array $meta */
$nav = [
    'map' => ['/map.php', 'Карта'],
    'partners' => ['/partners.php', 'Партнёры'],
    'channel' => ['/channel.php', 'Эфир'],
];
?><!DOCTYPE html>
<html lang="ru"<?php if ($holiday): ?> data-holiday="<?= e($holiday) ?>"<?php endif; ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0A0E13">
  <meta name="color-scheme" content="dark light">
  <meta name="format-detection" content="telephone=no">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="ru_RU">
  <meta property="og:site_name" content="РосНавигация">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e(APP_URL) ?>/assets/og.png">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="apple-touch-icon" href="/favicon.svg">
  <link rel="preload" href="/fonts/unbounded-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/fonts/manrope-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
  <?php if ($page === 'map'): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= e(asset('/css/app.css')) ?>">
  <script>
    try {
      var theme = localStorage.getItem('rosnav-theme');
      if (theme === 'light' || theme === 'dark') document.documentElement.setAttribute('data-theme', theme);
    } catch (e) {}
  </script>
  <?php if (!empty($meta['jsonld'])): ?>
    <script type="application/ld+json"><?= json_encode($meta['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <?php endif; ?>
</head>
<body data-page="<?= e($page) ?>"<?= $user ? ' data-auth="1"' : '' ?>>
  <a class="skip" href="#main">К содержанию</a>
  <div class="progress" aria-hidden="true"></div>
  <header class="site-header">
    <div class="flagline" aria-hidden="true"></div>
    <div class="container header__bar">
      <a class="logo" href="/" aria-label="РосНавигация, на главную"><?= logo_html(true) ?></a>
      <input class="nav-check" id="nav-toggle" type="checkbox">
      <div class="header__tools">
        <button class="icon-btn" type="button" id="theme-toggle" aria-pressed="false" aria-label="Светлая тема">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.2v1.8M12 19v1.8M4.8 12H3M21 12h-1.8M6.1 6.1 4.8 4.8M19.2 19.2l-1.3-1.3M17.9 6.1l1.3-1.3M4.8 19.2l1.3-1.3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="12" cy="12" r="3.2" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
        </button>
        <label class="nav-toggle" for="nav-toggle">
          <span class="visually-hidden">Открыть меню</span>
          <span class="burger" aria-hidden="true"><span></span><span></span><span></span></span>
        </label>
      </div>
      <nav class="nav-panel" aria-label="Основная">
        <?php foreach ($nav as $key => $item): ?>
          <a href="<?= e($item[0]) ?>"<?= $page === $key ? ' aria-current="page"' : '' ?>><?= e($item[1]) ?></a>
        <?php endforeach; ?>
        <a href="/sos.php"<?= $page === 'sos' ? ' aria-current="page"' : '' ?>>Выйти на связь</a>
        <?php if ($user): ?>
          <a href="/profile.php"<?= $page === 'profile' ? ' aria-current="page"' : '' ?>><?= e($user['name']) ?></a>
          <form method="post" action="/auth.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="logout">
            <button class="btn btn--ghost btn--small" type="submit">Выйти</button>
          </form>
        <?php else: ?>
          <a class="btn btn--ghost btn--small" href="/auth.php">Встать на приём</a>
        <?php endif; ?>
        <a class="btn btn--danger btn--small nav-sos" href="/sos.php">Выйти на связь</a>
      </nav>
      <div class="header__actions">
        <a class="btn btn--danger" href="/sos.php">Выйти на связь</a>
        <?php if ($user): ?>
          <a class="btn btn--ghost" href="/profile.php">Кабина</a>
        <?php else: ?>
          <a class="btn btn--ghost" href="/auth.php">Встать на приём</a>
        <?php endif; ?>
      </div>
    </div>
  </header>
  <?php if ($holiday === 'victory'): ?>
    <p class="victory-note">9 мая. Помним тех, кто прошёл свою дорогу.</p>
  <?php endif; ?>
  <main id="main" tabindex="-1">
    <?php if ($flash): ?>
      <div class="container">
        <p class="banner banner--<?= e($flash['type']) ?>" role="status"><?= e($flash['text']) ?></p>
      </div>
    <?php endif; ?>
