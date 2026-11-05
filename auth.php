<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$viewer = current_user();
$error = null;
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '/profile');
$done = !empty($_GET['done']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        csrf_check();
        $action = (string) ($_POST['action'] ?? 'login');
        if ($action === 'logout') {
            logout_user();
            header('Location: /');
            exit;
        }
        if (honeypot_tripped($_POST)) {
            header('Location: /auth?done=1');
            exit;
        }
        if (rate_limited('auth', 5, 60)) {
            throw new HttpError('rate_limit', 'Слишком часто. Минуту тишины в эфире.', 429);
        }
        if ($action === 'register') {
            repo_register($_POST);
            if (!login_user((string) $_POST['phone'], (string) $_POST['password'])) {
                throw new HttpError('auth', 'Место есть, но войти не вышло. Попробуйте ещё раз.', 500);
            }
            header('Location: /auth?done=1&next=' . rawurlencode($next));
            exit;
        }
        if (!login_user((string) ($_POST['phone'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            throw new HttpError('auth', 'Не слышно. Проверьте телефон и пароль.', 401);
        }
        header('Location: ' . $next);
        exit;
    } catch (HttpError $e) {
        $error = $e->getMessage();
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
    $viewer = current_user(true);
}

$page = [
    'title' => 'В колонну — РосНавигация',
    'description' => 'Телефон, имя и роль на трассе. Займите место в колонне РосНавигации.',
    'path' => '/auth',
    'nav' => 'auth',
    'body' => 'page-auth',
    'script' => 'auth',
    'robots' => 'noindex,follow',
];
require __DIR__ . '/templates/header.php';
?>
<main id="main" class="section">
  <div class="container wizard">
    <?php if ($done): ?>
      <div class="done">
        <p class="kicker">Колонна</p>
        <h1>Ты в колонне. Доброй дороги!</h1>
        <p class="lede">Имя уже в эфире. Если встанете — свои услышат.</p>
        <p class="stack-top"><a class="btn btn--brand" href="<?= e($next) ?>">Дальше по маршруту</a></p>
      </div>
    <?php elseif ($viewer && ($_GET['mode'] ?? '') !== 'in'): ?>
      <div class="done">
        <h1><?= e($viewer['name']) ?>, вы уже на связи</h1>
        <p class="lede"><?= e($viewer['city'] ?: 'Город можно дописать в профиле') ?></p>
        <p class="stack-top"><a class="btn btn--brand" href="/profile">Открыть профиль</a></p>
      </div>
    <?php else: ?>
      <p class="kicker">Приём! Как тебя слышно?</p>
      <h1>Занять место в колонне</h1>
      <div class="auth-tabs">
        <a class="btn <?= (($_GET['mode'] ?? '') !== 'in') ? 'btn--brand' : 'btn--ghost' ?>" href="/auth?next=<?= e(rawurlencode($next)) ?>">Впервые</a>
        <a class="btn <?= (($_GET['mode'] ?? '') === 'in') ? 'btn--brand' : 'btn--ghost' ?>" href="/auth?mode=in&next=<?= e(rawurlencode($next)) ?>">Уже свой</a>
      </div>
      <?php if ($error): ?><div class="state state--error"><p><?= e($error) ?></p></div><?php endif; ?>

      <?php if (($_GET['mode'] ?? '') === 'in'): ?>
        <form method="post" action="/auth" class="stack-top">
          <?= csrf_field() ?>
          <?= honeypot_field() ?>
          <input type="hidden" name="action" value="login">
          <input type="hidden" name="next" value="<?= e($next) ?>">
          <div class="field">
            <label for="login-phone">Телефон</label>
            <input id="login-phone" name="phone" type="tel" inputmode="tel" autocomplete="username" required placeholder="+7 900 000-00-15">
          </div>
          <div class="field">
            <label for="login-password">Пароль</label>
            <input id="login-password" name="password" type="password" autocomplete="current-password" required minlength="8">
          </div>
          <button class="btn btn--brand" type="submit">Войти</button>
        </form>
      <?php else: ?>
        <form method="post" action="/auth" id="join-form" class="stack-top">
          <?= csrf_field() ?>
          <?= honeypot_field() ?>
          <input type="hidden" name="action" value="register">
          <input type="hidden" name="next" value="<?= e($next) ?>">
          <div class="wizard__steps" aria-hidden="true"><span class="is-on"></span><span></span><span></span></div>
          <section data-step="1">
            <div class="field">
              <label for="phone">Телефон</label>
              <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required placeholder="+7 900 000-00-00">
            </div>
            <div class="field">
              <label for="password">Пароль, от 8 символов</label>
              <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8">
            </div>
            <button class="btn btn--brand" type="button" data-next>Дальше</button>
          </section>
          <section data-step="2">
            <div class="field">
              <label for="name">Имя или позывной</label>
              <input id="name" name="name" required maxlength="40" autocomplete="nickname" placeholder="Как к вам обращаться">
            </div>
            <div class="field">
              <label for="city">Город</label>
              <input id="city" name="city" maxlength="80" autocomplete="address-level2">
            </div>
            <fieldset class="role-pick">
              <legend>Кем выходите на связь</legend>
              <label><input type="radio" name="role" value="seeker" required> Ищу помощь</label>
              <label><input type="radio" name="role" value="helper"> Помогаю</label>
              <label><input type="radio" name="role" value="both" checked> И то, и то</label>
            </fieldset>
            <div class="form-actions">
              <button class="btn btn--ghost" type="button" data-prev>Назад</button>
              <button class="btn btn--brand" type="button" data-next>Дальше</button>
            </div>
          </section>
          <section data-step="3">
            <div class="field">
              <label for="truck">Машина</label>
              <input id="truck" name="truck_brand" maxlength="80" placeholder="Volvo FH">
            </div>
            <div class="field">
              <label for="plate">Номер</label>
              <input id="plate" name="truck_plate" maxlength="20" autocomplete="off">
            </div>
            <div class="field">
              <label for="years">Стаж, лет</label>
              <input id="years" name="experience_y" inputmode="numeric" maxlength="2">
            </div>
            <div class="form-actions">
              <button class="btn btn--ghost" type="button" data-prev>Назад</button>
              <button class="btn btn--quiet" type="submit" name="skip" value="1">Пропустить</button>
              <button class="btn btn--brand" type="submit">В колонну</button>
            </div>
          </section>
        </form>
      <?php endif; ?>
      <?php if (app_demo()): ?>
        <p class="demo-hint">Демо-эфир: телефон +7 900 000-00-15, пароль kolonna15. Перед боевым запуском этот вход отключается.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/templates/footer.php'; ?>
