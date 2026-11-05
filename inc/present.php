<?php
declare(strict_types=1);

/** @param array<string, mixed> $row */
function present_user(array $row, bool $self = false): array
{
    $status = (string) ($row['status'] ?? 'offline');
    $meta = status_meta($status);
    $count = (int) ($row['rating_count'] ?? 0);
    $sum = (int) ($row['rating_sum'] ?? 0);
    $rating = $count > 0 ? round($sum / $count, 1) : 0.0;
    $out = [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'city' => (string) ($row['city'] ?? ''),
        'role' => (string) ($row['role'] ?? 'both'),
        'role_label' => catalog_roles()[$row['role'] ?? 'both'] ?? 'И то, и то',
        'truck_brand' => (string) ($row['truck_brand'] ?? ''),
        'truck_trailer' => (string) ($row['truck_trailer'] ?? ''),
        'experience_y' => isset($row['experience_y']) && $row['experience_y'] !== null ? (int) $row['experience_y'] : null,
        'avatar_path' => $row['avatar_path'] ?: null,
        'car_path' => $row['car_path'] ?: null,
        'motto' => (string) ($row['motto'] ?? ''),
        'status' => $status,
        'status_label' => $meta['label'],
        'status_tone' => $meta['tone'],
        'rating' => $rating,
        'rating_count' => $count,
        'helps_count' => (int) ($row['helps_count'] ?? 0),
        'badges' => user_badges($row),
        'route' => (string) ($row['route'] ?? ''),
        'km' => isset($row['km']) && $row['km'] !== null ? (int) $row['km'] : null,
        'lat' => isset($row['lat']) && $row['lat'] !== null ? (float) $row['lat'] : null,
        'lng' => isset($row['lng']) && $row['lng'] !== null ? (float) $row['lng'] : null,
        'created_at' => (string) ($row['created_at'] ?? ''),
        'last_seen_at' => (string) ($row['last_seen_at'] ?? ''),
        'ago' => ru_ago($row['last_seen_at'] ?? null),
    ];
    if ($self) {
        $out['phone'] = (string) ($row['phone'] ?? '');
        $out['truck_plate'] = (string) ($row['truck_plate'] ?? '');
    }
    return $out;
}

/** @param array<string, mixed> $row */
function present_sos(array $row, ?float $fromLat = null, ?float $fromLng = null, bool $revealPhone = false): array
{
    $type = (string) $row['type'];
    $distance = null;
    if ($fromLat !== null && $fromLng !== null && coords_valid($fromLat, $fromLng)) {
        $distance = round(haversine_km($fromLat, $fromLng, (float) $row['lat'], (float) $row['lng']), 1);
    } elseif (isset($row['distance_km'])) {
        $distance = (float) $row['distance_km'];
    }
    $out = [
        'id' => (int) $row['id'],
        'user_id' => (int) $row['user_id'],
        'name' => (string) ($row['name'] ?? ''),
        'city' => (string) ($row['city'] ?? ''),
        'truck_brand' => (string) ($row['truck_brand'] ?? ''),
        'type' => $type,
        'type_label' => sos_label($type),
        'lat' => (float) $row['lat'],
        'lng' => (float) $row['lng'],
        'route' => (string) ($row['route'] ?? ''),
        'km' => isset($row['km']) && $row['km'] !== null && $row['km'] !== '' ? (int) $row['km'] : null,
        'comment' => (string) ($row['comment'] ?? ''),
        'status' => (string) $row['status'],
        'helper_id' => isset($row['helper_id']) && $row['helper_id'] ? (int) $row['helper_id'] : null,
        'helper_name' => (string) ($row['helper_name'] ?? ''),
        'created_at' => (string) ($row['created_at'] ?? ''),
        'ago' => ru_ago($row['created_at'] ?? null),
        'distance_km' => $distance,
        'distance_label' => format_km($distance),
    ];
    if ($revealPhone && !empty($row['phone'])) {
        $out['phone'] = (string) $row['phone'];
    }
    return $out;
}

/** @param array<string, mixed> $row */
function present_partner(array $row): array
{
    $type = (string) $row['type'];
    $price = isset($row['price_from']) && $row['price_from'] !== null && $row['price_from'] !== ''
        ? (int) $row['price_from'] : null;
    $priceLabel = '';
    if ($price !== null) {
        $n = number_format($price, 0, ',', ' ');
        $priceLabel = $type === 'fuel' ? 'от ' . $n . ' ₽/л' : 'от ' . $n . ' ₽';
    }
    return [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'type' => $type,
        'type_label' => catalog_partner_types()[$type] ?? 'Партнёр',
        'city' => (string) ($row['city'] ?? ''),
        'region' => (string) ($row['region'] ?? $row['city'] ?? ''),
        'address' => (string) ($row['address'] ?? ''),
        'lat' => isset($row['lat']) && $row['lat'] !== null ? (float) $row['lat'] : null,
        'lng' => isset($row['lng']) && $row['lng'] !== null ? (float) $row['lng'] : null,
        'phone' => (string) ($row['phone'] ?? ''),
        'site_url' => (string) ($row['site_url'] ?? ''),
        'description' => (string) ($row['description'] ?? ''),
        'photo_path' => $row['photo_path'] ?: null,
        'price_from' => $price,
        'price_label' => $priceLabel,
        'works_24_7' => (int) ($row['works_24_7'] ?? 0) === 1,
        'rating_avg' => round((float) ($row['rating_avg'] ?? 0), 1),
        'rating_count' => (int) ($row['rating_count'] ?? 0),
    ];
}

/** @param array<string, mixed> $row */
function present_post(array $row, bool $liked = false): array
{
    return [
        'id' => (int) $row['id'],
        'user_id' => (int) $row['user_id'],
        'name' => (string) ($row['name'] ?? ''),
        'city' => (string) ($row['city'] ?? ''),
        'route' => (string) ($row['route'] ?? ''),
        'body' => (string) $row['body'],
        'photo_path' => $row['photo_path'] ?: null,
        'likes_count' => (int) ($row['likes_count'] ?? 0),
        'liked' => $liked,
        'status' => (string) ($row['status'] ?? 'published'),
        'created_at' => (string) ($row['created_at'] ?? ''),
        'ago' => ru_ago($row['created_at'] ?? null),
    ];
}

/** @param array<string, mixed> $row */
function present_message(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'sos_id' => (int) $row['sos_id'],
        'user_id' => (int) $row['user_id'],
        'name' => (string) ($row['name'] ?? ''),
        'body' => (string) $row['body'],
        'created_at' => (string) ($row['created_at'] ?? ''),
        'ago' => ru_ago($row['created_at'] ?? null),
    ];
}
