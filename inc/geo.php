<?php
declare(strict_types=1);

function haversine_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earth = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function bbox(float $lat, float $lng, float $radiusKm): array
{
    $dLat = $radiusKm / 111.0;
    $dLng = $radiusKm / (111.0 * max(cos(deg2rad($lat)), 0.01));
    return [$lat - $dLat, $lat + $dLat, $lng - $dLng, $lng + $dLng];
}

/**
 * Схема для первого экрана. Это не навигационная проекция.
 *
 * @return array{0: float, 1: float}
 */
function teaser_point(float $lat, float $lng): array
{
    $x = ($lng - 28) / (110 - 28);
    $y = (69 - $lat) / (69 - 42);
    return [
        round(max(6, min(94, $x * 100)), 1),
        round(max(8, min(90, $y * 100)), 1),
    ];
}
