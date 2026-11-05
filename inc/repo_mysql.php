<?php
declare(strict_types=1);

function mysql_stats(): array
{
    $online = (int) db()->query("SELECT COUNT(*) FROM users WHERE is_banned = 0 AND status IN ('on_air','on_route','need_help')")->fetchColumn();
    $helpers = (int) db()->query("SELECT COUNT(*) FROM users WHERE is_banned = 0 AND status IN ('on_air','on_route')")->fetchColumn();
    $helps = (int) db()->query('SELECT COALESCE(SUM(helps_count),0) FROM users WHERE is_banned = 0')->fetchColumn();
    $sos = (int) db()->query("SELECT COUNT(*) FROM sos_signals WHERE status = 'active' AND expires_at > NOW()")->fetchColumn();
    return ['online' => $online, 'sos' => $sos, 'helps' => $helps, 'helpers' => $helpers];
}

function mysql_sos_rows(?float $lat, ?float $lng, ?float $radius): array
{
    $sql = "SELECT s.*, u.name, u.city, u.truck_brand, u.phone, h.name AS helper_name
            FROM sos_signals s
            JOIN users u ON u.id = s.user_id
            LEFT JOIN users h ON h.id = s.helper_id
            WHERE s.status IN ('active','accepted') AND (s.status = 'accepted' OR s.expires_at > NOW())";
    $args = [];
    if ($lat !== null && $lng !== null && $radius !== null && coords_valid($lat, $lng)) {
        [$minLat, $maxLat, $minLng, $maxLng] = geo_bbox($lat, $lng, $radius);
        $sql .= ' AND s.lat BETWEEN ? AND ? AND s.lng BETWEEN ? AND ?';
        $args = [$minLat, $maxLat, $minLng, $maxLng];
    }
    $sql .= ' ORDER BY s.created_at DESC LIMIT 100';
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return geo_filter($stmt->fetchAll(), $lat, $lng, $radius);
}

function mysql_helpers(?float $lat, ?float $lng, ?float $radius): array
{
    $sql = "SELECT * FROM users WHERE is_banned = 0 AND status IN ('on_air','on_route') AND lat IS NOT NULL AND lng IS NOT NULL";
    $args = [];
    if ($lat !== null && $lng !== null && $radius !== null && coords_valid($lat, $lng)) {
        [$minLat, $maxLat, $minLng, $maxLng] = geo_bbox($lat, $lng, $radius);
        $sql .= ' AND lat BETWEEN ? AND ? AND lng BETWEEN ? AND ?';
        $args = [$minLat, $maxLat, $minLng, $maxLng];
    }
    $sql .= ' LIMIT 150';
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return geo_filter($stmt->fetchAll(), $lat, $lng, $radius);
}

function mysql_partners(array $filters): array
{
    $sql = 'SELECT * FROM partners WHERE is_active = 1';
    $args = [];
    if (!empty($filters['type'])) {
        $sql .= ' AND type = ?';
        $args[] = $filters['type'];
    }
    if (!empty($filters['region'])) {
        $sql .= ' AND region = ?';
        $args[] = $filters['region'];
    }
    if (!empty($filters['rating'])) {
        $sql .= ' AND rating_avg >= ?';
        $args[] = (float) $filters['rating'];
    }
    if (!empty($filters['day'])) {
        $sql .= ' AND works_24_7 = 1';
    }
    $sql .= ' ORDER BY rating_avg DESC, name ASC LIMIT 200';
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

function mysql_regions(): array
{
    $rows = db()->query('SELECT DISTINCT region FROM partners WHERE is_active = 1 AND region IS NOT NULL AND region <> \'\' ORDER BY region')->fetchAll();
    return array_map(static fn ($row) => (string) $row['region'], $rows);
}

function mysql_posts(?int $viewerId, bool $mine = false): array
{
    $sql = "SELECT p.*, u.name FROM channel_posts p JOIN users u ON u.id = p.user_id
            WHERE (p.status = 'published'";
    $args = [];
    if ($viewerId) {
        $sql .= ' OR p.user_id = ?';
        $args[] = $viewerId;
    }
    $sql .= ')';
    if ($mine && $viewerId) {
        $sql .= ' AND p.user_id = ?';
        $args[] = $viewerId;
    }
    $sql .= ' ORDER BY p.created_at DESC LIMIT 50';
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    $liked = [];
    if ($viewerId) {
        $likeStmt = db()->prepare('SELECT post_id FROM channel_likes WHERE user_id = ?');
        $likeStmt->execute([$viewerId]);
        foreach ($likeStmt->fetchAll() as $like) {
            $liked[(int) $like['post_id']] = true;
        }
    }
    $out = [];
    foreach ($stmt->fetchAll() as $post) {
        $out[] = present_post($post, !empty($liked[(int) $post['id']]));
    }
    return $out;
}

function mysql_user_by_phone(string $phone): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE phone = ? AND is_banned = 0');
    $stmt->execute([$phone]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mysql_user(int $id, bool $self): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND is_banned = 0');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? present_user($row, $self) : null;
}

function mysql_register(array $in): array
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO users (phone, password_hash, name, city, role, truck_brand, truck_plate, truck_trailer, experience_y, status, last_seen_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'on_air\', NOW())'
        );
        $stmt->execute([
            $in['phone'],
            hash_password($in['password']),
            $in['name'],
            $in['city'] ?: null,
            $in['role'],
            $in['truck_brand'] ?: null,
            $in['truck_plate'] ?: null,
            $in['truck_trailer'] ?: null,
            $in['experience_y'],
        ]);
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '23000') {
            throw new HttpError('phone_taken', 'Этот телефон уже в колонне. Войдите.', 409);
        }
        throw $e;
    }
    $id = (int) db()->lastInsertId();
    $user = mysql_user($id, true);
    if (!$user) {
        throw new RuntimeException('Пользователь не прочитался после записи');
    }
    return $user;
}

