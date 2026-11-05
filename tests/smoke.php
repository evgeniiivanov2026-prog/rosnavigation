<?php
declare(strict_types=1);

$store = sys_get_temp_dir() . '/rosnav-smoke-' . getmypid() . '.json';
putenv('ROSNAV_STORE=' . $store);
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require dirname(__DIR__) . '/inc/bootstrap.php';

foreach (glob(dirname(__DIR__) . '/storage/ratelimit/*') ?: [] as $stale) {
    if (is_file($stale)) {
        unlink($stale);
    }
}

function check(bool $ok, string $message): void
{
    if ($ok) {
        echo "ok  $message\n";
        return;
    }
    fwrite(STDERR, "FAIL $message\n");
    exit(1);
}

check(route_point('м7') !== null, 'трасса без дефиса');
check(route_point('трасса М-7, 820') !== null, 'трасса внутри фразы');
check(route_point('двор') === null, 'не трасса не ставит точку');

try {
    assert_person_name('<script>');
    check(false, 'имя со скриптом должно отвалиться');
} catch (HttpError $e) {
    check($e->error === 'name', 'имя со скриптом');
}

try {
    repo_register('+79001112233', 'short', 'Иван');
    check(false, 'короткий пароль');
} catch (HttpError $e) {
    check(true, 'короткий пароль');
}

$first = repo_register('+79001112233', 'trassa-15', 'Иван Дорожный');
auth_start($first);
check(current_user()['phone'] === '+79001112233', 'свой телефон виден себе');
check(!isset(current_user()['password_hash']), 'хеш не уходит в профиль');

try {
    repo_register('+79001112233', 'trassa-15', 'Иван');
    check(false, 'повтор телефона');
} catch (HttpError $e) {
    check($e->status === 409, 'повтор телефона');
}

$signal = repo_sos_create((int) $first['id'], [
    'type' => 'fuel',
    'route' => 'М-7',
    'km' => 120,
    'comment' => 'Бак пустой, до колонки не дотягиваю',
]);
check($signal['approx_geo'] === true, 'без GPS точка ставится по трассе');
check($signal['author']['phone'] === '+79001112233', 'автор видит свой номер');

try {
    repo_sos_create((int) $first['id'], ['type' => 'breakdown', 'route' => 'М-4', 'comment' => 'второй сигнал']);
    check(false, 'второй сигнал');
} catch (HttpError $e) {
    check($e->error === 'already_active', 'второй сигнал закрыт');
}

try {
    repo_sos_accept(501, (int) $first['id']);
    check(false, 'учебный сигнал');
} catch (HttpError $e) {
    check($e->error === 'demo', 'учебный сигнал нельзя принять');
}

$hidden = repo_sos_public((int) $signal['id'], null);
check($hidden && $hidden['author']['phone'] === null, 'чужим номер автора закрыт');
check($hidden['contact_phone'] === null, 'до приёма контакта нет');

auth_logout();
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$second = repo_register('+79002223344', 'kolonna-15', 'Пётр Плечо');
auth_start($second);
$seen = repo_sos_public((int) $signal['id'], (int) $second['id']);
check($seen['can_accept'] === true, 'второй водитель может принять');
check($seen['author']['phone'] === null, 'номер всё ещё закрыт');

try {
    repo_message_add((int) $signal['id'], (int) $second['id'], 'Еду');
    check(false, 'чат до приёма');
} catch (HttpError $e) {
    check($e->error === 'forbidden', 'чат до приёма закрыт');
}

$accepted = repo_sos_accept((int) $signal['id'], (int) $second['id']);
check($accepted['status'] === 'accepted', 'сигнал принят');
check($accepted['contact_phone'] === '+79001112233', 'принявшему открыли номер');
repo_message_add((int) $signal['id'], (int) $second['id'], 'Буду через двадцать минут');
$messages = repo_messages((int) $signal['id'], (int) $second['id']);
check(count($messages) === 1, 'сообщение в чате');

auth_logout();
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
auth_start($first);
$done = repo_sos_complete((int) $signal['id'], (int) $first['id'], 5, 'Доехал');
check($done['status'] === 'completed', 'выручка закрыта');
$helper = repo_user_public((int) $second['id'], (int) $second['id']);
check($helper && $helper['helps_count'] === 1, 'выручка посчитана');
check($helper['rating'] === 5.0, 'оценка записана');

$near = haversine_km(56.129, 40.4066, 56.136, 40.396);
check($near < 2, 'километры рядом сходятся');
check(haversine_km(55.75, 37.62, 43.12, 131.9) > 6000, 'дальние точки не схлопываются');

$likes = repo_like((int) $first['id'], 801);
check($likes === 15, 'пятюня к образцу: было 14');
try {
    repo_like((int) $first['id'], 801);
    check(false, 'повтор пятюни');
} catch (HttpError $e) {
    check($e->status === 409, 'повтор пятюни');
}

repo_report('post', 801, 'Проверка жалобы');
$vladimir = repo_partners(['city' => 'Владимир']);
check(count($vladimir) === 1, 'фильтр партнёров по городу');
check($vladimir[0]['phone'] === null, 'у образца нет телефона');

$poll = repo_poll(56.13, 40.40, 15, (int) $first['id']);
check(isset($poll['count_online'], $poll['sos'], $poll['server_time']), 'поллинг собрался');
$encoded = json_encode($poll);
check(!str_contains((string) $encoded, 'password_hash'), 'в поллинге нет хеша');

$_SESSION['csrf_token'] = 'abc';
$_SERVER['HTTP_X_CSRF_TOKEN'] = 'nope';
try {
    csrf_check();
    check(false, 'чужой csrf');
} catch (HttpError $e) {
    check($e->status === 403, 'чужой csrf');
}

echo "SMOKE OK\n";
@unlink($store);
