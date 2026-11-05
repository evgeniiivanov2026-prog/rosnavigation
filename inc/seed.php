<?php
declare(strict_types=1);

function blank_store(): array
{
    return [
        'users' => [],
        'sos' => [],
        'messages' => [],
        'posts' => [],
        'likes' => [],
        'reviews' => [],
        'reports' => [],
    ];
}

function seed_enabled(): bool
{
    return getenv('ROSNAV_NO_SEED') !== '1';
}

/** Образцы интерфейса. Это не живые выезды и не сегодняшняя сводка. */
function seed_bundle(): array
{
    if (!seed_enabled()) {
        return blank_store();
    }
    $now = date('Y-m-d H:i:s');
    $users = [
        user_seed(101, 'Андрей Колёсов', 'Казань', 'both', 'Volvo FH', 'тягач с рефрижератором', 18, 'На 15-м отвечают свои.', 'on_air', 48, 10, 22, 55.7887, 49.1221, $now),
        user_seed(102, 'Илья Бережной', 'Воронеж', 'both', 'КАМАЗ 5490', 'тент', 11, 'Пишу, как есть.', 'on_route', 17, 4, 6, 51.6720, 39.1843, $now),
        user_seed(103, 'Марина Светлова', 'Пермь', 'seeker', 'кемпер', null, 6, 'Едем семьёй, ночь на трассе не геройство.', 'resting', 9, 2, 1, 58.0105, 56.2502, $now),
        user_seed(104, 'Олег Рябинин', 'Иркутск', 'helper', 'Scania', 'контейнер', 21, 'Байкал не прощает спешку.', 'on_air', 30, 6, 16, 52.2896, 104.2806, $now),
        user_seed(105, 'Павел Нуриев', 'Минеральные Воды', 'both', 'MAN TGX', 'цистерна', 9, null, 'on_route', 12, 3, 4, 44.2108, 43.1353, $now),
        user_seed(106, 'Сергей Плотников', 'Краснодар', 'seeker', 'DAF XF', 'тент', 7, null, 'need_help', 4, 1, 0, 50.8200, 40.1500, $now),
        user_seed(107, 'Никита Сафонов', 'Владимир', 'seeker', 'Mercedes Actros', null, 4, null, 'need_help', 0, 0, 0, 56.1360, 40.3960, $now),
    ];
    $sos = [
        sos_seed(501, 106, 'fuel', 50.8200, 40.1500, 'М-4', 840, 'Учебная метка: бак пустой, до колонки не дотягиваю.', 'active', null, $now),
        sos_seed(502, 107, 'breakdown', 56.1360, 40.3960, 'М-7', 184, 'Учебная метка: пробило заднее, запаска есть, домкрата нет.', 'active', null, $now),
        sos_seed(503, 102, 'breakdown', 51.50, 39.40, 'М-4', 500, 'Колесо. Разобрались на обочине.', 'completed', 101, date('Y-m-d H:i:s', time() - 86400 * 12)),
        sos_seed(504, 104, 'fuel', 52.40, 104.10, 'Р-258', 90, 'Докатили до колонки.', 'completed', 101, date('Y-m-d H:i:s', time() - 86400 * 40)),
    ];
    $posts = [
        post_seed(801, 102, 'На М-4 перед Воронежем рамка, хвост около сорока минут. Фура нормально встаёт за АЗС справа, там светло.', 'Воронеж', 'М-4', 14, $now),
        post_seed(802, 101, 'Колесо на М-7, 184-й километр. Запаска была у водителя, домкрат у меня. Дальше поехали оба.', 'Владимир', 'М-7', 31, date('Y-m-d H:i:s', time() - 3600 * 5)),
        post_seed(803, 104, 'Р-258 к Байкалу: ветер боковой, пустую фуру качает. Скорость ниже обычной, и никого не обгоняем на гребне.', 'Иркутск', 'Р-258', 9, date('Y-m-d H:i:s', time() - 86400)),
        post_seed(804, 103, 'Стоянка под Казанью: душ тёплый, кемпер и фура встают, свет есть. Ночью тихо.', 'Казань', 'М-7', 7, date('Y-m-d H:i:s', time() - 86400 * 2)),
    ];
    $reviews = [
        ['id' => 1, 'author_id' => 102, 'target_user' => 101, 'target_partner' => null, 'rating' => 5, 'body' => 'Доехал и не исчез.', 'status' => 'published', 'created_at' => date('Y-m-d H:i:s', time() - 86400 * 12)],
        ['id' => 2, 'author_id' => 104, 'target_user' => 101, 'target_partner' => null, 'rating' => 5, 'body' => 'Спокойно, по делу.', 'status' => 'published', 'created_at' => date('Y-m-d H:i:s', time() - 86400 * 40)],
    ];
    return [
        'users' => $users,
        'sos' => $sos,
        'messages' => [],
        'posts' => $posts,
        'likes' => [],
        'reviews' => $reviews,
        'reports' => [],
    ];
}

