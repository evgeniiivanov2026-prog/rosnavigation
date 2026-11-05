<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$configPath = dirname(APP_ROOT) . '/.env.php';
if (!is_file($configPath)) {
    $configPath = __DIR__ . '/config.php';
}
if (is_file($configPath)) {
    require $configPath;
}

if (!defined('APP_ENV')) {
    define('APP_ENV', 'local');
}
if (!defined('APP_URL')) {
    define('APP_URL', 'https://rosnavigation.ru');
}

require __DIR__ . '/helpers.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/cache.php';
require __DIR__ . '/geo.php';
require __DIR__ . '/rate.php';

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() !== PHP_SESSION_ACTIVE) {
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

$logDir = APP_ROOT . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/php-error.log');
ini_set('display_errors', APP_ENV === 'production' ? '0' : '1');

$dbDown = false;
$demo = true;
if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && extension_loaded('pdo_mysql')) {
    require __DIR__ . '/db.php';
    try {
        db()->query('SELECT 1');
        $demo = false;
    } catch (Throwable $e) {
        error_log('DB unavailable: ' . $e->getMessage());
        if (APP_ENV === 'production') {
            $demo = false;
            $dbDown = true;
        }
    }
} elseif (APP_ENV === 'production' && defined('DB_HOST')) {
    $dbDown = true;
    $demo = false;
}

define('DB_DOWN', $dbDown);
define('DEMO_MODE', $demo && !$dbDown);

require __DIR__ . '/auth.php';
require __DIR__ . '/repo.php';

if (PHP_SAPI !== 'cli' && !DB_DOWN && !empty($_SESSION['user_id'])) {
    try {
        repo_touch((int) $_SESSION['user_id']);
    } catch (Throwable $e) {
        error_log('touch failed: ' . $e->getMessage());
    }
}

function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }
    return $nonce;
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "img-src 'self' data: blob: https://tile.openstreetmap.org https://*.tile.openstreetmap.org; "
        . "style-src 'self' 'unsafe-inline'; "
        . "script-src 'self' 'nonce-" . csp_nonce() . "'; "
        . "connect-src 'self'; "
        . "font-src 'self'; "
        . "frame-ancestors 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'"
    );
}

$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (PHP_SAPI !== 'cli' && DB_DOWN && !str_contains($script, '/api/')) {
    http_response_code(503);
    require APP_ROOT . '/templates/maintenance.php';
    exit;
}
