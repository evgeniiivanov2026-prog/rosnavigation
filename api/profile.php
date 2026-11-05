<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/api_boot.php';

$action = (string) ($_GET['action'] ?? 'get');
if ($action === 'get' && isset($_GET['id'])) {
    $row = repo_user((int) $_GET['id']);
    if (!$row) {
        fail('not_found', 'Водителя нет.', 404);
    }
    $self = (int) ($_SESSION['user_id'] ?? 0) === (int) $row['id'];
    json_response(['user' => public_user($row, $self)]);
}

$user = require_user();
if ($action === 'get') {
    json_response(['user' => public_user($user, true)]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('method', 'Нужен POST.', 405);
}
if (!csrf_ok()) {
    fail('csrf_failed', 'Сессия устарела. Обнови страницу.', 403);
}
if ($action === 'ping') {
    $in = request_data();
    repo_ping((int) $user['id'], (float) ($in['lat'] ?? 0), (float) ($in['lng'] ?? 0));
    json_response(['ok' => true]);
}
if ($action === 'update') {
    $result = repo_save_profile((int) $user['id'], request_data());
    if (empty($result['ok'])) {
        fail('profile', (string) $result['message'], 422);
    }
    $fresh = repo_user((int) $user['id']);
    json_response(['ok' => true, 'user' => $fresh ? public_user($fresh, true) : null]);
}

fail('not_found', 'Такого действия нет.', 404);
