<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$GLOBALS['page'] = 'channel';
$user = current_user();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    assert_same_origin();
    csrf_check();
    if (honeypot_tripped($_POST)) {
        redirect('/channel.php');
    }
    $action = (string) ($_POST['action'] ?? 'create');
    if ($action === 'report') {
        handle(
            function () {
                repo_report('post', (int) ($_POST['id'] ?? 0), (string) ($_POST['reason'] ?? ''));
                flash('ok', 'Жалоба ушла модератору.');
                return true;
            },
            '/channel.php',
            '/channel.php'
        );
    }
    if (!$user) {
        flash('error', 'Писать в эфир можно после того, как займёте место.');
        redirect('/auth.php?next=' . rawurlencode('/channel.php'));
    }
    if ($action === 'like') {
        $id = (int) ($_POST['id'] ?? 0);
        handle(
            fn () => repo_like((int) $user['id'], $id),
            '/channel.php',
            '/channel.php'
        );
    }
    handle(
        function () use ($user) {
            $photo = null;
            if (!empty($_FILES['photo']['name'])) {
                $photo = handle_upload($_FILES['photo'], 'channel');
            }
            $created = repo_post_create((int) $user['id'], $_POST, $photo);
            flash('ok', $created['status'] === 'pending'
                ? 'Фото ждёт модератора. Текст без фото выходит сразу.'
                : 'Запись в эфире.');
            return $created;
        },
        '/channel.php',
        '/channel.php'
    );
}

$posts = repo_posts($user['id'] ?? null);
$meta = [
    'title' => 'Эфир — РосНавигация',
    'description' => 'Короткие записи с трассы: посты, стоянки, кто кому помог. Образцы помечены.',
    'canonical' => APP_URL . '/channel.php',
];
render_header($meta);
?>
    <section class="section container">
      <p class="kicker">Лента</p>
      <h1>Эфир</h1>
      <p class="lede">Пишите, как проехали: трасса, время, что изменилось. Образцы ниже держат тон, это не сводка на сегодня. Фото уходит на премодерацию.</p>

      <?php if ($user): ?>
        <form class="card" method="post" action="/channel.php" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <?= honeypot_field() ?>
          <input type="hidden" name="action" value="create">
          <label class="field"><span>Что видели</span><textarea name="body" required minlength="10" maxlength="1000" placeholder="М-7, 120-й, яма на правой колее"></textarea></label>
          <label class="field"><span>Трасса</span><input name="route" maxlength="50" placeholder="М-7"></label>
          <label class="field"><span>Город рядом</span><input name="city" maxlength="100"></label>
          <label class="field"><span>Фото, если есть</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
          <button class="btn" type="submit">В эфир</button>
        </form>
      <?php else: ?>
        <p class="actions"><a class="btn" href="/auth.php?next=<?= e(rawurlencode('/channel.php')) ?>">Занять место, чтобы писать</a></p>
      <?php endif; ?>

      <?php if (!$posts): ?>
        <p class="empty">Эфир молчит. Первая честная запись важнее десятка пустых.</p>
      <?php endif; ?>
      <div class="cards">
        <?php foreach ($posts as $post): ?>
          <article class="post">
            <div class="post__top">
              <div>
                <?= avatar_html($post['author']) ?>
              </div>
              <div>
                <strong><?= e($post['author']['name'] ?? 'Водитель') ?></strong>
                <p class="meta"><?= e($post['ago']) ?><?= $post['city'] ? ' · ' . e($post['city']) : '' ?></p>
              </div>
              <?= $post['route'] ? shield($post['route']) : '' ?>
              <?php if ($post['demo']): ?><span class="sample">Образец</span><?php endif; ?>
              <?php if ($post['status'] === 'pending'): ?><span class="tag">На модерации</span><?php endif; ?>
            </div>
            <?php if ($post['photo_path']): ?>
              <img src="<?= e($post['photo_path']) ?>" alt="Фото к записи" width="960" height="640">
            <?php endif; ?>
            <p><?= e($post['body']) ?></p>
            <div class="actions">
              <?php if ($user): ?>
                <form method="post" action="/channel.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="like">
                  <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                  <button class="btn btn--ghost" type="submit" <?= $post['liked'] ? 'disabled' : '' ?>><?= $post['liked'] ? 'Пятюня стоит' : 'Пятюня' ?> · <?= (int) $post['likes'] ?></button>
                </form>
              <?php else: ?>
                <a class="btn btn--ghost" href="/auth.php?next=<?= e(rawurlencode('/channel.php')) ?>">Пятюня · <?= (int) $post['likes'] ?></a>
              <?php endif; ?>
              <details class="report">
                <summary>Пожаловаться</summary>
                <form method="post" action="/channel.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="report">
                  <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                  <label class="visually-hidden" for="reason-<?= (int) $post['id'] ?>">Почему жалуетесь</label>
                  <input id="reason-<?= (int) $post['id'] ?>" name="reason" type="text" placeholder="Что не так" maxlength="300" required>
                  <button class="btn btn--ghost" type="submit">Отправить</button>
                </form>
              </details>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
<?php
render_footer($meta);
