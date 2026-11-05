<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

api_dispatch(static function (): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = (string) ($_GET['action'] ?? 'get');
    $viewer = current_user();
    if ($method === 'GET') {
        $id = (int) ($_GET['id'] ?? ($viewer['id'] ?? 0));
        $user = repo_user($id, $viewer && (int) $viewer['id'] === $id);
        if (!$user) {
            throw new HttpError('not_found', 'Водителя нет.', 404);
        }
        json_response(['user' => $user, 'helps' => repo_user_helps($id)]);
    }
    if (!$viewer) {
        throw new HttpError('auth_required', 'Сначала займите место в колонне.', 401);
    }
    csrf_check();
    if ($action === 'avatar') {
        $path = handle_upload($_FILES['avatar'] ?? [], 'avatars');
        json_response(['ok' => true, 'user' => repo_set_avatar((int) $viewer['id'], $path)]);
    }
    if ($action === 'car') {
        $path = handle_upload($_FILES['car'] ?? [], 'cars');
        json_response(['ok' => true, 'user' => repo_set_car((int) $viewer['id'], $path)]);
    }
    if ($action === 'status') {
        json_response(['ok' => true, 'user' => repo_update_profile((int) $viewer['id'], ['status' => request_data()['status'] ?? ''])]);
    }
    json_response(['ok' => true, 'user' => repo_update_profile((int) $viewer['id'], request_data())]);
});
