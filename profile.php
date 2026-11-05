<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$viewer = current_user();
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0 && !$viewer) {
    header('Location: /auth?next=/profile');
    exit;
}
if ($id <= 0 && $viewer) {
    $id = (int) $viewer['id'];
}
$error = null;
$notice = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        csrf_check();
        if (!$viewer || (int) $viewer['id'] !== $id) {
            throw new HttpError('forbidden', 'Чужой профиль не правится.', 403);
        }
        $action = (string) ($_POST['action'] ?? 'update');
        if ($action === 'avatar' && !empty($_FILES['avatar']['name'])) {
            repo_set_avatar($id, handle_upload($_FILES['avatar'], 'avatars'));
            $notice = 'Портрет обновлён.';
        } elseif ($action === 'car' && !empty($_FILES['car']['name'])) {
            repo_set_car($id, handle_upload($_FILES['car'], 'cars'));
            $notice = 'Фото машины на обложке.';
        } else {
            repo_update_profile($id, $_POST);
            $notice = 'Профиль записан.';
        }
        $viewer = current_user(true);
    } catch (HttpError $e) {
        $error = $e->getMessage();
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
}

$user = repo_user($id, $viewer && (int) $viewer['id'] === $id);
if (!$user) {
    http_response_code(404);
    $page = [
        'title' => 'Нет такого водителя — РосНавигация',
        'description' => 'Профиль не найден.',
        'path' => '/profile',
        'nav' => 'profile',
        'body' => 'page-profile',
        'script' => 'profile',
        'robots' => 'noindex,follow',
    ];
    require __DIR__ . '/templates/header.php';
    echo '<main id="main" class="miss"><div><h1>Такого позывного нет</h1><p><a class="btn btn--brand" href="/map">На карту</a></p></div></main>';
    require __DIR__ . '/templates/footer.php';
    exit;
}

$self = $viewer && (int) $viewer['id'] === (int) $user['id'];
$helps = repo_user_helps((int) $user['id']);
$posts = array_slice(repo_user_posts((int) $user['id']), 0, 5);

$page = [
    'title' => $user['name'] . ' — РосНавигация',
    'description' => $user['name'] . ($user['city'] ? ', ' . $user['city'] : '') . '. ' . $user['status_label'] . '.',
    'path' => '/profile/' . $user['id'],
    'nav' => 'profile',
    'body' => 'page-profile',
    'script' => 'profile',
];
require __DIR__ . '/templates/header.php';
?>
<main id="main" class="section">
  <div class="container">
    <div class="profile-cover">
      <?php if ($user['car_path']): ?>
        <img src="<?= e($user['car_path']) ?>" alt="Машина <?= e($user['name']) ?>" width="1180" height="180">
      <?php endif; ?>
    </div>
    <div class="profile__head">
      <?= view_avatar($user['name'], $user['avatar_path']) ?>
      <div>
        <h1><?= e($user['name']) ?></h1>
        <p><?= e($user['city'] ?: 'Город не указан') ?><?= $user['truck_brand'] ? ' · ' . e($user['truck_brand']) : '' ?><?= $user['experience_y'] ? ' · ' . (int) $user['experience_y'] . ' ' . e(ru_plural((int) $user['experience_y'], 'год', 'года', 'лет')) . ' на линии' : '' ?></p>
        <p class="stack-top"><?= view_status($user['status']) ?></p>
      </div>
    </div>
    <?php if ($user['motto']): ?><p class="quote stack-top"><?= e($user['motto']) ?></p><?php endif; ?>
    <div class="badges">
      <?php foreach ($user['badges'] as $badge): ?>
        <p class="badge"><strong><?= e($badge['name']) ?></strong><span><?= e($badge['hint']) ?></span></p>
      <?php endforeach; ?>
    </div>
    <p class="rating"><span class="rating__num"><?= e(number_format((float) $user['rating'], 1, ',', '')) ?></span><span class="rating__meta"><?= (int) $user['helps_count'] ?> выручек</span></p>
    <?php if ($error): ?><div class="state state--error stack-top"><p><?= e($error) ?></p></div><?php endif; ?>
    <?php if ($notice): ?><div class="state stack-top"><p><?= e($notice) ?></p></div><?php endif; ?>

    <?php if ($self): ?>
      <form class="panel stack-top" method="post" action="/profile" id="profile-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <div class="field">
          <label for="status">Статус</label>
          <select id="status" name="status">
            <?php foreach (catalog_statuses() as $key => $meta): ?>
              <option value="<?= e($key) ?>"<?= $user['status'] === $key ? ' selected' : '' ?>><?= e($meta['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="motto">Личный девиз</label>
          <input id="motto" name="motto" maxlength="200" value="<?= e($user['motto']) ?>">
        </div>
        <div class="field">
          <label for="city">Город</label>
          <input id="city" name="city" maxlength="80" value="<?= e($user['city']) ?>">
        </div>
        <div class="field">
          <label for="truck">Машина</label>
          <input id="truck" name="truck_brand" maxlength="80" value="<?= e($user['truck_brand']) ?>">
        </div>
        <div class="field">
          <label for="trailer">Прицеп</label>
          <input id="trailer" name="truck_trailer" maxlength="40" value="<?= e($user['truck_trailer']) ?>">
        </div>
        <div class="field">
          <label for="plate">Номер</label>
          <input id="plate" name="truck_plate" maxlength="20" value="<?= e($user['truck_plate'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="route">Где сейчас, трасса</label>
          <input id="route" name="route" maxlength="50" value="<?= e($user['route']) ?>" class="mono">
        </div>
        <label class="btn btn--ghost">
          <input type="checkbox" id="ether-sound"> Звуки эфира
        </label>
        <p class="fine">По умолчанию тихо. Короткий писк только когда вы сами передаёте сигнал.</p>
        <div class="form-actions"><button class="btn btn--brand" type="submit">Записать</button></div>
      </form>
      <form class="stack-top" method="post" action="/profile" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="avatar">
        <div class="field">
          <label for="avatar">Портрет, до 2 МБ</label>
          <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp">
        </div>
        <button class="btn btn--ghost" type="submit">Поставить портрет</button>
      </form>
      <form class="stack-top" method="post" action="/profile" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="car">
        <div class="field">
          <label for="car">Фото машины на обложку</label>
          <input id="car" name="car" type="file" accept="image/jpeg,image/png,image/webp">
        </div>
        <button class="btn btn--ghost" type="submit">Поставить обложку</button>
      </form>
    <?php endif; ?>

    <section class="stack-top">
      <h2>Выручки</h2>
      <div class="feed stack-top">
        <?php if (!$helps): ?>
          <?= view_state('empty', 'Пока без закрытых выручек', 'Когда доедете до своих и закроете сигнал, он появится здесь.') ?>
        <?php endif; ?>
        <?php foreach ($helps as $help) {
            echo view_signal($help, true);
        } ?>
      </div>
    </section>
    <?php if ($posts): ?>
      <section class="stack-top">
        <h2>В эфире</h2>
        <div class="feed stack-top">
          <?php foreach ($posts as $post) {
              echo view_post($post);
          } ?>
        </div>
      </section>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/templates/footer.php'; ?>
