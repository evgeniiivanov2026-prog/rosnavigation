<?php
declare(strict_types=1);

function demo_path(): string
{
    return dirname(__DIR__) . '/storage/demo/db.json';
}

function demo_stamp(int $secondsAgo): string
{
    return date('Y-m-d H:i:s', time() - $secondsAgo);
}

/** @return array<string, mixed> */
function demo_seed(): array
{
    $now = demo_stamp(0);
    $year = demo_stamp(400 * 86400);
    $hashDemo = hash_password(DEMO_PASSWORD);
    $hashOther = hash_password(bin2hex(random_bytes(8)));
    $user = static function (
        int $id,
        string $phone,
        string $hash,
        string $name,
        string $city,
        string $role,
        string $brand,
        string $plate,
        string $trailer,
        int $years,
        string $motto,
        string $status,
        int $helps,
        int $ratingSum,
        int $ratingCount,
        ?float $lat,
        ?float $lng,
        string $route,
        ?int $km
    ) use ($now, $year): array {
        return [
            'id' => $id,
            'phone' => $phone,
            'password_hash' => $hash,
            'name' => $name,
            'city' => $city,
            'role' => $role,
            'truck_brand' => $brand,
            'truck_plate' => $plate,
            'truck_trailer' => $trailer,
            'experience_y' => $years,
            'avatar_path' => null,
            'car_path' => null,
            'motto' => $motto,
            'status' => $status,
            'rating_sum' => $ratingSum,
            'rating_count' => $ratingCount,
            'helps_count' => $helps,
            'is_banned' => 0,
            'lat' => $lat,
            'lng' => $lng,
            'route' => $route,
            'km' => $km,
            'last_seen_at' => $now,
            'created_at' => $year,
        ];
    };

    return [
        'seed' => 1,
        'seq' => ['users' => 10, 'sos' => 3, 'posts' => 5, 'partners' => 8, 'messages' => 2, 'complaints' => 0],
        'users' => [
            $user(1, DEMO_PHONE, $hashDemo, 'Волга', 'Нижний Новгород', 'both', 'Volvo FH', 'А015ВВ52', 'тент', 19, 'Обочина — не конец рейса.', 'on_air', 128, 612, 126, 56.2300, 44.0700, 'М-7', 412, ),
            $user(2, '+79000000002', $hashOther, 'Дон', 'Воронеж', 'helper', 'Scania R', 'В204ОР36', 'реф', 14, 'Сначала доеду, потом расскажу.', 'on_route', 46, 210, 44, 51.6720, 39.1843, 'М-4', 515),
            $user(3, '+79000000003', $hashOther, 'Кола', 'Петрозаводск', 'seeker', 'КАМАЗ 5490', 'К118КК10', 'тент', 11, 'Север ошибок не прощает.', 'need_help', 9, 40, 9, 61.9000, 34.1800, 'Р-21', 412),
            $user(4, '+79000000004', $hashOther, 'Урал', 'Уфа', 'both', 'MAN TGX', 'У077УУ02', 'штора', 22, 'Снег — не повод гнать.', 'resting', 73, 340, 70, 54.7388, 55.9721, 'М-5', 1280),
            $user(5, '+79000000005', $hashOther, 'Байкал', 'Иркутск', 'helper', 'Volvo FM', 'И015ИР38', 'контейнер', 16, 'На спуске не геройствуй.', 'on_air', 31, 148, 30, 52.2870, 104.3050, 'Р-258', 96),
            $user(6, '+79000000006', $hashOther, 'Кавказ', 'Пятигорск', 'both', 'DAF XF', 'К303КК26', 'тент', 13, 'Боковой ветер слышно раньше, чем видно.', 'on_route', 18, 82, 17, 44.0486, 43.0594, 'Р-217', 214),
            $user(7, '+79000000007', $hashOther, 'Нева', 'Великий Новгород', 'helper', 'Mercedes Actros', 'Н511НН53', 'реф', 9, 'Стоянка без душа — ещё не ночлег.', 'on_air', 12, 55, 12, 58.5215, 31.2755, 'М-11', 418),
            $user(8, '+79000000008', $hashOther, 'Восток', 'Казань', 'both', 'Renault T', 'В812ВВ16', 'тент', 7, 'Новый на линии, не новый на дороге.', 'on_route', 4, 18, 4, 55.7887, 49.1221, 'М-12', 780),
            $user(9, '+79000000009', $hashOther, 'Степь', 'Ростов-на-Дону', 'seeker', 'Volvo FH', 'Р440РР61', 'тент', 15, 'Греть некому — значит, сам не стой.', 'need_help', 21, 96, 20, 48.3000, 40.1500, 'М-4', 933),
            $user(10, '+79000000010', $hashOther, 'Туман', 'Тверь', 'seeker', 'Scania S', 'Т015ТТ69', 'штора', 6, 'Сон в кабине на М-11 — плохая экономия.', 'need_help', 2, 10, 2, 57.4500, 34.9200, 'М-11', 214),
        ],
        'sos' => [
            [
                'id' => 1,
                'user_id' => 3,
                'type' => 'fuel',
                'lat' => 61.9000,
                'lng' => 34.1800,
                'route' => 'Р-21',
                'km' => 412,
                'comment' => 'Обсох за Петрозаводском, севернее города. Канистра на 20 литров поставит на ход.',
                'status' => 'active',
                'helper_id' => null,
                'created_at' => demo_stamp(14 * 60),
                'accepted_at' => null,
                'completed_at' => null,
                'expires_at' => demo_stamp(-2 * 3600),
            ],
            [
                'id' => 2,
                'user_id' => 9,
                'type' => 'breakdown',
                'lat' => 48.3000,
                'lng' => 40.1500,
                'route' => 'М-4',
                'km' => 933,
                'comment' => 'Лопнул шланг наддува. Стою на обочине с аварийкой, есть жилет и знак. Нужен хомут и компания до сервиса.',
                'status' => 'active',
                'helper_id' => null,
                'created_at' => demo_stamp(36 * 60),
                'accepted_at' => null,
                'completed_at' => null,
                'expires_at' => demo_stamp(-90 * 60),
            ],
            [
                'id' => 3,
                'user_id' => 10,
                'type' => 'sleep',
                'lat' => 57.4500,
                'lng' => 34.9200,
                'route' => 'М-11',
                'km' => 214,
                'comment' => 'Глаза закрываются. Подскажите карман, куда фура встаёт без цирка, лучше со светом.',
                'status' => 'active',
                'helper_id' => null,
                'created_at' => demo_stamp(8 * 60),
                'accepted_at' => null,
                'completed_at' => null,
                'expires_at' => demo_stamp(-2 * 3600),
            ],
        ],
        'messages' => [
            [
                'id' => 1,
                'sos_id' => 1,
                'user_id' => 5,
                'body' => 'Север сегодня злой. Если едешь на Колу — прихвати канистру заранее.',
                'created_at' => demo_stamp(3 * 3600),
            ],
        ],
        'posts' => [
            [
                'id' => 1,
                'user_id' => 1,
                'body' => 'Помог сегодня на М-7 у Иннополиса. Лопнул шланг, человек стоял с аварийкой. Двадцати минут хватило, фура снова в рейсе.',
                'photo_path' => null,
                'city' => 'Татарстан',
                'route' => 'М-7',
                'likes_count' => 18,
                'status' => 'published',
                'created_at' => demo_stamp(2 * 3600),
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'body' => 'Рамка на М-4 перед Воронежем. Хвост около получаса, фуры пускают правым карманом. Не лезьте в левый — там легковушки и нервы.',
                'photo_path' => null,
                'city' => 'Воронеж',
                'route' => 'М-4',
                'likes_count' => 11,
                'status' => 'published',
                'created_at' => demo_stamp(5 * 3600),
            ],
            [
                'id' => 3,
                'user_id' => 5,
                'body' => 'Р-258 после Слюдянки: на спуске к Байкалу гололёд, даже если в городе сухо. Цепи сегодня не для красоты.',
                'photo_path' => null,
                'city' => 'Иркутск',
                'route' => 'Р-258',
                'likes_count' => 23,
                'status' => 'published',
                'created_at' => demo_stamp(9 * 3600),
            ],
            [
                'id' => 4,
                'user_id' => 7,
                'body' => 'Стоянка на М-11, 418-й километр. Свет есть, душ в кафе до 23:00, фура встаёт ровно, без разворота на газоне.',
                'photo_path' => null,
                'city' => 'Новгород',
                'route' => 'М-11',
                'likes_count' => 7,
                'status' => 'published',
                'created_at' => demo_stamp(26 * 3600),
            ],
            [
                'id' => 5,
                'user_id' => 6,
                'body' => 'Р-217 перед Минеральными Водами: боковой ветер. Пустую тентовку лучше не обгонять и не прижиматься.',
                'photo_path' => null,
                'city' => 'Пятигорск',
                'route' => 'Р-217',
                'likes_count' => 9,
                'status' => 'published',
                'created_at' => demo_stamp(30 * 3600),
            ],
        ],
        'likes' => [
            ['user_id' => 2, 'post_id' => 1],
            ['user_id' => 5, 'post_id' => 1],
            ['user_id' => 1, 'post_id' => 3],
        ],
        'complaints' => [],
        'partners' => [
            ['id' => 1, 'name' => 'Рессоры М-7', 'type' => 'sto', 'city' => 'Чебоксары', 'region' => 'Чувашия', 'address' => 'Трасса М-7, карман за АЗС, рампа под фуру', 'lat' => 56.1100, 'lng' => 47.3000, 'phone' => '+78352001515', 'site_url' => '', 'description' => 'Пневма, шланги, электрика. Фура встаёт на рампу, не на обочину. Ночью дежурит механик.', 'photo_path' => null, 'price_from' => 2500, 'works_24_7' => 1, 'rating_avg' => 4.8, 'rating_count' => 36, 'is_active' => 1],
            ['id' => 2, 'name' => 'Плечо', 'type' => 'fuel', 'city' => 'Воронеж', 'region' => 'Воронежская область', 'address' => 'М-4, южный обход', 'lat' => 51.5800, 'lng' => 39.2200, 'phone' => '+74732001504', 'site_url' => '', 'description' => 'ДТ, душ, горячее. Колонки под фуру без очереди в легковой ряд. Чек отдают сразу.', 'photo_path' => null, 'price_from' => 64, 'works_24_7' => 1, 'rating_avg' => 4.6, 'rating_count' => 58, 'is_active' => 1],
            ['id' => 3, 'name' => 'Тихий карман', 'type' => 'hotel', 'city' => 'Казань', 'region' => 'Татарстан', 'address' => 'М-7, 820 км, за кафе', 'lat' => 55.8200, 'lng' => 49.0100, 'phone' => '+78432001507', 'site_url' => '', 'description' => 'Двор, куда влезает сцепка. Душ тёплый, свет во дворе, охрана до утра. Завтрак с пяти.', 'photo_path' => null, 'price_from' => 1800, 'works_24_7' => 1, 'rating_avg' => 4.7, 'rating_count' => 22, 'is_active' => 1],
            ['id' => 4, 'name' => 'Северная колонка', 'type' => 'fuel', 'city' => 'Петрозаводск', 'region' => 'Карелия', 'address' => 'Р-21, выезд на Мурманск', 'lat' => 61.8200, 'lng' => 34.3800, 'phone' => '+78142001521', 'site_url' => '', 'description' => 'ДТ зимой не сюрприз: фильтр и антигель на кассе. Канистры наливают без спора.', 'photo_path' => null, 'price_from' => 71, 'works_24_7' => 1, 'rating_avg' => 4.5, 'rating_count' => 19, 'is_active' => 1],
            ['id' => 5, 'name' => 'Обод', 'type' => 'sto', 'city' => 'Ростов-на-Дону', 'region' => 'Ростовская область', 'address' => 'М-4, северный въезд', 'lat' => 47.3200, 'lng' => 39.7200, 'phone' => '+78632001504', 'site_url' => '', 'description' => 'Шиномонтаж фуры, запаски, подкачка. Домкрат свой, яма глубокая. Днём без записи.', 'photo_path' => null, 'price_from' => 1500, 'works_24_7' => 0, 'rating_avg' => 4.4, 'rating_count' => 27, 'is_active' => 1],
            ['id' => 6, 'name' => 'Байкальский карман', 'type' => 'hotel', 'city' => 'Иркутск', 'region' => 'Иркутская область', 'address' => 'Р-258, перед Слюдянкой', 'lat' => 51.9800, 'lng' => 103.7000, 'phone' => '+73952001508', 'site_url' => '', 'description' => 'Ночёвка перед перевалом. Печь в комнате отдыха, розетка под автономку, двор ровный.', 'photo_path' => null, 'price_from' => 2200, 'works_24_7' => 1, 'rating_avg' => 4.9, 'rating_count' => 14, 'is_active' => 1],
            ['id' => 7, 'name' => 'Запас на борт', 'type' => 'shop', 'city' => 'Москва', 'region' => 'Москва', 'address' => 'МКАД, 24 км, внешняя сторона', 'lat' => 55.5700, 'lng' => 37.6000, 'phone' => '+74952001500', 'site_url' => '', 'description' => 'Хомуты, ремни, жидкость, фонарь, жилет. Без витрины «для дачи»: то, что просят ночью.', 'photo_path' => null, 'price_from' => 300, 'works_24_7' => 0, 'rating_avg' => 4.3, 'rating_count' => 41, 'is_active' => 1],
            ['id' => 8, 'name' => 'Полис рейса', 'type' => 'insurance', 'city' => 'Москва', 'region' => 'Москва', 'address' => 'По телефону, документы в рейсе', 'lat' => 55.7512, 'lng' => 37.6184, 'phone' => '+78002001515', 'site_url' => '', 'description' => 'Разговор про ОСАГО и груз до того, как случилось. Не обещают чудес на обочине — объясняют, что делать с бумагами.', 'photo_path' => null, 'price_from' => null, 'works_24_7' => 1, 'rating_avg' => 4.2, 'rating_count' => 11, 'is_active' => 1],
        ],
    ];
}

