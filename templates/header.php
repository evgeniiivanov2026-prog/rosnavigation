<?php
declare(strict_types=1);
/** @var array<string, mixed> $page */
$nav = (string) ($page['nav'] ?? '');
$viewer = current_user();
$script = (string) ($page['script'] ?? '');
$body = trim('page ' . (string) ($page['body'] ?? ''));

$links = [
    'map' => ['/map', 'Карта'],
    'channel' => ['/channel', '15-й канал'],
    'partners' => ['/partners', 'Партнёры'],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<?php require __DIR__ . '/meta.php'; ?>
</head>
<body class="<?= e($body) ?>" data-page="<?= e($script) ?>" data-email="<?= e(APP_EMAIL) ?>" data-forum="<?= e(APP_FORUM) ?>">
<a class="skip" href="#main">К содержанию</a>
<header class="site-header" id="top">
  <div class="flagbar" aria-hidden="true"><i></i><i></i><i></i></div>
  <div class="container header__bar">
    <a class="logo" href="/" aria-label="РосНавигация, на главную">
      <?= logo_mark() ?>
      <span class="logo__text">
        <span class="logo__word">РосНавигация</span>
        <span class="logo__sub">На 15-м</span>
      </span>
    </a>

    <input class="nav-check" id="nav-toggle" type="checkbox">
    <label class="nav-toggle" for="nav-toggle">
      <span class="visually-hidden">Открыть меню</span>
      <?= icon('menu', 'icon nav-toggle__open') ?>
      <?= icon('close', 'icon nav-toggle__close') ?>
    </label>

    <nav class="nav-panel" aria-label="Основная">
      <?php foreach ($links as $id => $item): ?>
        <a href="<?= e($item[0]) ?>"<?= $nav === $id ? ' aria-current="page"' : '' ?>><?= e($item[1]) ?></a>
      <?php endforeach; ?>
      <a class="nav-sos" href="/sos"<?= $nav === 'sos' ? ' aria-current="page"' : '' ?>>Найти помощь</a>
      <?php if ($viewer): ?>
        <a href="/profile"<?= $nav === 'profile' ? ' aria-current="page"' : '' ?>><?= e($viewer['name']) ?></a>
        <form class="nav-logout" method="post" action="/auth">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="logout">
          <button class="text-btn" type="submit">Выйти</button>
        </form>
      <?php else: ?>
        <a class="btn btn--brand btn--menu" href="/auth">Стать частью</a>
      <?php endif; ?>
    </nav>

    <div class="header__tools">
      <button class="icon-btn" type="button" data-theme-toggle aria-pressed="false" aria-label="Светлая тема">
        <?= icon('sun', 'icon icon--theme-sun') ?>
        <?= icon('moon', 'icon icon--theme-moon') ?>
      </button>
      <a class="btn btn--danger btn--header" href="/sos">Найти помощь</a>
      <?php if ($viewer): ?>
        <a class="btn btn--ghost btn--header" href="/profile"><?= e($viewer['name']) ?></a>
      <?php else: ?>
        <a class="btn btn--brand btn--header" href="/auth">Стать частью</a>
      <?php endif; ?>
    </div>
  </div>
</header>
