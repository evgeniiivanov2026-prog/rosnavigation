<?php
declare(strict_types=1);
/** @var array<string, mixed> $page */
$title = (string) ($page['title'] ?? APP_NAME);
$description = (string) ($page['description'] ?? '');
$path = (string) ($page['path'] ?? '/');
$canonical = rtrim(APP_URL, '/') . $path;
$robots = (string) ($page['robots'] ?? 'index,follow');
$ogImage = rtrim(APP_URL, '/') . '/assets/img/og.png';
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0A0E13">
<meta name="color-scheme" content="dark">
<meta name="format-detection" content="telephone=no">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="robots" content="<?= e($robots) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="ru_RU">
<meta property="og:site_name" content="РосНавигация">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/assets/img/icon-180.png">
<link rel="manifest" href="/manifest.webmanifest">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<link rel="preload" href="/fonts/unbounded-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/manrope-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
<?php if (!empty($page['leaflet'])): ?>
<link rel="stylesheet" href="/assets/vendor/leaflet/leaflet.css">
<?php endif; ?>
<link rel="stylesheet" href="/css/tokens.css">
<link rel="stylesheet" href="/css/base.css">
<link rel="stylesheet" href="/css/components.css">
<link rel="stylesheet" href="/css/pages.css">
<link rel="stylesheet" href="/css/holidays.css">
<style>
  body{margin:0;background:#0A0E13;color:#F2F5F8}
  .site-header{position:sticky;top:0;z-index:40}
</style>
<script nonce="<?= e(csp_nonce()) ?>">
  try {
    var theme = localStorage.getItem('rosnav-theme');
    if (theme === 'light' || theme === 'dark') document.documentElement.setAttribute('data-theme', theme);
  } catch (error) {}
</script>
<?php if (!empty($page['jsonld'])): ?>
<script type="application/ld+json" nonce="<?= e(csp_nonce()) ?>"><?= json_encode($page['jsonld'], json_flags_html()) ?></script>
<?php endif; ?>