function demo_boot(): array
{
    if (!empty($GLOBALS['demo_loaded'])) {
        return $GLOBALS['demo_data'];
    }
    $path = demo_path();
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Нет папки для демо-базы');
    }
    $fh = fopen($path, 'c+');
    if ($fh === false) {
        throw new RuntimeException('Не открыли демо-базу');
    }
    if (!flock($fh, LOCK_EX)) {
        throw new RuntimeException('Демо-база занята');
    }
    rewind($fh);
    $raw = stream_get_contents($fh);
    $data = json_decode($raw ?: '', true);
    if (!is_array($data) || empty($data['users'])) {
        $data = demo_seed();
        demo_write($fh, $data);
    } elseif (demo_expire($data)) {
        demo_write($fh, $data);
    }
    $GLOBALS['demo_fh'] = $fh;
    $GLOBALS['demo_data'] = $data;
    $GLOBALS['demo_loaded'] = true;
    register_shutdown_function(static function (): void {
        if (!empty($GLOBALS['demo_fh']) && is_resource($GLOBALS['demo_fh'])) {
            flock($GLOBALS['demo_fh'], LOCK_UN);
            fclose($GLOBALS['demo_fh']);
            $GLOBALS['demo_fh'] = null;
        }
    });
    return $data;
}

/** @param array<string, mixed> $data */
function demo_expire(array &$data): bool
{
    $changed = false;
    $now = time();
    foreach ($data['sos'] as &$sos) {
        if ($sos['status'] === 'active' && strtotime((string) $sos['expires_at']) < $now) {
            $sos['status'] = 'cancelled';
            $changed = true;
            foreach ($data['users'] as &$user) {
                if ((int) $user['id'] === (int) $sos['user_id'] && $user['status'] === 'need_help') {
                    $user['status'] = 'on_air';
                }
            }
            unset($user);
        }
    }
    unset($sos);
    return $changed;
}

