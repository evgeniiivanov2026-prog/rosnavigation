<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

$urls = [
    APP_URL . '/',
    APP_URL . '/sos.php',
    APP_URL . '/map.php',
    APP_URL . '/partners.php',
    APP_URL . '/channel.php',
    APP_URL . '/auth.php',
];
foreach (repo_on_air(20) as $driver) {
    if (!empty($driver['demo'])) {
        continue;
    }
    $urls[] = APP_URL . '/profile.php?id=' . (int) $driver['id'];
}
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    $xml .= '  <url><loc>' . htmlspecialchars($url, ENT_XML1) . '</loc></url>' . "\n";
}
$xml .= "</urlset>\n";
file_put_contents(dirname(__DIR__) . '/sitemap.xml', $xml);
echo 'urls ' . count($urls) . PHP_EOL;
