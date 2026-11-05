<?php
declare(strict_types=1);

/**
 * Маршрутизатор только для встроенного сервера PHP:
 * php -S 127.0.0.1:8080 router.php
 * На хостинге работает .htaccess, этот файл не нужен.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
if (preg_match('#^/(inc|storage|templates|sql|cron|tests)(/|$)#', $uri)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}
if (preg_match('#^/uploads/.*\.ph(p|ar)$#i', $uri)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}
if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
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
if (preg_match('#^/profile/(\d+)/?$#', $uri, $match)) {
    $_GET['id'] = $match[1];
    require __DIR__ . '/profile.php';
    return true;
}
http_response_code(404);
require __DIR__ . '/404.php';
return true;
