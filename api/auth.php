<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

api_dispatch(static function (): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = (string) ($_GET['action'] ?? '');
    if ($method === 'GET' && $action === 'check') {
        $user = current_user();
        json_response(['user' => $user]);
    }
    if ($method !== 'POST') {
        throw new HttpError('not_found', 'Нет такого запроса.', 404);
    }
    csrf_check();
    $in = request_data();
    if (honeypot_tripped($in)) {
        json_response(['ok' => true]);
    }
    if (rate_limited('auth', 5, 60)) {
        throw new HttpError('rate_limit', 'Слишком часто. Минуту тишины в эфире.', 429);
    }
    if ($action === 'logout') {
        logout_user();
        json_response(['ok' => true]);
    }
    if ($action === 'register') {
        $user = repo_register($in);
        login_user((string) $in['phone'], (string) $in['password']);
        json_response(['ok' => true, 'user' => current_user(true) ?: $user]);
    }
    if ($action === 'login') {
        if (!login_user((string) ($in['phone'] ?? ''), (string) ($in['password'] ?? ''))) {
            throw new HttpError('auth', 'Не слышно. Проверьте телефон и пароль.', 401);
        }
        json_response(['ok' => true, 'user' => current_user(true)]);
    }
    throw new HttpError('not_found', 'Нет такого запроса.', 404);
});
