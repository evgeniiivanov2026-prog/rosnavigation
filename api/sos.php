<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/api_boot.php';

$action = (string) ($_GET['action'] ?? '');
if ($action === 'list') {
    $lat = isset($_GET['lat']) ? (float) $_GET['lat'] : null;
    $lng = isset($_GET['lng']) ? (float) $_GET['lng'] : null;
    json_response(['sos' => repo_signals($lat, $lng)]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('method', 'Нужен POST.', 405);
}
if (!csrf_ok()) {
    fail('csrf_failed', 'Сессия устарела. Обнови страницу.', 403);
}
if (!rate_hit('sos:' . client_ip(), 8, 600)) {
    fail('rate', 'Слишком часто. Подожди немного.', 429);
}

$user = require_user();
$uid = (int) $user['id'];
$in = request_data();
$id = (int) ($in['sos_id'] ?? 0);
$result = match ($action) {
    'create' => repo_create_sos($uid, $in),
    'cancel' => repo_cancel_sos($id, $uid),
    'accept' => repo_accept_sos($id, $uid),
    'complete' => repo_complete_sos($id, $uid),
    default => ['ok' => false, 'message' => 'Такого действия нет.'],
};
if (empty($result['ok'])) {
    $status = ($result['error'] ?? '') === 'already_active' ? 409 : 422;
    fail((string) ($result['error'] ?? 'failed'), (string) ($result['message'] ?? 'Не вышло.'), $status);
}
json_response(['ok' => true, 'sos_id' => $result['id'] ?? $id]);