function seed_partners(): array
{
    if (!seed_enabled()) {
        return [];
    }
    return [
        partner_seed(301, 'СТО «Ночная яма»', 'sto', 'Владимир', 'Образец карточки у М-7', 56.1290, 40.4066, 'Фура встаёт в две ямы. Карточка показывает, как выглядит живой партнёр.', 3500, 1, 4.80, 36),
        partner_seed(302, 'АЗС «Плечо»', 'fuel', 'Воронеж', 'Образец карточки у М-4', 51.6608, 39.2003, 'ДТ, душ, место для фуры. Цену живой партнёр ставит сам.', 62, 1, 4.60, 120),
        partner_seed(303, 'Мотель «Рассвет»', 'hotel', 'Казань', 'Образец карточки у М-7', 55.8300, 49.0600, 'Душ, койка, охрана на въезде. Для образца, не для брони.', 1800, 1, 4.70, 54),
        partner_seed(304, 'Страховая «Дорога»', 'insurance', 'Москва', 'Образец карточки', 55.7512, 37.6184, 'Полис и эвакуация. Настоящий договор подключат отдельно.', null, 1, 4.40, 18),
        partner_seed(305, 'Юрист рейса', 'lawyer', 'Краснодар', 'Образец карточки', 45.0355, 38.9753, 'Штрафы, допуски, споры по документам. Это форма карточки.', 3000, 0, 4.90, 22),
        partner_seed(306, 'Магазин «Запаска»', 'shop', 'Пермь', 'Образец карточки у М-7', 58.0105, 56.2502, 'Расходники, которые можно забрать ночью. Пока без живого склада.', 400, 1, 4.50, 41),
    ];
}

function user_seed(int $id, string $name, string $city, string $role, ?string $brand, ?string $trailer, int $years, ?string $motto, string $status, int $ratingSum, int $ratingCount, int $helps, float $lat, float $lng, string $seen): array
{
    return [
        'id' => $id,
        'phone' => null,
        'password_hash' => null,
        'name' => $name,
        'city' => $city,
        'role' => $role,
        'truck_brand' => $brand,
        'truck_plate' => null,
        'truck_trailer' => $trailer,
        'experience_y' => $years,
        'avatar_path' => null,
        'car_path' => null,
        'motto' => $motto,
        'status' => $status,
        'rating_sum' => $ratingSum,
        'rating_count' => $ratingCount,
        'helps_count' => $helps,
        'is_partner' => 0,
        'is_banned' => 0,
        'last_seen_at' => $seen,
        'lat' => $lat,
        'lng' => $lng,
        'demo' => 1,
        'created_at' => '2026-01-15 08:00:00',
    ];
}

function sos_seed(int $id, int $userId, string $type, float $lat, float $lng, string $route, int $km, string $comment, string $status, ?int $helper, string $created): array
{
    return [
        'id' => $id,
        'user_id' => $userId,
        'type' => $type,
        'lat' => $lat,
        'lng' => $lng,
        'route' => $route,
        'km' => $km,
        'comment' => $comment,
        'status' => $status,
        'helper_id' => $helper,
        'approx_geo' => 0,
        'demo' => 1,
        'created_at' => $created,
        'accepted_at' => $status === 'completed' ? $created : null,
        'completed_at' => $status === 'completed' ? $created : null,
        'expires_at' => $status === 'active' ? date('Y-m-d H:i:s', time() + 7200) : $created,
    ];
}

function post_seed(int $id, int $userId, string $body, string $city, string $route, int $likes, string $created): array
{
    return [
        'id' => $id,
        'user_id' => $userId,
        'body' => $body,
        'photo_path' => null,
        'city' => $city,
        'route' => $route,
        'likes_count' => $likes,
        'status' => 'published',
        'demo' => 1,
        'created_at' => $created,
    ];
}

function partner_seed(int $id, string $name, string $type, string $city, string $address, float $lat, float $lng, string $description, ?int $price, int $day, float $rating, int $count): array
{
    return [
        'id' => $id,
        'name' => $name,
        'type' => $type,
        'city' => $city,
        'address' => $address,
        'lat' => $lat,
        'lng' => $lng,
        'phone' => null,
        'site_url' => null,
        'description' => $description,
        'photo_path' => null,
        'price_from' => $price,
        'works_24_7' => $day,
        'rating_avg' => $rating,
        'rating_count' => $count,
        'is_active' => 1,
        'demo' => 1,
        'created_at' => '2026-03-01 10:00:00',
    ];
}
