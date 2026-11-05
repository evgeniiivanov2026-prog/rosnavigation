<?php
declare(strict_types=1);

function mysql_stats(): array
{
    $online = (int) db()->query(
        "SELECT COUNT(*) FROM users
         WHERE is_banned = 0 AND status <> 'offline'
           AND last_seen_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
    )->fetchColumn();
    $active = (int) db()->query(
        "SELECT COUNT(*) FROM sos_signals
         WHERE status = 'active' AND expires_at > NOW()"
    )->fetchColumn();
    $helped = (int) db()->query(
        "SELECT COUNT(*) FROM sos_signals WHERE status = 'completed'"
    )->fetchColumn();
    return [
        'online' => $online,
        'active_sos' => $active,
        'helped' => $helped,
        'mode' => 'mysql',
    ];
}

function mysql_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND is_banned = 0');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mysql_user_by_phone(string $phone): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE phone = ?');
    $stmt->execute([$phone]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mysql_register(string $phone, string $password, string $name): array
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO users (phone, password_hash, name, status, last_seen_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$phone, hash_password($password), $name, 'on_air']);
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            throw new HttpError('phone_taken', 'Этот номер уже в колонне. Войдите.', 409);
        }
        throw $e;
    }
    $user = mysql_user_by_id((int) db()->lastInsertId());
    if (!$user) {
        throw new HttpError('store', 'Не удалось создать профиль.', 500);
    }
    return $user;
}

function mysql_update_user(int $id, array $fields): array
{
    if ($fields === []) {
        $user = mysql_user_by_id($id);
        if (!$user) {
            throw new HttpError('not_found', 'Водителя нет в колонне.', 404);
        }
        return $user;
    }
    $allowed = [
        'name', 'city', 'role', 'truck_brand', 'truck_plate', 'truck_trailer',
        'experience_y', 'motto', 'status', 'lat', 'lng', 'avatar_path', 'car_path',
    ];
    $sets = [];
    $values = [];
    foreach ($fields as $key => $value) {
        if (!in_array($key, $allowed, true)) {
            continue;
        }
        $sets[] = $key . ' = ?';
        $values[] = $value;
    }
    if ($sets === []) {
        return mysql_update_user($id, []);
    }
    $sets[] = 'last_seen_at = NOW()';
    $values[] = $id;
    $stmt = db()->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $stmt->execute($values);
    $user = mysql_user_by_id($id);
    if (!$user) {
        throw new HttpError('not_found', 'Водителя нет в колонне.', 404);
    }
    return $user;
}

function mysql_touch(int $id, ?float $lat, ?float $lng): void
{
    if ($lat !== null && $lng !== null) {
        $stmt = db()->prepare('UPDATE users SET last_seen_at = NOW(), lat = ?, lng = ? WHERE id = ?');
        $stmt->execute([$lat, $lng, $id]);
        return;
    }
    $stmt = db()->prepare('UPDATE users SET last_seen_at = NOW() WHERE id = ?');
    $stmt->execute([$id]);
}

function mysql_helpers_near(float $lat, float $lng, float $radius, ?int $except): array
{
    [$minLat, $maxLat, $minLng, $maxLng] = bbox($lat, $lng, $radius);
    $stmt = db()->prepare(
        "SELECT * FROM users
         WHERE is_banned = 0
           AND status IN ('on_air', 'on_route')
           AND lat BETWEEN ? AND ?
           AND lng BETWEEN ? AND ?
           AND id <> ?
         LIMIT 100"
    );
    $stmt->execute([$minLat, $maxLat, $minLng, $maxLng, $except ?? 0]);
    return $stmt->fetchAll();
}

