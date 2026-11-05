<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$GLOBALS['page'] = 'sos';
$user = current_user();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    assert_same_origin();
    csrf_check();
    if (honeypot_tripped($_POST)) {
        redirect('/sos.php');
    }
    if (!$user) {
        flash('error', 'Сначала займите место в колонне — так свои поймут, кто на связи.');
        redirect('/auth.php?next=' . rawurlencode('/sos.php'));
    }
    $action = (string) ($_POST['action'] ?? 'create');
    $fail = '/sos.php';
    if ($action === 'cancel') {
        handle(
            fn () => repo_sos_cancel((int) ($_POST['id'] ?? 0), (int) $user['id']),
            '/sos.php',
            $fail
        );
    }
    if ($action === 'message') {
        $id = (int) ($_POST['id'] ?? 0);
        handle(
            function () use ($id, $user) {
                repo_message_add($id, (int) $user['id'], (string) ($_POST['body'] ?? ''));
                flash('ok', 'Сообщение ушло своим по сигналу.');
                return true;
            },
            '/sos.php?id=' . $id,
            '/sos.php?id=' . $id
        );
    }
    if ($action === 'complete') {
        $id = (int) ($_POST['id'] ?? 0);
        $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? (int) $_POST['rating'] : null;
        handle(
            function () use ($id, $user, $rating) {
                repo_sos_complete($id, (int) $user['id'], $rating, (string) ($_POST['review'] ?? ''));
                flash('ok', 'Выручка закрыта. Доброй дороги.');
                return true;
            },
            '/sos.php?id=' . $id,
            '/sos.php?id=' . $id
        );
    }
    handle(
        function () use ($user) {
            $signal = repo_sos_create((int) $user['id'], $_POST);
            flash('ok', 'Сигнал в эфире. Доброй дороги, брат.');
            return $signal;
        },
        '/sos.php',
        $fail
    );
}

$viewer = $user['id'] ?? null;
$signal = null;
if ($viewer) {
    $signal = repo_sos_active((int) $viewer, (int) $viewer);
}
if (!$signal && isset($_GET['id'])) {
    $maybe = repo_sos_public((int) $_GET['id'], $viewer);
    if ($maybe && ($maybe['mine'] || $maybe['can_chat'] || $maybe['can_accept'] || ($user && ($maybe['helper']['id'] ?? null) === $user['id']))) {
        $signal = $maybe;
    }
}
$helpers = [];
$messages = [];
if ($signal) {
    $helpers = repo_helpers_near((float) $signal['lat'], (float) $signal['lng'], (float) HELP_RADIUS_KM, $viewer);
    if ($signal['can_chat'] && $viewer) {
        try {
            $messages = repo_messages((int) $signal['id'], (int) $viewer);
        } catch (HttpError $e) {
            $messages = [];
        }
    }
}
$GLOBALS['boot_extra'] = [
    'pollMs' => 15000,
    'signalId' => $signal['id'] ?? null,
];