/** @param resource $fh */
function demo_write($fh, array $data): void
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    rewind($fh);
    ftruncate($fh, 0);
    fwrite($fh, $json === false ? '{}' : $json);
    fflush($fh);
}

/** @param array<string, mixed> $data */
function demo_commit(array $data): void
{
    $GLOBALS['demo_data'] = $data;
    if (!empty($GLOBALS['demo_fh'])) {
        demo_write($GLOBALS['demo_fh'], $data);
    }
    cache_forget('stats');
}

function demo_next_id(array &$data, string $key): int
{
    $data['seq'][$key] = (int) ($data['seq'][$key] ?? 0) + 1;
    return (int) $data['seq'][$key];
}

/** @return array<string, mixed>|null */
function demo_user_row(array $data, int $id): ?array
{
    foreach ($data['users'] as $user) {
        if ((int) $user['id'] === $id && empty($user['is_banned'])) {
            return $user;
        }
    }
    return null;
}

function demo_attach_people(array $data, array $sos): array
{
    $user = demo_user_row($data, (int) $sos['user_id']);
    $sos['name'] = $user['name'] ?? '';
    $sos['city'] = $user['city'] ?? '';
    $sos['truck_brand'] = $user['truck_brand'] ?? '';
    $sos['phone'] = $user['phone'] ?? '';
    $sos['helper_name'] = '';
    if (!empty($sos['helper_id'])) {
        $helper = demo_user_row($data, (int) $sos['helper_id']);
        $sos['helper_name'] = $helper['name'] ?? '';
    }
    return $sos;
}

