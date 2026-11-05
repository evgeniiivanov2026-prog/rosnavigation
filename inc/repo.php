<?php
declare(strict_types=1);

const DUMMY_PASSWORD_HASH = '$argon2id$v=19$m=65536,t=4,p=1$eXJoVUk5QjNuVGNUTDFoUQ$NEuOaDG89d3enkk1+q6KHapekYFNqJgRIaAa7Q0/dUc';

function repo_mysql(): bool
{
    static $ready = null;
    if ($ready === null) {
        $ready = db_available();
    }
    return $ready;
}

function repo_mode(): string
{
    return repo_mysql() ? 'mysql' : 'file';
}

function repo_raw(int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    return repo_mysql() ? mysql_user_by_id($id) : file_find_user($id);
}

function present_user(?array $user, ?int $viewer): ?array
{
    if (!$user || !empty($user['is_banned'])) {
        return null;
    }
    $id = (int) $user['id'];
    $own = $viewer !== null && $viewer === $id;
    $demo = !empty($user['demo']);
    $status = (string) ($user['status'] ?? 'offline');
    return [
        'id' => $id,
        'name' => (string) $user['name'],
        'city' => ($user['city'] ?? '') !== '' ? $user['city'] : null,
        'role' => (string) ($user['role'] ?? 'both'),
        'truck_brand' => ($user['truck_brand'] ?? '') !== '' ? $user['truck_brand'] : null,
        'truck_plate' => $own && ($user['truck_plate'] ?? '') !== '' ? $user['truck_plate'] : null,
        'truck_trailer' => ($user['truck_trailer'] ?? '') !== '' ? $user['truck_trailer'] : null,
        'experience_y' => isset($user['experience_y']) && $user['experience_y'] !== null && $user['experience_y'] !== ''
            ? (int) $user['experience_y'] : null,
        'avatar_path' => ($user['avatar_path'] ?? '') !== '' ? $user['avatar_path'] : null,
        'car_path' => ($user['car_path'] ?? '') !== '' ? $user['car_path'] : null,
        'motto' => ($user['motto'] ?? '') !== '' ? $user['motto'] : null,
        'status' => $status,
        'status_label' => status_label($status),
        'rating' => user_rating($user),
        'rating_count' => (int) ($user['rating_count'] ?? 0),
        'helps_count' => (int) ($user['helps_count'] ?? 0),
        'demo' => $demo,
        'badges' => user_badges($user),
        'last_seen_at' => $user['last_seen_at'] ?? null,
        'ago' => ago($user['last_seen_at'] ?? null),
        'lat' => numeric_or_null($user['lat'] ?? null),
        'lng' => numeric_or_null($user['lng'] ?? null),
        'phone' => $own ? (($user['phone'] ?? '') !== '' ? $user['phone'] : null) : null,
    ];
}

function numeric_or_null(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    return is_numeric($value) ? (float) $value : null;
}

