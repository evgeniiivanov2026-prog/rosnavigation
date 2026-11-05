<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_ok()) {
        flash_set('error', 'Сессия устарела. Обнови страницу.');
        redirect('/profile');
    }
    $user = require_user_web();
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'logout') {
        perform_logout();
        redirect('/');
    }
    try {
        if (!empty($_FILES['avatar']['name'])) {
            repo_set_media((int) $user['id'], 'avatar_path', handle_upload($_FILES['avatar'], 'avatars'));
        }
        if (!empty($_FILES['car']['name'])) {
            repo_set_media((int) $user['id'], 'car_path', handle_upload($_FILES['car'], 'cars'));
        }
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        redirect('/profile');
    }
    if ($action === 'save') {
        $result = repo_save_profile((int) $user['id'], $_POST);
        flash_set(empty($result['ok']) ? 'error' : 'ok', empty($result['ok']) ? (string) $result['message'] : 'Профиль обновлён. Доброй дороги.');
    }
    redirect('/profile');
}

$requested = (int) ($_GET['id'] ?? 0);
$me = current_user();
if ($requested === 0) {
    if (!$me) {
        redirect('/auth?next=/profile');
    }
    $requested = (int) $me['id'];
}
$row = repo_user($requested);
if (!$row || (int) ($row['is_banned'] ?? 0) === 1) {
    http_response_code(404);
    $pageTitle = 'Свернул не туда — РосНавигация';
    $pageDescription = 'Такой страницы нет.';
    $pageCanonical = APP_URL . '/profile';
    $pageActive = '';
    $bodyClass = 'page-lost';
    require __DIR__ . '/templates/header.php';
    echo '<div class="container lost"><p class="kicker">404</p><h1>Свернул не туда. Бывает.</h1><a class="btn btn--primary" href="/">Вернуться на трассу</a></div>';
    require __DIR__ . '/templates/footer.php';
    exit;
}

$self = $me && (int) $me['id'] === (int) $row['id'];
$public = public_user($row, $self);
$helps = repo_helps((int) $row['id']);
$pageTitle = $public['name'] . ' — РосНавигация';
$pageDescription = trim($public['name'] . ', ' . $public['city'] . '. ' . $public['motto']);
$pageCanonical = APP_URL . '/profile/' . (int) $public['id'];
$pageActive = $self ? 'profile' : '';
$bodyClass = 'page-profile';
$pageScript = '/js/pages/profile.js';

require __DIR__ . '/templates/header.php';
?>
<article class="profile">
  <div class="cover">
    <?php if (!empty($public['car_path'])): ?>
      <img class="photo-trucker" src="<?= e($public['car_path']) ?>" alt="Машина <?= e($public['name']) ?>" width="1280" height="440">
    <?php endif; ?>
  </div>
  <div class="container">
    <header class="profile__head">
      <?= render_avatar($public, 'avatar') ?>
      <div>
        <h1><?= e($public['name']) ?></h1>
        <p><?= e(trim($public['city'] . ' · ' . $public['truck_brand'] . ' ' . $public['truck_trailer'])) ?></p>
        <p class="profile__row"><?= render_status($public['status']) ?>
          <?php if ($public['experience_y']): ?><span class="fine">стаж <?= (int) $public['experience_y'] ?> лет</span><?php endif; ?>
          <span class="fine">выручек <?= (int) $public['helps_count'] ?></span>
        </p>
      </div>
    </header>
    <?php if ($public['motto'] !== ''): ?>
      <p class="motto">«<?= e($public['motto']) ?>»</p>
    <?php endif; ?>
    <ul class="badges">
      <?php foreach ($public['badges'] as $badge): ?>
        <li class="badge"><?= e($badge['name']) ?></li>
      <?php endforeach; ?>
    </ul>

    <?php if ($self): ?>
      <form class="panel" method="post" action="/profile" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <h2>Кабина</h2>
        <label class="field"><span>Имя</span><input name="name" required minlength="2" maxlength="100" value="<?= e($public['name']) ?>"></label>
        <label class="field"><span>Город</span><input name="city" maxlength="100" value="<?= e($public['city']) ?>"></label>
        <label class="field"><span>Трасса сейчас</span><input name="route" maxlength="50" value="<?= e($public['route']) ?>"></label>
        <label class="field"><span>Машина</span><input name="truck_brand" maxlength="100" value="<?= e($public['truck_brand']) ?>"></label>
        <label class="field"><span>Прицеп</span><input name="truck_trailer" maxlength="50" value="<?= e($public['truck_trailer']) ?>"></label>
        <label class="field"><span>Номер, его видишь только ты</span><input name="truck_plate" maxlength="20" value="<?= e($public['truck_plate'] ?? '') ?>" autocomplete="off"></label>
        <label class="field"><span>Стаж, лет</span><input name="experience_y" inputmode="numeric" value="<?= e((string) ($public['experience_y'] ?? '')) ?>"></label>
        <label class="field"><span>Девиз</span><input name="motto" maxlength="200" value="<?= e($public['motto']) ?>"></label>
        <fieldset class="choices">
          <legend class="fine">Зачем ты здесь</legend>
          <?php foreach (['seeker' => 'Ищу помощь', 'helper' => 'Помогаю', 'both' => 'И то, и то'] as $key => $label): ?>
            <label class="choice"><input type="radio" name="role" value="<?= e($key) ?>"<?= $public['role'] === $key ? ' checked' : '' ?>><span><?= e($label) ?></span></label>
          <?php endforeach; ?>
        </fieldset>
        <fieldset class="choices">
          <legend class="fine">Статус</legend>
          <?php foreach (['on_air' => 'На приёме', 'on_route' => 'На маршруте', 'resting' => 'Отдыхаю', 'offline' => 'Молчу'] as $key => $label): ?>
            <label class="choice"><input type="radio" name="status" value="<?= e($key) ?>"<?= $public['status'] === $key || ($public['status'] === 'need_help' && $key === 'on_air') ? ' checked' : '' ?>><span><?= e($label) ?></span></label>
          <?php endforeach; ?>
        </fieldset>
        <label class="field"><span>Портрет</span><input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp"></label>
        <img id="avatar-preview" alt="" hidden width="84" height="84">
        <label class="field"><span>Фото машины на обложку</span><input name="car" type="file" accept="image/jpeg,image/png,image/webp"></label>
        <label class="switch"><input id="air-sound" type="checkbox"><span>Звуки эфира</span></label>
        <p class="fine">Выключено. Короткий писк — только когда сам передал сигнал.</p>
        <button class="btn btn--primary" type="submit">Сохранить</button>
      </form>
      <form method="post" action="/profile">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="logout">
        <button class="text-btn" type="submit">Выйти из колонны</button>
      </form>
    <?php endif; ?>

    <h2>Выручки</h2>
    <div class="stack">
      <?php if (!$helps): ?>
        <?= render_empty('Пока тихо', $self ? 'Когда выручишь или попросишь — история появится здесь.' : 'Историй пока нет.') ?>
      <?php endif; ?>
      <?php foreach ($helps as $help): ?>
        <article class="signal">
          <p class="eyebrow"><?= ($help['part'] ?? '') === 'helped' ? 'Помог' : 'Попросил' ?> · <?= e($help['route']) ?></p>
          <h3><?= e($help['type_label']) ?></h3>
          <p><?= e($help['comment']) ?></p>
          <p class="fine"><?= e($help['ago']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</article>
<?php require __DIR__ . '/templates/footer.php';