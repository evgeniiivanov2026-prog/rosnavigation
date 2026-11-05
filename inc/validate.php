<?php
declare(strict_types=1);

function assert_phone(string $phone): string
{
    $phone = normalize_phone($phone);
    if (!preg_match('/^\+7\d{10}$/', $phone)) {
        throw new HttpError('phone', 'Нужен телефон в виде +7 и десяти цифр.');
    }
    return $phone;
}

function assert_password(string $password): string
{
    $length = mb_strlen($password);
    if ($length < 8) {
        throw new HttpError('password', 'Пароль короче 8 символов.');
    }
    if ($length > 200) {
        throw new HttpError('password', 'Пароль слишком длинный.');
    }
    return $password;
}

function assert_person_name(string $name): string
{
    $name = clean_text($name, 100);
    if (!preg_match('/^[\p{L}\p{M}][\p{L}\p{M}\s\-\.\']{1,99}$/u', $name)) {
        throw new HttpError('name', 'Имя — от двух букв, без цифр и ссылок.');
    }
    return $name;
}

function hash_password(string $password): string
{
    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    return password_hash($password, PASSWORD_DEFAULT);
}

/** @return array{type:string,lat:float,lng:float,route:?string,km:?int,comment:string,approx:int} */
function sos_payload(array $in): array
{
    $type = (string) ($in['type'] ?? '');
    $allowed = ['breakdown', 'fuel', 'sleep', 'accident', 'medical', 'other'];
    if (!in_array($type, $allowed, true)) {
        throw new HttpError('invalid_type', 'Выберите, что случилось.');
    }
    $lat = is_numeric($in['lat'] ?? null) ? (float) $in['lat'] : null;
    $lng = is_numeric($in['lng'] ?? null) ? (float) $in['lng'] : null;
    $approx = 0;
    $route = clean_text($in['route'] ?? '', 50);
    $route = $route !== '' ? $route : null;
    if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        $point = $route ? route_point($route) : null;
        if ($point === null) {
            throw new HttpError('invalid_coords', 'Не видим точку. Включите геолокацию или напишите трассу: М-7, М-4, Р-21.');
        }
        [$lat, $lng] = $point;
        $approx = 1;
    }
    $km = null;
    if (isset($in['km']) && $in['km'] !== '' && $in['km'] !== null) {
        if (!is_numeric($in['km'])) {
            throw new HttpError('km', 'Километр — это число.');
        }
        $km = (int) $in['km'];
        if ($km < 0 || $km > 20000) {
            throw new HttpError('km', 'Такого километра на трассе нет.');
        }
    }
    return [
        'type' => $type,
        'lat' => $lat,
        'lng' => $lng,
        'route' => $route,
        'km' => $km,
        'comment' => clean_text($in['comment'] ?? '', 500),
        'approx' => $approx,
    ];
}

/** @return array<string, mixed> */
function profile_payload(array $in, bool $partial = true): array
{
    $out = [];
    if (array_key_exists('name', $in)) {
        $out['name'] = assert_person_name((string) $in['name']);
    } elseif (!$partial) {
        throw new HttpError('name', 'Как к вам обращаться?');
    }
    if (array_key_exists('city', $in)) {
        $city = clean_text((string) $in['city'], 100);
        $out['city'] = $city !== '' ? $city : null;
    }
    if (array_key_exists('role', $in)) {
        $role = (string) $in['role'];
        if (!in_array($role, ['seeker', 'helper', 'both'], true)) {
            throw new HttpError('role', 'Выберите роль: ищу помощь, помогаю или и то и то.');
        }
        $out['role'] = $role;
    }
    foreach (['truck_brand' => 100, 'truck_plate' => 20, 'truck_trailer' => 50, 'motto' => 200] as $key => $max) {
        if (array_key_exists($key, $in)) {
            $value = clean_text((string) $in[$key], $max);
            if ($key === 'truck_plate') {
                $value = mb_strtoupper($value);
            }
            $out[$key] = $value !== '' ? $value : null;
        }
    }
    if (array_key_exists('experience_y', $in) && $in['experience_y'] !== '' && $in['experience_y'] !== null) {
        if (!is_numeric($in['experience_y'])) {
            throw new HttpError('experience', 'Стаж — число лет.');
        }
        $years = (int) $in['experience_y'];
        if ($years < 0 || $years > 60) {
            throw new HttpError('experience', 'Стаж от 0 до 60 лет.');
        }
        $out['experience_y'] = $years;
    }
    if (array_key_exists('status', $in)) {
        $status = (string) $in['status'];
        if (!in_array($status, ['on_air', 'on_route', 'need_help', 'resting', 'offline'], true)) {
            throw new HttpError('status', 'Такого статуса нет.');
        }
        $out['status'] = $status;
    }
    if (isset($in['lat'], $in['lng']) && is_numeric($in['lat']) && is_numeric($in['lng'])) {
        $lat = (float) $in['lat'];
        $lng = (float) $in['lng'];
        if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
            $out['lat'] = $lat;
            $out['lng'] = $lng;
        }
    }
    return $out;
}

function sos_type_label(string $type): string
{
    return match ($type) {
        'breakdown' => 'Встал',
        'fuel' => 'Обсох',
        'sleep' => 'Притомился',
        'accident' => 'ДТП',
        'medical' => 'Медицина',
        'other' => 'Другое',
        default => 'Сигнал',
    };
}

function status_label(string $status): string
{
    return match ($status) {
        'on_air' => 'На приёме',
        'on_route' => 'На маршруте',
        'need_help' => 'Нужна помощь',
        'resting' => 'Отдыхаю',
        default => 'Молчу',
    };
}

function partner_type_label(string $type): string
{
    return match ($type) {
        'sto' => 'СТО',
        'fuel' => 'АЗС',
        'hotel' => 'Ночлег',
        'insurance' => 'Страховая',
        'lawyer' => 'Юрист',
        'shop' => 'Магазин',
        default => 'Партнёр',
    };
}

/** @param array<string, mixed> $user */
function user_badges(array $user): array
{
    $badges = [];
    if (empty($user['demo'])) {
        $badges[] = '15-й канал';
    }
    $years = (int) ($user['experience_y'] ?? 0);
    $helps = (int) ($user['helps_count'] ?? 0);
    $role = (string) ($user['role'] ?? 'both');
    if ($years >= 10 && in_array($role, ['helper', 'both'], true)) {
        $badges[] = 'Дальнобой России';
    }
    if ($helps >= 5) {
        $badges[] = 'Наставник';
    }
    if ($years >= 15 || $helps >= 15) {
        $badges[] = 'Ветеран эфира';
    }
    return $badges;
}

/** @param array<string, mixed> $user */
function user_rating(array $user): ?float
{
    $count = (int) ($user['rating_count'] ?? 0);
    if ($count < 1) {
        return null;
    }
    return round(((int) $user['rating_sum']) / $count, 1);
}