function present_sos(array $sos, ?int $viewer, ?float $fromLat = null, ?float $fromLng = null): array
{
    $ownerId = (int) $sos['user_id'];
    $helperId = (int) ($sos['helper_id'] ?? 0);
    $author = repo_raw($ownerId);
    $helper = $helperId > 0 ? repo_raw($helperId) : null;
    $contact = null;
    if ($viewer && $viewer === $helperId && $author && !empty($author['phone'])) {
        $contact = (string) $author['phone'];
    } elseif ($viewer && $viewer === $ownerId && $helper && !empty($helper['phone'])) {
        $contact = (string) $helper['phone'];
    }
    $distance = null;
    if ($fromLat !== null && $fromLng !== null) {
        $distance = round(haversine_km($fromLat, $fromLng, (float) $sos['lat'], (float) $sos['lng']), 1);
    } elseif (isset($sos['distance_km'])) {
        $distance = (float) $sos['distance_km'];
    }
    $expired = (strtotime((string) ($sos['expires_at'] ?? '')) ?: 0) < time();
    $status = (string) $sos['status'];
    if ($expired && $status === 'active') {
        $status = 'cancelled';
    }
    $open = $status === 'active';
    return [
        'id' => (int) $sos['id'],
        'type' => (string) $sos['type'],
        'type_label' => sos_type_label((string) $sos['type']),
        'lat' => (float) $sos['lat'],
        'lng' => (float) $sos['lng'],
        'route' => ($sos['route'] ?? '') !== '' ? $sos['route'] : null,
        'km' => isset($sos['km']) && $sos['km'] !== null && $sos['km'] !== '' ? (int) $sos['km'] : null,
        'comment' => (string) ($sos['comment'] ?? ''),
        'status' => $status,
        'demo' => !empty($sos['demo']),
        'approx_geo' => !empty($sos['approx_geo']),
        'created_at' => (string) ($sos['created_at'] ?? ''),
        'ago' => ago($sos['created_at'] ?? null),
        'distance_km' => $distance,
        'author' => present_user($author, $viewer),
        'helper' => present_user($helper, $viewer),
        'contact_phone' => $contact,
        'mine' => $viewer !== null && $viewer === $ownerId,
        'can_accept' => $open && empty($sos['demo']) && $viewer !== null && $viewer !== $ownerId,
        'can_chat' => $viewer !== null
            && ($viewer === $ownerId || $viewer === $helperId)
            && in_array($status, ['accepted', 'completed'], true),
    ];
}

function repo_stats(): array
{
    $bypass = getenv('ROSNAV_STORE') !== false && getenv('ROSNAV_STORE') !== '';
    if (!$bypass) {
        $cached = cache_get('stats:' . repo_mode(), 10);
        if ($cached !== null) {
            return $cached;
        }
    }
    $stats = repo_mysql() ? mysql_stats() : file_stats();
    if (!$bypass) {
        cache_set('stats:' . repo_mode(), $stats);
    }
    return $stats;
}

function repo_on_air(int $limit = 4): array
{
    if (repo_mysql()) {
        $stmt = db()->query(
            "SELECT * FROM users
             WHERE is_banned = 0 AND status IN ('on_air', 'on_route')
             ORDER BY last_seen_at DESC
             LIMIT 12"
        );
        $rows = $stmt->fetchAll();
    } else {
        $rows = array_values(array_filter(
            file_users(),
            static fn (array $user) => empty($user['is_banned']) && in_array($user['status'], ['on_air', 'on_route'], true)
        ));
    }
    $public = [];
    foreach (array_slice($rows, 0, $limit) as $row) {
        $public[] = present_user($row, null);
    }
    return $public;
}

function repo_register(string $phone, string $password, string $name): array
{
    rate_limit('register', 5, 60);
    $phone = assert_phone($phone);
    $password = assert_password($password);
    $name = assert_person_name($name);
    return repo_mysql()
        ? mysql_register($phone, $password, $name)
        : file_register($phone, $password, $name);
}

function repo_login(string $phone, string $password): array
{
    rate_limit('login', 5, 60);
    $phone = assert_phone($phone);
    $user = repo_mysql() ? mysql_user_by_phone($phone) : file_user_by_phone($phone);
    $hash = is_array($user) && !empty($user['password_hash']) ? (string) $user['password_hash'] : DUMMY_PASSWORD_HASH;
    $valid = password_verify($password, $hash);
    if (!$user || empty($user['password_hash']) || !$valid || !empty($user['is_banned'])) {
        throw new HttpError('login', 'Не слышно. Проверьте телефон и пароль.', 401);
    }
    return $user;
}

function repo_update_user(int $id, array $fields): array
{
    $raw = repo_mysql() ? mysql_update_user($id, $fields) : file_update_user($id, $fields);
    $public = present_user($raw, $id);
    if (!$public) {
        throw new HttpError('not_found', 'Водителя нет в колонне.', 404);
    }
    return $public;
}

function repo_touch(int $id, ?float $lat, ?float $lng): void
{
    if (repo_mysql()) {
        mysql_touch($id, $lat, $lng);
        return;
    }
    $user = file_find_user($id);
    if (!$user || !empty($user['demo'])) {
        return;
    }
    file_touch($id, $lat, $lng);
}

