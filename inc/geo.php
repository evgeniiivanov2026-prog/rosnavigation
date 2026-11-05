<?php
declare(strict_types=1);

function haversine_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $r = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $r * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
}

function coords_valid(float $lat, float $lng): bool
{
    if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
        return false;
    }
    return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
}

/**
 * @param array<int, array<string, mixed>> $rows
 * @return array<int, array<string, mixed>>
 */
function geo_filter(array $rows, ?float $lat, ?float $lng, ?float $radiusKm): array
{
    if ($lat === null || $lng === null || $radiusKm === null || !coords_valid($lat, $lng)) {
        return $rows;
    }
    $out = [];
    foreach ($rows as $row) {
        if (!isset($row['lat'], $row['lng']) || $row['lat'] === null || $row['lng'] === null) {
            continue;
        }
        $dist = haversine_km($lat, $lng, (float) $row['lat'], (float) $row['lng']);
        if ($dist <= $radiusKm) {
            $row['distance_km'] = round($dist, 1);
            $out[] = $row;
        }
    }
    usort($out, static fn ($a, $b) => ($a['distance_km'] <=> $b['distance_km']));
    return $out;
}

/**
 * @return array{0: float, 1: float, 2: float, 3: float}
 */
function geo_bbox(float $lat, float $lng, float $radiusKm): array
{
    $dLat = $radiusKm / 111.0;
    $dLng = $radiusKm / (111.0 * max(cos(deg2rad($lat)), 0.01));
    return [$lat - $dLat, $lat + $dLat, $lng - $dLng, $lng + $dLng];
}
