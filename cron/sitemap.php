<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

$urls = ['/', '/sos', '/map', '/partners', '/channel', '/auth'];
if (!DEMO_MODE) {
    foreach (repo_partners([]) as $partner) {
        $urls[] = '/partners?id=' . (int) $partner['id'];
    }
}
$xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
foreach ($urls as $path) {
    $xml[] = '  <url><loc>' . htmlspecialchars(APP_URL . $path, ENT_XML1) . '</loc></url>';
}
$xml[] = '</urlset>';
file_put_contents(APP_ROOT . '/sitemap.xml', implode("\n", $xml) . "\n");
echo "sitemap\n";