function repo_user_public(int $id, ?int $viewer): ?array
{
    return present_user(repo_raw($id), $viewer);
}

function repo_helpers_near(float $lat, float $lng, float $radius, ?int $viewer): array
{
    $rows = repo_mysql()
        ? mysql_helpers_near($lat, $lng, $radius, $viewer)
        : file_helpers_near($lat, $lng, $radius, $viewer);
    $rows = array_slice(filter_near($rows, $lat, $lng, $radius), 0, 100);
    return array_map(static fn (array $row) => present_user($row, $viewer), $rows);
}

function repo_sos_raw(int $id): ?array
{
    return repo_mysql() ? mysql_sos_by_id($id) : file_sos_by_id($id);
}

function repo_sos_public(int $id, ?int $viewer): ?array
{
    $raw = repo_sos_raw($id);
    return $raw ? present_sos($raw, $viewer) : null;
}

function repo_sos_active(int $userId, ?int $viewer): ?array
{
    $raw = repo_mysql() ? mysql_sos_active_for($userId) : file_sos_active_for($userId);
    return $raw ? present_sos($raw, $viewer) : null;
}

function repo_sos_near(?float $lat, ?float $lng, float $radius, ?int $viewer): array
{
    if (repo_mysql()) {
        $rows = mysql_sos_candidates($lat, $lng, $radius);
    } else {
        $rows = array_values(array_filter(file_sos(), static function (array $sos) {
            return in_array($sos['status'], ['active', 'accepted'], true)
                && (strtotime((string) $sos['expires_at']) ?: 0) > time();
        }));
    }
    if ($lat !== null && $lng !== null) {
        $rows = filter_near($rows, $lat, $lng, $radius);
    }
    $rows = array_slice($rows, 0, 100);
    return array_map(static fn (array $row) => present_sos($row, $viewer, $lat, $lng), $rows);
}

function repo_sos_create(int $userId, array $in): array
{
    rate_limit('sos', 8, 60);
    $payload = sos_payload($in);
    $raw = repo_mysql() ? mysql_sos_create($userId, $payload) : file_sos_create($userId, $payload);
    $public = present_sos($raw, $userId);
    return $public;
}

function repo_sos_cancel(int $id, int $ownerId): array
{
    if (repo_mysql()) {
        return present_sos(mysql_sos_cancel($id, $ownerId), $ownerId);
    }
    $updated = file_patch_sos($id, function (array &$sos, array &$data) use ($ownerId) {
        if ((int) $sos['user_id'] !== $ownerId) {
            throw new HttpError('forbidden', 'Закрыть сигнал может только тот, кто его передал.', 403);
        }
        if (!in_array($sos['status'], ['active', 'accepted'], true)) {
            throw new HttpError('state', 'Сигнал уже закрыт.', 409);
        }
        $sos['status'] = 'cancelled';
        foreach ($data['users'] as &$user) {
            if ((int) $user['id'] === $ownerId && ($user['status'] ?? '') === 'need_help') {
                $user['status'] = 'on_air';
            }
        }
        unset($user);
    });
    return present_sos($updated, $ownerId);
}

function repo_sos_accept(int $id, int $helperId): array
{
    if (repo_mysql()) {
        return present_sos(mysql_sos_accept($id, $helperId), $helperId);
    }
    $updated = file_patch_sos($id, function (array &$sos) use ($helperId) {
        if ((int) $sos['user_id'] === $helperId) {
            throw new HttpError('self', 'Свой сигнал не принимают.', 409);
        }
        if ($sos['status'] !== 'active' || (strtotime((string) $sos['expires_at']) ?: 0) < time()) {
            throw new HttpError('state', 'Сигнал уже разобрали или он погас.', 409);
        }
        $sos['status'] = 'accepted';
        $sos['helper_id'] = $helperId;
        $sos['accepted_at'] = date('Y-m-d H:i:s');
    });
    return present_sos($updated, $helperId);
}