function demo_stats(): array
{
    $data = demo_boot();
    $online = 0;
    $helpers = 0;
    $helps = 0;
    foreach ($data['users'] as $user) {
        if (!empty($user['is_banned'])) {
            continue;
        }
        if (in_array($user['status'], ['on_air', 'on_route', 'need_help'], true)) {
            $online++;
        }
        if (in_array($user['status'], ['on_air', 'on_route'], true)) {
            $helpers++;
        }
        $helps += (int) $user['helps_count'];
    }
    $sos = 0;
    foreach ($data['sos'] as $row) {
        if ($row['status'] === 'active') {
            $sos++;
        }
    }
    return ['online' => $online, 'sos' => $sos, 'helps' => $helps, 'helpers' => $helpers];
}

function demo_sos_rows(?float $lat, ?float $lng, ?float $radius): array
{
    $data = demo_boot();
    $rows = [];
    foreach ($data['sos'] as $sos) {
        if ($sos['status'] !== 'active' && $sos['status'] !== 'accepted') {
            continue;
        }
        $rows[] = demo_attach_people($data, $sos);
    }
    return geo_filter($rows, $lat, $lng, $radius);
}

function demo_helpers(?float $lat, ?float $lng, ?float $radius): array
{
    $data = demo_boot();
    $rows = [];
    foreach ($data['users'] as $user) {
        if (!empty($user['is_banned'])) {
            continue;
        }
        if (!in_array($user['status'], ['on_air', 'on_route'], true)) {
            continue;
        }
        if ($user['lat'] === null || $user['lng'] === null) {
            continue;
        }
        $rows[] = $user;
    }
    return geo_filter($rows, $lat, $lng, $radius);
}

