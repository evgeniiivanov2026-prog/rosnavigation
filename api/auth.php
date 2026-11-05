<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/api_boot.php';

$action = (string) ($_GET['action'] ?? '');

if ($action === 'check') {
    $user = current_user();
    json_response(['user' => $user ? public_user($user, true) : null]);
}

if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        fail('csrf_failed', 'Сессия устарела. Обнови страницу.', 403);
    }
    perform_logout();
    json_response(['ok' => true]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('method', 'Нужен POST.', 405);
}
if (!csrf_ok()) {
    fail('csrf_failed', 'Сессия устарела. Обнови страницу.', 403);
}
if (!rate_hit('auth:' . client_ip(), 5, 60)) {
    fail('rate', 'Слишком часто. Подожди минуту и позови ещё раз.', 429);
}

$in = request_data();
if ($action === 'login') {
    $result = perform_login((string) ($in['phone'] ?? ''), (string) ($in['password'] ?? ''));
    if (empty($result['ok'])) {
        fail('credentials', (string) $result['message'], 401);
    }
    $user = current_user();
    json_response(['ok' => true, 'user' => $user ? public_user($user, true) : null]);
}

if ($action === 'register') {
    if (!empty($in['website'])) {
        json_response(['ok' => true]);
    }
    $result = perform_register($in);
    if (empty($result['ok'])) {
        fail((string) ($result['error'] ?? 'invalid'), (string) $result['message'], 422);
    }
    json_response(['ok' => true, 'id' => $result['id']]);
}

fail('not_found', 'Такого действия нет.', 404);