function repo_sos_complete(int $id, int $actorId, ?int $rating, string $review): array
{
    if ($rating !== null && ($rating < 1 || $rating > 5)) {
        throw new HttpError('rating', 'Оценка от 1 до 5.');
    }
    $review = clean_text($review, 500);
    if (repo_mysql()) {
        return present_sos(mysql_sos_complete($id, $actorId, $rating, $review), $actorId);
    }
    $updated = file_patch_sos($id, function (array &$sos, array &$data) use ($actorId, $rating, $review) {
        $owner = (int) $sos['user_id'];
        $helper = (int) ($sos['helper_id'] ?? 0);
        if ($actorId !== $owner && $actorId !== $helper) {
            throw new HttpError('forbidden', 'Закрыть выручку могут только свои по этому сигналу.', 403);
        }
        if ($sos['status'] !== 'accepted' || $helper < 1) {
            throw new HttpError('state', 'Сначала кто-то должен принять сигнал.', 409);
        }
        $now = date('Y-m-d H:i:s');
        $sos['status'] = 'completed';
        $sos['completed_at'] = $now;
        foreach ($data['users'] as &$user) {
            if ((int) $user['id'] === $owner && ($user['status'] ?? '') === 'need_help') {
                $user['status'] = 'on_air';
            }
            if ((int) $user['id'] === $helper) {
                $user['helps_count'] = (int) $user['helps_count'] + 1;
                if ($rating !== null && $actorId === $owner) {
                    $user['rating_sum'] = (int) $user['rating_sum'] + $rating;
                    $user['rating_count'] = (int) $user['rating_count'] + 1;
                }
            }
        }
        unset($user);
        if ($rating !== null && $actorId === $owner) {
            $data['reviews'][] = [
                'id' => file_next_id($data['reviews']),
                'author_id' => $owner,
                'target_user' => $helper,
                'target_partner' => null,
                'rating' => $rating,
                'body' => $review,
                'status' => 'published',
                'created_at' => $now,
            ];
        }
    });
    return present_sos($updated, $actorId);
}

function repo_messages(int $sosId, int $viewer): array
{
    $sos = repo_sos_raw($sosId);
    if (!$sos) {
        throw new HttpError('not_found', 'Сигнал уже погас.', 404);
    }
    assert_party($sos, $viewer);
    $rows = repo_mysql() ? mysql_messages($sosId) : file_messages($sosId);
    $out = [];
    foreach ($rows as $row) {
        $author = present_user(repo_raw((int) $row['user_id']), $viewer);
        $out[] = [
            'id' => (int) $row['id'],
            'body' => (string) $row['body'],
            'created_at' => (string) $row['created_at'],
            'ago' => ago($row['created_at']),
            'mine' => (int) $row['user_id'] === $viewer,
            'author' => $author['name'] ?? 'Водитель',
        ];
    }
    return $out;
}

function repo_message_add(int $sosId, int $userId, string $body): array
{
    $sos = repo_sos_raw($sosId);
    if (!$sos) {
        throw new HttpError('not_found', 'Сигнал уже погас.', 404);
    }
    assert_party($sos, $userId);
    $body = clean_text($body, 500);
    if (mb_strlen($body) < 1) {
        throw new HttpError('empty', 'Пустое сообщение не уходит в эфир.');
    }
    $row = repo_mysql()
        ? mysql_message_add($sosId, $userId, $body)
        : file_message_add($sosId, $userId, $body);
    $author = present_user(repo_raw($userId), $userId);
    return [
        'id' => (int) $row['id'],
        'body' => $body,
        'created_at' => (string) $row['created_at'],
        'ago' => 'только что',
        'mine' => true,
        'author' => $author['name'] ?? 'Водитель',
    ];
}

function assert_party(array $sos, int $userId): void
{
    $owner = (int) $sos['user_id'];
    $helper = (int) ($sos['helper_id'] ?? 0);
    if ($userId !== $owner && $userId !== $helper) {
        throw new HttpError('forbidden', 'Этот разговор только для своих по сигналу.', 403);
    }
    if (!in_array($sos['status'], ['accepted', 'completed'], true)) {
        throw new HttpError('state', 'Чат откроется, когда сигнал примут.', 409);
    }
}

