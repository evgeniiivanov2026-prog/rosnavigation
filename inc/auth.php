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
    if ($id <= 0) {
        return null;
    }
    $user = repo_user($id, true);
    if (!$user) {
        unset($_SESSION['user_id'], $_SESSION['user_name']);
    }
    return $user;
}

function login_user(string $phone, string $password): bool
{
    $row = repo_user_by_phone(normalize_phone($phone));
    if (!$row || !password_verify($password, (string) $row['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $row['id'];
    $_SESSION['user_name'] = (string) $row['name'];
    unset($_SESSION['csrf_token']);
    current_user(true);
    repo_touch((int) $row['id'], null, null);
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function require_auth(): array
{
    $user = current_user();
    if ($user) {
        return $user;
    }
    if (is_api_request()) {
        throw new HttpError('auth_required', 'Сначала займите место в колонне.', 401);
    }
    $next = safe_next($_SERVER['REQUEST_URI'] ?? '/profile');
    header('Location: /auth?next=' . rawurlencode($next));
    exit;
}
