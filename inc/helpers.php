<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function nl2br_e(?string $value): string
{
    return nl2br(e($value ?? ''), false);
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $code, string $message, int $status): never
{
    json_response(['error' => $code, 'message' => $message], $status);
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return $_POST;
    }
    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        fail('bad_json', 'Не разобрал запрос.', 400);
    }
    return is_array($data) ? $data : [];
}

function request_data(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        return json_input();
    }
    return $_POST;
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return preg_match('/^[0-9a-fA-F:.]+$/', $ip) ? $ip : '0.0.0.0';
}

function normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) === 11 && ($digits[0] === '8' || $digits[0] === '7')) {
        $digits = '7' . substr($digits, 1);
    }
    if (strlen($digits) === 10) {
        $digits = '7' . $digits;
    }
    return '+' . $digits;
}

function valid_phone(string $phone): bool
{
    return (bool) preg_match('/^\+7\d{10}$/', $phone);
}

function hash_password(string $password): string
{
    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 32768,
            'time_cost' => 2,
            'threads' => 1,
        ]);
    }
    return password_hash($password, PASSWORD_DEFAULT);
}

function clip(?string $value, int $max): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    if (mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    }
    return $value;
}

function safe_next(?string $next, string $fallback = '/profile'): string
{
    $next = trim((string) $next);
    if ($next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//') || str_contains($next, "\n")) {
        return $fallback;
    }
    $path = parse_url($next, PHP_URL_PATH) ?? '';
    if (!str_starts_with($path, '/')) {
        return $fallback;
    }
    return $next;
}

function redirect(string $to): never
{
    header('Location: ' . safe_next($to, '/'), true, 303);
    exit;
}

function flash_set(string $type, string $text): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