function mysql_sos_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM sos_signals WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mysql_sos_active_for(int $userId): ?array
{
    $stmt = db()->prepare(
        "SELECT * FROM sos_signals
         WHERE user_id = ? AND status = 'active' AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mysql_sos_candidates(?float $lat, ?float $lng, float $radius): array
{
    if ($lat === null || $lng === null) {
        $stmt = db()->query(
            "SELECT * FROM sos_signals
             WHERE status IN ('active', 'accepted') AND expires_at > NOW()
             ORDER BY created_at DESC LIMIT 100"
        );
        return $stmt->fetchAll();
    }
    [$minLat, $maxLat, $minLng, $maxLng] = bbox($lat, $lng, $radius);
    $stmt = db()->prepare(
        "SELECT * FROM sos_signals
         WHERE status IN ('active', 'accepted')
           AND expires_at > NOW()
           AND lat BETWEEN ? AND ?
           AND lng BETWEEN ? AND ?
         LIMIT 100"
    );
    $stmt->execute([$minLat, $maxLat, $minLng, $maxLng]);
    return $stmt->fetchAll();
}

function mysql_sos_create(int $userId, array $payload): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "SELECT id FROM sos_signals
             WHERE user_id = ? AND status = 'active' AND expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        if ($stmt->fetch()) {
            throw new HttpError('already_active', 'У вас уже есть сигнал. Сначала закройте его.', 409);
        }
        $stmt = $pdo->prepare(
            'INSERT INTO sos_signals
             (user_id, type, lat, lng, route, km, comment, approx_geo, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ' . (int) SOS_TTL_HOURS . ' HOUR))'
        );
        $stmt->execute([
            $userId,
            $payload['type'],
            $payload['lat'],
            $payload['lng'],
            $payload['route'],
            $payload['km'],
            $payload['comment'],
            $payload['approx'],
        ]);
        $id = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('UPDATE users SET status = ?, lat = ?, lng = ?, last_seen_at = NOW() WHERE id = ?');
        $stmt->execute(['need_help', $payload['lat'], $payload['lng'], $userId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    $row = mysql_sos_by_id($id);
    if (!$row) {
        throw new HttpError('store', 'Сигнал не сохранился.', 500);
    }
    return $row;
}

function mysql_sos_cancel(int $id, int $ownerId): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $sos = mysql_sos_by_id($id);
        if (!$sos || (int) $sos['user_id'] !== $ownerId) {
            throw new HttpError('forbidden', 'Закрыть сигнал может только тот, кто его передал.', 403);
        }
        if (!empty($sos['demo'])) {
            throw new HttpError('demo', 'Это учебная метка. На неё не едут и её не закрывают.', 409);
        }
        if (!in_array($sos['status'], ['active', 'accepted'], true)) {
            throw new HttpError('state', 'Сигнал уже закрыт.', 409);
        }
        $stmt = $pdo->prepare("UPDATE sos_signals SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("UPDATE users SET status = 'on_air' WHERE id = ? AND status = 'need_help'");
        $stmt->execute([$ownerId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return mysql_sos_by_id($id) ?? [];
}

function mysql_sos_accept(int $id, int $helperId): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM sos_signals WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $sos = $stmt->fetch();
        if (!$sos) {
            throw new HttpError('not_found', 'Сигнал уже погас.', 404);
        }
        if (!empty($sos['demo'])) {
            throw new HttpError('demo', 'Это учебная метка. На неё не едут и её не закрывают.', 409);
        }
        if ((int) $sos['user_id'] === $helperId) {
            throw new HttpError('self', 'Свой сигнал не принимают.', 409);
        }
        if ($sos['status'] !== 'active' || strtotime((string) $sos['expires_at']) < time()) {
            throw new HttpError('state', 'Сигнал уже разобрали или он погас.', 409);
        }
        $stmt = $pdo->prepare(
            "UPDATE sos_signals
             SET status = 'accepted', helper_id = ?, accepted_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$helperId, $id]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return mysql_sos_by_id($id) ?? [];
}

function mysql_sos_complete(int $id, int $actorId, ?int $rating, string $review): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM sos_signals WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $sos = $stmt->fetch();
        if (!$sos) {
            throw new HttpError('not_found', 'Сигнал уже погас.', 404);
        }
        if (!empty($sos['demo'])) {
            throw new HttpError('demo', 'Это учебная метка. На неё не едут и её не закрывают.', 409);
        }
        $owner = (int) $sos['user_id'];
        $helper = (int) ($sos['helper_id'] ?? 0);
        if ($actorId !== $owner && $actorId !== $helper) {
            throw new HttpError('forbidden', 'Закрыть выручку могут только свои по этому сигналу.', 403);
        }
        if ($sos['status'] !== 'accepted' || $helper < 1) {
            throw new HttpError('state', 'Сначала кто-то должен принять сигнал.', 409);
        }
        $stmt = $pdo->prepare(
            "UPDATE sos_signals SET status = 'completed', completed_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("UPDATE users SET status = 'on_air' WHERE id = ? AND status = 'need_help'");
        $stmt->execute([$owner]);
        if ($rating !== null && $actorId === $owner) {
            $stmt = $pdo->prepare(
                'INSERT INTO reviews (author_id, target_user, rating, body, status)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$owner, $helper, $rating, $review !== '' ? $review : null, 'published']);
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET helps_count = helps_count + 1,
                     rating_sum = rating_sum + ?,
                     rating_count = rating_count + 1
                 WHERE id = ?'
            );
            $stmt->execute([$rating, $helper]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET helps_count = helps_count + 1 WHERE id = ?');
            $stmt->execute([$helper]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return mysql_sos_by_id($id) ?? [];
}

function mysql_messages(int $sosId): array
{
    $stmt = db()->prepare('SELECT * FROM sos_messages WHERE sos_id = ? ORDER BY id ASC');
    $stmt->execute([$sosId]);
    return $stmt->fetchAll();
}

function mysql_message_add(int $sosId, int $userId, string $body): array
{
    $stmt = db()->prepare('INSERT INTO sos_messages (sos_id, user_id, body) VALUES (?, ?, ?)');
    $stmt->execute([$sosId, $userId, $body]);
    return [
        'id' => (int) db()->lastInsertId(),
        'sos_id' => $sosId,
        'user_id' => $userId,
        'body' => $body,
        'created_at' => date('Y-m-d H:i:s'),
    ];
}

function mysql_posts(): array
{
    $stmt = db()->query('SELECT * FROM channel_posts ORDER BY created_at DESC LIMIT 100');
    return $stmt->fetchAll();
}

function mysql_post_create(int $userId, string $body, ?string $city, ?string $route, ?string $photo): array
{
    $status = $photo ? 'pending' : 'published';
    $stmt = db()->prepare(
        'INSERT INTO channel_posts (user_id, body, photo_path, city, route, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $body, $photo, $city, $route, $status]);
    $id = (int) db()->lastInsertId();
    $stmt = db()->prepare('SELECT * FROM channel_posts WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: [];
}

function mysql_like(int $userId, int $postId): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT id, likes_count, status FROM channel_posts WHERE id = ? FOR UPDATE");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        if (!$post || $post['status'] !== 'published') {
            throw new HttpError('not_found', 'Записи нет на эфире.', 404);
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO channel_likes (user_id, post_id) VALUES (?, ?)');
            $stmt->execute([$userId, $postId]);
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw new HttpError('liked', 'Пятюня уже стоит.', 409);
            }
            throw $e;
        }
        $stmt = $pdo->prepare('UPDATE channel_posts SET likes_count = likes_count + 1 WHERE id = ?');
        $stmt->execute([$postId]);
        $count = (int) $post['likes_count'] + 1;
        $pdo->commit();
        return $count;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function mysql_liked(int $userId, int $postId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM channel_likes WHERE user_id = ? AND post_id = ?');
    $stmt->execute([$userId, $postId]);
    return (bool) $stmt->fetchColumn();
}

function mysql_report(string $type, int $id, string $reason): void
{
    $stmt = db()->prepare(
        'INSERT INTO reports (target_type, target_id, reason) VALUES (?, ?, ?)'
    );
    $stmt->execute([$type, $id, $reason]);
}

function mysql_partners(): array
{
    $stmt = db()->query('SELECT * FROM partners WHERE is_active = 1 ORDER BY rating_avg DESC');
    return $stmt->fetchAll();
}

function mysql_helps(int $userId): array
{
    $stmt = db()->prepare(
        "SELECT * FROM sos_signals
         WHERE helper_id = ? AND status = 'completed'
         ORDER BY completed_at DESC LIMIT 20"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function mysql_cleanup(): array
{
    $stmt = db()->prepare(
        "UPDATE sos_signals SET status = 'cancelled'
         WHERE status = 'active' AND expires_at < NOW()"
    );
    $stmt->execute();
    $marked = $stmt->rowCount();
    $stmt = db()->prepare(
        "DELETE FROM sos_signals
         WHERE status IN ('cancelled', 'completed')
           AND expires_at < DATE_SUB(NOW(), INTERVAL 14 DAY)"
    );
    $stmt->execute();
    db()->exec('DELETE FROM csrf_tokens WHERE expires_at < NOW()');
    return ['marked' => $marked, 'removed' => $stmt->rowCount()];
}
