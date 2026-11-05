<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/api_boot.php';

$action = (string) ($_GET['action'] ?? 'list');
$viewer = (int) ($_SESSION['user_id'] ?? 0);

if ($action === 'list') {
    json_response(['posts' => repo_posts($viewer ?: null)]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('method', 'Нужен POST.', 405);
}
if (!csrf_ok()) {
    fail('csrf_failed', 'Сессия устарела. Обнови страницу.', 403);
}
$user = require_user();
$in = request_data();
$postId = (int) ($in['post_id'] ?? 0);

if ($action === 'like') {
    $result = repo_like((int) $user['id'], $postId);
    if (empty($result['ok'])) {
        fail('like', (string) $result['message'], 422);
    }
    json_response($result);
}
if ($action === 'report') {
    $result = repo_report((int) $user['id'], $postId);
    if (empty($result['ok'])) {
        fail('report', (string) $result['message'], 422);
    }
    json_response(['ok' => true, 'message' => 'Жалоба ушла модератору.']);
}
if ($action === 'create') {
    if (!rate_hit('post:' . client_ip(), 6, 600)) {
        fail('rate', 'Слишком часто для эфира. Подожди.', 429);
    }
    $result = repo_create_post((int) $user['id'], $in);
    if (empty($result['ok'])) {
        fail('post', (string) $result['message'], 422);
    }
    json_response($result);
}

fail('not_found', 'Такого действия нет.', 404);
