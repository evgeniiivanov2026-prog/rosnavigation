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
 * @param array<int, array<string, mixed>> $rows
 * @return array<int, array<string, mixed>>
 */
function filter_near(array $rows, float $lat, float $lng, float $radiusKm, string $latKey = 'lat', string $lngKey = 'lng'): array
{
    $result = [];
    foreach ($rows as $row) {
        if ($row[$latKey] === null || $row[$lngKey] === null || $row[$latKey] === '' || $row[$lngKey] === '') {
            continue;
        }
        $dist = haversine_km($lat, $lng, (float) $row[$latKey], (float) $row[$lngKey]);
        if ($dist <= $radiusKm) {
            $row['distance_km'] = round($dist, 1);
            $result[] = $row;
        }
    }
    usort($result, static fn ($a, $b) => $a['distance_km'] <=> $b['distance_km']);
    return $result;
}

/** Опорные точки трасс, если GPS молчит. Это не километровая привязка. */
function route_point(string $route): ?array
{
    $key = mb_strtoupper(trim($route));
    $key = preg_replace('/[\x{00A0}\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}\x{2212}]+/u', '-', $key) ?? $key;
    $compact = preg_replace('/[^0-9A-ZА-ЯЁ]/u', '', $key) ?? '';
    $map = [
        'М4' => [51.6608, 39.2003],
        'M4' => [51.6608, 39.2003],
        'М5' => [54.7355, 55.9587],
        'M5' => [54.7355, 55.9587],
        'М7' => [56.1290, 40.4066],
        'M7' => [56.1290, 40.4066],
        'М11' => [58.5213, 31.2755],
        'M11' => [58.5213, 31.2755],
        'М12' => [55.7887, 49.1221],
        'M12' => [55.7887, 49.1221],
        'Р21' => [61.7849, 34.3469],
        'P21' => [61.7849, 34.3469],
        'Р217' => [44.2108, 43.1353],
        'P217' => [44.2108, 43.1353],
        'Р258' => [52.2896, 104.2806],
        'P258' => [52.2896, 104.2806],
        'А370' => [43.1332, 131.9113],
        'A370' => [43.1332, 131.9113],
    ];
    uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
    foreach ($map as $code => $point) {
        if ($code !== '' && ($compact === $code || str_contains($compact, $code))) {
            return $point;
        }
    }
    return null;
}
