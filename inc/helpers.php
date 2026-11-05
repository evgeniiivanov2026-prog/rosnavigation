<?php
declare(strict_types=1);

final class HttpError extends RuntimeException
{
    public function __construct(
        public string $error,
        string $message,
        public int $status = 422
    ) {
        parent::__construct($message);
    }
}

function log_line(string $channel, string $message): void
{
    $dir = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $line = date('c') . ' [' . $channel . '] ' . str_replace(["\n", "\r"], ' ', $message) . "\n";
    @file_put_contents($dir . '/app.log', $line, FILE_APPEND);
}

function wants_json(): bool
{
    if (defined('ROSNAV_API') && ROSNAV_API) {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($accept, 'application/json')) {
        return true;
    }
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        throw new HttpError('bad_json', 'Не разобрали запрос', 400);
    }
    if (!is_array($data)) {
        throw new HttpError('bad_json', 'Не разобрали запрос', 400);
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

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function safe_next(?string $next, string $fallback = '/profile.php'): string
{
    $next = trim((string) $next);
    if ($next === '' || $next[0] !== '/' || str_starts_with($next, '//') || str_contains($next, '://') || str_contains($next, "\n")) {
        return $fallback;
    }
    return $next;
}

function flash(string $type, string $text): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

function flash_take(): ?array
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = ($flash['type'] ?? '') === 'ok' ? 'ok' : 'error';
    return ['type' => $type, 'text' => (string) ($flash['text'] ?? '')];
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 64);
}

function rate_limit(string $bucket, int $limit, int $windowSec): void
{
    $dir = dirname(__DIR__) . '/storage/ratelimit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/' . hash('sha256', $bucket . '|' . client_ip()) . '.json';
    $now = time();
    $hits = [];
    if (is_file($file)) {
        $decoded = json_decode((string) file_get_contents($file), true);
        if (is_array($decoded)) {
            $hits = array_values(array_filter($decoded, static fn ($t) => is_int($t) && $t > $now - $windowSec));
        }
    }
    if (count($hits) >= $limit) {
        throw new HttpError('rate_limit', 'Слишком часто. Подождите минуту и попробуйте снова.', 429);
    }
    $hits[] = $now;
    @file_put_contents($file, json_encode($hits));
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

function clean_text(?string $value, int $max): string
{
    $value = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    if (mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    }
    return $value;
}

function plural(int $n, string $one, string $few, string $many): string
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

function ago(?string $dt): string
{
    if (!$dt) {
        return '';
    }
    $t = strtotime($dt);
    if ($t === false) {
        return '';
    }
    $diff = time() - $t;
    if ($diff < 60) {
        return 'только что';
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);
        return $m . ' ' . plural($m, 'минуту', 'минуты', 'минут') . ' назад';
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return $h . ' ' . plural($h, 'час', 'часа', 'часов') . ' назад';
    }
    return date('j.m', $t);
}

function km_label(float $d): string
{
    $digits = $d < 10 ? 1 : 0;
    return number_format($d, $digits, ',', ' ') . ' км';
}

function rub_label(?int $n): string
{
    if (!$n) {
        return '';
    }
    return 'от ' . number_format($n, 0, '', ' ') . " \u{20BD}";
}

function asset(string $path): string
{
    $full = dirname(__DIR__) . $path;
    $v = is_file($full) ? (string) filemtime($full) : '1';
    return $path . '?v=' . $v;
}

function honeypot_tripped(array $in): bool
{
    return trim((string) ($in['website'] ?? '')) !== '';
}

function handle(callable $fn, string $ok, string $fail): void
{
    try {
        $result = $fn();
        if (wants_json()) {
            json_response(['ok' => true, 'result' => $result]);
        }
        redirect($ok);
    } catch (HttpError $e) {
        if (wants_json()) {
            json_response(['error' => $e->error, 'message' => $e->getMessage()], $e->status);
        }
        flash('error', $e->getMessage());
        redirect($fail);
    }
}

function assert_same_origin(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === '') {
        return;
    }
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $originHost = parse_url($origin, PHP_URL_HOST);
    $originPort = parse_url($origin, PHP_URL_PORT);
    if (!$originHost) {
        throw new HttpError('origin', 'Чужой источник запроса', 403);
    }
    $composed = $originHost . ($originPort ? ':' . $originPort : '');
    $hostOnly = preg_replace('/:\d+$/', '', $host) ?? $host;
    if (strcasecmp($composed, $host) !== 0 && strcasecmp($originHost, $hostOnly) !== 0) {
        throw new HttpError('origin', 'Чужой источник запроса', 403);
    }
}