function demo_partners(array $filters): array
{
    $data = demo_boot();
    $type = (string) ($filters['type'] ?? '');
    $region = (string) ($filters['region'] ?? '');
    $rating = (float) ($filters['rating'] ?? 0);
    $day = !empty($filters['day']);
    $rows = [];
    foreach ($data['partners'] as $partner) {
        if (empty($partner['is_active'])) {
            continue;
        }
        if ($type !== '' && $partner['type'] !== $type) {
            continue;
        }
        if ($region !== '' && $partner['region'] !== $region) {
            continue;
        }
        if ($rating > 0 && (float) $partner['rating_avg'] < $rating) {
            continue;
        }
        if ($day && empty($partner['works_24_7'])) {
            continue;
        }
        $rows[] = $partner;
    }
    usort($rows, static fn ($a, $b) => ($b['rating_avg'] <=> $a['rating_avg']));
    return $rows;
}

function demo_regions(): array
{
    $regions = [];
    foreach (demo_boot()['partners'] as $partner) {
        if (!empty($partner['region'])) {
            $regions[$partner['region']] = true;
        }
    }
    $list = array_keys($regions);
    sort($list, SORT_STRING);
    return $list;
}

function demo_posts(?int $viewerId, bool $mine = false): array
{
    $data = demo_boot();
    $liked = [];
    if ($viewerId) {
        foreach ($data['likes'] as $like) {
            if ((int) $like['user_id'] === $viewerId) {
                $liked[(int) $like['post_id']] = true;
            }
        }
    }
    $rows = $data['posts'];
    usort($rows, static fn ($a, $b) => strcmp((string) $b['created_at'], (string) $a['created_at']));
    $out = [];
    foreach ($rows as $post) {
        $author = demo_user_row($data, (int) $post['user_id']);
        if ($post['status'] !== 'published' && (int) $post['user_id'] !== $viewerId) {
            continue;
        }
        if ($mine && (int) $post['user_id'] !== $viewerId) {
            continue;
        }
        $post['name'] = $author['name'] ?? '';
        if ($post['city'] === '' || $post['city'] === null) {
            $post['city'] = $author['city'] ?? '';
        }
        $out[] = present_post($post, !empty($liked[(int) $post['id']]));
    }
    return $out;
}

