<?php
declare(strict_types=1);

define('ROSNAV_API', true);
require dirname(__DIR__) . '/inc/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = (string) ($_GET['action'] ?? '');

if ($method === 'GET' && $action === 'check') {
    json_response(['ok' => true, 'user' => current_user()]);
}

if ($method !== 'POST') {
    json_response(['error' => 'not_found', 'message' => 'Нет такого действия'], 404);
}

assert_same_origin();
$in = request_data();
if (honeypot_tripped($in)) {
    json_response(['ok' => true]);
}

if ($action === 'logout') {
    csrf_check();
    auth_logout();
    json_response(['ok' => true]);
}

if ($action === 'login') {
    csrf_check();
    $raw = repo_login((string) ($in['phone'] ?? ''), (string) ($in['password'] ?? ''));
    auth_start($raw);
    json_response(['ok' => true, 'user' => current_user(true)]);
}

if ($action === 'register') {
    csrf_check();
    $raw = repo_register(
        (string) ($in['phone'] ?? ''),
        (string) ($in['password'] ?? ''),
        (string) ($in['name'] ?? '')
    );
    auth_start($raw);
    json_response(['ok' => true, 'user' => current_user(true)]);
}

json_response(['error' => 'not_found', 'message' => 'Нет такого действия'], 404);
