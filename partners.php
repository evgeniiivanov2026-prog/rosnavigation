<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$filters = [
    'type' => (string) ($_GET['type'] ?? ''),
    'region' => (string) ($_GET['region'] ?? ''),
    'rating' => (string) ($_GET['rating'] ?? ''),
    'day' => !empty($_GET['day']),
];
if ($filters['type'] !== '' && !isset(catalog_partner_types()[$filters['type']])) {
    $filters['type'] = '';
}
$rating = in_array($filters['rating'], ['4', '4.5'], true) ? (float) $filters['rating'] : 0;
$partners = repo_partners([]);
$regions = repo_regions();
$visible = 0;
foreach ($partners as $partner) {
    $ok = ($filters['type'] === '' || $partner['type'] === $filters['type'])
        && ($filters['region'] === '' || $partner['region'] === $filters['region'])
        && ($rating <= 0 || (float) $partner['rating_avg'] >= $rating)
        && (!$filters['day'] || !empty($partner['works_24_7']));
    if ($ok) {
        $visible++;
    }
}

$page = [
    'title' => 'Партнёры на трассе — РосНавигация',
    'description' => 'Сервис, АЗС и ночлег, где встаёт фура. Телефон и маршрут у каждой карточки.',
    'path' => '/partners',
    'nav' => 'partners',
    'body' => 'page-partners',
    'script' => 'partners',
];
require __DIR__ . '/templates/header.php';
?>
<main id="main" class="section">
  <div class="container">
    <header class="section__head">
      <p class="kicker">Плечо</p>
      <h1>Куда свернуть</h1>
      <p class="lede">Места, где фура помещается, а ночью есть кому открыть.</p>
    </header>
    <form class="filters" id="partner-filter" method="get" action="/partners">
      <div class="field">
        <label for="f-type">Тип</label>
        <select id="f-type" name="type">
          <option value="">Все</option>
          <?php foreach (catalog_partner_types() as $key => $label): ?>
            <option value="<?= e($key) ?>"<?= $filters['type'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="f-region">Регион</label>
        <select id="f-region" name="region">
          <option value="">Все</option>
          <?php foreach ($regions as $region): ?>
            <option value="<?= e($region) ?>"<?= $filters['region'] === $region ? ' selected' : '' ?>><?= e($region) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="f-rating">Оценка</label>
        <select id="f-rating" name="rating">
          <option value="">Любая</option>
          <option value="4"<?= $filters['rating'] === '4' ? ' selected' : '' ?>>от 4</option>
          <option value="4.5"<?= $filters['rating'] === '4.5' ? ' selected' : '' ?>>от 4,5</option>
        </select>
      </div>
      <label class="btn btn--ghost">
        <input type="checkbox" name="day" value="1"<?= $filters['day'] ? ' checked' : '' ?>> Круглосуточно
      </label>
      <noscript><button class="btn btn--brand" type="submit">Показать</button></noscript>
    </form>
    <div class="catalog" id="partner-list">
      <div id="partner-empty" <?= $visible > 0 ? 'hidden' : '' ?>>
        <?= view_state('empty', 'На этом плече пусто', 'Смените фильтр — или заезжайте, когда откроется новое место.') ?>
      </div>
      <?php foreach ($partners as $partner) {
          $ok = ($filters['type'] === '' || $partner['type'] === $filters['type'])
              && ($filters['region'] === '' || $partner['region'] === $filters['region'])
              && ($rating <= 0 || (float) $partner['rating_avg'] >= $rating)
              && (!$filters['day'] || !empty($partner['works_24_7']));
          echo view_partner($partner, !$ok);
      } ?>
    </div>
  </div>
</main>
<?php require __DIR__ . '/templates/footer.php'; ?>
