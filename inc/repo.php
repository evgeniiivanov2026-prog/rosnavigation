<?php
declare(strict_types=1);

function repo_stats(): array
{
    $cached = cache_get('stats', 10);
    if (is_array($cached) && isset($cached['online'])) {
        return $cached;
    }
    $stats = app_demo() ? demo_stats() : mysql_stats();
    cache_set('stats', $stats);
    return $stats;
}

function repo_sos_near(?float $lat, ?float $lng, ?float $radius): array
{
    $rows = app_demo() ? demo_sos_rows($lat, $lng, $radius) : mysql_sos_rows($lat, $lng, $radius);
    return array_map(static fn ($row) => present_sos($row, $lat, $lng, false), $rows);
}

function repo_helpers_near(?float $lat, ?float $lng, ?float $radius): array
{
    $rows = app_demo() ? demo_helpers($lat, $lng, $radius) : mysql_helpers($lat, $lng, $radius);
    return array_map(static fn ($row) => present_user($row, false), $rows);
}

function repo_partners(array $filters = []): array
{
    $rows = app_demo() ? demo_partners($filters) : mysql_partners($filters);
    return array_map('present_partner', $rows);
}

function repo_regions(): array
{
    return app_demo() ? demo_regions() : mysql_regions();
}

function repo_posts(?int $viewerId = null): array
{
    return app_demo() ? demo_posts($viewerId, false) : mysql_posts($viewerId, false);
}

function repo_user_posts(int $userId): array
{
    return app_demo() ? demo_posts($userId, true) : mysql_posts($userId, true);
}

function repo_user(int $id, bool $self = false): ?array
{
    return app_demo() ? demo_user($id, $self) : mysql_user($id, $self);
}

function repo_user_by_phone(string $phone): ?array
{
    return app_demo() ? demo_user_by_phone($phone) : mysql_user_by_phone($phone);
}

/** @return array<string, mixed> */
function repo_registration_input(array $in): array
{
    $phone = normalize_phone((string) ($in['phone'] ?? ''));
    if (!preg_match('/^\+7\d{10}$/', $phone)) {
        throw new InvalidArgumentException('Телефон нужен в виде +7 и десяти цифр.');
    }
    $password = (string) ($in['password'] ?? '');
    if (mb_strlen($password) < 8) {
        throw new InvalidArgumentException('Пароль короче 8 символов.');
    }
    if (mb_strlen($password) > 72) {
        throw new InvalidArgumentException('Пароль слишком длинный.');
    }
    $name = clip($in['name'] ?? '', 40);
    if (mb_strlen($name) < 2) {
        throw new InvalidArgumentException('Как к вам обращаться? Хотя бы два знака.');
    }
    $role = (string) ($in['role'] ?? 'both');
    if (!in_array($role, ['seeker', 'helper', 'both'], true)) {
        $role = 'both';
    }
    $years = trim((string) ($in['experience_y'] ?? ''));
    $experience = null;
    if ($years !== '') {
        if (!ctype_digit($years) || (int) $years > 60) {
            throw new InvalidArgumentException('Стаж — число лет, не больше 60.');
        }
        $experience = (int) $years;
    }
    return [
        'phone' => $phone,
        'password' => $password,
        'name' => $name,
        'city' => clip($in['city'] ?? '', 80),
        'role' => $role,
        'truck_brand' => clip($in['truck_brand'] ?? '', 80),
        'truck_plate' => clip($in['truck_plate'] ?? '', 20),
        'truck_trailer' => clip($in['truck_trailer'] ?? '', 40),
        'experience_y' => $experience,
    ];
}

function repo_register(array $in): array
{
    $clean = repo_registration_input($in);
    return app_demo() ? demo_register($clean) : mysql_register($clean);
}

function repo_touch(int $id, ?float $lat, ?float $lng): void
{
    if (app_demo()) {
        demo_touch($id, $lat, $lng);
        return;
    }
    mysql_touch($id, $lat, $lng);
}