function demo_register(array $in): array
{
    $data = demo_boot();
    foreach ($data['users'] as $user) {
        if ($user['phone'] === $in['phone']) {
            throw new HttpError('phone_taken', 'Этот телефон уже в колонне. Войдите.', 409);
        }
    }
    $id = demo_next_id($data, 'users');
    $data['users'][] = [
        'id' => $id,
        'phone' => $in['phone'],
        'password_hash' => hash_password($in['password']),
        'name' => $in['name'],
        'city' => $in['city'],
        'role' => $in['role'],
        'truck_brand' => $in['truck_brand'],
        'truck_plate' => $in['truck_plate'],
        'truck_trailer' => $in['truck_trailer'],
        'experience_y' => $in['experience_y'],
        'avatar_path' => null,
        'car_path' => null,
        'motto' => '',
        'status' => 'on_air',
        'rating_sum' => 0,
        'rating_count' => 0,
        'helps_count' => 0,
        'is_banned' => 0,
        'lat' => null,
        'lng' => null,
        'route' => '',
        'km' => null,
        'last_seen_at' => demo_stamp(0),
        'created_at' => demo_stamp(0),
    ];
    demo_commit($data);
    $row = demo_user_row(demo_boot(), $id);
    return present_user($row ?? [], true);
}

function demo_user_by_phone(string $phone): ?array
{
    foreach (demo_boot()['users'] as $user) {
        if ($user['phone'] === $phone && empty($user['is_banned'])) {
            return $user;
        }
    }
    return null;
}

function demo_user(int $id, bool $self): ?array
{
    $row = demo_user_row(demo_boot(), $id);
    return $row ? present_user($row, $self) : null;
}

function demo_touch(int $id, ?float $lat, ?float $lng): void
{
    $data = demo_boot();
    foreach ($data['users'] as &$user) {
        if ((int) $user['id'] !== $id) {
            continue;
        }
        $user['last_seen_at'] = demo_stamp(0);
        if ($lat !== null && $lng !== null && coords_valid($lat, $lng)) {
            $user['lat'] = $lat;
            $user['lng'] = $lng;
        }
        break;
    }
    unset($user);
    demo_commit($data);
}

function demo_update_profile(int $id, array $in): array
{
    $data = demo_boot();
    $found = null;
    foreach ($data['users'] as &$user) {
        if ((int) $user['id'] !== $id) {
            continue;
        }
        foreach (['name', 'city', 'role', 'truck_brand', 'truck_plate', 'truck_trailer', 'motto', 'status', 'route'] as $key) {
            if (array_key_exists($key, $in)) {
                $user[$key] = $in[$key];
            }
        }
        if (array_key_exists('experience_y', $in)) {
            $user['experience_y'] = $in['experience_y'];
        }
        if (array_key_exists('km', $in)) {
            $user['km'] = $in['km'];
        }
        if (!empty($in['avatar_path'])) {
            $user['avatar_path'] = $in['avatar_path'];
        }
        if (!empty($in['car_path'])) {
            $user['car_path'] = $in['car_path'];
        }
        $found = $user;
        break;
    }
    unset($user);
    if (!$found) {
        throw new HttpError('not_found', 'Водителя нет в колонне.', 404);
    }
    demo_commit($data);
    return present_user($found, true);
}

function demo_create_sos(int $userId, array $in): array
{
    $data = demo_boot();
    foreach ($data['sos'] as $sos) {
        if ((int) $sos['user_id'] === $userId && $sos['status'] === 'active' && strtotime((string) $sos['expires_at']) > time()) {
            throw new HttpError('already_active', 'У вас уже есть сигнал в эфире. Сначала закройте его.', 409);
        }
    }
    $id = demo_next_id($data, 'sos');
    $row = [
        'id' => $id,
        'user_id' => $userId,
        'type' => $in['type'],
        'lat' => $in['lat'],
        'lng' => $in['lng'],
        'route' => $in['route'],
        'km' => $in['km'],
        'comment' => $in['comment'],
        'status' => 'active',
        'helper_id' => null,
        'created_at' => demo_stamp(0),
        'accepted_at' => null,
        'completed_at' => null,
        'expires_at' => date('Y-m-d H:i:s', time() + 2 * 3600),
    ];
    $data['sos'][] = $row;
    foreach ($data['users'] as &$user) {
        if ((int) $user['id'] === $userId) {
            $user['status'] = 'need_help';
            $user['lat'] = $in['lat'];
            $user['lng'] = $in['lng'];
            $user['route'] = $in['route'];
            $user['km'] = $in['km'];
            $user['last_seen_at'] = demo_stamp(0);
        }
    }
    unset($user);
    demo_commit($data);
    return present_sos(demo_attach_people(demo_boot(), $row), null, null, true);
}

