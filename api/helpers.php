<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

api_dispatch(static function (): void {
    $lat = isset($_GET['lat']) ? (float) $_GET['lat'] : null;
    $lng = isset($_GET['lng']) ? (float) $_GET['lng'] : null;
    $radius = isset($_GET['radius']) ? (float) $_GET['radius'] : 15.0;
    if ($lat === null || $lng === null || !coords_valid($lat, $lng)) {
        $lat = null;
        $lng = null;
        $radius = null;
    }
    json_response([
        'helpers' => repo_helpers_near($lat, $lng, $radius),
        'sos' => repo_sos_near($lat, $lng, $radius),
    ]);
});