/** @return array<string, mixed> */
function repo_profile_input(array $in): array
{
    $out = [];
    if (array_key_exists('name', $in)) {
        $name = clip($in['name'], 40);
        if (mb_strlen($name) < 2) {
            throw new InvalidArgumentException('Имя короче двух знаков.');
        }
        $out['name'] = $name;
    }
    if (array_key_exists('city', $in)) {
        $out['city'] = clip($in['city'], 80);
    }
    if (array_key_exists('role', $in) && in_array($in['role'], ['seeker', 'helper', 'both'], true)) {
        $out['role'] = $in['role'];
    }
    foreach (['truck_brand' => 80, 'truck_plate' => 20, 'truck_trailer' => 40, 'motto' => 200, 'route' => 50] as $key => $max) {
        if (array_key_exists($key, $in)) {
            $out[$key] = clip((string) $in[$key], $max);
        }
    }
    if (array_key_exists('status', $in)) {
        if (!isset(catalog_statuses()[(string) $in['status']])) {
            throw new InvalidArgumentException('Такого статуса нет.');
        }
        $out['status'] = (string) $in['status'];
    }
    if (array_key_exists('experience_y', $in)) {
        $years = trim((string) $in['experience_y']);
        if ($years === '') {
            $out['experience_y'] = null;
        } elseif (!ctype_digit($years) || (int) $years > 60) {
            throw new InvalidArgumentException('Стаж — число лет, не больше 60.');
        } else {
            $out['experience_y'] = (int) $years;
        }
    }
    if (array_key_exists('km', $in)) {
        $km = trim((string) $in['km']);
        if ($km === '') {
            $out['km'] = null;
        } elseif (!ctype_digit($km) || (int) $km > 10000) {
            throw new InvalidArgumentException('Километр выглядит неправдоподобно.');
        } else {
            $out['km'] = (int) $km;
        }
    }
    return $out;
}

function repo_update_profile(int $id, array $in): array
{
    $clean = repo_profile_input($in);
    return app_demo() ? demo_update_profile($id, $clean) : mysql_update_profile($id, $clean);
}

function repo_set_avatar(int $id, string $path): array
{
    return app_demo() ? demo_update_profile($id, ['avatar_path' => $path]) : mysql_update_profile($id, ['avatar_path' => $path]);
}

function repo_set_car(int $id, string $path): array
{
    return app_demo() ? demo_update_profile($id, ['car_path' => $path]) : mysql_update_profile($id, ['car_path' => $path]);
}

/** @return array{type: string, lat: float, lng: float, route: string, km: ?int, comment: string} */
function repo_sos_input(array $in): array
{
    $type = (string) ($in['type'] ?? '');
    if (!isset(catalog_sos_types()[$type])) {
        throw new HttpError('invalid_type', 'Выберите, что случилось.', 422);
    }
    $lat = isset($in['lat']) && $in['lat'] !== '' ? (float) $in['lat'] : 0.0;
    $lng = isset($in['lng']) && $in['lng'] !== '' ? (float) $in['lng'] : 0.0;
    $route = clip($in['route'] ?? '', 50);
    if (!coords_valid($lat, $lng) && isset($in['point']) && $in['point'] !== '') {
        $points = catalog_points();
        $index = (int) $in['point'];
        if (!isset($points[$index])) {
            throw new HttpError('invalid_coords', 'Такой точки на трассе нет в списке.', 422);
        }
        $lat = $points[$index]['lat'];
        $lng = $points[$index]['lng'];
        if ($route === '') {
            $route = strtok($points[$index]['label'], ' ') ?: '';
        }
    }
    if (!coords_valid($lat, $lng)) {
        throw new HttpError('invalid_coords', 'Нужна точка: разрешите геолокацию или выберите место на трассе.', 422);
    }
    $kmRaw = trim((string) ($in['km'] ?? ''));
    $km = null;
    if ($kmRaw !== '') {
        if (!ctype_digit($kmRaw) || (int) $kmRaw > 10000) {
            throw new InvalidArgumentException('Километр — целое число.');
        }
        $km = (int) $kmRaw;
    }
    return [
        'type' => $type,
        'lat' => $lat,
        'lng' => $lng,
        'route' => $route,
        'km' => $km,
        'comment' => clip($in['comment'] ?? '', 500),
    ];
}

