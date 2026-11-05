<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$GLOBALS['page'] = 'auth';
$user = current_user();
$step = (int) ($_GET['step'] ?? 1);
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null, '/profile.php');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    assert_same_origin();
    csrf_check();
    if (honeypot_tripped($_POST)) {
        redirect('/auth.php');
    }
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'logout') {
        auth_logout();
        redirect('/');
    }
    if ($action === 'login') {
        handle(
            function () use ($next) {
                $raw = repo_login((string) ($_POST['phone'] ?? ''), (string) ($_POST['password'] ?? ''));
                auth_start($raw);
                flash('ok', 'Слышно. Доброй дороги.');
                return true;
            },
            $next,
            '/auth.php?mode=login'
        );
    }
    if ($action === 'register') {
        handle(
            function () {
                $raw = repo_register(
                    (string) ($_POST['phone'] ?? ''),
                    (string) ($_POST['password'] ?? ''),
                    (string) ($_POST['name'] ?? '')
                );
                auth_start($raw);
                flash('ok', 'Приём хороший. Осталось пару слов о вас.');
                return true;
            },
            '/auth.php?step=2',
            '/auth.php?step=1'
        );
    }
    if (!$user) {
        flash('error', 'Сначала телефон и пароль.');
        redirect('/auth.php?step=1');
    }
    if ($action === 'profile') {
        handle(
            function () use ($user) {
                repo_update_user((int) $user['id'], profile_payload([
                    'city' => $_POST['city'] ?? '',
                    'role' => $_POST['role'] ?? 'both',
                ]));
                current_user(true);
                return true;
            },
            '/auth.php?step=3',
            '/auth.php?step=2'
        );
    }
    if ($action === 'truck' || $action === 'skip') {
        handle(
            function () use ($user, $action) {
                if ($action === 'truck') {
                    repo_update_user((int) $user['id'], profile_payload([
                        'truck_brand' => $_POST['truck_brand'] ?? '',
                        'truck_plate' => $_POST['truck_plate'] ?? '',
                        'experience_y' => $_POST['experience_y'] ?? '',
                    ]));
                }
                flash('ok', 'Ты в колонне. Доброй дороги.');
                return true;
            },
            '/profile.php',
            '/auth.php?step=3'
        );
    }
    redirect('/auth.php');
}

if ($step > 1 && !$user) {
    $step = 1;
}
if ($user && $step === 1 && ($_GET['mode'] ?? '') !== 'login' && !isset($_GET['step'])) {
    $step = 2;
}

$meta = [
    'title' => 'Занять место в колонне — РосНавигация',
    'description' => 'Регистрация водителя: телефон, имя, роль. Приём! Как тебя слышно?',
    'canonical' => APP_URL . '/auth.php',
];
render_header($meta);
?>
    <section class="section">
      <div class="container wizard">
        <p class="kicker">Колонна</p>
        <h1><?= $step === 1 && ($_GET['mode'] ?? '') === 'login' ? 'С возвращением' : 'Приём! Как тебя слышно?' ?></h1>
        <div class="wizard__steps" aria-hidden="true">
          <span class="<?= $step >= 1 ? 'is-on' : '' ?>"></span>
          <span class="<?= $step >= 2 ? 'is-on' : '' ?>"></span>
          <span class="<?= $step >= 3 ? 'is-on' : '' ?>"></span>
        </div>

        <?php if ($step === 1 && ($_GET['mode'] ?? '') === 'login'): ?>
          <form method="post" action="/auth.php" id="login-form">
            <?= csrf_field() ?>
            <?= honeypot_field() ?>
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <label class="field"><span>Телефон</span><input name="phone" type="tel" inputmode="tel" autocomplete="tel" required placeholder="+7 900 000-00-00"></label>
            <label class="field"><span>Пароль</span><input name="password" type="password" autocomplete="current-password" required minlength="8"></label>
            <button class="btn" type="submit">Войти</button>
            <p class="meta"><a href="/auth.php">Нет позывного? Занять место</a></p>
          </form>
        <?php elseif ($step === 1): ?>
          <form method="post" action="/auth.php" id="register-form">
            <?= csrf_field() ?>
            <?= honeypot_field() ?>
            <input type="hidden" name="action" value="register">
            <label class="field"><span>Имя</span><input name="name" required maxlength="100" autocomplete="name" placeholder="Как к вам обращаться"></label>
            <label class="field"><span>Телефон</span><input name="phone" type="tel" inputmode="tel" autocomplete="tel" required placeholder="+7 900 000-00-00"></label>
            <label class="field"><span>Пароль</span><input name="password" type="password" autocomplete="new-password" required minlength="8" placeholder="Не короче 8 символов"></label>
            <button class="btn" type="submit">Дальше</button>
            <p class="meta"><a href="/auth.php?mode=login">Уже в колонне? Войти</a></p>
          </form>
        <?php elseif ($step === 2): ?>
          <form method="post" action="/auth.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">
            <label class="field"><span>Город</span><input name="city" maxlength="100" value="<?= e($user['city'] ?? '') ?>" placeholder="Откуда обычно выезжаете"></label>
            <fieldset class="choice">
              <legend>Кто вы на трассе</legend>
              <?php
              $roles = ['seeker' => 'Ищу помощь', 'helper' => 'Помогаю', 'both' => 'И то, и то'];
              foreach ($roles as $value => $label): ?>
                <label><input type="radio" name="role" value="<?= e($value) ?>" <?= ($user['role'] ?? 'both') === $value ? 'checked' : '' ?>> <?= e($label) ?></label>
              <?php endforeach; ?>
            </fieldset>
            <button class="btn" type="submit">Дальше</button>
          </form>
        <?php else: ?>
          <form method="post" action="/auth.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="truck">
            <p class="lede">Машину можно не указывать. На стоянке добавите в кабине.</p>
            <label class="field"><span>Марка</span><input name="truck_brand" maxlength="100" placeholder="Volvo FH, КАМАЗ, кемпер"></label>
            <label class="field"><span>Номер</span><input name="truck_plate" maxlength="20" placeholder="Виден только вам"></label>
            <label class="field"><span>Стаж, лет</span><input name="experience_y" inputmode="numeric" maxlength="2"></label>
            <div class="actions">
              <button class="btn" type="submit" name="action" value="truck">Готово</button>
              <button class="btn btn--ghost" type="submit" name="action" value="skip">Пропустить</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </section>
<?php
render_footer($meta);
