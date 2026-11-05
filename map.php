<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$viewer = current_user();
$signals = repo_sos_near(null, null, null);
$helpers = repo_helpers_near(null, null, null);
$partners = repo_partners();
$boot = [
    'signals' => $signals,
    'helpers' => $helpers,
    'partners' => $partners,
    'me' => $viewer ? ['id' => $viewer['id'], 'name' => $viewer['name']] : null,
];

$page = [
    'title' => 'Карта помощи — РосНавигация',
    'description' => 'Кто на приёме, кто на маршруте и кому нужна помощь. Тёмная карта трасс России.',
    'path' => '/map',
    'nav' => 'map',
    'body' => 'page-map',
    'script' => 'map',
    'leaflet' => true,
    'bare' => true,
];
require __DIR__ . '/templates/header.php';
?>
<main id="main" class="map-app">
  <div id="map" role="application" aria-label="Карта помощи"></div>
  <div class="map-ui">
    <div class="chips" role="toolbar" aria-label="Фильтры">
      <button class="chip is-on" type="button" data-filter="all" aria-pressed="true">Все</button>
      <button class="chip" type="button" data-filter="help" aria-pressed="false">Нужна помощь</button>
      <button class="chip" type="button" data-filter="air" aria-pressed="false">На приёме</button>
      <button class="chip" type="button" data-filter="route" aria-pressed="false">На маршруте</button>
      <button class="chip" type="button" data-filter="partner" aria-pressed="false">Партнёры</button>
    </div>
    <div class="chips" aria-label="Радиус">
      <button class="chip" type="button" data-radius="15">15 км</button>
      <button class="chip" type="button" data-radius="50">50 км</button>
      <button class="chip" type="button" data-radius="200">200 км</button>
      <button class="chip is-on" type="button" data-radius="0" aria-pressed="true">Вся сеть</button>
    </div>
    <button class="btn btn--brand" type="button" id="locate">Где я</button>
    <p class="banner net-banner" id="net-banner" hidden>Связь потеряна. Показываю последние данные.</p>
  </div>
  <section class="sheet is-closed" id="sheet" aria-label="Список на карте">
    <button class="sheet__grip" type="button" id="sheet-toggle" aria-expanded="false">Список · <?= count($signals) ?> сигналов</button>
    <div class="sheet__body" id="sheet-body">
      <?php if (!$signals && !$helpers): ?>
        <?= view_state('empty', 'Рядом тихо', 'Это хорошая новость. Если встанете — сигнал увидят отсюда.') ?>
      <?php endif; ?>
      <?php foreach ($signals as $signal) {
          echo view_signal($signal);
      } ?>
      <noscript><p>Карта включается со скриптом. Список сигналов выше уже на странице.</p></noscript>
    </div>
  </section>
  <script type="application/json" id="map-boot" nonce="<?= e(csp_nonce()) ?>"><?= json_encode($boot, json_flags_html()) ?></script>
</main>
<?php require __DIR__ . '/templates/footer.php'; ?>