function repo_partner_rows(): array
{
    $cached = cache_get('partners:' . repo_mode(), 60);
    if ($cached !== null && getenv('ROSNAV_STORE') === false) {
        return $cached;
    }
    $rows = repo_mysql() ? mysql_partners() : seed_partners();
    $normal = [];
    foreach ($rows as $row) {
        $normal[] = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'type' => (string) $row['type'],
            'type_label' => partner_type_label((string) $row['type']),
            'city' => (string) $row['city'],
            'address' => $row['address'] ?: null,
            'lat' => numeric_or_null($row['lat'] ?? null),
            'lng' => numeric_or_null($row['lng'] ?? null),
            'phone' => ($row['phone'] ?? '') !== '' ? $row['phone'] : null,
            'site_url' => ($row['site_url'] ?? '') !== '' ? $row['site_url'] : null,
            'description' => (string) ($row['description'] ?? ''),
            'photo_path' => ($row['photo_path'] ?? '') !== '' ? $row['photo_path'] : null,
            'price_from' => isset($row['price_from']) && $row['price_from'] !== null && $row['price_from'] !== ''
                ? (int) $row['price_from'] : null,
            'works_24_7' => !empty($row['works_24_7']),
            'rating_avg' => round((float) ($row['rating_avg'] ?? 0), 2),
            'rating_count' => (int) ($row['rating_count'] ?? 0),
            'is_active' => !isset($row['is_active']) || !empty($row['is_active']),
            'demo' => !empty($row['demo']),
        ];
    }
    if (getenv('ROSNAV_STORE') === false) {
        cache_set('partners:' . repo_mode(), $normal);
    }
    return $normal;
}

function repo_partners(array $query): array
{
    $type = (string) ($query['type'] ?? '');
    $allowed = ['', 'sto', 'fuel', 'hotel', 'insurance', 'lawyer', 'shop', 'other'];
    if (!in_array($type, $allowed, true)) {
        $type = '';
    }
    $city = clean_text((string) ($query['city'] ?? ''), 100);
    $day = !empty($query['day']);
    $rating = is_numeric($query['rating'] ?? null) ? (float) $query['rating'] : 0.0;
    $text = mb_strtolower(clean_text((string) ($query['q'] ?? ''), 80));
    $lat = numeric_or_null($query['lat'] ?? null);
    $lng = numeric_or_null($query['lng'] ?? null);
    $out = [];
    foreach (repo_partner_rows() as $row) {
        if (!$row['is_active']) {
            continue;
        }
        if ($type !== '' && $row['type'] !== $type) {
            continue;
        }
        if ($city !== '' && $row['city'] !== $city) {
            continue;
        }
        if ($day && !$row['works_24_7']) {
            continue;
        }
        if ($rating > 0 && $row['rating_avg'] < $rating) {
            continue;
        }
        if ($text !== '') {
            $hay = mb_strtolower($row['name'] . ' ' . $row['city'] . ' ' . $row['description']);
            if (!str_contains($hay, $text)) {
                continue;
            }
        }
        $out[] = $row;
    }
    if ($lat !== null && $lng !== null) {
        $out = filter_near($out, $lat, $lng, 20000);
    } else {
        usort($out, static fn ($a, $b) => $b['rating_avg'] <=> $a['rating_avg']);
    }
    return $out;
}

function repo_partner_cities(): array
{
    $cities = [];
    foreach (repo_partner_rows() as $row) {
        if ($row['city'] !== '') {
            $cities[$row['city']] = true;
        }
    }
    $list = array_keys($cities);
    sort($list, SORT_STRING);
    return $list;
}

