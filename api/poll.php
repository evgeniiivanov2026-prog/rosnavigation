<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

api_dispatch(static function (): void {
    $lat = isset($_GET['lat']) ? (float) $_GET['lat'] : null;
    $lng = isset($_GET['lng']) ? (float) $_GET['lng'] : null;
    $radius = isset($_GET['radius']) ? (float) $_GET['radius'] : null;
    if ($radius !== null && $radius <= 0) {
        $radius = null;
    }
    if ($radius !== null) {
        $radius = min($radius, 500);
    }
    if ($lat !== null && $lng !== null && !coords_valid($lat, $lng)) {
        $lat = null;
        $lng = null;
        $radius = null;
    }
    $viewer = current_user();
    if ($viewer && $lat !== null && $lng !== null) {
        repo_touch((int) $viewer['id'], $lat, $lng);
    }
    $key = sprintf('poll:%s:%s:%s', $lat === null ? 'x' : round($lat, 1), $lng === null ? 'x' : round($lng, 1), $radius ?? 'all');
    $payload = cache_get($key, 10);
    if (!$payload) {
        $payload = [
            'sos' => repo_sos_near($lat, $lng, $radius),
            'helpers' => repo_helpers_near($lat, $lng, $radius),
            'stats' => repo_stats(),
            'server_time' => date('c'),
        ];
        cache_set($key, $payload);
    }
    json_response($payload);
});
