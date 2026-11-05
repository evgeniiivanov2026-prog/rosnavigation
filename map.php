<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$GLOBALS['page'] = 'map';
$user = current_user();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    assert_same_origin();
    csrf_check();
    if (!$user) {
        flash('error', 'Чтобы принять сигнал, займите место в колонне.');
        redirect('/auth.php?next=' . rawurlencode('/map.php'));
    }
    $id = (int) ($_POST['id'] ?? 0);
    handle(
        function () use ($user, $id) {
            repo_sos_accept($id, (int) $user['id']);
            flash('ok', 'Вы приняли сигнал. Номер открыт, можно звонить.');
            return true;
        },
        '/sos.php?id=' . $id,
        '/map.php'
    );
}
$lat = numeric_or_null($_GET['lat'] ?? null);
$lng = numeric_or_null($_GET['lng'] ?? null);
$radius = is_numeric($_GET['radius'] ?? null) ? (float) $_GET['radius'] : (float) HELP_RADIUS_KM;
if (!in_array($radius, [15.0, 50.0, 150.0, 1500.0], true)) {
    $radius = (float) HELP_RADIUS_KM;
}
$type = (string) ($_GET['type'] ?? '');
$allowedTypes = ['', 'breakdown', 'fuel', 'sleep', 'accident', 'medical', 'other'];
if (!in_array($type, $allowedTypes, true)) {
    $type = '';
}
$signals = repo_sos_near($lat, $lng, $radius, $user['id'] ?? null);
if ($type !== '') {
    $signals = array_values(array_filter($signals, static fn (array $row) => $row['type'] === $type));
}
$helpers = ($lat !== null && $lng !== null)
    ? repo_helpers_near($lat, $lng, $radius, $user['id'] ?? null)
    : repo_on_air(8);
$GLOBALS['boot_extra'] = [
    'pollMs' => 15000,
    'map' => [
        'lat' => $lat,
        'lng' => $lng,
        'radius' => $radius,
        'type' => $type,
        'signals' => $signals,
        'helpers' => $helpers,
    ],
];
$meta = [
    'title' => 'Карта помощи — РосНавигация',
    'description' => 'Свои на плече и открытые сигналы на трассах России.',
    'canonical' => APP_URL . '/map.php',
    'chrome' => 'map',
];
render_header($meta);
?>
    <div class="map-shell">
      <div id="map" class="map-canvas" role="region" aria-label="Карта">
        <p class="meta">Если карта не открылась, список ниже работает без неё.</p>
      </div>
      <aside class="map-side">
        <p class="net-banner" id="offline-banner" hidden>Связь потеряна. Показываю последние данные.</p>
        <div class="row-between">
          <h1>Карта помощи</h1>
          <button class="btn btn--ghost btn--small" type="button" id="geo-btn">Где я</button>
        </div>
        <p class="meta">Учебные метки перечёркнуты. На них не едут.</p>
        <div class="filters" aria-label="Радиус">
          <?php foreach ([15 => '15 км', 50 => '50 км', 150 => '150 км', 1500 => 'Страна'] as $value => $label):
              $href = '/map.php?radius=' . $value . ($type !== '' ? '&type=' . rawurlencode($type) : '') . ($lat !== null ? '&lat=' . $lat . '&lng=' . $lng : '');
              ?>
            <a class="chip<?= (int) $radius === (int) $value ? ' is-on' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </div>
        <div class="filters" aria-label="Тип сигнала">
          <?php
          $typeLinks = ['' => 'Все', 'breakdown' => 'Встал', 'fuel' => 'Обсох', 'sleep' => 'Притомился', 'accident' => 'ДТП', 'medical' => 'Медицина'];
          foreach ($typeLinks as $value => $label):
              $href = '/map.php?radius=' . (int) $radius . ($value !== '' ? '&type=' . rawurlencode($value) : '') . ($lat !== null ? '&lat=' . $lat . '&lng=' . $lng : '');
              ?>
            <a class="chip<?= $type === $value ? ' is-on' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </div>
        <div id="map-list">
          <?php if (!$signals && !$helpers): ?>
            <p class="empty">В этом круге тихо. Расширьте радиус или передайте свой сигнал.</p>
          <?php endif; ?>
          <?php foreach ($signals as $signal): ?>
            <article class="signal">
              <div class="signal__top">
                <strong><?= e($signal['type_label']) ?></strong>
                <?php if ($signal['demo']): ?><span class="sample">Учебная</span><?php else: ?><?= status_pill('need_help') ?><?php endif; ?>
              </div>
              <p><?= e($signal['author']['name'] ?? 'Водитель') ?><?= $signal['route'] ? ' · ' : '' ?><?= $signal['route'] ? shield($signal['route']) : '' ?></p>
              <p><?= e($signal['comment']) ?></p>
              <p class="meta"><?= $signal['distance_km'] !== null ? e(km_label((float) $signal['distance_km'])) . ' · ' : '' ?><?= e($signal['ago']) ?></p>
              <?php if ($signal['can_accept']): ?>
                <form method="post" action="/map.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $signal['id'] ?>">
                  <button class="btn" type="submit">Еду</button>
                </form>
              <?php elseif ($signal['demo']): ?>
                <p class="meta">Учебная метка. Это не вызов.</p>
              <?php elseif (!$user && $signal['status'] === 'active'): ?>
                <a class="btn btn--ghost" href="/auth.php?next=<?= e(rawurlencode('/map.php')) ?>">Войти, чтобы принять</a>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
          <?php foreach ($helpers as $helper): ?>
            <a class="card" href="/profile.php?id=<?= (int) $helper['id'] ?>">
              <?= status_pill($helper['status']) ?>
              <?php if (!empty($helper['demo'])): ?><span class="sample">Образец</span><?php endif; ?>
              <h3><?= e($helper['name']) ?></h3>
              <p class="meta"><?= e($helper['city'] ?? '') ?> <?= isset($helper['distance_km']) ? '· ' . e(km_label((float) $helper['distance_km'])) : '' ?></p>
            </a>
          <?php endforeach; ?>
        </div>
        <p class="fine">Подложка Esri, World Dark Gray. Номер не показываем, пока сигнал не принят.</p>
      </aside>
    </div>
<?php
render_footer($meta);
