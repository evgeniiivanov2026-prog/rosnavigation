<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/api_boot.php';

$lat = isset($_GET['lat']) ? (float) $_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float) $_GET['lng'] : null;
$radius = isset($_GET['radius']) ? (float) $_GET['radius'] : 15.0;
if ($lat === null || $lng === null || !valid_coords($lat, $lng)) {
    json_response(['helpers' => repo_drivers()]);
}
json_response(['helpers' => repo_drivers($lat, $lng, max(1, min(500, $radius)))]);
