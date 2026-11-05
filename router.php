<?php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rawurldecode($uri);

if (preg_match('#^/(inc|storage|templates|cron|sql)(/|$)#', $uri)) {
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
];

if (isset($routes[$uri])) {
    require __DIR__ . $routes[$uri];
    return true;
}

if (preg_match('#^/profile/(\d+)/?$#', $uri, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/profile.php';
    return true;
}

$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false;
}

http_response_code(404);
require __DIR__ . '/404.php';
return true;
