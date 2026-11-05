<?php
declare(strict_types=1);

function current_user(bool $refresh = false): ?array
{
    static $user = null;
    static $loaded = false;
    if ($refresh) {
        $loaded = false;
        $user = null;
    }
    if ($loaded) {
        return $user;
    }
    $loaded = true;
    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id < 1) {
        return $user = null;
    }
    $user = repo_user_public($id, $id);
    if (!$user) {
        unset($_SESSION['user_id'], $_SESSION['user_name']);
    }
    return $user;
}

function require_user(): array
{
    $user = current_user();
    if (!$user) {
        throw new HttpError('auth_required', 'Сначала займите место в колонне.', 401);
    }
    return $user;
}

function auth_start(array $rawUser): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $rawUser['id'];
    $_SESSION['user_name'] = (string) $rawUser['name'];
    $_SESSION['touched_at'] = time();
    current_user(true);
}

function auth_logout(): void
{
    $_SESSION = [];
    current_user(true);
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