function repo_create_sos(int $userId, array $in): array
{
    $clean = repo_sos_input($in);
    $sos = app_demo() ? demo_create_sos($userId, $clean) : mysql_create_sos($userId, $clean);
    cache_forget('stats');
    return $sos;
}

function repo_my_active_sos(int $userId): ?array
{
    return app_demo() ? demo_my_active_sos($userId) : mysql_my_active_sos($userId);
}

function repo_sos(int $id): ?array
{
    if (app_demo()) {
        $row = demo_sos_by_id($id);
        return $row ? present_sos($row) : null;
    }
    $row = mysql_sos_raw($id);
    return $row ? present_sos($row) : null;
}

function repo_sos_for_viewer(int $id, ?array $viewer): ?array
{
    $raw = app_demo() ? demo_sos_by_id($id) : mysql_sos_raw($id);
    if (!$raw) {
        return null;
    }
    $reveal = false;
    if ($viewer) {
        $reveal = (int) $viewer['id'] === (int) $raw['user_id'] || (int) ($viewer['id']) === (int) ($raw['helper_id'] ?? 0);
    }
    $sos = present_sos($raw, null, null, $reveal);
    if ($viewer && ((int) $viewer['id'] === (int) $raw['user_id'] || (int) $viewer['id'] === (int) ($raw['helper_id'] ?? 0))) {
        $sos['messages'] = app_demo() ? demo_messages($id) : mysql_messages($id);
    } else {
        $sos['messages'] = [];
    }
    return $sos;
}

function repo_cancel_sos(int $userId, int $sosId): void
{
    if (app_demo()) {
        demo_cancel_sos($userId, $sosId);
    } else {
        mysql_cancel_sos($userId, $sosId);
    }
    cache_forget('stats');
}

function repo_accept_sos(int $userId, int $sosId): array
{
    return app_demo() ? demo_accept_sos($userId, $sosId) : mysql_accept_sos($userId, $sosId);
}

function repo_complete_sos(int $userId, int $sosId, int $rating): array
{
    $rating = max(0, min(5, $rating));
    $sos = app_demo() ? demo_complete_sos($userId, $sosId, $rating) : mysql_complete_sos($userId, $sosId, $rating);
    cache_forget('stats');
    return $sos;
}

function repo_add_message(int $userId, int $sosId, string $body): array
{
    $body = clip($body, 500);
    if (mb_strlen($body) < 1) {
        throw new InvalidArgumentException('Пустое сообщение не уйдёт.');
    }
    return app_demo() ? demo_add_message($userId, $sosId, $body) : mysql_add_message($userId, $sosId, $body);
}

function repo_post_input(array $in, ?string $photo): array
{
    $body = clip($in['body'] ?? '', 1000);
    if (mb_strlen($body) < 4) {
        throw new InvalidArgumentException('Напишите хотя бы пару слов, что на трассе.');
    }
    return [
        'body' => $body,
        'city' => clip($in['city'] ?? '', 80),
        'route' => clip($in['route'] ?? '', 50),
        'photo_path' => $photo,
    ];
}

function repo_create_post(int $userId, array $in): array
{
    return app_demo() ? demo_create_post($userId, $in) : mysql_create_post($userId, $in);
}

function repo_like(int $userId, int $postId): array
{
    return app_demo() ? demo_like($userId, $postId) : mysql_like($userId, $postId);
}

function repo_complain(int $userId, int $postId, string $reason): void
{
    $reason = clip($reason, 300);
    if (mb_strlen($reason) < 4) {
        throw new InvalidArgumentException('Коротко опишите, что не так.');
    }
    if (app_demo()) {
        demo_complain($userId, $postId, $reason);
        return;
    }
    mysql_complain($userId, $postId, $reason);
}

function repo_user_helps(int $userId): array
{
    return app_demo() ? demo_user_helps($userId) : mysql_user_helps($userId);
}
