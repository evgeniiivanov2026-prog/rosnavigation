<?php
declare(strict_types=1);

function store_file(): string
{
    $custom = getenv('ROSNAV_STORE');
    if (is_string($custom) && $custom !== '') {
        return $custom;
    }
    return dirname(__DIR__) . '/storage/data.json';
}

function store_read(): array
{
    $path = store_file();
    if (!is_file($path)) {
        return blank_store();
    }
    $raw = file_get_contents($path);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        log_line('store', 'повреждён data.json, прячу копию');
        @rename($path, $path . '.bad-' . date('Ymd-His'));
        return blank_store();
    }
    return array_merge(blank_store(), $data);
}

function store_mutate(callable $fn): mixed
{
    $path = store_file();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $lockPath = $path . '.lock';
    $lock = fopen($lockPath, 'c');
    if ($lock === false) {
        throw new HttpError('store', 'Хранилище занято.', 500);
    }
    try {
        if (!flock($lock, LOCK_EX)) {
            throw new HttpError('store', 'Хранилище занято.', 500);
        }
        $data = store_read();
        $result = $fn($data);
        $tmp = $path . '.tmp';
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false || file_put_contents($tmp, $json) === false) {
            throw new HttpError('store', 'Не записали данные.', 500);
        }
        if (!rename($tmp, $path)) {
            throw new HttpError('store', 'Не записали данные.', 500);
        }
        return $result;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function file_users(): array
{
    return array_merge(seed_bundle()['users'], store_read()['users']);
}

function file_sos(): array
{
    return array_merge(seed_bundle()['sos'], store_read()['sos']);
}

function file_posts(): array
{
    return array_merge(seed_bundle()['posts'], store_read()['posts']);
}

function file_reviews(): array
{
    return array_merge(seed_bundle()['reviews'], store_read()['reviews']);
}

function file_find_user(int $id): ?array
{
    foreach (file_users() as $user) {
        if ((int) $user['id'] === $id && empty($user['is_banned'])) {
            return $user;
        }
    }
    return null;
}

function file_next_id(array $rows): int
{
    $max = 0;
    foreach ($rows as $row) {
        $max = max($max, (int) ($row['id'] ?? 0));
    }
    return $max + 1;
}

function file_stats(): array
{
    $online = 0;
    $cutoff = time() - 1800;
    foreach (file_users() as $user) {
        if (!empty($user['is_banned']) || !empty($user['demo']) || ($user['status'] ?? '') === 'offline') {
            continue;
        }
        $seen = strtotime((string) ($user['last_seen_at'] ?? '')) ?: 0;
        if ($seen >= $cutoff) {
            $online++;
        }
    }
    $active = 0;
    $helped = 0;
    foreach (file_sos() as $sos) {
        if ($sos['status'] === 'completed') {
            $helped++;
        }
        if ($sos['status'] === 'active' && strtotime((string) $sos['expires_at']) > time()) {
            $active++;
        }
    }
    return [
        'online' => $online,
        'active_sos' => $active,
        'helped' => $helped,
        'mode' => 'file',
    ];
}

function file_user_by_phone(string $phone): ?array
{
    foreach (store_read()['users'] as $user) {
        if (($user['phone'] ?? '') === $phone) {
            return $user;
        }
    }
    return null;
}

function file_register(string $phone, string $password, string $name): array
{
    return store_mutate(function (array &$data) use ($phone, $password, $name) {
        foreach ($data['users'] as $user) {
            if (($user['phone'] ?? '') === $phone) {
                throw new HttpError('phone_taken', 'Этот номер уже в колонне. Войдите.', 409);
            }
        }
        $now = date('Y-m-d H:i:s');
        $user = [
            'id' => file_next_id($data['users']),
            'phone' => $phone,
            'password_hash' => hash_password($password),
            'name' => $name,
            'city' => null,
            'role' => 'both',
            'truck_brand' => null,
            'truck_plate' => null,
            'truck_trailer' => null,
            'experience_y' => null,
            'avatar_path' => null,
            'car_path' => null,
            'motto' => null,
            'status' => 'on_air',
            'rating_sum' => 0,
            'rating_count' => 0,
            'helps_count' => 0,
            'is_partner' => 0,
            'is_banned' => 0,
            'last_seen_at' => $now,
            'lat' => null,
            'lng' => null,
            'demo' => 0,
            'created_at' => $now,
        ];
        $data['users'][] = $user;
        return $user;
    });
}

function file_update_user(int $id, array $fields): array
{
    return store_mutate(function (array &$data) use ($id, $fields) {
        foreach ($data['users'] as &$user) {
            if ((int) $user['id'] !== $id) {
                continue;
            }
            foreach ($fields as $key => $value) {
                $user[$key] = $value;
            }
            $user['last_seen_at'] = date('Y-m-d H:i:s');
            return $user;
        }
        unset($user);
        throw new HttpError('not_found', 'Водителя нет в колонне.', 404);
    });
}

function file_touch(int $id, ?float $lat, ?float $lng): void
{
    store_mutate(function (array &$data) use ($id, $lat, $lng) {
        foreach ($data['users'] as &$user) {
            if ((int) $user['id'] === $id) {
                $user['last_seen_at'] = date('Y-m-d H:i:s');
                if ($lat !== null && $lng !== null) {
                    $user['lat'] = $lat;
                    $user['lng'] = $lng;
                }
                break;
            }
        }
        return null;
    });
}

function file_helpers_near(float $lat, float $lng, float $radius, ?int $except): array
{
    $rows = [];
    foreach (file_users() as $user) {
        if (!empty($user['is_banned'])) {
            continue;
        }
        if ($except !== null && (int) $user['id'] === $except) {
            continue;
        }
        if (!in_array($user['status'], ['on_air', 'on_route'], true)) {
            continue;
        }
        $rows[] = $user;
    }
    return array_slice(filter_near($rows, $lat, $lng, $radius), 0, 100);
}

function file_sos_active_for(int $userId): ?array
{
    foreach (file_sos() as $sos) {
        if ((int) $sos['user_id'] === $userId && $sos['status'] === 'active' && strtotime((string) $sos['expires_at']) > time()) {
            return $sos;
        }
    }
    return null;
}

function file_sos_by_id(int $id): ?array
{
    foreach (file_sos() as $sos) {
        if ((int) $sos['id'] === $id) {
            return $sos;
        }
    }
    return null;
}

function file_sos_create(int $userId, array $payload): array
{
    return store_mutate(function (array &$data) use ($userId, $payload) {
        foreach (array_merge(seed_bundle()['sos'], $data['sos']) as $sos) {
            if ((int) $sos['user_id'] === $userId && $sos['status'] === 'active' && strtotime((string) $sos['expires_at']) > time()) {
                throw new HttpError('already_active', 'У вас уже есть сигнал. Сначала закройте его.', 409);
            }
        }
        $now = date('Y-m-d H:i:s');
        $row = [
            'id' => file_next_id($data['sos']),
            'user_id' => $userId,
            'type' => $payload['type'],
            'lat' => $payload['lat'],
            'lng' => $payload['lng'],
            'route' => $payload['route'],
            'km' => $payload['km'],
            'comment' => $payload['comment'],
            'status' => 'active',
            'helper_id' => null,
            'approx_geo' => $payload['approx'],
            'demo' => 0,
            'created_at' => $now,
            'accepted_at' => null,
            'completed_at' => null,
            'expires_at' => date('Y-m-d H:i:s', time() + SOS_TTL_HOURS * 3600),
        ];
        $data['sos'][] = $row;
        foreach ($data['users'] as &$user) {
            if ((int) $user['id'] === $userId) {
                $user['status'] = 'need_help';
                $user['lat'] = $payload['lat'];
                $user['lng'] = $payload['lng'];
                $user['last_seen_at'] = $now;
            }
        }
        unset($user);
        return $row;
    });
}

function file_patch_sos(int $id, callable $fn): array
{
    $found = file_sos_by_id($id);
    if ($found && !empty($found['demo'])) {
        throw new HttpError('demo', 'Это учебная метка. На неё не едут и её не закрывают.', 409);
    }
    return store_mutate(function (array &$data) use ($id, $fn) {
        foreach ($data['sos'] as &$sos) {
            if ((int) $sos['id'] === $id) {
                $fn($sos, $data);
                return $sos;
            }
        }
        unset($sos);
        throw new HttpError('not_found', 'Сигнал уже погас.', 404);
    });
}

function file_messages(int $sosId): array
{
    $rows = array_values(array_filter(store_read()['messages'], static fn ($m) => (int) $m['sos_id'] === $sosId));
    usort($rows, static fn ($a, $b) => strcmp((string) $a['created_at'], (string) $b['created_at']));
    return $rows;
}

function file_message_add(int $sosId, int $userId, string $body): array
{
    return store_mutate(function (array &$data) use ($sosId, $userId, $body) {
        $row = [
            'id' => file_next_id($data['messages']),
            'sos_id' => $sosId,
            'user_id' => $userId,
            'body' => $body,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $data['messages'][] = $row;
        return $row;
    });
}

function file_posts_all(): array
{
    $extraLikes = [];
    foreach (store_read()['likes'] as $like) {
        $pid = (int) $like['post_id'];
        $extraLikes[$pid] = ($extraLikes[$pid] ?? 0) + 1;
    }
    $rows = file_posts();
    foreach ($rows as &$post) {
        if (!empty($post['demo'])) {
            continue;
        }
        $post['likes_count'] = (int) $post['likes_count'] + ($extraLikes[(int) $post['id']] ?? 0);
    }
    unset($post);
    foreach ($rows as &$post) {
        if (!empty($post['demo'])) {
            $post['likes_count'] = (int) $post['likes_count'] + ($extraLikes[(int) $post['id']] ?? 0);
        }
    }
    unset($post);
    usort($rows, static fn ($a, $b) => strcmp((string) $b['created_at'], (string) $a['created_at']));
    return $rows;
}

function file_post_create(int $userId, string $body, ?string $city, ?string $route, ?string $photo): array
{
    return store_mutate(function (array &$data) use ($userId, $body, $city, $route, $photo) {
        $row = [
            'id' => file_next_id($data['posts']),
            'user_id' => $userId,
            'body' => $body,
            'photo_path' => $photo,
            'city' => $city,
            'route' => $route,
            'likes_count' => 0,
            'status' => $photo ? 'pending' : 'published',
            'demo' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $data['posts'][] = $row;
        return $row;
    });
}

function file_like(int $userId, int $postId): int
{
    return store_mutate(function (array &$data) use ($userId, $postId) {
        $exists = false;
        foreach (file_posts() as $post) {
            if ((int) $post['id'] === $postId && $post['status'] === 'published') {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            throw new HttpError('not_found', 'Записи нет на эфире.', 404);
        }
        foreach ($data['likes'] as $like) {
            if ((int) $like['user_id'] === $userId && (int) $like['post_id'] === $postId) {
                throw new HttpError('liked', 'Пятюня уже стоит.', 409);
            }
        }
        $data['likes'][] = [
            'user_id' => $userId,
            'post_id' => $postId,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $base = 0;
        foreach (file_posts() as $post) {
            if ((int) $post['id'] === $postId) {
                $base = (int) $post['likes_count'];
                break;
            }
        }
        $extra = 0;
        foreach ($data['likes'] as $like) {
            if ((int) $like['post_id'] === $postId) {
                $extra++;
            }
        }
        return $base + $extra;
    });
}

function file_liked(int $userId, int $postId): bool
{
    foreach (store_read()['likes'] as $like) {
        if ((int) $like['user_id'] === $userId && (int) $like['post_id'] === $postId) {
            return true;
        }
    }
    return false;
}

function file_report(string $type, int $id, string $reason): void
{
    store_mutate(function (array &$data) use ($type, $id, $reason) {
        $data['reports'][] = [
            'id' => file_next_id($data['reports']),
            'target_type' => $type,
            'target_id' => $id,
            'reason' => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        return null;
    });
}

function file_review_add(int $authorId, int $targetUser, int $rating, string $body): void
{
    store_mutate(function (array &$data) use ($authorId, $targetUser, $rating, $body) {
        $data['reviews'][] = [
            'id' => file_next_id($data['reviews']),
            'author_id' => $authorId,
            'target_user' => $targetUser,
            'target_partner' => null,
            'rating' => $rating,
            'body' => $body,
            'status' => 'published',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        foreach ($data['users'] as &$user) {
            if ((int) $user['id'] === $targetUser) {
                $user['rating_sum'] = (int) $user['rating_sum'] + $rating;
                $user['rating_count'] = (int) $user['rating_count'] + 1;
                $user['helps_count'] = (int) $user['helps_count'] + 1;
            }
        }
        return null;
    });
}

function file_cleanup(): array
{
    return store_mutate(function (array &$data) {
        $now = time();
        $removed = 0;
        $data['sos'] = array_values(array_filter($data['sos'], static function ($sos) use ($now, &$removed) {
            $expired = strtotime((string) ($sos['expires_at'] ?? '')) ?: 0;
            if ($sos['status'] === 'active' && $expired < $now) {
                return true;
            }
            if (in_array($sos['status'], ['cancelled', 'completed'], true) && $expired < $now - 14 * 86400) {
                $removed++;
                return false;
            }
            return true;
        }));
        foreach ($data['sos'] as &$sos) {
            if ($sos['status'] === 'active' && (strtotime((string) $sos['expires_at']) ?: 0) < $now) {
                $sos['status'] = 'cancelled';
            }
        }
        unset($sos);
        return ['expired_marked' => true, 'removed' => $removed];
    });
}
