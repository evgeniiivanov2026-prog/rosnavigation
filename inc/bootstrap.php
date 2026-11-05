<?php
declare(strict_types=1);

$configCandidates = [
    dirname(__DIR__, 2) . '/.env.php',
    __DIR__ . '/config.local.php',
    __DIR__ . '/config.php',
];
$configLoaded = false;
foreach ($configCandidates as $configPath) {
    if (is_file($configPath)) {
        require $configPath;
        $configLoaded = true;
        break;
    }
}
if (!$configLoaded) {
    http_response_code(500);
    echo 'Нет файла конфигурации.';
    exit;
}

require __DIR__ . '/helpers.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/geo.php';
require __DIR__ . '/cache.php';
require __DIR__ . '/db.php';
require __DIR__ . '/catalog.php';
require __DIR__ . '/icons.php';
require __DIR__ . '/present.php';
require __DIR__ . '/repo_demo.php';
require __DIR__ . '/repo_mysql.php';
require __DIR__ . '/repo.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/view.php';

if (!app_demo() && DB_NAME === '') {
    http_response_code(500);
    echo 'Боевой режим без базы. Заполните конфиг.';
    exit;
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('rosnav');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Europe/Moscow');
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$logDir = dirname(__DIR__) . '/storage/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php.log');

$GLOBALS['csp_nonce'] = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
$nonce = $GLOBALS['csp_nonce'];
$csp = "default-src 'self'; "
    . "img-src 'self' data: blob: https://tile.openstreetmap.org https://*.tile.openstreetmap.org; "
    . "style-src 'self' 'unsafe-inline'; "
    . "script-src 'self' 'nonce-{$nonce}'; "
    . "connect-src 'self'; "
    . "font-src 'self'; "
    . "worker-src 'self'; "
    . "manifest-src 'self'; "
    . "media-src 'self'; "
    . "object-src 'none'; "
    . "base-uri 'self'; "
    . "form-action 'self'; "
    . "frame-ancestors 'none'";

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(self), microphone=()');
header('Content-Security-Policy: ' . $csp);
if ($https) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

set_exception_handler(static function (Throwable $e): void {
    app_log($e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (is_api_request()) {
        $message = APP_ENV === 'production' ? 'Связь сбилась. Повторите через минуту.' : $e->getMessage();
        json_response(['error' => 'server', 'message' => $message], 500);
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><html lang="ru"><meta charset="utf-8"><title>Связь сбилась</title>'
        . '<body style="margin:0;background:#0A0E13;color:#F2F5F8;font-family:sans-serif">'
        . '<main style="max-width:36rem;margin:15vh auto;padding:24px"><h1>Связь сбилась</h1>'
        . '<p>Обновите страницу через минуту. Если человеку плохо — звоните 112, не ждите сайт.</p>'
        . '<p><a style="color:#FFC933" href="/">На главную</a></p></main></body></html>';
    exit;
});
