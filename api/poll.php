<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/api_boot.php';

$lat = (isset($_GET['lat']) && $_GET['lat'] !== '') ? (float) $_GET['lat'] : null;
$lng = (isset($_GET['lng']) && $_GET['lng'] !== '') ? (float) $_GET['lng'] : null;
if ($lat === null || $lng === null || !valid_coords($lat, $lng)) {
    $lat = null;
    $lng = null;
}
$uid = (int) ($_SESSION['user_id'] ?? 0);
if ($uid && $lat !== null && $lng !== null) {
    repo_ping($uid, $lat, $lng);
}

$key = sprintf('poll:%d:%.2f:%.2f', poll_generation(), $lat ?? 0, $lng ?? 0);
$cached = cache_get($key, 10);
if ($cached) {
    json_response($cached);
}

$stats = repo_stats();
$payload = [
    'online' => $stats['online'],
    'on_air' => $stats['on_air'],
    'helped' => $stats['helped'],
    'shoulder' => ($lat !== null && $lng !== null) ? count(repo_drivers($lat, $lng, 15, $uid)) : null,
    'sos' => repo_signals($lat, $lng),
    'helpers' => repo_drivers($lat, $lng, null, $uid),
    'server_time' => date('c'),
];
cache_set($key, $payload);
json_response($payload);