function demo_my_active_sos(int $userId): ?array
{
    $data = demo_boot();
    foreach ($data['sos'] as $sos) {
        if ((int) $sos['user_id'] === $userId && in_array($sos['status'], ['active', 'accepted'], true)) {
            return present_sos(demo_attach_people($data, $sos), null, null, true);
        }
    }
    return null;
}

function demo_sos_by_id(int $id): ?array
{
    foreach (demo_boot()['sos'] as $sos) {
        if ((int) $sos['id'] === $id) {
            return demo_attach_people(demo_boot(), $sos);
        }
    }
    return null;
}

function demo_messages(int $sosId): array
{
    $data = demo_boot();
    $out = [];
    foreach ($data['messages'] as $message) {
        if ((int) $message['sos_id'] !== $sosId) {
            continue;
        }
        $author = demo_user_row($data, (int) $message['user_id']);
        $message['name'] = $author['name'] ?? '';
        $out[] = present_message($message);
    }
    return $out;
}

function demo_cancel_sos(int $userId, int $sosId): void
{
    $data = demo_boot();
    $ok = false;
    foreach ($data['sos'] as &$sos) {
        if ((int) $sos['id'] !== $sosId || (int) $sos['user_id'] !== $userId) {
            continue;
        }
        if (!in_array($sos['status'], ['active', 'accepted'], true)) {
            throw new HttpError('bad_status', 'Этот сигнал уже закрыт.', 409);
        }
        $sos['status'] = 'cancelled';
        $ok = true;
    }
    unset($sos);
    if (!$ok) {
        throw new HttpError('not_found', 'Сигнал не найден.', 404);
    }
    foreach ($data['users'] as &$user) {
        if ((int) $user['id'] === $userId) {
            $user['status'] = 'on_air';
        }
    }
    unset($user);
    demo_commit($data);
}

function demo_accept_sos(int $userId, int $sosId): array
{
    $data = demo_boot();
    $row = null;
    foreach ($data['sos'] as &$sos) {
        if ((int) $sos['id'] !== $sosId) {
            continue;
        }
        if ($sos['status'] !== 'active') {
            throw new HttpError('bad_status', 'Сигнал уже приняли или закрыли.', 409);
        }
        if ((int) $sos['user_id'] === $userId) {
            throw new HttpError('self', 'Свой сигнал принимают другие.', 409);
        }
        $sos['status'] = 'accepted';
        $sos['helper_id'] = $userId;
        $sos['accepted_at'] = demo_stamp(0);
        $row = $sos;
    }
    unset($sos);
    if (!$row) {
        throw new HttpError('not_found', 'Сигнал не найден.', 404);
    }
    $helper = demo_user_row($data, $userId);
    $data['messages'][] = [
        'id' => demo_next_id($data, 'messages'),
        'sos_id' => $sosId,
        'user_id' => $userId,
        'body' => ($helper['name'] ?? 'Свой') . ' на приёме и берёт этот сигнал.',
        'created_at' => demo_stamp(0),
    ];
    demo_commit($data);
    return present_sos(demo_attach_people(demo_boot(), $row));
}

function demo_complete_sos(int $userId, int $sosId, int $rating): array
{
    $data = demo_boot();
    $row = null;
    foreach ($data['sos'] as &$sos) {
        if ((int) $sos['id'] !== $sosId) {
            continue;
        }
        $party = (int) $sos['user_id'] === $userId || (int) ($sos['helper_id'] ?? 0) === $userId;
        if (!$party) {
            throw new HttpError('forbidden', 'Закрыть сигнал может тот, кто звал, или тот, кто поехал.', 403);
        }
        if (!in_array($sos['status'], ['active', 'accepted'], true)) {
            throw new HttpError('bad_status', 'Сигнал уже закрыт.', 409);
        }
        $sos['status'] = 'completed';
        $sos['completed_at'] = demo_stamp(0);
        $row = $sos;
    }
    unset($sos);
    if (!$row) {
        throw new HttpError('not_found', 'Сигнал не найден.', 404);
    }
    $authorId = (int) $row['user_id'];
    $helperId = (int) ($row['helper_id'] ?? 0);
    foreach ($data['users'] as &$user) {
        if ((int) $user['id'] === $authorId) {
            $user['status'] = 'on_air';
        }
        if ($helperId && (int) $user['id'] === $helperId) {
            $user['helps_count'] = (int) $user['helps_count'] + 1;
            if ($rating >= 1 && $rating <= 5 && $userId === $authorId) {
                $user['rating_sum'] = (int) $user['rating_sum'] + $rating;
                $user['rating_count'] = (int) $user['rating_count'] + 1;
            }
        }
    }
    unset($user);
    demo_commit($data);
    return present_sos(demo_attach_people(demo_boot(), $row), null, null, true);
}

