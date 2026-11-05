<?php
declare(strict_types=1);

final class HttpError extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 422
    ) {
        parent::__construct($message);
    }
}

function app_demo(): bool
{
    return APP_ENV !== 'production';
}

function is_api_request(): bool
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    return str_contains($script, '/api/');
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) {
        throw new JsonException('Ожидался объект JSON');
    }
    return $data;
}

function request_data(): array
{
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($type, 'application/json')) {
        return json_input();
    }
    return $_POST;
}

function api_dispatch(callable $fn): void
{
    try {
        $fn();
    } catch (HttpError $e) {
        json_response(['error' => $e->errorCode, 'message' => $e->getMessage()], $e->status);
    } catch (InvalidArgumentException $e) {
        json_response(['error' => 'invalid', 'message' => $e->getMessage()], 422);
    } catch (JsonException $e) {
        json_response(['error' => 'bad_json', 'message' => 'Не разобрали запрос.'], 400);
    } catch (Throwable $e) {
        app_log($e::class . ': ' . $e->getMessage());
        $message = APP_ENV === 'production' ? 'Связь сбилась. Повторите через минуту.' : $e->getMessage();
        json_response(['error' => 'server', 'message' => $message], 500);
    }
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) === 11 && $digits[0] === '8') {
        $digits = '7' . substr($digits, 1);
    }
    if (strlen($digits) === 10) {
        $digits = '7' . $digits;
    }
    return '+' . $digits;
}

function mask_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) < 6) {
        return '';
    }
    return '+7 ' . substr($digits, 1, 3) . ' ••• •• ' . substr($digits, -2);
}

function hash_password(string $password): string
{
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    return password_hash($password, $algo);
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return substr($ip, 0, 64);
}

function rate_limited(string $bucket, int $max, int $windowSec): bool
{
    $dir = dirname(__DIR__) . '/storage/cache';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }
    $file = $dir . '/rl-' . hash('sha256', $bucket . '|' . client_ip()) . '.json';
    $now = time();
    $hits = [];
    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $hits = $decoded;
        }
    }
    $hits = array_values(array_filter($hits, static fn ($t) => (int) $t > $now - $windowSec));
    if (count($hits) >= $max) {
        return true;
    }
    $hits[] = $now;
    file_put_contents($file, json_encode($hits), LOCK_EX);
    return false;
}

function app_log(string $message): void
{
    $dir = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $line = date('c') . ' ' . str_replace(["\n", "\r"], ' ', $message) . "\n";
    file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

function ru_plural(int $n, string $one, string $few, string $many): string
{
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) {
        return $many;
    }
    if ($n1 > 1 && $n1 < 5) {
        return $few;
    }
    if ($n1 === 1) {
        return $one;
    }
    return $many;
}

function ru_num(int $n): string
{
    return number_format($n, 0, ',', ' ');
}

function ru_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 45) {
        return 'только что';
    }
    if ($diff < 3600) {
        $m = max(1, intdiv($diff, 60));
        return $m . ' ' . ru_plural($m, 'минуту', 'минуты', 'минут') . ' назад';
    }
    if ($diff < 86400) {
        $h = intdiv($diff, 3600);
        return $h . ' ' . ru_plural($h, 'час', 'часа', 'часов') . ' назад';
    }
    $d = intdiv($diff, 86400);
    if ($d < 14) {
        return $d . ' ' . ru_plural($d, 'день', 'дня', 'дней') . ' назад';
    }
    return date('d.m.Y', $ts);
}

function format_km(?float $km): string
{
    if ($km === null) {
        return '';
    }
    if ($km < 10) {
        return number_format($km, 1, ',', ' ') . ' км';
    }
    return number_format($km, 0, ',', ' ') . ' км';
}

function initials(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '•';
    }
    return mb_strtoupper(mb_substr($name, 0, 1));
}

function clip(?string $value, int $max): string
{
    $value = trim((string) $value);
    if (mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    }
    return $value;
}

function safe_next(?string $next, string $fallback = '/profile'): string
{
    $next = trim((string) $next);
    if ($next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//')) {
        return $fallback;
    }
    if (preg_match('/[\r\n\\\\]/', $next)) {
        return $fallback;
    }
    if (preg_match('#^/(api|inc|storage|uploads|templates|cron)(/|$)#', $next)) {
        return $fallback;
    }
    return $next;
}

function route_url(float $lat, float $lng): string
{
    return 'https://yandex.ru/maps/?rtext=~' . rawurlencode($lat . ',' . $lng) . '&rtt=auto';
}

function tel_href(string $phone): string
{
    $clean = preg_replace('/[^\d+]/', '', $phone) ?? '';
    return 'tel:' . $clean;
}

function honeypot_tripped(array $data): bool
{
    return trim((string) ($data['fax_number'] ?? '')) !== '';
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csp_nonce(): string
{
    return $GLOBALS['csp_nonce'] ?? '';
}

/**
 * Безопасная загрузка фото. Пережимает в WebP, если GD умеет.
 * @return string путь от корня сайта
 */
function handle_upload(array $file, string $subdir, int $maxBytes = 2_097_152): string
{
    $allowedDirs = ['avatars', 'cars', 'channel'];
    if (!in_array($subdir, $allowedDirs, true)) {
        throw new HttpError('upload', 'Неизвестная папка загрузки.', 400);
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new HttpError('upload', 'Файл не пришёл.', 400);
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new HttpError('upload', 'Ошибка загрузки.', 400);
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new HttpError('upload', 'Файл больше 2 МБ.', 413);
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    $mime = mime_content_type($tmp);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        throw new HttpError('upload', 'Нужна фотография JPG, PNG или WebP.', 415);
    }

    $dir = dirname(__DIR__) . '/uploads/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new HttpError('upload', 'Не удалось подготовить папку.', 500);
    }

    $image = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($tmp),
        'image/png' => @imagecreatefrompng($tmp),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
        default => false,
    };

    if ($image instanceof GdImage && function_exists('imagewebp')) {
        $w = imagesx($image);
        $h = imagesy($image);
        $max = 1600;
        $scale = max($w, $h) > $max ? $max / max($w, $h) : 1;
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $canvas = imagecreatetruecolor($nw, $nh);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $name = bin2hex(random_bytes(16)) . '.webp';
        $dest = $dir . '/' . $name;
        if (!imagewebp($canvas, $dest, 82)) {
            throw new HttpError('upload', 'Не удалось сохранить фото.', 500);
        }
        imagedestroy($image);
        imagedestroy($canvas);
        return '/uploads/' . $subdir . '/' . $name;
    }

    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        throw new HttpError('upload', 'Не удалось сохранить файл.', 500);
    }
    return '/uploads/' . $subdir . '/' . $name;
}

function json_flags_html(): int
{
    return JSON_UNESCAPED_UNICODE
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT;
}
