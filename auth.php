<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '/profile', '/profile');
$welcome = isset($_GET['welcome']);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_ok()) {
        flash_set('error', 'Сессия устарела. Обнови страницу.');
        redirect('/auth');
    }
    if (!rate_hit('auth:' . client_ip(), 5, 60)) {
        flash_set('error', 'Слишком часто. Подожди минуту и позови ещё раз.');
        redirect('/auth?next=' . rawurlencode($next));
    }
    $action = (string) ($_POST['action'] ?? 'register');
    if (!empty($_POST['website'])) {
        redirect('/auth?welcome=1');
    }
    if ($action === 'login') {
        $result = perform_login((string) ($_POST['phone'] ?? ''), (string) ($_POST['password'] ?? ''));
        if (empty($result['ok'])) {
            flash_set('error', (string) $result['message']);
            redirect('/auth?next=' . rawurlencode($next));
        }
        redirect($next);
    }
    $result = perform_register($_POST);
    if (empty($result['ok'])) {
        flash_set('error', (string) $result['message']);
        redirect('/auth?next=' . rawurlencode($next));
    }
    redirect('/auth?welcome=1&next=' . rawurlencode($next));
}

$me = current_user();
$pageTitle = 'В колонну — РосНавигация';
$pageDescription = 'Занять место в колонне: телефон, имя и пара слов о машине.';
$pageCanonical = APP_URL . '/auth';
$pageActive = 'auth';
$bodyClass = 'page-auth';
$pageScript = '/js/pages/auth.js';

require __DIR__ . '/templates/header.php';
?>
<div class="container section">
  <?php if ($welcome && $me): ?>
    <div class="welcome">
      <p class="kicker">Приём</p>
      <h1>Ты в колонне. Доброй дороги<?= $me['name'] ? ', ' . e($me['name']) : '' ?>!</h1>
      <p class="lede">Место занято. Если встанешь — выходи на связь. Если едешь мимо — ответь своим.</p>
      <div class="actions">
        <a class="btn btn--danger" href="<?= e($next === '/profile' ? '/sos' : $next) ?>">Выйти на связь</a>
        <a class="btn btn--ghost" href="/profile">Открыть профиль</a>
      </div>
    </div>
  <?php elseif ($me): ?>
    <div class="welcome">
      <h1>Ты уже в колонне</h1>
      <p class="lede"><?= e($me['name']) ?>, профиль на месте. Можно править машину и статус.</p>
      <a class="btn btn--primary" href="/profile">К профилю</a>
    </div>
  <?php else: ?>
    <div class="split">
      <div>
        <p class="kicker">Колонна</p>
        <h1>Приём! Как тебя слышно?</h1>
        <p class="lede">Три коротких шага. Машину можно не заполнять — главное, чтобы свои понимали, кто на связи.</p>
        <?php if (DEMO_MODE): ?>
          <div class="panel">
            <p>Учебный вход уже есть: номер <strong>+7 900 000-00-15</strong>, пароль <strong>doroga15</strong>. Это Сергей Волгин, вымышленный водитель.</p>
          </div>
        <?php endif; ?>
      </div>
      <div class="stack">
        <form class="panel" id="join-form" method="post" action="/auth" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="register">
          <input type="hidden" name="next" value="<?= e($next) ?>">
          <p class="hp" aria-hidden="true"><label>Сайт<input name="website" tabindex="-1" autocomplete="off"></label></p>
          <p class="form-error" role="alert"></p>
          <div class="wizard__step is-active">
            <label class="field"><span>Телефон</span><input name="phone" type="tel" inputmode="tel" autocomplete="tel" required placeholder="+7 900 000-00-00"></label>
            <label class="field"><span>Пароль</span><input name="password" type="password" autocomplete="new-password" required minlength="8" placeholder="Не короче 8 знаков"></label>
          </div>
          <div class="wizard__step">
            <label class="field"><span>Имя или позывной</span><input name="name" required minlength="2" maxlength="100" autocomplete="name" placeholder="Как к тебе обращаться"></label>
            <label class="field"><span>Город</span><input name="city" maxlength="100" placeholder="Откуда обычно выходишь"></label>
            <fieldset class="choices">
              <legend class="fine">Зачем ты здесь</legend>
              <label class="choice"><input type="radio" name="role" value="seeker" required><span>Ищу помощь</span></label>
              <label class="choice"><input type="radio" name="role" value="helper"><span>Помогаю</span></label>
              <label class="choice"><input type="radio" name="role" value="both" checked><span>И то, и то</span></label>
            </fieldset>
          </div>
          <div class="wizard__step">
            <label class="field"><span>Машина</span><input name="truck_brand" maxlength="100" placeholder="Volvo FH, КамАЗ, MAN"></label>
            <label class="field"><span>Прицеп</span><input name="truck_trailer" maxlength="50" placeholder="тент, реф, штора"></label>
            <label class="field"><span>Стаж, лет</span><input name="experience_y" inputmode="numeric" maxlength="2" placeholder="Не обязательно"></label>
            <p class="fine">Можно пропустить и дописать в профиле.</p>
          </div>
          <div class="wizard__nav">
            <button class="btn btn--ghost js-only" type="button" data-prev hidden>Назад</button>
            <button class="btn btn--primary js-only" type="button" data-next>Дальше</button>
            <button class="btn btn--primary" type="submit" data-submit>Занять место в колонне</button>
          </div>
        </form>
        <form class="panel" method="post" action="/auth">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="login">
          <input type="hidden" name="next" value="<?= e($next) ?>">
          <h2>Уже в колонне</h2>
          <label class="field"><span>Телефон</span><input name="phone" type="tel" autocomplete="username" required></label>
          <label class="field"><span>Пароль</span><input name="password" type="password" autocomplete="current-password" required></label>
          <button class="btn btn--ghost" type="submit">Войти</button>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/templates/footer.php';