function mysql_touch(int $id, ?float $lat, ?float $lng): void
{
    if ($lat !== null && $lng !== null && coords_valid($lat, $lng)) {
        $stmt = db()->prepare('UPDATE users SET last_seen_at = NOW(), lat = ?, lng = ? WHERE id = ?');
        $stmt->execute([$lat, $lng, $id]);
        return;
    }
    $stmt = db()->prepare('UPDATE users SET last_seen_at = NOW() WHERE id = ?');
    $stmt->execute([$id]);
}

function mysql_update_profile(int $id, array $in): array
{
    $fields = [];
    $args = [];
    foreach (['name', 'city', 'role', 'truck_brand', 'truck_plate', 'truck_trailer', 'motto', 'status', 'route', 'avatar_path', 'car_path'] as $key) {
        if (array_key_exists($key, $in) && $in[$key] !== null && $in[$key] !== '') {
            $fields[] = $key . ' = ?';
            $args[] = $in[$key];
        }
    }
    if (array_key_exists('experience_y', $in)) {
        $fields[] = 'experience_y = ?';
        $args[] = $in['experience_y'];
    }
    if (array_key_exists('km', $in)) {
        $fields[] = 'km = ?';
        $args[] = $in['km'];
    }
    if ($fields) {
        $args[] = $id;
        $stmt = db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($args);
    }
    $user = mysql_user($id, true);
    if (!$user) {
        throw new HttpError('not_found', 'Водителя нет в колонне.', 404);
    }
    return $user;
}

function mysql_create_sos(int $userId, array $in): array
{
    $stmt = db()->prepare("SELECT id FROM sos_signals WHERE user_id = ? AND status = 'active' AND expires_at > NOW()");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        throw new HttpError('already_active', 'У вас уже есть сигнал в эфире. Сначала закройте его.', 409);
    }
    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'INSERT INTO sos_signals (user_id, type, lat, lng, route, km, comment, expires_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 2 HOUR))'
    );
    $stmt->execute([$userId, $in['type'], $in['lat'], $in['lng'], $in['route'], $in['km'], $in['comment']]);
    $id = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare('UPDATE users SET status = \'need_help\', lat = ?, lng = ?, route = ?, km = ?, last_seen_at = NOW() WHERE id = ?');
    $stmt->execute([$in['lat'], $in['lng'], $in['route'], $in['km'], $userId]);
    $pdo->commit();
    $row = mysql_sos_raw($id);
    return present_sos($row ?? [], null, null, true);
}

