<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$GLOBALS['page'] = 'profile';
$me = current_user();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    assert_same_origin();
    csrf_check();
    if (!$me) {
        flash('error', 'Сначала займите место в колонне.');
        redirect('/auth.php?next=' . rawurlencode('/profile.php'));
    }
    $action = (string) ($_POST['action'] ?? 'save');
    handle(
        function () use ($me, $action) {
            $id = (int) $me['id'];
            if ($action === 'avatar' || $action === 'car') {
                $file = $_FILES['photo'] ?? null;
                if (!is_array($file)) {
                    throw new HttpError('upload', 'Файл не выбран.');
                }
                $path = handle_upload($file, $action === 'avatar' ? 'avatars' : 'cars');
                $field = $action === 'avatar' ? 'avatar_path' : 'car_path';
                repo_update_user($id, [$field => $path]);
                flash('ok', 'Фото на месте.');
                return true;
            }
            if ($action === 'status') {
                repo_update_user($id, profile_payload(['status' => $_POST['status'] ?? '']));
                flash('ok', 'Статус обновили.');
                return true;
            }
            repo_update_user($id, profile_payload([
                'name' => $_POST['name'] ?? '',
                'city' => $_POST['city'] ?? '',
                'motto' => $_POST['motto'] ?? '',
                'truck_brand' => $_POST['truck_brand'] ?? '',
                'truck_plate' => $_POST['truck_plate'] ?? '',
                'truck_trailer' => $_POST['truck_trailer'] ?? '',
                'experience_y' => $_POST['experience_y'] ?? '',
                'role' => $_POST['role'] ?? 'both',
            ]));
            flash('ok', 'Кабина сохранена.');
            return true;
        },
        '/profile.php',
        '/profile.php'
    );
}

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    if (!$me) {
        redirect('/auth.php?next=' . rawurlencode('/profile.php'));
    }
    $id = (int) $me['id'];
}
$profile = repo_user_public($id, $me['id'] ?? null);
if (!$profile) {
    http_response_code(404);
    $GLOBALS['page'] = 'missing';
    render_header([
        'title' => 'Нет такого водителя — РосНавигация',
        'description' => 'Профиль не найден.',
        'canonical' => APP_URL . '/profile.php',
    ]);
    echo '<section class="section container miss"><h1>Такого позывного нет.</h1><p class="lede">Вернитесь на карту и выберите своих, кто на связи.</p><p class="actions"><a class="btn" href="/map.php">На карту</a></p></section>';
    render_footer([]);
    exit;
}
$owns = $me && (int) $me['id'] === (int) $profile['id'];
if ($owns) {
    $profile = current_user(true) ?? $profile;
}
$helps = repo_helps((int) $profile['id'], $me['id'] ?? null);
$meta = [
    'title' => $profile['name'] . ' — РосНавигация',
    'description' => trim(($profile['city'] ?? 'Водитель') . '. ' . ($profile['motto'] ?? 'Профиль водителя колонны.')),
    'canonical' => APP_URL . '/profile.php?id=' . (int) $profile['id'],
];
render_header($meta);
?>
    <section class="section container">
      <?php if ($profile['demo']): ?>
        <p class="banner">Это образец профиля. Человек с таким именем здесь не зарегистрирован, на сигнал к нему не едут.</p>
      <?php endif; ?>
      <div class="card card--plain">
        <div class="profile-cover"></div>
        <div class="profile-head">
          <?= avatar_html($profile, 'avatar avatar--lg') ?>
          <h1><?= e($profile['name']) ?></h1>
          <p><?= e($profile['city'] ?? 'Город не указан') ?><?= $profile['truck_brand'] ? ' · ' . e($profile['truck_brand']) : '' ?><?= $profile['experience_y'] !== null ? ' · стаж ' . (int) $profile['experience_y'] . ' ' . e(plural((int) $profile['experience_y'], 'год', 'года', 'лет')) : '' ?></p>
          <?= status_pill($profile['status']) ?>
          <?php if ($profile['motto']): ?><p class="lede">«<?= e($profile['motto']) ?>»</p><?php endif; ?>
          <div class="badges">
            <?php foreach ($profile['badges'] as $badge): ?>
              <span class="badge"><?= e($badge) ?></span>
            <?php endforeach; ?>
            <?php if (!$profile['badges']): ?><span class="meta">Бейджи появятся с рейсами и выручками.</span><?php endif; ?>
          </div>
          <?php if ($profile['rating'] !== null): ?>
            <p class="meta">Оценка <?= e(number_format((float) $profile['rating'], 1, ',', '')) ?> · выручек <?= (int) $profile['helps_count'] ?></p>
          <?php else: ?>
            <p class="meta">Выручек: <?= (int) $profile['helps_count'] ?></p>
          <?php endif; ?>
        </div>
      </div>

      <div class="profile-sheet">
        <div>
          <h2>Мои выручки</h2>
          <?php if (!$helps): ?>
            <p class="empty">Пока пусто. Первая выручка появится, когда закроете чужой сигнал.</p>
          <?php endif; ?>
          <div class="cards">
            <?php foreach ($helps as $help): ?>
              <article class="signal">
                <div class="signal__top"><?= shield($help['route']) ?> <?php if ($help['demo']): ?><span class="sample">Образец</span><?php endif; ?></div>
                <p><?= e($help['type_label']) ?>. <?= e($help['comment']) ?></p>
                <p class="meta"><?= e($help['ago']) ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($owns): ?>
          <div>
            <h2>Кабина</h2>
            <form method="post" action="/profile.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save">
              <label class="field"><span>Имя</span><input name="name" required maxlength="100" value="<?= e($profile['name']) ?>"></label>
              <label class="field"><span>Город</span><input name="city" maxlength="100" value="<?= e($profile['city'] ?? '') ?>"></label>
              <label class="field"><span>Девиз</span><input name="motto" maxlength="200" value="<?= e($profile['motto'] ?? '') ?>"></label>
              <label class="field"><span>Машина</span><input name="truck_brand" maxlength="100" value="<?= e($profile['truck_brand'] ?? '') ?>"></label>
              <label class="field"><span>Номер, виден только вам</span><input name="truck_plate" maxlength="20" value="<?= e($profile['truck_plate'] ?? '') ?>"></label>
              <label class="field"><span>Прицеп</span><input name="truck_trailer" maxlength="50" value="<?= e($profile['truck_trailer'] ?? '') ?>"></label>
              <label class="field"><span>Стаж, лет</span><input name="experience_y" inputmode="numeric" value="<?= $profile['experience_y'] !== null ? (int) $profile['experience_y'] : '' ?>"></label>
              <label class="field"><span>Роль</span>
                <select name="role">
                  <?php foreach (['seeker' => 'Ищу помощь', 'helper' => 'Помогаю', 'both' => 'И то, и то'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $profile['role'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <button class="btn" type="submit">Сохранить</button>
            </form>

            <form method="post" action="/profile.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="status">
              <label class="field"><span>Статус</span>
                <select name="status">
                  <?php foreach (['on_air' => 'На приёме', 'on_route' => 'На маршруте', 'resting' => 'Отдыхаю', 'offline' => 'Молчу'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $profile['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <button class="btn btn--ghost" type="submit">Поставить статус</button>
            </form>

            <form method="post" action="/profile.php" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="avatar">
              <label class="field"><span>Фото себя, до 2 МБ</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required></label>
              <button class="btn btn--ghost" type="submit">Загрузить портрет</button>
            </form>

            <form method="post" action="/auth.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="logout">
              <button class="btn btn--ghost" type="submit">Выйти из кабины</button>
            </form>
            <label class="switch"><input type="checkbox" id="sound-toggle"> Звуки эфира</label>
            <p class="fine">По умолчанию выключены. Короткий писк, когда сигнал ушёл или нашёлся помощник. Сами не играют.</p>
          </div>
        <?php endif; ?>
      </div>
    </section>
<?php
render_footer($meta);