function flash_get(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function sos_catalog(): array
{
    return [
        'breakdown' => ['Встал', 'Колесо, двигатель, электрика', 'wrench'],
        'fuel' => ['Обсох', 'Топливо кончилось', 'fuel'],
        'sleep' => ['Притомился', 'Нужен отдых или смена', 'bed'],
        'accident' => ['ДТП', 'Нужны люди рядом', 'alert'],
        'medical' => ['Медицина', 'Плохо человеку', 'pulse'],
        'other' => ['Другое', 'Своими словами', 'help'],
    ];
}

function partner_types(): array
{
    return [
        'sto' => 'Сервис',
        'fuel' => 'АЗС',
        'hotel' => 'Ночлег',
        'insurance' => 'Страховая',
        'lawyer' => 'Юрист',
        'shop' => 'Магазин',
        'other' => 'Другое',
    ];
}

function status_meta(string $status): array
{
    return match ($status) {
        'on_air' => ['На приёме', 'success'],
        'on_route' => ['На маршруте', 'accent'],
        'need_help' => ['Нужна помощь', 'danger'],
        'resting' => ['Отдыхаю', 'muted'],
        default => ['Молчу', 'muted'],
    };
}

function role_label(string $role): string
{
    return match ($role) {
        'seeker' => 'Ищу помощь',
        'helper' => 'Помогаю',
        default => 'И то, и то',
    };
}

function user_badges(array $user): array
{
    $badges = [['id' => 'channel', 'name' => '15-й канал']];
    $years = (int) ($user['experience_y'] ?? 0);
    $helps = (int) ($user['helps_count'] ?? 0);
    if ($years >= 10) {
        $badges[] = ['id' => 'russia', 'name' => 'Дальнобой России'];
    }
    if ($helps >= 20) {
        $badges[] = ['id' => 'mentor', 'name' => 'Наставник'];
    }
    if ($years >= 15 || $helps >= 40) {
        $badges[] = ['id' => 'veteran', 'name' => 'Ветеран эфира'];
    }
    return $badges;
}

function public_user(array $user, bool $self = false): array
{
    $count = (int) ($user['rating_count'] ?? 0);
    $status = (string) ($user['status'] ?? 'offline');
    $out = [
        'id' => (int) $user['id'],
        'name' => (string) ($user['name'] ?? ''),
        'city' => (string) ($user['city'] ?? ''),
        'role' => (string) ($user['role'] ?? 'both'),
        'truck_brand' => (string) ($user['truck_brand'] ?? ''),
        'truck_trailer' => (string) ($user['truck_trailer'] ?? ''),
        'experience_y' => isset($user['experience_y']) && $user['experience_y'] !== null ? (int) $user['experience_y'] : null,
        'avatar_path' => ($user['avatar_path'] ?? '') !== '' ? $user['avatar_path'] : null,
        'car_path' => ($user['car_path'] ?? '') !== '' ? $user['car_path'] : null,
        'motto' => (string) ($user['motto'] ?? ''),
        'status' => $status,
        'helps_count' => (int) ($user['helps_count'] ?? 0),
        'rating' => $count > 0 ? round(((int) $user['rating_sum']) / $count, 1) : null,
        'rating_count' => $count,
        'route' => (string) ($user['route'] ?? ''),
        'badges' => user_badges($user),
        'last_seen_at' => $user['last_seen_at'] ?? null,
    ];
    if ($self) {
        $out['phone'] = (string) ($user['phone'] ?? '');
        $out['truck_plate'] = (string) ($user['truck_plate'] ?? '');
    }
    if (in_array($status, ['on_air', 'on_route', 'need_help'], true)) {
        $out['lat'] = isset($user['lat']) && $user['lat'] !== null && $user['lat'] !== '' ? (float) $user['lat'] : null;
        $out['lng'] = isset($user['lng']) && $user['lng'] !== null && $user['lng'] !== '' ? (float) $user['lng'] : null;
    }
    return $out;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice(array_filter($parts), 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $letters !== '' ? $letters : 'РН';
}

function ru_when(?string $dt): string
{
    if (!$dt) {
        return '';
    }
    $ts = strtotime($dt);
    if ($ts === false) {
        return '';
    }
    $months = [1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function ru_ago(?string $dt): string
{
    if (!$dt) {
        return '';
    }
    $ts = strtotime($dt);
    if ($ts === false) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'только что';
    }
    if ($diff < 3600) {
        return (int) floor($diff / 60) . ' мин назад';
    }
    if ($diff < 86400) {
        return (int) floor($diff / 3600) . ' ч назад';
    }
    if ($diff < 172800) {
        return 'вчера';
    }
    return ru_when($dt);
}

function money_label(?int $value): string
{
    if ($value === null) {
        return '';
    }
    return number_format($value, 0, ',', ' ') . ' ₽';
}

function km_label(?float $km): string
{
    if ($km === null) {
        return '';
    }
    if ($km < 1) {
        return (string) max(50, (int) round($km * 1000)) . ' м';
    }
    $digits = $km < 10 ? 1 : 0;
    return number_format($km, $digits, ',', ' ') . ' км';
}

function yandex_point(?float $lat, ?float $lng): ?string
{
    if ($lat === null || $lng === null) {
        return null;
    }
    return 'https://yandex.ru/maps/?rtext=~' . rawurlencode($lat . ',' . $lng) . '&rtt=auto';
}

function valid_coords(float $lat, float $lng): bool
{
    return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180 && !($lat === 0.0 && $lng === 0.0);
}

function handle_upload(array $file, string $subdir, int $maxBytes = 2_097_152): string
{
    if (!in_array($subdir, ['avatars', 'cars', 'channel'], true)) {
        throw new RuntimeException('Неизвестная папка загрузки');
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Файл не пришёл');
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Ошибка загрузки');
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('Файл больше 2 МБ');
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    $mime = mime_content_type($tmp) ?: '';
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Нужна фотография JPG, PNG или WebP');
    }

    $dir = APP_ROOT . '/uploads/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Не удалось подготовить папку');
    }

    $name = bin2hex(random_bytes(16)) . '.webp';
    $target = $dir . '/' . $name;
    if (!save_as_webp($tmp, $mime, $target)) {
        $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $target = $dir . '/' . $name;
        if (!move_uploaded_file($tmp, $target)) {
            throw new RuntimeException('Не удалось сохранить файл');
        }
    }
    return '/uploads/' . $subdir . '/' . $name;
}

function save_as_webp(string $tmp, string $mime, string $target): bool
{
    if (!function_exists('imagewebp') || !function_exists('imagecreatefromstring')) {
        return false;
    }
    $blob = file_get_contents($tmp);
    if ($blob === false) {
        return false;
    }
    $src = @imagecreatefromstring($blob);
    if (!$src) {
        return false;
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $max = 1600;
    $scale = min(1, $max / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $clear = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $nw, $nh, $clear);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = imagewebp($dst, $target, 82);
    imagedestroy($src);
    imagedestroy($dst);
    return $ok && is_file($target);
}
