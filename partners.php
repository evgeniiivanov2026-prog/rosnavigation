<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'type' => (string) ($_GET['type'] ?? ''),
    'city' => (string) ($_GET['city'] ?? ''),
    'day' => !empty($_GET['day']) ? '1' : '',
    'rating' => (string) ($_GET['rating'] ?? ''),
];
if (!isset(partner_types()[$filters['type']])) {
    $filters['type'] = '';
}
$cities = repo_partner_cities();
if (!in_array($filters['city'], $cities, true)) {
    $filters['city'] = '';
}
$partners = repo_partners([
    'q' => $filters['q'],
    'type' => $filters['type'],
    'city' => $filters['city'],
    'day' => $filters['day'],
    'rating' => $filters['rating'],
]);
$pageTitle = 'Партнёры на трассе — РосНавигация';
$pageDescription = 'Сервис, АЗС и ночлег, куда встаёт фура. Фильтр по типу, городу и круглосуточной работе.';
$pageCanonical = APP_URL . '/partners';
$pageActive = 'partners';
$bodyClass = 'page-partners';
$pageScript = '/js/pages/partners.js';

require __DIR__ . '/templates/header.php';
?>
<div class="container section">
  <header class="section__head">
    <p class="kicker">Плечо</p>
    <h1>Куда можно встать</h1>
    <p class="lede">Сервис, колонка, ночлег. Телефон появляется только у проверенного места.</p>
  </header>
  <form class="filters" id="partner-filter" method="get" action="/partners">
    <label class="field">
      <span>Поиск</span>
      <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Город, трасса, душ" maxlength="80">
    </label>
    <label class="field">
      <span>Тип</span>
      <select name="type">
        <option value="">Все</option>
        <?php foreach (partner_types() as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= $filters['type'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field">
      <span>Город</span>
      <select name="city">
        <option value="">Все</option>
        <?php foreach ($cities as $city): ?>
          <option value="<?= e($city) ?>"<?= $filters['city'] === $city ? ' selected' : '' ?>><?= e($city) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="field">
      <span>Оценка</span>
      <select name="rating">
        <option value="">Любая</option>
        <option value="4"<?= $filters['rating'] === '4' ? ' selected' : '' ?>>от 4</option>
        <option value="4.5"<?= $filters['rating'] === '4.5' ? ' selected' : '' ?>>от 4,5</option>
      </select>
    </label>
    <label class="choice">
      <input type="checkbox" name="day" value="1"<?= $filters['day'] ? ' checked' : '' ?>>
      <span>Круглосуточно</span>
    </label>
    <button class="btn btn--primary" type="submit">Показать</button>
  </form>
  <div class="catalog catalog--partners" id="partner-list" aria-live="polite">
    <?php if (!$partners): ?>
      <?= render_empty('Пусто на этом плече', 'Сними фильтр или поищи соседний город.') ?>
    <?php endif; ?>
    <?php foreach ($partners as $partner): ?>
      <?= render_partner($partner) ?>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/templates/footer.php';