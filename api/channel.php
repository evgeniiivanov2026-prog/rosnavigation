<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

api_dispatch(static function (): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = (string) ($_GET['action'] ?? 'list');
    $viewer = current_user();
    if ($method === 'GET') {
        json_response(['posts' => repo_posts($viewer ? (int) $viewer['id'] : null)]);
    }
    csrf_check();
    if (!$viewer) {
        throw new HttpError('auth_required', 'Сначала займите место в колонне.', 401);
    }
    $in = request_data();
    $id = (int) $viewer['id'];
    if ($action === 'like') {
        json_response(repo_like($id, (int) ($in['post_id'] ?? 0)));
    }
    if ($action === 'complain') {
        repo_complain($id, (int) ($in['post_id'] ?? 0), (string) ($in['reason'] ?? ''));
        json_response(['ok' => true]);
    }
    if ($action === 'create') {
        if (rate_limited('post', 6, 600)) {
            throw new HttpError('rate_limit', 'Слишком часто для эфира. Минуту тишины.', 429);
        }
        $photo = null;
        if (!empty($_FILES['photo']['name'])) {
            $photo = handle_upload($_FILES['photo'], 'channel');
        }
        json_response(['ok' => true, 'post' => repo_create_post($id, repo_post_input($in, $photo))]);
    }
    throw new HttpError('not_found', 'Нет такого запроса.', 404);
});
