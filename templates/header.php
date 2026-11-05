<?php
declare(strict_types=1);

require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/ui.php';

$pageTitle = $pageTitle ?? 'РосНавигация';
$pageDescription = $pageDescription ?? 'Всероссийская сеть взаимовыручки дальнобойщиков. Ты не один на трассе.';
$pageCanonical = $pageCanonical ?? APP_URL . '/';
$pageActive = $pageActive ?? '';
$bodyClass = trim('page ' . ($bodyClass ?? ''));
$me = current_user();
$mePublic = $me ? public_user($me, true) : null;
$flash = flash_get();
$holiday = date('m-d') === '05-09' ? ' holiday-victory' : '';
$nonce = csp_nonce();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0A0E13">
  <meta name="color-scheme" content="dark light">
  <meta name="format-detection" content="telephone=no">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <link rel="canonical" href="<?= e($pageCanonical) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="ru_RU">
  <meta property="og:site_name" content="РосНавигация">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:url" content="<?= e($pageCanonical) ?>">
  <meta property="og:image" content="<?= e(APP_URL) ?>/assets/img/og.png">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="preload" href="/fonts/unbounded-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/fonts/manrope-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
  <script nonce="<?= e($nonce) ?>">
    (function () {
      try {
        if (localStorage.getItem("rn-theme") === "light") {
          document.documentElement.setAttribute("data-theme", "light");
          var meta = document.querySelector('meta[name="theme-color"]');
          if (meta) meta.content = "#F4F6F8";
        }
      } catch (e) {}
      document.documentElement.classList.add("js");
    })();
  </script>
  <link rel="stylesheet" href="/css/app.css">
  <link rel="stylesheet" href="/css/holidays.css">
  <?php if (!empty($pageHead)) {
      echo $pageHead;
  } ?>
  <?php if (!empty($jsonLd)): ?>
    <script type="application/ld+json" nonce="<?= e($nonce) ?>"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <?php endif; ?>
</head>
<body class="<?= e($bodyClass . $holiday) ?>" data-user="<?= $me ? (int) $me['id'] : '' ?>" data-demo="<?= DEMO_MODE ? '1' : '0' ?>">
  <a class="skip" href="#main">К содержанию</a>
  <div class="flagline" aria-hidden="true"><span></span><span></span><span></span></div>
  <header class="site-header">
    <div class="container header__bar">
      <a class="logo" href="/" aria-label="РосНавигация, на главную">
        <?= logo_mark() ?>
        <span class="logo__text">
          <span class="logo__name">РосНавигация</span>
          <span class="logo__sub">На 15-м</span>
        </span>
      </a>
      <nav class="nav" aria-label="Основная">
        <a class="nav__link<?= $pageActive === 'map' ? ' is-active' : '' ?>" href="/map"<?= $pageActive === 'map' ? ' aria-current="page"' : '' ?>>Карта</a>
        <a class="nav__link<?= $pageActive === 'partners' ? ' is-active' : '' ?>" href="/partners"<?= $pageActive === 'partners' ? ' aria-current="page"' : '' ?>>Партнёры</a>
        <a class="nav__link<?= $pageActive === 'channel' ? ' is-active' : '' ?>" href="/channel"<?= $pageActive === 'channel' ? ' aria-current="page"' : '' ?>>Эфир</a>
        <?php if ($mePublic): ?>
          <a class="nav__link nav__account<?= $pageActive === 'profile' ? ' is-active' : '' ?>" href="/profile"><?= e($mePublic['name']) ?></a>
        <?php else: ?>
          <a class="nav__link nav__account" href="/auth">Войти</a>
        <?php endif; ?>
      </nav>
      <div class="header__tools">
        <button class="icon-btn" id="theme-toggle" type="button" aria-pressed="false" aria-label="Светлая тема">
          <?= icon('sun') ?>
        </button>
        <a class="btn btn--danger btn--header" href="/sos">
          <span class="btn__full">Выйти на связь</span>
          <span class="btn__short">SOS</span>
        </a>
      </div>
    </div>
  </header>
  <?php if (DEMO_MODE): ?>
    <p class="demo-banner" role="note">Учебный эфир. Люди, сигналы и партнёры вымышлены — так видна сеть, пока не подключена своя база.</p>
  <?php endif; ?>
  <div class="toaster" id="toaster" aria-live="polite">
    <?php if ($flash): ?>
      <p class="toast toast--<?= e($flash['type'] === 'error' ? 'error' : 'ok') ?>"><?= e($flash['text']) ?></p>
    <?php endif; ?>
  </div>
  <main id="main" tabindex="-1">
