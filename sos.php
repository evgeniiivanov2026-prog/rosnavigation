<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/forms.php';

handle_sos_post('/sos');

$me = current_user();
$viewer = $me ? (int) $me['id'] : 0;
$sentId = (int) ($_GET['sent'] ?? 0);
$sent = $sentId ? repo_signal($sentId) : null;
$mine = $viewer ? repo_active_for($viewer) : null;
$pageTitle = 'Что случилось — РосНавигация';
$pageDescription = 'Сигнал своим на трассе: встал, обсох, притомился. Удержи кнопку две секунды, чтобы не отправить случайно.';
$pageCanonical = APP_URL . '/sos';
$pageActive = 'sos';
$bodyClass = 'page-sos';
$pageClassic = '/assets/vendor/leaflet/leaflet.js';
$pageHead = '<link rel="stylesheet" href="/assets/vendor/leaflet/leaflet.css">';
$pageScript = '/js/pages/sos.js';

require __DIR__ . '/templates/header.php';
?>
<div class="container sos-layout">
  <div>
    <p class="kicker">Сигнал</p>
    <h1>Что случилось, брат?</h1>
    <p class="lede">Выбери одно. Коротко напиши, где стоишь. Свои рядом увидят точку.</p>
    <p class="sos-urgent">Если угроза жизни — сначала 112. Этот сигнал зовёт своих, он не заменяет скорую и не вызывает ГАИ.</p>

    <?php $shown = $sent ?: $mine; ?>
    <?php if ($shown): ?>
      <div class="stack gap">
        <?php if ($sent): ?>
          <p class="status status--success">Сигнал в эфире</p>
        <?php else: ?>
          <p>Сигнал уже горит. Второй не нужен — закрой этот, когда выручили или поехал сам.</p>
        <?php endif; ?>
        <?= render_signal($shown, $viewer, '/sos') ?>
        <div id="sos-done-map" class="sos-map" data-lat="<?= e((string) $shown['lat']) ?>" data-lng="<?= e((string) $shown['lng']) ?>" role="img" aria-label="Точка сигнала на карте"></div>
        <div class="actions">
          <a class="btn btn--primary" href="/map?sos=<?= (int) $shown['id'] ?>">Кто рядом</a>
          <a class="btn btn--ghost" href="/channel">Написать в эфир</a>
        </div>
        <p class="fine">Номер в эфир не выкладываем: когда свои примут сигнал, договаривайтесь здесь. Если угроза жизни — 112, не жди ответ на сайте.</p>
      </div>
    <?php else: ?>
      <form class="gap" id="sos-form" method="post" action="/sos">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="tiles">
          <?php foreach (sos_catalog() as $key => [$name, $hint, $icon]): ?>
            <label class="tile">
              <input type="radio" name="type" value="<?= e($key) ?>" required>
              <span class="tile__icon"><?= icon($icon, 'icon icon--lg') ?></span>
              <span class="tile__name"><?= e($name) ?></span>
              <span class="tile__hint"><?= e($hint) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="stack gap">
          <div class="field">
            <label for="route">Трасса</label>
            <input id="route" name="route" maxlength="50" placeholder="М-7" autocomplete="off">
          </div>
          <div class="field">
            <label for="km">Километр</label>
            <input id="km" name="km" inputmode="numeric" maxlength="6" placeholder="820">
          </div>
          <div class="field">
            <label for="comment">Пара слов</label>
            <textarea id="comment" name="comment" maxlength="500" placeholder="Заднее левое, запаска есть, домкрата нет"></textarea>
          </div>
          <p class="form-error" id="sos-error" role="alert" hidden></p>
        </div>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!$shown): ?>
  <aside class="sos-side">
    <h2>Точка</h2>
    <p id="point-label">Ищу, где ты стоишь…</p>
    <div id="sos-map" class="sos-map" role="application" aria-label="Метка на карте"></div>
    <div class="point-card">
      <div class="field">
        <label for="lat">Широта</label>
        <input id="lat" name="lat" form="sos-form" inputmode="decimal" placeholder="55.7887">
      </div>
      <div class="field">
        <label for="lng">Долгота</label>
        <input id="lng" name="lng" form="sos-form" inputmode="decimal" placeholder="49.1221">
      </div>
      <p class="fine">Можно взять из карт: долгий тап по обочине и скопировать числа. Не ставь точку «примерно в области» — свои поедут туда.</p>
    </div>
    <div class="sos-dock">
      <button class="sos-hold" id="sos-hold" type="submit" form="sos-form" aria-describedby="sos-hold-help" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
        <svg class="sos-hold__svg" viewBox="0 0 160 160" aria-hidden="true">
          <circle class="sos-hold__track" cx="80" cy="80" r="70"></circle>
          <circle class="sos-hold__bar" cx="80" cy="80" r="70"></circle>
        </svg>
        <span class="sos-hold__label">Передать<br>сигнал</span>
      </button>
      <p class="fine" id="sos-hold-help">Удерживай две секунды, чтобы сигнал не ушёл от случайного касания. С клавиатуры — Enter.</p>
    </div>
  </aside>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/templates/footer.php';