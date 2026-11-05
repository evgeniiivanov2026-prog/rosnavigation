<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/geo.php';
require __DIR__ . '/cache.php';
require __DIR__ . '/validate.php';
require __DIR__ . '/upload.php';
require __DIR__ . '/seed.php';
require __DIR__ . '/repo_file.php';
require __DIR__ . '/repo_mysql.php';
require __DIR__ . '/repo.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/view.php';

date_default_timezone_set('Europe/Moscow');

$logDir = dirname(__DIR__) . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/php.log');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(self), microphone=()');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "img-src 'self' data: blob: https://server.arcgisonline.com; "
        . "style-src 'self' 'unsafe-inline' https://unpkg.com; "
        . "script-src 'self' https://unpkg.com; "
        . "connect-src 'self' https://unpkg.com; "
        . "font-src 'self'; "
        . "worker-src 'self'; "
        . "frame-ancestors 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'"
    );
    session_name('ROSNAV');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

set_exception_handler(static function (Throwable $e): void {
    log_line('error', $e::class . ' ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if ($e instanceof HttpError) {
        $status = $e->status;
        $message = $e->getMessage();
        $code = $e->error;
    } else {
        $status = 500;
        $code = 'server';
        $message = APP_ENV === 'local'
            ? $e->getMessage()
            : 'Что-то сломалось на нашей стороне. Запись уже в журнале.';
    }
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
    if (!headers_sent() && (wants_json() || (defined('ROSNAV_API') && ROSNAV_API))) {
        json_response(['error' => $code, 'message' => $message], $status);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        flash('error', $message);
    }
    if (!headers_sent()) {
        redirect('/');
    }
    echo e($message);
    exit;
});

if (PHP_SAPI !== 'cli' && !empty($_SESSION['user_id'])) {
    $seen = (int) ($_SESSION['touched_at'] ?? 0);
    if (time() - $seen > 60) {
        try {
            repo_touch((int) $_SESSION['user_id'], null, null);
            $_SESSION['touched_at'] = time();
        } catch (Throwable $e) {
            log_line('touch', $e->getMessage());
        }
    }
}
