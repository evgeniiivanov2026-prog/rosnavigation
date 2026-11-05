<?php
declare(strict_types=1);

define('ROSNAV_API', true);
require dirname(__DIR__) . '/inc/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = (string) ($_GET['action'] ?? 'get');
$viewer = current_user();

if ($method === 'GET') {
    $id = (int) ($_GET['id'] ?? ($viewer['id'] ?? 0));
    $user = repo_user_public($id, $viewer['id'] ?? null);
    if (!$user) {
        json_response(['error' => 'not_found', 'message' => 'Водителя нет.'], 404);
    }
    json_response(['ok' => true, 'user' => $user, 'helps' => repo_helps($id, $viewer['id'] ?? null)]);
}

assert_same_origin();
csrf_check();
$user = require_user();
$in = request_data();

if ($action === 'ping') {
    $lat = numeric_or_null($in['lat'] ?? null);
    $lng = numeric_or_null($in['lng'] ?? null);
    repo_touch((int) $user['id'], $lat, $lng);
    if (isset($in['status'])) {
        repo_update_user((int) $user['id'], profile_payload(['status' => $in['status']]));
    }
    json_response(['ok' => true, 'user' => current_user(true)]);
}

if ($action === 'update') {
    $updated = repo_update_user((int) $user['id'], profile_payload($in));
    current_user(true);
    json_response(['ok' => true, 'user' => $updated]);
}

json_response(['error' => 'not_found', 'message' => 'Нет такого действия'], 404);
