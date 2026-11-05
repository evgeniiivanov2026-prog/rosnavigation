<?php
declare(strict_types=1);

define('ROSNAV_API', true);
require dirname(__DIR__) . '/inc/bootstrap.php';

$lat = numeric_or_null($_GET['lat'] ?? null);
$lng = numeric_or_null($_GET['lng'] ?? null);
if ($lat === null || $lng === null) {
    json_response(['error' => 'invalid_coords', 'message' => 'Нужны координаты.'], 422);
}
$radius = is_numeric($_GET['radius'] ?? null) ? (float) $_GET['radius'] : (float) HELP_RADIUS_KM;
$viewer = current_user();
json_response([
    'ok' => true,
    'helpers' => repo_helpers_near($lat, $lng, $radius, $viewer['id'] ?? null),
]);