function mysql_sos_raw(int $id): ?array
{
    $stmt = db()->prepare(
        "SELECT s.*, u.name, u.city, u.truck_brand, u.phone, h.name AS helper_name
         FROM sos_signals s
         JOIN users u ON u.id = s.user_id
         LEFT JOIN users h ON h.id = s.helper_id
         WHERE s.id = ?"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mysql_my_active_sos(int $userId): ?array
{
    $stmt = db()->prepare(
        "SELECT s.*, u.name, u.city, u.truck_brand, u.phone, h.name AS helper_name
         FROM sos_signals s
         JOIN users u ON u.id = s.user_id
         LEFT JOIN users h ON h.id = s.helper_id
         WHERE s.user_id = ? AND s.status IN ('active','accepted')
         ORDER BY s.id DESC LIMIT 1"
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? present_sos($row, null, null, true) : null;
}

function mysql_messages(int $sosId): array
{
    $stmt = db()->prepare(
        'SELECT m.*, u.name FROM sos_messages m JOIN users u ON u.id = m.user_id WHERE m.sos_id = ? ORDER BY m.id'
    );
    $stmt->execute([$sosId]);
    return array_map('present_message', $stmt->fetchAll());
}

function mysql_cancel_sos(int $userId, int $sosId): void
{
    $stmt = db()->prepare("UPDATE sos_signals SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('active','accepted')");
    $stmt->execute([$sosId, $userId]);
    if ($stmt->rowCount() === 0) {
        throw new HttpError('not_found', 'Сигнал не найден или уже закрыт.', 404);
    }
    $stmt = db()->prepare("UPDATE users SET status = 'on_air' WHERE id = ?");
    $stmt->execute([$userId]);
}

function mysql_accept_sos(int $userId, int $sosId): array
{
    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT * FROM sos_signals WHERE id = ? FOR UPDATE");
    $stmt->execute([$sosId]);
    $sos = $stmt->fetch();
    if (!$sos) {
        $pdo->rollBack();
        throw new HttpError('not_found', 'Сигнал не найден.', 404);
    }
    if ($sos['status'] !== 'active') {
        $pdo->rollBack();
        throw new HttpError('bad_status', 'Сигнал уже приняли или закрыли.', 409);
    }
    if ((int) $sos['user_id'] === $userId) {
        $pdo->rollBack();
        throw new HttpError('self', 'Свой сигнал принимают другие.', 409);
    }
    $stmt = $pdo->prepare("UPDATE sos_signals SET status = 'accepted', helper_id = ?, accepted_at = NOW() WHERE id = ?");
    $stmt->execute([$userId, $sosId]);
    $name = mysql_user($userId, false)['name'] ?? 'Свой';
    $stmt = $pdo->prepare('INSERT INTO sos_messages (sos_id, user_id, body) VALUES (?, ?, ?)');
    $stmt->execute([$sosId, $userId, $name . ' на приёме и берёт этот сигнал.']);
    $pdo->commit();
    return present_sos(mysql_sos_raw($sosId) ?? []);
}

function mysql_complete_sos(int $userId, int $sosId, int $rating): array
{
    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT * FROM sos_signals WHERE id = ? FOR UPDATE');
    $stmt->execute([$sosId]);
    $sos = $stmt->fetch();
    if (!$sos) {
        $pdo->rollBack();
        throw new HttpError('not_found', 'Сигнал не найден.', 404);
    }
    $party = (int) $sos['user_id'] === $userId || (int) ($sos['helper_id'] ?? 0) === $userId;
    if (!$party) {
        $pdo->rollBack();
        throw new HttpError('forbidden', 'Закрыть сигнал может тот, кто звал, или тот, кто поехал.', 403);
    }
    if (!in_array($sos['status'], ['active', 'accepted'], true)) {
        $pdo->rollBack();
        throw new HttpError('bad_status', 'Сигнал уже закрыт.', 409);
    }
    $stmt = $pdo->prepare("UPDATE sos_signals SET status = 'completed', completed_at = NOW() WHERE id = ?");
    $stmt->execute([$sosId]);
    $stmt = $pdo->prepare("UPDATE users SET status = 'on_air' WHERE id = ?");
    $stmt->execute([(int) $sos['user_id']]);
    if (!empty($sos['helper_id'])) {
        if ($rating >= 1 && $rating <= 5 && $userId === (int) $sos['user_id']) {
            $stmt = $pdo->prepare('UPDATE users SET helps_count = helps_count + 1, rating_sum = rating_sum + ?, rating_count = rating_count + 1 WHERE id = ?');
            $stmt->execute([$rating, (int) $sos['helper_id']]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET helps_count = helps_count + 1 WHERE id = ?');
            $stmt->execute([(int) $sos['helper_id']]);
        }
    }
    $pdo->commit();
    return present_sos(mysql_sos_raw($sosId) ?? [], null, null, true);
}

function mysql_add_message(int $userId, int $sosId, string $body): array
{
    $sos = mysql_sos_raw($sosId);
    if (!$sos) {
        throw new HttpError('not_found', 'Сигнал не найден.', 404);
    }
    $party = (int) $sos['user_id'] === $userId || (int) ($sos['helper_id'] ?? 0) === $userId;
    if (!$party) {
        throw new HttpError('forbidden', 'Переписка открыта тем, кого касается сигнал.', 403);
    }
    $stmt = db()->prepare('INSERT INTO sos_messages (sos_id, user_id, body) VALUES (?, ?, ?)');
    $stmt->execute([$sosId, $userId, $body]);
    $id = (int) db()->lastInsertId();
    $stmt = db()->prepare('SELECT m.*, u.name FROM sos_messages m JOIN users u ON u.id = m.user_id WHERE m.id = ?');
    $stmt->execute([$id]);
    return present_message($stmt->fetch() ?: ['id' => $id, 'sos_id' => $sosId, 'user_id' => $userId, 'body' => $body, 'name' => '']);
}

function mysql_create_post(int $userId, array $in): array
{
    $status = $in['photo_path'] ? 'pending' : 'published';
    $stmt = db()->prepare(
        'INSERT INTO channel_posts (user_id, body, photo_path, city, route, status) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $in['body'], $in['photo_path'], $in['city'] ?: null, $in['route'] ?: null, $status]);
    $id = (int) db()->lastInsertId();
    $stmt = db()->prepare('SELECT p.*, u.name FROM channel_posts p JOIN users u ON u.id = p.user_id WHERE p.id = ?');
    $stmt->execute([$id]);
    return present_post($stmt->fetch() ?: [], false);
}

function mysql_like(int $userId, int $postId): array
{
    $stmt = db()->prepare("SELECT id FROM channel_posts WHERE id = ? AND status = 'published'");
    $stmt->execute([$postId]);
    if (!$stmt->fetch()) {
        throw new HttpError('not_found', 'Записи нет в эфире.', 404);
    }
    $stmt = db()->prepare('SELECT 1 FROM channel_likes WHERE user_id = ? AND post_id = ?');
    $stmt->execute([$userId, $postId]);
    if ($stmt->fetch()) {
        $stmt = db()->prepare('DELETE FROM channel_likes WHERE user_id = ? AND post_id = ?');
        $stmt->execute([$userId, $postId]);
        $liked = false;
    } else {
        $stmt = db()->prepare('INSERT INTO channel_likes (user_id, post_id) VALUES (?, ?)');
        $stmt->execute([$userId, $postId]);
        $liked = true;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM channel_likes WHERE post_id = ?');
    $stmt->execute([$postId]);
    $count = (int) $stmt->fetchColumn();
    $stmt = db()->prepare('UPDATE channel_posts SET likes_count = ? WHERE id = ?');
    $stmt->execute([$count, $postId]);
    return ['likes' => $count, 'liked' => $liked];
}

function mysql_complain(int $userId, int $postId, string $reason): void
{
    $stmt = db()->prepare('SELECT id FROM complaints WHERE user_id = ? AND post_id = ?');
    $stmt->execute([$userId, $postId]);
    if ($stmt->fetch()) {
        throw new HttpError('duplicate', 'Жалоба уже ушла модератору.', 409);
    }
    $stmt = db()->prepare('SELECT id FROM channel_posts WHERE id = ?');
    $stmt->execute([$postId]);
    if (!$stmt->fetch()) {
        throw new HttpError('not_found', 'Записи нет.', 404);
    }
    $stmt = db()->prepare('INSERT INTO complaints (user_id, post_id, reason) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $postId, $reason]);
}

function mysql_user_helps(int $userId): array
{
    $stmt = db()->prepare(
        "SELECT s.*, u.name, u.city, u.truck_brand, u.phone, h.name AS helper_name
         FROM sos_signals s
         JOIN users u ON u.id = s.user_id
         LEFT JOIN users h ON h.id = s.helper_id
         WHERE s.helper_id = ? AND s.status = 'completed'
         ORDER BY s.completed_at DESC LIMIT 20"
    );
    $stmt->execute([$userId]);
    return array_map(static fn ($row) => present_sos($row), $stmt->fetchAll());
}