$meta = [
    'title' => 'Что случилось — РосНавигация',
    'description' => 'Передать сигнал своим на трассе: встал, обсох, притомился. Если угроза жизни — звоните 112.',
    'canonical' => APP_URL . '/sos.php',
];
$types = [
    'breakdown' => ['Встал', 'Двигатель, колесо, электрика'],
    'fuel' => ['Обсох', 'Топливо кончилось'],
    'sleep' => ['Притомился', 'Нужна стоянка или компания'],
    'accident' => ['ДТП', 'Сначала 112, если есть пострадавшие'],
    'medical' => ['Медицина', 'Плохо человеку'],
    'other' => ['Другое', 'Своими словами'],
];
render_header($meta);
?>
    <section class="section sos-layout container">
      <div>
        <p class="kicker">Сигнал</p>
        <h1>Что случилось, брат?</h1>
        <p class="lede">Удерживайте кнопку две секунды, чтобы не передать сигнал из кармана. С клавиатуры достаточно Enter.</p>
        <p class="sos-urgent" id="urgent">Если жизнь под угрозой — <a href="tel:112">112</a>. Этот сигнал не вызывает скорую и не заменяет спасателей.</p>

        <?php if ($signal && in_array($signal['status'], ['active', 'accepted'], true)): ?>
          <article class="signal" id="signal-card">
            <div class="signal__top">
              <strong><?= e($signal['type_label']) ?></strong>
              <?php if ($signal['demo']): ?><span class="sample">Учебная метка</span><?php endif; ?>
              <?= $signal['route'] ? shield($signal['route']) : '' ?>
            </div>
            <p><?= e($signal['comment'] !== '' ? $signal['comment'] : 'Без комментария.') ?></p>
            <p class="meta">
              <?= $signal['km'] !== null ? 'км ' . (int) $signal['km'] . ' · ' : '' ?>
              <?= e($signal['ago']) ?>
              <?= $signal['approx_geo'] ? ' · точка по трассе, не по GPS' : '' ?>
            </p>
            <?php if ($signal['helper']): ?>
              <p>На сигнал едет <strong><?= e($signal['helper']['name']) ?></strong>.</p>
              <?php if ($signal['contact_phone']): ?>
                <p><a class="btn" href="tel:<?= e(preg_replace('/\s+/', '', (string) $signal['contact_phone'])) ?>">Позвонить</a></p>
              <?php endif; ?>
            <?php else: ?>
              <p>Номер откроется тому, кто примет сигнал. Пока рядом <?= count($helpers) ?> <?= e(plural(count($helpers), 'свой', 'своих', 'своих')) ?>.</p>
            <?php endif; ?>
            <?php if ($signal['mine'] && !$signal['demo']): ?>
              <form method="post" action="/sos.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="id" value="<?= (int) $signal['id'] ?>">
                <button class="btn btn--ghost" type="submit">Погасить сигнал</button>
              </form>
            <?php endif; ?>
          </article>

          <?php if ($signal['can_chat']): ?>
            <div class="card" id="chat">
              <h2>Чат по сигналу</h2>
              <div id="chat-log">
                <?php foreach ($messages as $message): ?>
                  <p><strong><?= e($message['author']) ?>.</strong> <?= e($message['body']) ?> <span class="meta"><?= e($message['ago']) ?></span></p>
                <?php endforeach; ?>
              </div>
              <form method="post" action="/sos.php" id="chat-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="message">
                <input type="hidden" name="id" value="<?= (int) $signal['id'] ?>">
                <label class="field"><span>Сообщение</span><textarea name="body" maxlength="500" required></textarea></label>
                <button class="btn" type="submit">Отправить</button>
              </form>
            </div>
          <?php endif; ?>

          <?php if ($signal['status'] === 'accepted' && ($signal['mine'] || ($user && ($signal['helper']['id'] ?? null) === $user['id']))): ?>
            <form class="card" method="post" action="/sos.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="complete">
              <input type="hidden" name="id" value="<?= (int) $signal['id'] ?>">
              <h2>Выручили друг друга</h2>
              <label class="field"><span>Оценка помощи, если хотите</span>
                <select name="rating">
                  <option value="">Без оценки</option>
                  <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                  <?php endfor; ?>
                </select>
              </label>
              <label class="field"><span>Пара слов</span><input name="review" maxlength="500"></label>
              <button class="btn" type="submit">Закрыть выручку</button>
            </form>
          <?php endif; ?>
        <?php else: ?>
          <form id="sos-form" method="post" action="/sos.php">
            <?= csrf_field() ?>
            <?= honeypot_field() ?>
            <input type="hidden" name="lat" id="sos-lat">
            <input type="hidden" name="lng" id="sos-lng">
            <div class="tiles">
              <?php foreach ($types as $value => $label): ?>
                <label class="tile">
                  <input type="radio" name="type" value="<?= e($value) ?>" required>
                  <span><?= e($label[0]) ?><small><?= e($label[1]) ?></small></span>
                </label>
              <?php endforeach; ?>
            </div>
            <div class="field field--space">
              <label for="route">Трасса</label>
              <input id="route" name="route" list="routes" maxlength="50" placeholder="М-7" autocomplete="off">
              <datalist id="routes">
                <option value="М-4"></option><option value="М-5"></option><option value="М-7"></option>
                <option value="М-11"></option><option value="М-12"></option><option value="Р-21"></option>
                <option value="Р-217"></option><option value="Р-258"></option>
              </datalist>
            </div>
            <label class="field" for="km"><span>Километр</span><input id="km" name="km" inputmode="numeric" maxlength="5"></label>
            <label class="field" for="comment"><span>Коротко, что нужно</span><textarea id="comment" name="comment" maxlength="500" placeholder="Запаска есть, домкрата нет"></textarea></label>
            <p class="meta" id="geo-note">Ищем точку телефона. Если связи с небом нет — напишите трассу.</p>
            <button class="sos-button" id="sos-hold" type="submit">
              <svg class="sos-button__ring" viewBox="0 0 160 160" aria-hidden="true">
                <circle class="sos-button__track" cx="80" cy="80" r="70"></circle>
                <circle class="sos-button__progress" cx="80" cy="80" r="70"></circle>
              </svg>
              <span class="sos-button__label">Передать<br>сигнал</span>
            </button>
            <p class="meta center-note" id="hold-hint">Удерживайте две секунды. Без скриптов кнопка отправит форму сразу.</p>
          </form>
        <?php endif; ?>
      </div>
      <aside>
        <h2>Свои на плече</h2>
        <div class="cards" id="helpers">
          <?php if (!$helpers): ?>
            <p class="empty">Рядом пока тихо. Сигнал всё равно увидят те, кто откроет карту.</p>
          <?php endif; ?>
          <?php foreach ($helpers as $helper): ?>
            <a class="card" href="/profile.php?id=<?= (int) $helper['id'] ?>">
              <?= status_pill($helper['status']) ?>
              <h3><?= e($helper['name']) ?><?php if ($helper['demo']): ?> <span class="sample">Образец</span><?php endif; ?></h3>
              <p><?= e(trim(($helper['city'] ?? '') . ' ' . ($helper['truck_brand'] ?? ''))) ?> · <?= isset($helper['distance_km']) ? e(km_label((float) $helper['distance_km'])) : '' ?></p>
            </a>
          <?php endforeach; ?>
        </div>
        <p class="actions"><a class="btn btn--ghost" href="/map.php">Смотреть на карте</a></p>
      </aside>
    </section>
<?php
render_footer($meta);
