<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/forms.php';

handle_sos_post('/map');

$me = current_user();
$viewer = $me ? (int) $me['id'] : 0;
$signals = repo_signals();
$drivers = repo_drivers();
$pageTitle = 'Карта помощи — РосНавигация';
$pageDescription = 'Свои на трассе: кто на приёме, кто на маршруте и кому нужна помощь.';
$pageCanonical = APP_URL . '/map';
$pageActive = 'map';
$bodyClass = 'page-map';
$pageClassic = '/assets/vendor/leaflet/leaflet.js';
$pageHead = '<link rel="stylesheet" href="/assets/vendor/leaflet/leaflet.css">';
$pageScript = '/js/pages/map.js';

require __DIR__ . '/templates/header.php';
?>
<div class="map-app">
  <div class="map-toolbar">
    <div class="chip-row" id="map-filters">
      <button class="chip is-on" type="button" data-filter="all">Все</button>
      <button class="chip" type="button" data-filter="sos">Сигналы</button>
      <button class="chip" type="button" data-filter="on_air">На приёме</button>
      <button class="chip" type="button" data-filter="on_route">На маршруте</button>
      <button class="chip" type="button" data-filter="r15">15 км</button>
      <button class="chip" type="button" data-filter="r50">50 км</button>
      <button class="chip" type="button" data-filter="r0">Вся страна</button>
    </div>
    <p class="fine">Пока карта открыта и ты на приёме, свои видят точку. Это не навигатор для фуры.</p>
    <p class="offline-note" id="offline-note" hidden>Связь потеряна. Показываю последние данные.</p>
  </div>
  <div class="map-body">
    <div id="help-map" class="map-canvas" role="application" aria-label="Карта помощи"></div>
    <aside class="sheet" id="sheet" aria-label="Кто рядом">
      <div id="map-card"></div>
      <div class="stack" id="map-list">
        <?php if (!$signals && !$drivers): ?>
          <?= render_empty('На этом плече тихо', 'Сигналов нет. Если встал сам — выйди на связь.') ?>
        <?php endif; ?>
        <?php foreach ($signals as $signal): ?>
          <?= render_signal($signal, $viewer, '/map') ?>
        <?php endforeach; ?>
        <?php foreach ($drivers as $driver): ?>
          <article class="signal" id="driver-<?= (int) $driver['id'] ?>">
            <div class="signal__top">
              <span class="eyebrow"><?= e($driver['route'] ?: 'Трасса') ?></span>
              <?= render_status($driver['status']) ?>
            </div>
            <h3><?= e($driver['name']) ?></h3>
            <p><?= e(trim($driver['city'] . ' · ' . $driver['truck_brand'], ' ·')) ?></p>
            <p class="signal__who"><a href="/profile/<?= (int) $driver['id'] ?>">Профиль</a></p>
          </article>
        <?php endforeach; ?>
      </div>
    </aside>
  </div>
</div>
<?php require __DIR__ . '/templates/footer.php';