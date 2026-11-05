<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$GLOBALS['page'] = 'partners';
$query = [
    'type' => (string) ($_GET['type'] ?? ''),
    'city' => (string) ($_GET['city'] ?? ''),
    'day' => $_GET['day'] ?? '',
    'rating' => $_GET['rating'] ?? '',
    'q' => (string) ($_GET['q'] ?? ''),
    'lat' => $_GET['lat'] ?? null,
    'lng' => $_GET['lng'] ?? null,
];
$partners = repo_partners($query);
$cities = repo_partner_cities();
$types = [
    '' => 'Все',
    'sto' => 'СТО',
    'fuel' => 'АЗС',
    'hotel' => 'Ночлег',
    'insurance' => 'Страховая',
    'lawyer' => 'Юрист',
    'shop' => 'Магазин',
];
$meta = [
    'title' => 'Партнёры на трассе — РосНавигация',
    'description' => 'СТО, АЗС и ночлег для фуры. Карточка показывает, кто работает ночью и от какой цены.',
    'canonical' => APP_URL . '/partners.php',
];
render_header($meta);
?>
    <section class="section container">
      <p class="kicker">Каталог</p>
      <h1>Плечо, которое не уезжает</h1>
      <p class="lede">Образец карточки помечен. Телефон и маршрут появляются у живого партнёра, когда договор уже есть.</p>
      <form class="filters" method="get" action="/partners.php">
        <label class="field"><span class="visually-hidden">Поиск</span><input name="q" value="<?= e($query['q']) ?>" placeholder="Имя или город"></label>
        <label class="field"><span class="visually-hidden">Город</span>
          <select name="city">
            <option value="">Все города</option>
            <?php foreach ($cities as $city): ?>
              <option value="<?= e($city) ?>" <?= $query['city'] === $city ? 'selected' : '' ?>><?= e($city) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span class="visually-hidden">Оценка</span>
          <select name="rating">
            <option value="">Любая оценка</option>
            <option value="4" <?= (string) $query['rating'] === '4' ? 'selected' : '' ?>>от 4</option>
            <option value="4.5" <?= (string) $query['rating'] === '4.5' ? 'selected' : '' ?>>от 4,5</option>
          </select>
        </label>
        <label class="switch"><input type="checkbox" name="day" value="1" <?= !empty($query['day']) ? 'checked' : '' ?>> Круглосуточно</label>
        <button class="btn" type="submit">Показать</button>
      </form>
      <div class="filters">
        <?php foreach ($types as $value => $label):
            $href = '/partners.php?type=' . rawurlencode($value);
            ?>
          <a class="chip<?= $query['type'] === $value ? ' is-on' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </div>
      <?php if (!$partners): ?>
        <p class="empty">Никого не нашли. Снимите фильтр или посмотрите весь список.</p>
      <?php endif; ?>
      <div class="cards">
        <?php foreach ($partners as $partner): ?>
          <article class="partner">
            <div class="partner__top">
              <span class="tag"><?= e($partner['type_label']) ?></span>
              <?php if ($partner['demo']): ?><span class="sample">Образец</span><?php endif; ?>
              <?php if ($partner['works_24_7']): ?><span class="tag">24/7</span><?php endif; ?>
            </div>
            <h2><?= e($partner['name']) ?></h2>
            <p><?= e($partner['city']) ?><?= $partner['address'] ? ' · ' . e($partner['address']) : '' ?></p>
            <p><?= e($partner['description']) ?></p>
            <p class="meta">
              <?= $partner['rating_count'] ? e(number_format((float) $partner['rating_avg'], 1, ',', '')) . ' · ' . (int) $partner['rating_count'] . ' ' . e(plural((int) $partner['rating_count'], 'оценка', 'оценки', 'оценок')) : 'Пока без оценок' ?>
              <?= $partner['price_from'] ? ' · ' . e(rub_label((int) $partner['price_from'])) : '' ?>
              <?= isset($partner['distance_km']) ? ' · ' . e(km_label((float) $partner['distance_km'])) : '' ?>
            </p>
            <div class="actions">
              <?php if ($partner['phone']): ?>
                <a class="btn" href="tel:<?= e(preg_replace('/\s+/', '', (string) $partner['phone'])) ?>">Позвонить</a>
              <?php else: ?>
                <p class="meta">Телефон появится у живого партнёра</p>
              <?php endif; ?>
              <?php if ($partner['lat'] !== null && !$partner['demo']): ?>
                <a class="btn btn--ghost" href="https://yandex.ru/maps/?pt=<?= e((string) $partner['lng']) ?>,<?= e((string) $partner['lat']) ?>&z=14&l=map">Маршрут</a>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
<?php
render_footer($meta);
