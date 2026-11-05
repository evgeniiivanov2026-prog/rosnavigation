<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$viewer = current_user();
$error = null;
$notice = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        csrf_check();
        if (!$viewer) {
            header('Location: /auth?next=/channel');
            exit;
        }
        $action = (string) ($_POST['action'] ?? 'create');
        if ($action === 'complain') {
            repo_complain((int) $viewer['id'], (int) ($_POST['post_id'] ?? 0), (string) ($_POST['reason'] ?? ''));
            $notice = 'Жалоба ушла модератору. Толпа в теме её не разбирает.';
        } else {
            if (honeypot_tripped($_POST)) {
                header('Location: /channel');
                exit;
            }
            if (rate_limited('post', 6, 600)) {
                throw new HttpError('rate_limit', 'Слишком часто для эфира. Минуту тишины.', 429);
            }
            $photo = null;
            if (!empty($_FILES['photo']['name'])) {
                $photo = handle_upload($_FILES['photo'], 'channel');
            }
            $post = repo_create_post((int) $viewer['id'], repo_post_input($_POST, $photo));
            $notice = $post['status'] === 'pending'
                ? 'Фото на премодерации. Текст увидите вы, остальные — после проверки.'
                : 'Вы в эфире.';
        }
    } catch (HttpError $e) {
        $error = $e->getMessage();
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
    $viewer = current_user(true);
}

$posts = repo_posts($viewer['id'] ?? null);
$page = [
    'title' => '15-й канал — РосНавигация',
    'description' => 'Короткие вести с трассы: где помогли, где рамка, где гололёд. Пятюня вместо лайка.',
    'path' => '/channel',
    'nav' => 'channel',
    'body' => 'page-channel',
    'script' => 'channel',
];
require __DIR__ . '/templates/header.php';
?>
<main id="main" class="section">
  <div class="container">
    <header class="section__head">
      <p class="kicker">Эфир</p>
      <h1>15-й канал</h1>
      <p class="lede">Коротко и по делу. Фото смотрит модератор, прежде чем его увидят остальные.</p>
    </header>
    <?php if ($error): ?><div class="state state--error"><p><?= e($error) ?></p></div><?php endif; ?>
    <?php if ($notice): ?><div class="state stack-top"><p><?= e($notice) ?></p></div><?php endif; ?>

    <?php if ($viewer): ?>
      <form class="panel stack-top" id="channel-form" method="post" action="/channel" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?= honeypot_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="field">
          <label for="body">Что на трассе?</label>
          <textarea id="body" name="body" maxlength="1000" required placeholder="Где стоите, что видели, чем помогли"></textarea>
        </div>
        <div class="field">
          <label for="post-route">Трасса</label>
          <input id="post-route" name="route" maxlength="50" class="mono" placeholder="М-4">
        </div>
        <div class="field">
          <label for="post-city">Город или участок</label>
          <input id="post-city" name="city" maxlength="80">
        </div>
        <div class="field">
          <label for="post-photo">Фото, до 2 МБ</label>
          <input id="post-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp">
        </div>
        <button class="btn btn--brand" type="submit">Выйти в эфир</button>
      </form>
    <?php else: ?>
      <div class="state stack-top">
        <h3>Эфир слышен всем</h3>
        <p>Чтобы говорить, займите место в колонне.</p>
        <p class="stack-top"><a class="btn btn--brand" href="/auth?next=/channel">Встать на приём</a></p>
      </div>
    <?php endif; ?>

    <div class="feed stack-top" id="feed">
      <?php if (!$posts): ?>
        <?= view_state('empty', 'Эфир тихий', 'Напишите первым, что на трассе.') ?>
      <?php endif; ?>
      <?php foreach ($posts as $post) {
          echo view_post($post);
      } ?>
    </div>
  </div>
</main>
<dialog class="modal" id="report-dialog">
  <form method="post" action="/channel">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="complain">
    <input type="hidden" name="post_id" id="report-id" value="">
    <h2>Пожаловаться</h2>
    <p class="fine">Модератор, не толпа. Напишите, что не так.</p>
    <div class="field stack-top">
      <label for="reason">Причина</label>
      <textarea id="reason" name="reason" maxlength="300" required></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn--ghost" type="button" id="report-cancel">Отмена</button>
      <button class="btn btn--danger" type="submit">Отправить</button>
    </div>
  </form>
</dialog>
<?php require __DIR__ . '/templates/footer.php'; ?>
