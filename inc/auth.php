<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $user = null;
    static $loaded = false;
    if ($loaded) {
        return $user;
    }
    $loaded = true;
    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id === 0) {
        return null;
    }
    $user = repo_user($id);
    if (!$user || (int) ($user['is_banned'] ?? 0) === 1) {
        $user = null;
        unset($_SESSION['user_id'], $_SESSION['user_name']);
    }
    return $user;
}

function require_user(): array
{
    $user = current_user();
    if (!$user) {
        fail('auth_required', 'Сначала займи место в колонне.', 401);
    }
    return $user;
}

function require_user_web(): array
{
    $user = current_user();
    if (!$user) {
        flash_set('error', 'Сначала займи место в колонне.');
        $next = $_SERVER['REQUEST_URI'] ?? '/profile';
        redirect('/auth?next=' . rawurlencode(safe_next($next)));
    }
    return $user;
}

/**
 * @return array{ok: bool, error?: string, message?: string, id?: int}
 */
function perform_register(array $in): array
{
    $phone = normalize_phone((string) ($in['phone'] ?? ''));
    $password = (string) ($in['password'] ?? '');
    $name = clip((string) ($in['name'] ?? ''), 100) ?? '';
    $city = clip((string) ($in['city'] ?? ''), 100);
    $role = (string) ($in['role'] ?? 'both');
    if (!in_array($role, ['seeker', 'helper', 'both'], true)) {
        $role = 'both';
    }
    if (!valid_phone($phone)) {
        return ['ok' => false, 'error' => 'phone', 'message' => 'Нужен мобильный номер в формате +7 900 000-00-00.'];
    }
    if (mb_strlen($password) < 8) {
        return ['ok' => false, 'error' => 'password', 'message' => 'Пароль короче 8 символов. Так кабину не закрывают.'];
    }
    if (mb_strlen($name) < 2) {
        return ['ok' => false, 'error' => 'name', 'message' => 'Как к тебе обращаться? Имя или позывной, от 2 букв.'];
    }
    if (repo_phone_taken($phone)) {
        return ['ok' => false, 'error' => 'phone', 'message' => 'Этот номер уже в колонне. Войди, если это ты.'];
    }
    $years = $in['experience_y'] ?? null;
    $years = ($years === '' || $years === null) ? null : max(0, min(60, (int) $years));
    $id = repo_insert_user([
        'phone' => $phone,
        'password_hash' => hash_password($password),
        'name' => $name,
        'city' => $city,
        'role' => $role,
        'truck_brand' => clip((string) ($in['truck_brand'] ?? ''), 100),
        'truck_plate' => clip((string) ($in['truck_plate'] ?? ''), 20),
        'truck_trailer' => clip((string) ($in['truck_trailer'] ?? ''), 50),
        'experience_y' => $years,
        'motto' => clip((string) ($in['motto'] ?? ''), 200),
        'status' => 'on_air',
    ]);
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['user_name'] = $name;
    return ['ok' => true, 'id' => $id];
}

/**
 * @return array{ok: bool, error?: string, message?: string}
 */
function perform_login(string $phone, string $password): array
{
    $phone = normalize_phone($phone);
    $user = repo_user_by_phone($phone);
    $hash = (string) ($user['password_hash'] ?? '');
    if (!$user || $hash === '' || !password_verify($password, $hash) || (int) ($user['is_banned'] ?? 0) === 1) {
        return ['ok' => false, 'error' => 'credentials', 'message' => 'Не слышно. Проверь номер и пароль.'];
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = (string) $user['name'];
    repo_touch((int) $user['id']);
    return ['ok' => true];
}

function perform_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies') && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
