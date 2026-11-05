<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$viewer = current_user();
$error = null;
$notice = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        csrf_check();
        if (honeypot_tripped($_POST)) {
            header('Location: /sos');
            exit;
        }
        if (!$viewer) {
            header('Location: /auth?next=' . rawurlencode('/sos'));
            exit;
        }
        $action = (string) ($_POST['action'] ?? 'create');
        if ($action === 'create') {
            if (rate_limited('sos-create', 5, 600)) {
                throw new HttpError('rate_limit', 'Слишком часто. Подождите немного.', 429);
            }
            $created = repo_create_sos((int) $viewer['id'], $_POST);
            header('Location: /sos?id=' . $created['id']);
            exit;
        }
        $sosId = (int) ($_POST['sos_id'] ?? 0);
        if ($action === 'cancel') {
            repo_cancel_sos((int) $viewer['id'], $sosId);
            header('Location: /sos?closed=1');
            exit;
        }
        if ($action === 'accept') {
            repo_accept_sos((int) $viewer['id'], $sosId);
            header('Location: /sos?id=' . $sosId);
            exit;
        }
        if ($action === 'complete') {
            repo_complete_sos((int) $viewer['id'], $sosId, (int) ($_POST['rating'] ?? 5));
            header('Location: /sos?thanks=1');
            exit;
        }
        if ($action === 'message') {
            repo_add_message((int) $viewer['id'], $sosId, (string) ($_POST['body'] ?? ''));
            header('Location: /sos?id=' . $sosId);
            exit;
        }
        throw new HttpError('bad_action', 'Неизвестное действие.', 400);
    } catch (HttpError $e) {
        $error = $e->getMessage();
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
    $viewer = current_user(true);
}

$focusId = (int) ($_GET['id'] ?? 0);
$signal = null;
if ($focusId > 0) {
    $signal = repo_sos_for_viewer($focusId, $viewer);
}
if (!$signal && $viewer) {
    $active = repo_my_active_sos((int) $viewer['id']);
    if ($active) {
        $signal = repo_sos_for_viewer((int) $active['id'], $viewer);
    }
}
if (!empty($_GET['closed'])) {
    $notice = 'Сигнал снят. Доброй дороги.';
}
if (!empty($_GET['thanks'])) {
    $notice = 'Выручили друг друга. Доброй дороги, брат.';
}

