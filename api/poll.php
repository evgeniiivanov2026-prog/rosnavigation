<?php
declare(strict_types=1);

define('ROSNAV_API', true);
require dirname(__DIR__) . '/inc/bootstrap.php';

$viewer = current_user();
$lat = numeric_or_null($_GET['lat'] ?? null);
$lng = numeric_or_null($_GET['lng'] ?? null);
$radius = is_numeric($_GET['radius'] ?? null) ? (float) $_GET['radius'] : (float) HELP_RADIUS_KM;
if ($viewer && $lat !== null && $lng !== null) {
    try {
        repo_touch((int) $viewer['id'], $lat, $lng);
    } catch (Throwable $e) {
        log_line('touch', $e->getMessage());
    }
}
json_response(repo_poll($lat, $lng, $radius, $viewer['id'] ?? null));
