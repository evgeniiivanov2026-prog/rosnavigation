<?php
declare(strict_types=1);

define('ROSNAV_API', true);
require dirname(__DIR__) . '/inc/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = (string) ($_GET['action'] ?? 'list');
$viewer = current_user();

if ($method === 'GET') {
    json_response(['ok' => true, 'posts' => repo_posts($viewer['id'] ?? null)]);
}

assert_same_origin();
csrf_check();
$in = request_data();
if ($action === 'report') {
    repo_report('post', (int) ($in['id'] ?? 0), (string) ($in['reason'] ?? ''));
    json_response(['ok' => true]);
}
$user = require_user();
if ($action === 'like') {
    $count = repo_like((int) $user['id'], (int) ($in['id'] ?? 0));
    json_response(['ok' => true, 'result' => $count]);
}
if ($action === 'create') {
    $created = repo_post_create((int) $user['id'], $in, null);
    json_response(['ok' => true, 'result' => $created]);
}

json_response(['error' => 'not_found', 'message' => 'Нет такого действия'], 404);