function repo_posts(?int $viewer): array
{
    $rows = repo_mysql() ? mysql_posts() : file_posts_all();
    $out = [];
    foreach ($rows as $post) {
        $authorId = (int) $post['user_id'];
        $status = (string) ($post['status'] ?? 'pending');
        if ($status !== 'published' && $viewer !== $authorId) {
            continue;
        }
        $out[] = [
            'id' => (int) $post['id'],
            'body' => (string) $post['body'],
            'photo_path' => ($post['photo_path'] ?? '') !== '' ? $post['photo_path'] : null,
            'city' => ($post['city'] ?? '') !== '' ? $post['city'] : null,
            'route' => ($post['route'] ?? '') !== '' ? $post['route'] : null,
            'likes' => (int) ($post['likes_count'] ?? 0),
            'status' => $status,
            'demo' => !empty($post['demo']),
            'created_at' => (string) ($post['created_at'] ?? ''),
            'ago' => ago($post['created_at'] ?? null),
            'author' => present_user(repo_raw($authorId), $viewer),
            'liked' => $viewer
                ? (repo_mysql() ? mysql_liked($viewer, (int) $post['id']) : file_liked($viewer, (int) $post['id']))
                : false,
            'mine' => $viewer === $authorId,
        ];
    }
    return $out;
}

function repo_post_create(int $userId, array $in, ?string $photo): array
{
    rate_limit('post', 6, 60);
    $body = trim((string) ($in['body'] ?? ''));
    $body = clean_text($body, 1000);
    if (mb_strlen($body) < 10) {
        throw new HttpError('body', 'Напишите хотя бы пару слов: где и что видели.');
    }
    $city = clean_text((string) ($in['city'] ?? ''), 100);
    $route = clean_text((string) ($in['route'] ?? ''), 50);
    $raw = repo_mysql()
        ? mysql_post_create($userId, $body, $city !== '' ? $city : null, $route !== '' ? $route : null, $photo)
        : file_post_create($userId, $body, $city !== '' ? $city : null, $route !== '' ? $route : null, $photo);
    return [
        'id' => (int) $raw['id'],
        'status' => (string) $raw['status'],
    ];
}

function repo_like(int $userId, int $postId): int
{
    return repo_mysql() ? mysql_like($userId, $postId) : file_like($userId, $postId);
}

function repo_report(string $type, int $id, string $reason): void
{
    rate_limit('report', 8, 60);
    if (!in_array($type, ['post', 'user', 'sos'], true) || $id < 1) {
        throw new HttpError('report', 'Не на что жаловаться.');
    }
    $reason = clean_text($reason, 300);
    if (mb_strlen($reason) < 3) {
        throw new HttpError('report', 'Напишите коротко, что не так.');
    }
    if (repo_mysql()) {
        mysql_report($type, $id, $reason);
        return;
    }
    file_report($type, $id, $reason);
}

function repo_helps(int $userId, ?int $viewer): array
{
    if (repo_mysql()) {
        $rows = mysql_helps($userId);
    } else {
        $rows = array_values(array_filter(file_sos(), static function (array $sos) use ($userId) {
            return (int) ($sos['helper_id'] ?? 0) === $userId && $sos['status'] === 'completed';
        }));
        usort($rows, static fn ($a, $b) => strcmp((string) $b['created_at'], (string) $a['created_at']));
        $rows = array_slice($rows, 0, 20);
    }
    return array_map(static fn (array $row) => present_sos($row, $viewer), $rows);
}

function repo_poll(?float $lat, ?float $lng, float $radius, ?int $viewer): array
{
    $signals = repo_sos_near($lat, $lng, $radius, $viewer);
    $helpers = ($lat !== null && $lng !== null)
        ? repo_helpers_near($lat, $lng, $radius, $viewer)
        : [];
    return [
        'sos' => $signals,
        'helpers' => $helpers,
        'count_online' => (int) repo_stats()['online'],
        'server_time' => date('c'),
        'mine' => $viewer ? repo_sos_active($viewer, $viewer) : null,
        'mode' => repo_mode(),
    ];
}

function repo_cleanup(): array
{
    $cache = cache_purge_expired();
    $data = repo_mysql() ? mysql_cleanup() : file_cleanup();
    $data['cache'] = $cache;
    return $data;
}
