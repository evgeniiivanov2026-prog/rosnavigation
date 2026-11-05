<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$me = current_user();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_ok()) {
        flash_set('error', 'Сессия устарела. Обнови страницу.');
        redirect('/channel');
    }
    $user = require_user_web();
    $action = (string) ($_POST['action'] ?? '');
    $postId = (int) ($_POST['post_id'] ?? 0);
    if ($action === 'like') {
        $result = repo_like((int) $user['id'], $postId);
        flash_set(empty($result['ok']) ? 'error' : 'ok', empty($result['ok']) ? (string) $result['message'] : 'Пятюня на месте.');
        redirect('/channel#post-' . $postId);
    }
    if ($action === 'report') {
        $result = repo_report((int) $user['id'], $postId);
        flash_set(empty($result['ok']) ? 'error' : 'ok', empty($result['ok']) ? (string) $result['message'] : 'Жалоба ушла модератору, не в общий крик.');
        redirect('/channel');
    }
    if ($action === 'create') {
        if (!rate_hit('post:' . client_ip(), 6, 600)) {
            flash_set('error', 'Слишком часто для эфира. Подожди.');
            redirect('/channel');
        }
        $photo = null;
        if (!empty($_FILES['photo']['name'])) {
            try {
                $photo = handle_upload($_FILES['photo'], 'channel');
            } catch (Throwable $e) {
                flash_set('error', $e->getMessage());
                redirect('/channel');
            }
        }
        $result = repo_create_post((int) $user['id'], [
            'body' => $_POST['body'] ?? '',
            'route' => $_POST['route'] ?? '',
            'city' => $_POST['city'] ?? '',
            'photo_path' => $photo,
        ]);
        if (empty($result['ok'])) {
            flash_set('error', (string) ($result['message'] ?? 'Не отправилось.'));
            redirect('/channel');
        }
        flash_set('ok', ($result['status'] ?? '') === 'pending'
            ? 'Текст принят. Фото ждёт проверку и в эфир пока не ставлю.'
            : 'В эфире.');
        redirect('/channel');
    }
}

$viewer = $me ? (int) $me['id'] : 0;
$posts = repo_posts($viewer ?: null);
$pending = $viewer ? repo_pending_posts($viewer) : [];
$pageTitle = 'Эфир — РосНавигация';
$pageDescription = 'Короткие вести с трассы: туман, стоянка, ветер, сервис. Без рекламной помойки.';
$pageCanonical = APP_URL . '/channel';
$pageActive = 'channel';
$bodyClass = 'page-channel';
$pageScript = '/js/pages/channel.js';

require __DIR__ . '/templates/header.php';
?>
<div class="container section split">
  <div>
    <p class="kicker">Эфир</p>
    <h1>Что видели только что</h1>
    <p class="lede">Коротко и по делу. Фото уходит на проверку, текст — сразу, если без картинки.</p>
    <?php if ($me): ?>
      <form class="panel" id="composer" method="post" action="/channel" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <p class="form-error" role="alert"></p>
        <label class="field">
          <span>Запись</span>
          <textarea name="body" required minlength="10" maxlength="2000" placeholder="Где, что видел, чем это важно фуре"></textarea>
        </label>
        <label class="field">
          <span>Трасса</span>
          <input name="route" maxlength="50" placeholder="М-5">
        </label>
        <label class="field">
          <span>Город или участок</span>
          <input name="city" maxlength="100" placeholder="Челябинск">
        </label>
        <label class="field">
          <span>Фото, если есть</span>
          <input name="photo" type="file" accept="image/jpeg,image/png,image/webp">
        </label>
        <button class="btn btn--primary" type="submit">В эфир</button>
      </form>
    <?php else: ?>
      <div class="panel">
        <p>Говорить в эфире можно после того, как займёшь место в колонне.</p>
        <a class="btn btn--primary" href="/auth?next=/channel">Встать на приём</a>
      </div>
    <?php endif; ?>
  </div>
  <div class="stack" id="feed" aria-live="polite">
    <?php foreach ($pending as $post): ?>
      <?= render_post($post, false) ?>
    <?php endforeach; ?>
    <?php if (!$posts): ?>
      <?= render_empty('Эфир тихий', 'Скажи, что видел на трассе. Пустая лента никому не помогает.') ?>
    <?php endif; ?>
    <?php foreach ($posts as $post): ?>
      <?= render_post($post) ?>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/templates/footer.php';