function demo_add_message(int $userId, int $sosId, string $body): array
{
    $data = demo_boot();
    $sos = null;
    foreach ($data['sos'] as $row) {
        if ((int) $row['id'] === $sosId) {
            $sos = $row;
            break;
        }
    }
    if (!$sos) {
        throw new HttpError('not_found', 'Сигнал не найден.', 404);
    }
    $party = (int) $sos['user_id'] === $userId || (int) ($sos['helper_id'] ?? 0) === $userId;
    if (!$party) {
        throw new HttpError('forbidden', 'Переписка открыта тем, кого касается сигнал.', 403);
    }
    $message = [
        'id' => demo_next_id($data, 'messages'),
        'sos_id' => $sosId,
        'user_id' => $userId,
        'body' => $body,
        'created_at' => demo_stamp(0),
    ];
    $data['messages'][] = $message;
    demo_commit($data);
    $author = demo_user_row(demo_boot(), $userId);
    $message['name'] = $author['name'] ?? '';
    return present_message($message);
}

function demo_create_post(int $userId, array $in): array
{
    $data = demo_boot();
    $id = demo_next_id($data, 'posts');
    $post = [
        'id' => $id,
        'user_id' => $userId,
        'body' => $in['body'],
        'photo_path' => $in['photo_path'],
        'city' => $in['city'],
        'route' => $in['route'],
        'likes_count' => 0,
        'status' => $in['photo_path'] ? 'pending' : 'published',
        'created_at' => demo_stamp(0),
    ];
    $data['posts'][] = $post;
    demo_commit($data);
    $author = demo_user_row(demo_boot(), $userId);
    $post['name'] = $author['name'] ?? '';
    return present_post($post, false);
}

function demo_like(int $userId, int $postId): array
{
    $data = demo_boot();
    $exists = false;
    foreach ($data['posts'] as $post) {
        if ((int) $post['id'] === $postId && $post['status'] === 'published') {
            $exists = true;
        }
    }
    if (!$exists) {
        throw new HttpError('not_found', 'Записи нет в эфире.', 404);
    }
    $liked = false;
    $keep = [];
    foreach ($data['likes'] as $like) {
        if ((int) $like['user_id'] === $userId && (int) $like['post_id'] === $postId) {
            $liked = true;
            continue;
        }
        $keep[] = $like;
    }
    $delta = $liked ? -1 : 1;
    if ($liked) {
        $data['likes'] = $keep;
    } else {
        $data['likes'][] = ['user_id' => $userId, 'post_id' => $postId];
    }
    $count = 0;
    foreach ($data['posts'] as &$post) {
        if ((int) $post['id'] === $postId) {
            $post['likes_count'] = max(0, (int) $post['likes_count'] + $delta);
            $count = (int) $post['likes_count'];
        }
    }
    unset($post);
    demo_commit($data);
    return ['likes' => $count, 'liked' => !$liked];
}

function demo_complain(int $userId, int $postId, string $reason): void
{
    $data = demo_boot();
    foreach ($data['complaints'] as $complaint) {
        if ((int) $complaint['user_id'] === $userId && (int) $complaint['post_id'] === $postId) {
            throw new HttpError('duplicate', 'Жалоба уже ушла модератору.', 409);
        }
    }
    $found = false;
    foreach ($data['posts'] as $post) {
        if ((int) $post['id'] === $postId) {
            $found = true;
        }
    }
    if (!$found) {
        throw new HttpError('not_found', 'Записи нет.', 404);
    }
    $data['complaints'][] = [
        'id' => demo_next_id($data, 'complaints'),
        'user_id' => $userId,
        'post_id' => $postId,
        'reason' => $reason,
        'created_at' => demo_stamp(0),
    ];
    demo_commit($data);
}

function demo_user_helps(int $userId): array
{
    $data = demo_boot();
    $out = [];
    foreach ($data['sos'] as $sos) {
        if ((int) ($sos['helper_id'] ?? 0) === $userId && $sos['status'] === 'completed') {
            $out[] = present_sos(demo_attach_people($data, $sos));
        }
    }
    return $out;
}