$page = [
    'title' => 'Что случилось — РосНавигация',
    'description' => 'Сигнал своим на трассе: встал, обсох, притомился, ДТП. Удерживайте кнопку две секунды.',
    'path' => '/sos',
    'nav' => 'sos',
    'body' => 'page-sos',
    'script' => 'sos',
];
require __DIR__ . '/templates/header.php';
$types = catalog_sos_types();
$points = catalog_points();
?>
<main id="main" class="section">
  <div class="container sos-layout">
    <div>
      <p class="kicker">Сигнал</p>
      <h1>Что случилось, брат?</h1>
      <p class="lede">Если угроза жизни — сначала <a href="tel:112">112</a>. Этот экран зовёт своих, он не скорая.</p>

      <?php if ($error): ?><div class="state state--error stack-top"><p><?= e($error) ?></p></div><?php endif; ?>
      <?php if ($notice): ?><div class="state stack-top"><p><?= e($notice) ?></p></div><?php endif; ?>

      <?php if ($signal): ?>
        <article class="result stack-top" id="sos-result" data-sos="<?= (int) $signal['id'] ?>">
          <p class="status status--danger"><i class="status__dot" aria-hidden="true"></i><?= e($signal['type_label']) ?> · <?= e($signal['status'] === 'accepted' ? 'приняли' : 'в эфире') ?></p>
          <h2 class="stack-top"><?= e($signal['name']) ?></h2>
          <p class="mono"><?= e(trim($signal['route'] . ($signal['km'] ? ' · ' . $signal['km'] . ' км' : ''))) ?></p>
          <p class="stack-top"><?= e($signal['comment']) ?></p>
          <?php if (!empty($signal['helper_name'])): ?><p class="stack-top">На приёме: <?= e($signal['helper_name']) ?></p><?php endif; ?>
          <div class="signal__actions">
            <?php if (!empty($signal['phone'])): ?>
              <a class="btn btn--brand" href="<?= e(tel_href($signal['phone'])) ?>">Позвонить</a>
            <?php elseif (!$viewer): ?>
              <a class="btn btn--ghost" href="/auth?next=<?= e(rawurlencode('/sos?id=' . $signal['id'])) ?>">Войти, чтобы позвонить</a>
            <?php endif; ?>
            <a class="btn btn--ghost" href="<?= e(route_url((float) $signal['lat'], (float) $signal['lng'])) ?>" rel="noopener">Маршрут</a>
            <a class="btn btn--ghost" href="/map">На карте</a>
          </div>
          <?php if ($viewer && ((int) $viewer['id'] === (int) $signal['user_id'] || (int) $viewer['id'] === (int) ($signal['helper_id'] ?? 0))): ?>
            <div class="chat" id="sos-chat">
              <?php foreach ($signal['messages'] as $message): ?>
                <p class="chat__item"><strong><?= e($message['name']) ?></strong><?= e($message['body']) ?></p>
              <?php endforeach; ?>
            </div>
            <form method="post" action="/sos" id="sos-chat-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="message">
              <input type="hidden" name="sos_id" value="<?= (int) $signal['id'] ?>">
              <div class="field">
                <label for="chat-body">Коротко своим</label>
                <input id="chat-body" name="body" maxlength="500" required placeholder="Где вы и чем поможете">
              </div>
              <button class="btn btn--brand" type="submit">Отправить</button>
            </form>
          <?php endif; ?>
          <div class="form-actions">
            <?php if ($viewer && (int) $viewer['id'] === (int) $signal['user_id'] && $signal['status'] !== 'completed'): ?>
              <form method="post" action="/sos">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="sos_id" value="<?= (int) $signal['id'] ?>">
                <button class="btn btn--ghost" type="submit">Снять сигнал</button>
              </form>
            <?php endif; ?>
            <?php if ($viewer && (int) $viewer['id'] !== (int) $signal['user_id'] && $signal['status'] === 'active'): ?>
              <form method="post" action="/sos">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="accept">
                <input type="hidden" name="sos_id" value="<?= (int) $signal['id'] ?>">
                <button class="btn btn--brand" type="submit">Принять сигнал</button>
              </form>
            <?php endif; ?>
            <?php if ($viewer && $signal['status'] === 'accepted' && ((int) $viewer['id'] === (int) $signal['user_id'] || (int) $viewer['id'] === (int) ($signal['helper_id'] ?? 0))): ?>
              <form method="post" action="/sos">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="sos_id" value="<?= (int) $signal['id'] ?>">
                <?php if ((int) $viewer['id'] === (int) $signal['user_id']): ?>
                  <label class="field">Оценка помощи
                    <select name="rating">
                      <option value="5">5 — выручил</option>
                      <option value="4">4</option>
                      <option value="3">3</option>
                    </select>
                  </label>
                <?php endif; ?>
                <button class="btn btn--brand" type="submit">Закрыть: выручили</button>
              </form>
            <?php endif; ?>
          </div>
        </article>
      <?php endif; ?>

      <?php if (!$signal || ($viewer && (int) ($signal['user_id'] ?? 0) !== (int) $viewer['id'])): ?>
      <form class="stack-top" id="sos-form" method="post" action="/sos">
        <?= csrf_field() ?>
        <?= honeypot_field() ?>
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="lat" id="sos-lat" value="">
        <input type="hidden" name="lng" id="sos-lng" value="">
        <fieldset class="sos-grid">
          <legend class="visually-hidden">Что случилось</legend>
          <?php foreach ($types as $key => $type): ?>
            <label class="sos-tile">
              <input type="radio" name="type" value="<?= e($key) ?>" <?= $key === 'breakdown' ? 'required' : '' ?>>
              <?= icon($type['icon']) ?>
              <span class="sos-tile__name"><?= e($type['label']) ?></span>
              <span class="sos-tile__hint"><?= e($type['hint']) ?></span>
            </label>
          <?php endforeach; ?>
        </fieldset>
        <div class="field stack-top">
          <button class="btn btn--ghost" type="button" id="geo-btn">Определить, где я</button>
          <p class="fine" id="geo-status">Точка нужна, чтобы свои поняли, куда ехать.</p>
        </div>
        <div class="field">
          <label for="point">Если телефон молчит — точка на трассе</label>
          <select id="point" name="point">
            <option value="">Не выбрано</option>
            <?php foreach ($points as $i => $point): ?>
              <option value="<?= (int) $i ?>"><?= e($point['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="route">Трасса</label>
          <input id="route" name="route" maxlength="50" placeholder="М-7" class="mono">
        </div>
        <div class="field">
          <label for="km">Километр</label>
          <input id="km" name="km" inputmode="numeric" maxlength="5" placeholder="412">
        </div>
        <div class="field">
          <label for="comment">Коротко, что нужно</label>
          <textarea id="comment" name="comment" maxlength="500" placeholder="Шланг, канистра, компания до сервиса"></textarea>
        </div>
        <?php if (!$viewer): ?>
          <p class="fine">Чтобы сигнал ушёл в колонну, <a href="/auth?next=/sos">займите место</a>. Это одна минута.</p>
        <?php endif; ?>
        <div class="sos-hold-wrap">
          <button class="sos-hold" id="sos-hold" type="submit" aria-describedby="hold-hint">
            <svg viewBox="0 0 160 160" aria-hidden="true">
              <circle class="sos-hold__track" cx="80" cy="80" r="74"></circle>
              <circle class="sos-hold__progress" cx="80" cy="80" r="74"></circle>
            </svg>
            <span>Передать сигнал</span>
          </button>
          <p class="fine" id="hold-hint">Удерживайте две секунды. С клавиатуры — сразу, по Enter.</p>
        </div>
      </form>
      <?php endif; ?>
    </div>
    <aside class="sos-side">
      <h2>Пока ждёте</h2>
      <p>Аварийка, знак, жилет. Не стойте на проезжей части. Если темно — фонарь в руку, не на дорогу.</p>
      <p class="stack-top"><a class="btn btn--danger" href="tel:112">Позвонить 112</a></p>
      <p class="fine stack-top">Сигнал живёт два часа и гаснет сам, если его закрыли или помощь нашлась.</p>
    </aside>
  </div>
</main>
<?php require __DIR__ . '/templates/footer.php'; ?>
