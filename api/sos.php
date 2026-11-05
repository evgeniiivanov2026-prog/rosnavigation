<?php
declare(strict_types=1);

define('ROSNAV_API', true);
require dirname(__DIR__) . '/inc/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = (string) ($_GET['action'] ?? '');
$viewer = current_user();

if ($method === 'GET' && $action === 'poll') {
    $lat = numeric_or_null($_GET['lat'] ?? null);
    $lng = numeric_or_null($_GET['lng'] ?? null);
    $radius = is_numeric($_GET['radius'] ?? null) ? (float) $_GET['radius'] : (float) HELP_RADIUS_KM;
    json_response(repo_poll($lat, $lng, $radius, $viewer['id'] ?? null));
}

if ($method !== 'POST') {
    json_response(['error' => 'not_found', 'message' => 'Нет такого действия'], 404);
}

assert_same_origin();
csrf_check();
$user = require_user();
$in = request_data();
if (honeypot_tripped($in)) {
    json_response(['ok' => true]);
}
$id = (int) ($in['id'] ?? 0);

if ($action === 'create') {
    json_response(['ok' => true, 'result' => repo_sos_create((int) $user['id'], $in)]);
}
if ($action === 'cancel') {
    json_response(['ok' => true, 'result' => repo_sos_cancel($id, (int) $user['id'])]);
}
if ($action === 'accept') {
    json_response(['ok' => true, 'result' => repo_sos_accept($id, (int) $user['id'])]);
}
if ($action === 'complete') {
    $rating = isset($in['rating']) && $in['rating'] !== '' ? (int) $in['rating'] : null;
    json_response(['ok' => true, 'result' => repo_sos_complete($id, (int) $user['id'], $rating, (string) ($in['review'] ?? ''))]);
}
if ($action === 'message') {
    json_response(['ok' => true, 'result' => repo_message_add($id, (int) $user['id'], (string) ($in['body'] ?? ''))]);
}

json_response(['error' => 'not_found', 'message' => 'Нет такого действия'], 404);
