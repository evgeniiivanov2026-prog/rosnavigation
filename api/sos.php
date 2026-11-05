<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

api_dispatch(static function (): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = (string) ($_GET['action'] ?? '');
    $viewer = current_user();

    if ($method === 'GET' && $action === 'poll') {
        $lat = (float) ($_GET['lat'] ?? 0);
        $lng = (float) ($_GET['lng'] ?? 0);
        json_response([
            'sos' => repo_sos_near(coords_valid($lat, $lng) ? $lat : null, coords_valid($lat, $lng) ? $lng : null, 15),
            'helpers' => repo_helpers_near(coords_valid($lat, $lng) ? $lat : null, coords_valid($lat, $lng) ? $lng : null, 15),
            'count_online' => repo_stats()['online'],
            'server_time' => date('c'),
        ]);
    }
    if ($method === 'GET' && $action === 'get') {
        $sos = repo_sos_for_viewer((int) ($_GET['id'] ?? 0), $viewer);
        if (!$sos) {
            throw new HttpError('not_found', 'Сигнал не найден.', 404);
        }
        json_response(['sos' => $sos]);
    }
    if ($method !== 'POST') {
        throw new HttpError('not_found', 'Нет такого запроса.', 404);
    }
    csrf_check();
    if (!$viewer) {
        throw new HttpError('auth_required', 'Сначала займите место в колонне.', 401);
    }
    $in = request_data();
    $id = (int) ($viewer['id']);
    if ($action === 'create') {
        if (rate_limited('sos-create', 5, 600)) {
            throw new HttpError('rate_limit', 'Слишком часто. Подождите немного.', 429);
        }
        json_response(['ok' => true, 'sos' => repo_create_sos($id, $in)]);
    }
    $sosId = (int) ($in['sos_id'] ?? $in['id'] ?? 0);
    if ($action === 'cancel') {
        repo_cancel_sos($id, $sosId);
        json_response(['ok' => true]);
    }
    if ($action === 'accept') {
        json_response(['ok' => true, 'sos' => repo_accept_sos($id, $sosId)]);
    }
    if ($action === 'complete') {
        json_response(['ok' => true, 'sos' => repo_complete_sos($id, $sosId, (int) ($in['rating'] ?? 5))]);
    }
    if ($action === 'message') {
        json_response(['ok' => true, 'message' => repo_add_message($id, $sosId, (string) ($in['body'] ?? ''))]);
    }
    throw new HttpError('not_found', 'Нет такого запроса.', 404);
});
