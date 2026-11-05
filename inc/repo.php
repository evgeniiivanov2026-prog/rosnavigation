<?php
declare(strict_types=1);

require __DIR__ . '/store_demo.php';
require __DIR__ . '/store_mysql.php';

function repo_user(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    if (DEMO_MODE) {
        foreach (demo_read()['users'] as $user) {
            if ((int) $user['id'] === $id) {
                return $user;
            }
        }
        return null;
    }
    return mysql_user($id);
}

function repo_user_by_phone(string $phone): ?array
{
    if (DEMO_MODE) {
        foreach (demo_read()['users'] as $user) {
            if (($user['phone'] ?? '') === $phone) {
                return $user;
            }
        }
        return null;
    }
    return mysql_user_by_phone($phone);
}

function repo_phone_taken(string $phone): bool
{
    return repo_user_by_phone($phone) !== null;
}

function repo_insert_user(array $row): int
{
    $now = date('Y-m-d H:i:s');
    $row += [
        'avatar_path' => null,
        'car_path' => null,
        'is_partner' => 0,
        'is_banned' => 0,
        'lat' => null,
        'lng' => null,
        'route' => null,
        'helps_count' => 0,
        'rating_sum' => 0,
        'rating_count' => 0,
        'last_seen_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ];
    if (!DEMO_MODE) {
        return mysql_insert_user($row);
    }
    return (int) demo_edit(static function (array &$data) use ($row): int {
        $row['id'] = demo_next_id($data['users']);
        $data['users'][] = $row;
        return (int) $row['id'];
    });
}

function repo_touch(int $id): void
{
    if ($id <= 0) {
        return;
    }
    $now = date('Y-m-d H:i:s');
    if (DEMO_MODE) {
        demo_edit(static function (array &$data) use ($id, $now): bool {
            foreach ($data['users'] as &$user) {
                if ((int) $user['id'] === $id) {
                    $user['last_seen_at'] = $now;
                }
            }
            unset($user);
            return true;
        });
        return;
    }
    mysql_touch($id);
}

function repo_ping(int $id, float $lat, float $lng): void
{
    if (!valid_coords($lat, $lng)) {
        return;
    }
    $user = repo_user($id);
    if (!$user || !in_array($user['status'] ?? '', ['on_air', 'on_route', 'need_help'], true)) {
        repo_touch($id);
        return;
    }
    $now = date('Y-m-d H:i:s');
    if (DEMO_MODE) {
        demo_edit(static function (array &$data) use ($id, $lat, $lng, $now): bool {
            foreach ($data['users'] as &$user) {
                if ((int) $user['id'] === $id) {
                    $user['lat'] = $lat;
                    $user['lng'] = $lng;
                    $user['last_seen_at'] = $now;
                }
            }
            unset($user);
            return true;
        });
        return;
    }
    mysql_ping($id, $lat, $lng);
}

function repo_stats(): array
{
    if (!DEMO_MODE) {
        return mysql_stats();
    }
    $online = 0;
    $onAir = 0;
    $helped = 0;
    foreach (demo_read()['users'] as $user) {
        if ((int) ($user['is_banned'] ?? 0) === 1) {
            continue;
        }
        $helped += (int) $user['helps_count'];
        if (in_array($user['status'], ['on_air', 'on_route', 'need_help'], true)) {
            $online++;
        }
        if ($user['status'] === 'on_air') {
            $onAir++;
        }
    }
    return ['online' => $online, 'on_air' => $onAir, 'helped' => $helped];
}

function repo_drivers(?float $lat = null, ?float $lng = null, ?float $radius = null, int $exceptId = 0): array
{
    $rows = DEMO_MODE ? demo_read()['users'] : mysql_users();
    $out = [];
    foreach ($rows as $user) {
        if ((int) ($user['is_banned'] ?? 0) === 1 || (int) $user['id'] === $exceptId) {
            continue;
        }
        $public = public_user($user);
        if (!isset($public['lat'], $public['lng']) || $public['lat'] === null || $public['lng'] === null) {
            continue;
        }
        if ($lat !== null && $lng !== null) {
            $public['distance_km'] = round(haversine_km($lat, $lng, (float) $public['lat'], (float) $public['lng']), 1);
            if ($radius !== null && $public['distance_km'] > $radius) {
                continue;
            }
        }
        $out[] = $public;
    }
    usort($out, static function (array $a, array $b): int {
        $da = $a['distance_km'] ?? 99999;
        $db = $b['distance_km'] ?? 99999;
        return $da <=> $db;
    });
    return $out;
}

function sos_is_open(array $row): bool
{
    if (!in_array($row['status'] ?? '', ['active', 'accepted'], true)) {
        return false;
    }
    $expires = strtotime((string) ($row['expires_at'] ?? ''));
    return $expires !== false && $expires >= time();
}

function shape_sos(array $row, ?float $lat = null, ?float $lng = null): ?array
{
    $author = repo_user((int) $row['user_id']);
    if (!$author) {
        return null;
    }
    $helper = null;
    if (!empty($row['helper_id'])) {
        $helperRow = repo_user((int) $row['helper_id']);
        $helper = $helperRow ? public_user($helperRow) : null;
    }
    $catalog = sos_catalog();
    $type = (string) $row['type'];
    $distance = null;
    if ($lat !== null && $lng !== null) {
        $distance = round(haversine_km($lat, $lng, (float) $row['lat'], (float) $row['lng']), 1);
    }
    return [
        'id' => (int) $row['id'],
        'type' => $type,
        'type_label' => $catalog[$type][0] ?? 'Сигнал',
        'lat' => (float) $row['lat'],
        'lng' => (float) $row['lng'],
        'route' => (string) ($row['route'] ?? ''),
        'km' => ($row['km'] ?? '') === '' || $row['km'] === null ? null : (int) $row['km'],
        'comment' => (string) ($row['comment'] ?? ''),
        'status' => (string) $row['status'],
        'created_at' => (string) ($row['created_at'] ?? ''),
        'ago' => ru_ago((string) ($row['created_at'] ?? '')),
        'expires_at' => (string) ($row['expires_at'] ?? ''),
        'distance_km' => $distance,
        'author' => public_user($author),
        'helper' => $helper,
        'user_id' => (int) $row['user_id'],
        'helper_id' => !empty($row['helper_id']) ? (int) $row['helper_id'] : null,
    ];
}

function repo_sos_rows(): array
{
    return DEMO_MODE ? demo_read()['sos'] : mysql_sos_rows();
}

function repo_signal(int $id, ?float $lat = null, ?float $lng = null): ?array
{
    foreach (repo_sos_rows() as $row) {
        if ((int) $row['id'] === $id) {
            return shape_sos($row, $lat, $lng);
        }
    }
    return null;
}

function repo_signals(?float $lat = null, ?float $lng = null, ?float $radius = null, bool $openOnly = true): array
{
    $out = [];
    foreach (repo_sos_rows() as $row) {
        if ($openOnly && !sos_is_open($row)) {
            continue;
        }
        $shaped = shape_sos($row, $lat, $lng);
        if (!$shaped) {
            continue;
        }
        if ($radius !== null && $lat !== null && $lng !== null && ($shaped['distance_km'] ?? 99999) > $radius) {
            continue;
        }
        $out[] = $shaped;
    }
    usort($out, static function (array $a, array $b): int {
        $da = $a['distance_km'];
        $db = $b['distance_km'];
        if ($da !== null && $db !== null && $da !== $db) {
            return $da <=> $db;
        }
        return strcmp($b['created_at'], $a['created_at']);
    });
    return $out;
}

function repo_active_for(int $userId): ?array
{
    foreach (repo_sos_rows() as $row) {
        if ((int) $row['user_id'] === $userId && sos_is_open($row)) {
            return shape_sos($row);
        }
    }
    return null;
}

function repo_helps(int $userId): array
{
    $out = [];
    foreach (repo_sos_rows() as $row) {
        if (($row['status'] ?? '') !== 'completed') {
            continue;
        }
        $asked = (int) $row['user_id'] === $userId;
        $helped = (int) ($row['helper_id'] ?? 0) === $userId;
        if (!$asked && !$helped) {
            continue;
        }
        $shaped = shape_sos($row);
        if (!$shaped) {
            continue;
        }
        $shaped['part'] = $helped ? 'helped' : 'asked';
        $out[] = $shaped;
    }
    usort($out, static fn (array $a, array $b): int => strcmp($b['created_at'], $a['created_at']));
    return $out;
}

/**
 * @return array{ok: bool, error?: string, message?: string, id?: int}
 */
function repo_create_sos(int $userId, array $in): array
{
    $type = (string) ($in['type'] ?? '');
    if (!isset(sos_catalog()[$type])) {
        return ['ok' => false, 'error' => 'type', 'message' => 'Выбери, что случилось.'];
    }
    $lat = (float) ($in['lat'] ?? 0);
    $lng = (float) ($in['lng'] ?? 0);
    if (!valid_coords($lat, $lng)) {
        return ['ok' => false, 'error' => 'coords', 'message' => 'Нужна точка на карте. Без неё свои проедут мимо.'];
    }
    $kmRaw = $in['km'] ?? null;
    $km = ($kmRaw === '' || $kmRaw === null) ? null : (int) $kmRaw;
    if ($km !== null && ($km < 0 || $km > 20000)) {
        return ['ok' => false, 'error' => 'km', 'message' => 'Километр выглядит неправдоподобно.'];
    }
    if (repo_active_for($userId)) {
        return ['ok' => false, 'error' => 'already_active', 'message' => 'У тебя уже есть сигнал в эфире. Сначала закрой его.'];
    }
    $route = clip((string) ($in['route'] ?? ''), 50);
    $comment = clip((string) ($in['comment'] ?? ''), 500) ?? '';
    $now = date('Y-m-d H:i:s');
    $row = [
        'user_id' => $userId,
        'type' => $type,
        'lat' => $lat,
        'lng' => $lng,
        'route' => $route,
        'km' => $km,
        'comment' => $comment,
        'status' => 'active',
        'helper_id' => null,
        'created_at' => $now,
        'accepted_at' => null,
        'completed_at' => null,
        'expires_at' => date('Y-m-d H:i:s', time() + 7200),
    ];
    if (DEMO_MODE) {
        $id = (int) demo_edit(static function (array &$data) use ($row, $userId, $now): int {
            $row['id'] = demo_next_id($data['sos']);
            $data['sos'][] = $row;
            foreach ($data['users'] as &$user) {
                if ((int) $user['id'] === $userId) {
                    $user['status'] = 'need_help';
                    $user['lat'] = $row['lat'];
                    $user['lng'] = $row['lng'];
                    $user['route'] = $row['route'];
                    $user['updated_at'] = $now;
                }
            }
            unset($user);
            return (int) $row['id'];
        });
        poll_bump();
        return ['ok' => true, 'id' => $id];
    }
    $id = mysql_insert_sos($row);
    mysql_update_user($userId, [
        'status' => 'need_help',
        'lat' => $lat,
        'lng' => $lng,
        'route' => $route,
    ]);
    poll_bump();
    return ['ok' => true, 'id' => $id];
}

function repo_cancel_sos(int $id, int $userId): array
{
    return repo_mutate_sos($id, $userId, 'cancel');
}

function repo_accept_sos(int $id, int $helperId): array
{
    return repo_mutate_sos($id, $helperId, 'accept');
}

function repo_complete_sos(int $id, int $userId): array
{
    return repo_mutate_sos($id, $userId, 'complete');
}

function repo_mutate_sos(int $id, int $actorId, string $op): array
{
    if (DEMO_MODE) {
        $result = demo_edit(static function (array &$data) use ($id, $actorId, $op): array {
            foreach ($data['sos'] as &$sos) {
                if ((int) $sos['id'] !== $id) {
                    continue;
                }
                $result = apply_sos_op($sos, $data['users'], $actorId, $op);
                unset($sos);
                return $result;
            }
            unset($sos);
            return ['ok' => false, 'message' => 'Сигнал уже погас.'];
        });
        if (!empty($result['ok'])) {
            poll_bump();
        }
        return $result;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM sos_signals WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $sos = $stmt->fetch();
        if (!$sos) {
            $pdo->rollBack();
            return ['ok' => false, 'message' => 'Сигнал уже погас.'];
        }
        $users = [];
        foreach ([(int) $sos['user_id'], (int) ($sos['helper_id'] ?? 0)] as $linkedId) {
            if ($linkedId <= 0) {
                continue;
            }
            $linked = mysql_user($linkedId);
            if ($linked) {
                $users[] = $linked;
            }
        }
        $result = apply_sos_op($sos, $users, $actorId, $op);
        if (!$result['ok']) {
            $pdo->rollBack();
            return $result;
        }
        mysql_update_sos($id, $result['sos_fields']);
        if (!empty($result['user_fields'])) {
            foreach ($result['user_fields'] as $userId => $fields) {
                mysql_update_user((int) $userId, $fields);
            }
        }
        $pdo->commit();
        poll_bump();
        return ['ok' => true];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log($e->getMessage());
        return ['ok' => false, 'message' => 'Не удалось обновить сигнал.'];
    }
}

/**
 * @param array<int, array<string, mixed>> $users
 * @return array{ok: bool, message?: string, sos_fields?: array, user_fields?: array}
 */
function apply_sos_op(array &$sos, array &$users, int $actorId, string $op): array
{
    $now = date('Y-m-d H:i:s');
    $authorId = (int) $sos['user_id'];
    if ($op === 'cancel') {
        if ($authorId !== $actorId || !sos_is_open($sos)) {
            return ['ok' => false, 'message' => 'Этот сигнал уже нельзя снять.'];
        }
        $sos['status'] = 'cancelled';
        set_user_field($users, $authorId, ['status' => 'on_air', 'updated_at' => $now]);
        return ['ok' => true, 'sos_fields' => ['status' => 'cancelled'], 'user_fields' => [$authorId => ['status' => 'on_air']]];
    }
    if ($op === 'accept') {
        if (!sos_is_open($sos) || $sos['status'] !== 'active' || $authorId === $actorId) {
            return ['ok' => false, 'message' => 'Подхватить этот сигнал уже нельзя.'];
        }
        $sos['status'] = 'accepted';
        $sos['helper_id'] = $actorId;
        $sos['accepted_at'] = $now;
        return ['ok' => true, 'sos_fields' => ['status' => 'accepted', 'helper_id' => $actorId, 'accepted_at' => 'NOW']];
    }
    if ($op === 'complete') {
        if ($authorId !== $actorId || !in_array($sos['status'], ['active', 'accepted'], true)) {
            return ['ok' => false, 'message' => 'Закрыть этот сигнал может тот, кто его передал.'];
        }
        $helperId = (int) ($sos['helper_id'] ?? 0);
        $sos['status'] = 'completed';
        $sos['completed_at'] = $now;
        set_user_field($users, $authorId, ['status' => 'on_air', 'updated_at' => $now]);
        $userFields = [$authorId => ['status' => 'on_air']];
        if ($helperId > 0) {
            $current = 0;
            foreach ($users as $user) {
                if ((int) $user['id'] === $helperId) {
                    $current = (int) $user['helps_count'];
                }
            }
            set_user_field($users, $helperId, ['helps_count' => $current + 1]);
            $userFields[$helperId] = ['helps_count' => $current + 1];
        }
        return [
            'ok' => true,
            'sos_fields' => ['status' => 'completed', 'completed_at' => 'NOW'],
            'user_fields' => $userFields,
        ];
    }
    return ['ok' => false, 'message' => 'Неизвестное действие.'];
}

function set_user_field(array &$users, int $id, array $fields): void
{
    foreach ($users as &$user) {
        if ((int) $user['id'] === $id) {
            foreach ($fields as $key => $value) {
                $user[$key] = $value;
            }
        }
    }
    unset($user);
}

function repo_partners(array $filters = []): array
{
    $rows = DEMO_MODE ? demo_read()['partners'] : mysql_partners();
    $q = mb_strtolower(trim((string) ($filters['q'] ?? '')));
    $type = (string) ($filters['type'] ?? '');
    $city = (string) ($filters['city'] ?? '');
    $day = !empty($filters['day']);
    $rating = (float) ($filters['rating'] ?? 0);
    $types = partner_types();
    $out = [];
    foreach ($rows as $partner) {
        if ((int) ($partner['is_active'] ?? 1) !== 1) {
            continue;
        }
        if ($type !== '' && ($partner['type'] ?? '') !== $type) {
            continue;
        }
        if ($city !== '' && ($partner['city'] ?? '') !== $city) {
            continue;
        }
        if ($day && (int) ($partner['works_24_7'] ?? 0) !== 1) {
            continue;
        }
        if ($rating > 0 && (float) ($partner['rating_avg'] ?? 0) < $rating) {
            continue;
        }
        if ($q !== '') {
            $hay = mb_strtolower(($partner['name'] ?? '') . ' ' . ($partner['city'] ?? '') . ' ' . ($partner['address'] ?? '') . ' ' . ($partner['description'] ?? ''));
            if (!mb_strpos_contains($hay, $q)) {
                continue;
            }
        }
        $price = $partner['price_from'];
        $out[] = [
            'id' => (int) $partner['id'],
            'name' => (string) $partner['name'],
            'type' => (string) $partner['type'],
            'type_label' => $types[$partner['type']] ?? 'Партнёр',
            'city' => (string) ($partner['city'] ?? ''),
            'address' => (string) ($partner['address'] ?? ''),
            'lat' => $partner['lat'] !== null && $partner['lat'] !== '' ? (float) $partner['lat'] : null,
            'lng' => $partner['lng'] !== null && $partner['lng'] !== '' ? (float) $partner['lng'] : null,
            'phone' => ($partner['phone'] ?? '') !== '' ? (string) $partner['phone'] : null,
            'description' => (string) ($partner['description'] ?? ''),
            'photo_path' => ($partner['photo_path'] ?? '') !== '' ? $partner['photo_path'] : null,
            'price_from' => $price !== null && $price !== '' ? (int) $price : null,
            'works_24_7' => (int) ($partner['works_24_7'] ?? 0) === 1,
            'rating' => round((float) ($partner['rating_avg'] ?? 0), 1),
            'rating_count' => (int) ($partner['rating_count'] ?? 0),
        ];
    }
    return $out;
}

function mb_strpos_contains(string $hay, string $needle): bool
{
    return $needle === '' || mb_strpos($hay, $needle) !== false;
}

function repo_partner_cities(): array
{
    $cities = [];
    foreach (repo_partners([]) as $partner) {
        if ($partner['city'] !== '') {
            $cities[$partner['city']] = true;
        }
    }
    $list = array_keys($cities);
    sort($list, SORT_STRING);
    return $list;
}

function shape_post(array $post, array $author, bool $liked): array
{
    return [
        'id' => (int) $post['id'],
        'body' => (string) $post['body'],
        'photo_path' => ($post['photo_path'] ?? '') !== '' ? $post['photo_path'] : null,
        'city' => (string) ($post['city'] ?? ''),
        'route' => (string) ($post['route'] ?? ''),
        'likes_count' => (int) ($post['likes_count'] ?? 0),
        'status' => (string) ($post['status'] ?? 'pending'),
        'created_at' => (string) ($post['created_at'] ?? ''),
        'ago' => ru_ago((string) ($post['created_at'] ?? '')),
        'liked' => $liked,
        'author' => public_user($author),
    ];
}

function repo_posts(?int $viewerId = null): array
{
    $posts = DEMO_MODE ? demo_read()['posts'] : mysql_posts();
    $likes = DEMO_MODE ? demo_read()['likes'] : mysql_likes();
    $liked = [];
    foreach ($likes as $like) {
        if ($viewerId && (int) $like['user_id'] === $viewerId) {
            $liked[(int) $like['post_id']] = true;
        }
    }
    $out = [];
    foreach ($posts as $post) {
        if (($post['status'] ?? '') !== 'published') {
            continue;
        }
        $author = repo_user((int) $post['user_id']);
        if (!$author || (int) ($author['is_banned'] ?? 0) === 1) {
            continue;
        }
        $out[] = shape_post($post, $author, isset($liked[(int) $post['id']]));
    }
    usort($out, static fn (array $a, array $b): int => strcmp($b['created_at'], $a['created_at']));
    return $out;
}

function repo_pending_posts(int $userId): array
{
    $posts = DEMO_MODE ? demo_read()['posts'] : mysql_posts();
    $out = [];
    foreach ($posts as $post) {
        if ((int) $post['user_id'] === $userId && ($post['status'] ?? '') === 'pending') {
            $author = repo_user($userId);
            if ($author) {
                $out[] = shape_post($post, $author, false);
            }
        }
    }
    return $out;
}

function repo_create_post(int $userId, array $in): array
{
    $body = clip((string) ($in['body'] ?? ''), 2000) ?? '';
    if (mb_strlen($body) < 10) {
        return ['ok' => false, 'message' => 'Напиши хотя бы пару слов: где и что видел.'];
    }
    $photo = ($in['photo_path'] ?? '') !== '' ? (string) $in['photo_path'] : null;
    $status = $photo ? 'pending' : 'published';
    $row = [
        'user_id' => $userId,
        'body' => $body,
        'photo_path' => $photo,
        'city' => clip((string) ($in['city'] ?? ''), 100),
        'route' => clip((string) ($in['route'] ?? ''), 50),
        'likes_count' => 0,
        'status' => $status,
        'created_at' => date('Y-m-d H:i:s'),
    ];
    if (DEMO_MODE) {
        $id = (int) demo_edit(static function (array &$data) use ($row): int {
            $row['id'] = demo_next_id($data['posts']);
            $data['posts'][] = $row;
            return (int) $row['id'];
        });
    } else {
        $id = mysql_insert_post($row);
    }
    return ['ok' => true, 'id' => $id, 'status' => $status];
}

function repo_like(int $userId, int $postId): array
{
    if (DEMO_MODE) {
        return demo_edit(static function (array &$data) use ($userId, $postId): array {
            foreach ($data['posts'] as $index => $post) {
                if ((int) $post['id'] !== $postId) {
                    continue;
                }
                if (($post['status'] ?? '') !== 'published') {
                    return ['ok' => false, 'message' => 'Запись ещё не в эфире.'];
                }
                foreach ($data['likes'] as $like) {
                    if ((int) $like['user_id'] === $userId && (int) $like['post_id'] === $postId) {
                        return ['ok' => true, 'liked' => true, 'count' => (int) $post['likes_count']];
                    }
                }
                $data['likes'][] = ['user_id' => $userId, 'post_id' => $postId];
                $data['posts'][$index]['likes_count'] = (int) $post['likes_count'] + 1;
                return ['ok' => true, 'liked' => true, 'count' => (int) $data['posts'][$index]['likes_count']];
            }
            return ['ok' => false, 'message' => 'Запись не найдена.'];
        });
    }
    $found = false;
    foreach (mysql_posts() as $post) {
        if ((int) $post['id'] === $postId && ($post['status'] ?? '') === 'published') {
            $found = true;
        }
    }
    if (!$found) {
        return ['ok' => false, 'message' => 'Запись не найдена.'];
    }
    mysql_like($userId, $postId);
    foreach (mysql_posts() as $post) {
        if ((int) $post['id'] === $postId) {
            return ['ok' => true, 'liked' => true, 'count' => (int) $post['likes_count']];
        }
    }
    return ['ok' => false, 'message' => 'Запись не найдена.'];
}

function repo_report(int $userId, int $postId): array
{
    $posts = DEMO_MODE ? demo_read()['posts'] : mysql_posts();
    $owner = null;
    foreach ($posts as $post) {
        if ((int) $post['id'] === $postId) {
            $owner = (int) $post['user_id'];
        }
    }
    if ($owner === null) {
        return ['ok' => false, 'message' => 'Запись не найдена.'];
    }
    if ($owner === $userId) {
        return ['ok' => false, 'message' => 'Свою запись не жалуют. Можно просто не писать так в следующий раз.'];
    }
    if (DEMO_MODE) {
        demo_edit(static function (array &$data) use ($userId, $postId): bool {
            foreach ($data['reports'] as $report) {
                if ((int) $report['user_id'] === $userId && (int) $report['post_id'] === $postId) {
                    return true;
                }
            }
            $data['reports'][] = ['user_id' => $userId, 'post_id' => $postId, 'created_at' => date('Y-m-d H:i:s')];
            return true;
        });
        return ['ok' => true];
    }
    mysql_report($userId, $postId);
    return ['ok' => true];
}

/**
 * @return array{ok: bool, message?: string}
 */
function repo_save_profile(int $id, array $in): array
{
    $name = clip((string) ($in['name'] ?? ''), 100) ?? '';
    if (mb_strlen($name) < 2) {
        return ['ok' => false, 'message' => 'Имя или позывной — от двух букв.'];
    }
    $role = (string) ($in['role'] ?? 'both');
    if (!in_array($role, ['seeker', 'helper', 'both'], true)) {
        $role = 'both';
    }
    $status = (string) ($in['status'] ?? 'on_air');
    if (!in_array($status, ['on_air', 'on_route', 'need_help', 'resting', 'offline'], true)) {
        $status = 'on_air';
    }
    if (repo_active_for($id)) {
        $status = 'need_help';
    } elseif ($status === 'need_help') {
        $status = 'on_air';
    }
    $yearsRaw = $in['experience_y'] ?? null;
    $years = ($yearsRaw === '' || $yearsRaw === null) ? null : max(0, min(60, (int) $yearsRaw));
    $fields = [
        'name' => $name,
        'city' => clip((string) ($in['city'] ?? ''), 100),
        'role' => $role,
        'truck_brand' => clip((string) ($in['truck_brand'] ?? ''), 100),
        'truck_plate' => clip((string) ($in['truck_plate'] ?? ''), 20),
        'truck_trailer' => clip((string) ($in['truck_trailer'] ?? ''), 50),
        'experience_y' => $years,
        'motto' => clip((string) ($in['motto'] ?? ''), 200),
        'status' => $status,
        'route' => clip((string) ($in['route'] ?? ''), 50),
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    repo_update_user($id, $fields);
    $_SESSION['user_name'] = $name;
    poll_bump();
    return ['ok' => true];
}

function repo_set_media(int $id, string $field, string $path): void
{
    if (!in_array($field, ['avatar_path', 'car_path'], true)) {
        return;
    }
    repo_update_user($id, [$field => $path, 'updated_at' => date('Y-m-d H:i:s')]);
}

function repo_update_user(int $id, array $fields): void
{
    if (DEMO_MODE) {
        demo_edit(static function (array &$data) use ($id, $fields): bool {
            foreach ($data['users'] as &$user) {
                if ((int) $user['id'] === $id) {
                    foreach ($fields as $key => $value) {
                        $user[$key] = $value;
                    }
                }
            }
            unset($user);
            return true;
        });
        return;
    }
    mysql_update_user($id, $fields);
}

function repo_cleanup(): void
{
    if (DEMO_MODE) {
        demo_edit(static function (array &$data): bool {
            foreach ($data['sos'] as &$sos) {
                if (($sos['status'] ?? '') === 'active' && strtotime((string) $sos['expires_at']) < time()) {
                    $sos['status'] = 'cancelled';
                    set_user_field($data['users'], (int) $sos['user_id'], ['status' => 'on_air']);
                }
            }
            unset($sos);
            return true;
        });
        poll_bump();
        return;
    }
    mysql_expire_sos();
    db()->exec(
        "UPDATE users u SET status = 'on_air'
         WHERE status = 'need_help' AND NOT EXISTS (
           SELECT 1 FROM sos_signals s
           WHERE s.user_id = u.id AND s.status IN ('active','accepted') AND s.expires_at > NOW()
         )"
    );
    poll_bump();
}
