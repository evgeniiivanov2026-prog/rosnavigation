<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require dirname(__DIR__) . '/inc/bootstrap.php';

$paths = ['/', '/sos', '/map', '/partners', '/channel', '/auth'];
if (app_demo()) {
    foreach (demo_boot()['users'] as $user) {
        $paths[] = '/profile/' . (int) $user['id'];
    }
} else {
    $rows = db()->query('SELECT id FROM users WHERE is_banned = 0')->fetchAll();
    foreach ($rows as $row) {
        $paths[] = '/profile/' . (int) $row['id'];
    }
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($paths as $path) {
    $xml .= '  <url><loc>' . htmlspecialchars(rtrim(APP_URL, '/') . $path, ENT_XML1) . '</loc></url>' . "\n";
}
$xml .= '</urlset>' . "\n";
file_put_contents(dirname(__DIR__) . '/sitemap.xml', $xml);
fwrite(STDOUT, 'sitemap: ' . count($paths) . " адресов\n");
