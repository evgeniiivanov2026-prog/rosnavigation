<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$uri = rtrim($uri, '/') ?: '/';

if (preg_match('#^/(inc|storage|templates|cron)(/|$)#', $uri)) {
    http_response_code(403);
    echo '403';
    return true;
}
if (preg_match('#\.(sql|md|log|env)$#', $uri)) {
    http_response_code(403);
    echo '403';
    return true;
}

$routes = [
    '/' => '/index.php',
    '/sos' => '/sos.php',
    '/map' => '/map.php',
    '/partners' => '/partners.php',
    '/channel' => '/channel.php',
    '/auth' => '/auth.php',
    '/profile' => '/profile.php',
    '/offline' => '/offline.html',
];

if (isset($routes[$uri])) {
    require __DIR__ . $routes[$uri];
    return true;
}
if (preg_match('#^/profile/(\d+)$#', $uri, $match)) {
    $_GET['id'] = $match[1];
    require __DIR__ . '/profile.php';
    return true;
}

$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    if (str_ends_with($uri, '.php') && !preg_match('#^/(index|sos|map|partners|profile|auth|channel|404)\.php$#', $uri) && !preg_match('#^/api/[a-z]+\.php$#', $uri)) {
        http_response_code(403);
        echo '403';
        return true;
    }
    return false;
}

require __DIR__ . '/404.php';
return true;
