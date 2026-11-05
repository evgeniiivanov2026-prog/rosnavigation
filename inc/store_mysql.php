<?php
declare(strict_types=1);

function mysql_user(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mysql_user_by_phone(string $phone): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
    $stmt->execute([$phone]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mysql_insert_user(array $row): int
{
    $stmt = db()->prepare(
        'INSERT INTO users
        (phone, password_hash, name, city, role, truck_brand, truck_plate, truck_trailer, experience_y, motto, status, last_seen_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $row['phone'], $row['password_hash'], $row['name'], $row['city'], $row['role'],
        $row['truck_brand'], $row['truck_plate'], $row['truck_trailer'], $row['experience_y'],
        $row['motto'], $row['status'],
    ]);
    return (int) db()->lastInsertId();
}

function mysql_update_user(int $id, array $fields): void
{
    $allowed = [
        'name', 'city', 'role', 'truck_brand', 'truck_plate', 'truck_trailer', 'experience_y',
        'motto', 'status', 'avatar_path', 'car_path', 'lat', 'lng', 'route', 'last_seen_at',
        'helps_count', 'rating_sum', 'rating_count',
    ];
    $sets = [];
    $args = [];
    foreach ($allowed as $key) {
        if (!array_key_exists($key, $fields)) {
            continue;
        }
        $sets[] = $key . ' = ?';
        $args[] = $fields[$key];
    }
    if (!$sets) {
        return;
    }
    $args[] = $id;
    $stmt = db()->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $stmt->execute($args);
}

function mysql_touch(int $id): void
{
    $stmt = db()->prepare('UPDATE users SET last_seen_at = NOW() WHERE id = ?');
    $stmt->execute([$id]);
}

function mysql_ping(int $id, float $lat, float $lng): void
{
    $stmt = db()->prepare(
        "UPDATE users SET last_seen_at = NOW(), lat = ?, lng = ?
         WHERE id = ? AND status IN ('on_air','on_route','need_help')"
    );
    $stmt->execute([$lat, $lng, $id]);
}

function mysql_users(): array
{
    return db()->query('SELECT * FROM users WHERE is_banned = 0')->fetchAll();
}

function mysql_stats(): array
{
    $online = (int) db()->query(
        "SELECT COUNT(*) FROM users WHERE is_banned = 0 AND last_seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
    )->fetchColumn();
    $onAir = (int) db()->query(
        "SELECT COUNT(*) FROM users WHERE is_banned = 0 AND status = 'on_air'"
    )->fetchColumn();
    $helped = (int) db()->query('SELECT COALESCE(SUM(helps_count), 0) FROM users')->fetchColumn();
    return ['online' => $online, 'on_air' => $onAir, 'helped' => $helped];
}

function mysql_sos_rows(): array
{
    return db()->query('SELECT * FROM sos_signals ORDER BY created_at DESC LIMIT 300')->fetchAll();
}

function mysql_insert_sos(array $row): int
{
    $stmt = db()->prepare(
        'INSERT INTO sos_signals (user_id, type, lat, lng, route, km, comment, status, expires_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, \'active\', DATE_ADD(NOW(), INTERVAL 2 HOUR))'
    );
    $stmt->execute([
        $row['user_id'], $row['type'], $row['lat'], $row['lng'], $row['route'], $row['km'], $row['comment'],
    ]);
    return (int) db()->lastInsertId();
}

function mysql_update_sos(int $id, array $fields): void
{
    $allowed = ['status', 'helper_id', 'accepted_at', 'completed_at'];
    $sets = [];
    $args = [];
    foreach ($allowed as $key) {
        if (!array_key_exists($key, $fields)) {
            continue;
        }
        if ($fields[$key] === 'NOW') {
            $sets[] = $key . ' = NOW()';
            continue;
        }
        $sets[] = $key . ' = ?';
        $args[] = $fields[$key];
    }
    if (!$sets) {
        return;
    }
    $args[] = $id;
    $stmt = db()->prepare('UPDATE sos_signals SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $stmt->execute($args);
}

function mysql_partners(): array
{
    return db()->query('SELECT * FROM partners WHERE is_active = 1 ORDER BY rating_avg DESC, name LIMIT 200')->fetchAll();
}

function mysql_posts(): array
{
    return db()->query('SELECT * FROM channel_posts ORDER BY created_at DESC LIMIT 200')->fetchAll();
}

function mysql_likes(): array
{
    return db()->query('SELECT user_id, post_id FROM channel_likes')->fetchAll();
}

function mysql_reports(): array
{
    return db()->query('SELECT user_id, post_id FROM channel_reports')->fetchAll();
}

function mysql_insert_post(array $row): int
{
    $stmt = db()->prepare(
        'INSERT INTO channel_posts (user_id, body, photo_path, city, route, likes_count, status)
         VALUES (?, ?, ?, ?, ?, 0, ?)'
    );
    $stmt->execute([
        $row['user_id'], $row['body'], $row['photo_path'], $row['city'], $row['route'], $row['status'],
    ]);
    return (int) db()->lastInsertId();
}

function mysql_like(int $userId, int $postId): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT IGNORE INTO channel_likes (user_id, post_id) VALUES (?, ?)');
        $stmt->execute([$userId, $postId]);
        if ($stmt->rowCount() > 0) {
            $upd = $pdo->prepare('UPDATE channel_posts SET likes_count = likes_count + 1 WHERE id = ?');
            $upd->execute([$postId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function mysql_report(int $userId, int $postId): void
{
    $stmt = db()->prepare('INSERT IGNORE INTO channel_reports (user_id, post_id) VALUES (?, ?)');
    $stmt->execute([$userId, $postId]);
}

function mysql_expire_sos(): int
{
    $stmt = db()->prepare(
        "UPDATE sos_signals SET status = 'cancelled'
         WHERE status = 'active' AND expires_at < NOW()"
    );
    $stmt->execute();
    return $stmt->rowCount();
}
